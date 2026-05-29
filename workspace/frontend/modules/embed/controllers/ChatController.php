<?php

namespace frontend\modules\embed\controllers;

use common\models\Assistant;
use common\models\Integration;
use common\models\Message;
use common\models\Thread;
use Google\Cloud\TextToSpeech\V1\TextToSpeechClient;
use Google\Cloud\TextToSpeech\V1\SynthesisInput;
use Google\Cloud\TextToSpeech\V1\VoiceSelectionParams;
use Google\Cloud\TextToSpeech\V1\SsmlVoiceGender;
use Google\Cloud\TextToSpeech\V1\AudioConfig;
use Google\Cloud\TextToSpeech\V1\AudioEncoding;
use League\CommonMark\CommonMarkConverter;
use Yii;
use yii\filters\VerbFilter;
use yii\validators\EmailValidator;
use yii\web\Response;

class ChatController extends DefaultController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		$behaviors = parent::behaviors();
        $behaviors['verbs'] = [
		'class' => VerbFilter::class,
			'actions' => [
				'index' => ['GET', 'POST'],
				'speak' => ['POST'],
				'thread' => ['POST'],
			],
		];
        return $behaviors;
	}


	public function actionIndex()
	{
		$this->layout = 'embed';

		if (Yii::$app->request->isAjax || Yii::$app->request->isPost) {
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

			$prompt   = Yii::$app->request->post('prompt');
			$threadId = Yii::$app->request->post('thread_id');

			if (empty($prompt)) {
				return ['error' => 'No prompt provided'];
			}

			if (empty($threadId)) {
				return ['error' => 'Missing thread ID'];
			}

			$integration = Integration::find()->where([
				'status' => Integration::STATUS_ACTIVE,
				'deleted' => Integration::NO,
				'type' => Integration::TYPE_OPENAI,
				'default' => Integration::YES,
			])->one();

			$assistant = Assistant::find()->where([
				'status' => Assistant::STATUS_ACTIVE,
				'deleted' => Assistant::NO,
				'default' => Assistant::YES,
			])->one();

			$thread = Thread::find()->where([
				'status' => Thread::STATUS_ACTIVE,
				'deleted' => Thread::NO,
				'openai_id' => $threadId,
			])->one();

			if (!$integration || !$assistant || !$thread) {
				return ['error' => 'Missing assistant, integration, or thread.'];
			}

			$apiKey = $integration->data;
			$endpoint = 'https://api.openai.com/v1/chat/completions';

			$data = [
				'model' => $assistant->model ?? 'gpt-4-turbo',
				'messages' => [
					['role' => 'system', 'content' => $assistant->instructions ?? 'You are a helpful assistant.'],
					['role' => 'user', 'content' => $prompt],
				],
				'temperature' => (float)($assistant->temperature ?? 0.7),
				'top_p' => (float)($assistant->top_p ?? 0.9),
			];

			// Log request
			Yii::info('OpenAI request: ' . json_encode($data), __METHOD__);

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey,
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				return ['error' => 'OpenAI cURL error: ' . $error];
			}

			$resultData = json_decode($response, true);
			Yii::info('OpenAI response: ' . json_encode($resultData), __METHOD__);

			$reply = $resultData['choices'][0]['message']['content'] ?? null;

			if (!empty($reply)) {
				$transaction = Yii::$app->db->beginTransaction();

				try {
					$userMessage = new Message([
						'thread_id'    => $thread->id,
						'assistant_id' => $assistant->id,
						'role'         => Message::ROLE_USER,
						'content'      => $prompt,
						'status'       => Message::STATUS_COMPLETED,
					]);

					if (!$userMessage->save()) {
						throw new \RuntimeException('User message save failed: ' . json_encode($userMessage->getErrors()));
					}

					$openaiMessageId = $resultData['id'] ?: null;
					$assistantMessage = new Message([
						'openai_id'    => $openaiMessageId,
						'thread_id'    => $thread->id,
						'assistant_id' => $assistant->id,
						'role'         => Message::ROLE_ASSISTANT,
						'content'      => $reply,
						'status'       => Message::STATUS_COMPLETED,
					]);

					if (!$assistantMessage->save()) {
						throw new \RuntimeException('Assistant message save failed: ' . json_encode($assistantMessage->getErrors()));
					}

					$transaction->commit();

					return ['reply' => $reply];

				} catch (\Throwable $e) {
					$transaction->rollBack();
					Yii::error('Transaction failed: ' . $e->getMessage(), __METHOD__);
					return ['error' => 'Message save failed.', 'details' => $e->getMessage()];
				}
			}

			return ['error' => 'No reply received from OpenAI.'];
		}

		return $this->render('index');
	}

	/**
	 * AJAX endpoint: synthesize the given text and return Base64-MP3.
	 */
	public function actionSpeak()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		// 1) Retrieve the input text (JSON body or form POST)
		$raw  = Yii::$app->request->getRawBody();
		$data = $raw ? json_decode($raw, true) : [];
		if ($raw && json_last_error() !== JSON_ERROR_NONE) {
			return ['error' => 'Invalid JSON payload', 'details' => json_last_error_msg()];
		}
		$text = $data['text'] ?? Yii::$app->request->post('text');
		if (empty($text)) {
			return ['error' => 'No text provided'];
		}

		try {
			// 2) Load the general settings to find the JSON key filename
			$settings = Yii::$app->masterSettings->getCategory('general');
			$fileName = $settings['textToSpeechServiceAccountFile'] ?? '';

			// 3) Resolve the key file path (must be a filesystem path)
			$saFile = Yii::getAlias('@master/backend/runtime/tts/' . $fileName);
			if (!is_file($saFile) || !is_readable($saFile)) {
				throw new \RuntimeException("Key file not found or unreadable at {$saFile}");
			}

			// 4) Escape for XML and wrap digits in <say-as interpret-as="digits">
			$escaped = htmlspecialchars($text, ENT_XML1, 'UTF-8');
			$escaped = preg_replace_callback('/\d+/', function($m) {
				return '<say-as interpret-as="digits">' . $m[0] . '</say-as>';
			}, $escaped);

			// 5) Build SSML, isolating '?' for prosody + emphasis
			if (substr(rtrim($text), -1) === '?') {
				$main = rtrim(substr($escaped, 0, -1));
				$ssml = <<<XML
<speak>
  {$main}
  <break time="250ms"/>
  <prosody pitch="+8st" rate="1.2">
    <emphasis level="strong">?</emphasis>
  </prosody>
</speak>
XML;
			} else {
				$ssml = "<speak>{$escaped}</speak>";
			}

			// 6) Prepare TTS request using a WaveNet voice
			$input       = (new SynthesisInput())->setSsml($ssml);
			$voice       = (new VoiceSelectionParams())
				->setLanguageCode('ro-RO')
				->setName('ro-RO-Wavenet-B')
				->setSsmlGender(SsmlVoiceGender::FEMALE);
			$audioConfig = (new AudioConfig())
				->setAudioEncoding(AudioEncoding::MP3);

			// 7) Instantiate client and synthesize
			$tts      = new TextToSpeechClient(['credentials' => $saFile]);
			$response = $tts->synthesizeSpeech($input, $voice, $audioConfig);
			$tts->close();

			// 8) Return the Base64-encoded MP3
			return ['audioData' => base64_encode($response->getAudioContent())];

		} catch (\Throwable $e) {
			Yii::error('TTS synthesis failed: ' . $e->getMessage(), __METHOD__);
			return [
				'error'   => 'Text-to-Speech synthesis failed.',
				'details' => $e->getMessage(),
			];
		}
	}

	/**
	 * AJAX endpoint: create thread
	 *
	 * @return mixed
	 */
	public function actionThread()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		try {
			// 1. Get active OpenAI integration
			$integration = Integration::find()
				->where([
					'status'  => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type'    => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();

			if (!$integration) {
				return ['success' => false, 'error' => 'No OpenAI integration found.'];
			}

			$apiKey = $integration->data;

			// 2. Prepare cURL call to OpenAI
			$endpoint = 'https://api.openai.com/v1/threads';

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey,
				'OpenAI-Beta: assistants=v2',
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, '{}'); // Empty body to create blank thread

			$response = curl_exec($ch);
			$error    = curl_error($ch);
			curl_close($ch);

			if ($error) {
				return ['success' => false, 'error' => 'cURL error: ' . $error];
			}

			$result = json_decode($response, true);

			if (empty($result['id'])) {
				return ['success' => false, 'error' => 'OpenAI thread creation failed.', 'response' => $result];
			}

			$openaiThreadId = $result['id'];

			// 3. Save to DB
			$thread = new Thread();
			$thread->openai_id = $openaiThreadId;
			$thread->status = Thread::STATUS_ACTIVE;

			if (!$thread->save()) {
				return [
					'success' => false,
					'error' => 'Failed to save thread to database.',
					'details' => $thread->getErrors(),
				];
			}

			// 4. Return thread ID
			return [
				'success'   => true,
				'thread_id' => $thread->openai_id,
			];

		} catch (\Throwable $e) {
			Yii::error('Thread creation error: ' . $e->getMessage(), __METHOD__);
			return [
				'success' => false,
				'error'   => 'Exception occurred.',
				'message' => $e->getMessage(),
			];
		}
	}

	public function actionValidateThread($id)
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		$thread = Thread::find()->where([
			'openai_id' => $id,
			'status' => Thread::STATUS_ACTIVE,
			'deleted' => Thread::NO,
		])->one();

		return ['valid' => $thread !== null];
	}

	public function actionSendConversation()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		$data = Yii::$app->request->getRawBody();
		$payload = json_decode($data, true);

		if (json_last_error() !== JSON_ERROR_NONE) {
			return ['success' => false, 'error' => 'Invalid JSON.'];
		}

		$email = $payload['email'] ?? '';
		$threadId = $payload['thread_id'] ?? null;

		$validator = new EmailValidator();
		if (!$validator->validate($email)) {
			return ['success' => false, 'error' => 'Invalid email address.'];
		}

		if (empty($threadId)) {
			return ['success' => false, 'error' => 'Missing thread ID.'];
		}

		$thread = Thread::find()->where([
			'openai_id' => $threadId,
			'status' => Thread::STATUS_ACTIVE,
			'deleted' => Thread::NO,
		])->one();

		if (!$thread) {
			return ['success' => false, 'error' => 'Thread not found.'];
		}

		$messages = Message::find()
			->where(['thread_id' => $thread->id])
			->orderBy(['id' => SORT_ASC])
			->all();

		if (empty($messages)) {
			return ['success' => false, 'error' => 'No messages found.'];
		}

		// Convert messages to HTML using CommonMark
		$converter = new CommonMarkConverter();
		$htmlBody = '<h2>Conversația cu ' . Yii::$app->name . '</h2><hr>';
		$plainTextBody = '';

		foreach ($messages as $message) {
			$role = ucfirst($message->role);
			$rawContent = trim($message->content);
			$convertedHtml = $message->role === Message::ROLE_ASSISTANT
				? $converter->convertToHtml($rawContent)
				: nl2br(htmlspecialchars($rawContent));

			$htmlBody .= "<p><strong>{$role}:</strong><br>{$convertedHtml}</p><hr>";
			$plainTextBody .= "{$role}:\n{$rawContent}\n\n";
		}

		$sent = Yii::$app->mailer->compose()
			->setTo($email)
			->setSubject('Conversația ta cu ' . Yii::$app->name)
			->setTextBody($plainTextBody)
			->setHtmlBody("<html><body>{$htmlBody}</body></html>")
			->send();

		if (!$sent) {
			return ['success' => false, 'error' => 'Failed to send email.'];
		}

		return ['success' => true];
	}
}
