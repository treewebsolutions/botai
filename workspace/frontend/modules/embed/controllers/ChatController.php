<?php

namespace frontend\modules\embed\controllers;

use common\filters\RateLimit;
use common\models\Assistant;
use common\models\Conversation;
use common\models\Message;
use common\services\OpenAIResponsesService;
use common\services\OpenAiRecordVectorStoreService;
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

/**
 * The embed chat widget endpoints.
 *
 * A widget session is a local {@see Conversation} row whose public token is the OpenAI
 * conversation id (`openai_conversation_id`); the turns are answered through the OpenAI
 * Responses API with `file_search` over the vector stores of the chat {@see Assistant}'s
 * knowledge bases (see {@see OpenAIResponsesService::createConversationResponse()}).
 */
class ChatController extends DefaultController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		$behaviors = parent::behaviors();
		// The widget is public and unauthenticated by design, and every turn costs an
		// upstream model call, so an unthrottled endpoint is somebody else's bill. The
		// conversation token is the closest thing to an identity here, which also keeps
		// one abusive visitor from spending a tenant's whole budget.
		$behaviors['rateLimit'] = [
			'class' => RateLimit::class,
			'only' => ['index', 'speak', 'conversation', 'send-conversation'],
			'limit' => 60,
			'window' => 900,
			'identityParams' => ['conversation_id'],
			'identityLimit' => 60,
			'identityWindow' => 900,
			'keyPrefix' => 'ratelimit:embed',
		];
		$behaviors['verbs'] = [
			'class' => VerbFilter::class,
			'actions' => [
				'index' => ['GET', 'POST'],
				'speak' => ['POST'],
				'conversation' => ['POST'],
				'validate-conversation' => ['GET'],
				'send-conversation' => ['POST'],
			],
		];
		return $behaviors;
	}

	/**
	 * Renders the widget (GET) or answers one turn (POST `prompt` + `conversation_id`).
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$this->layout = 'embed';

		if (!Yii::$app->request->isAjax && !Yii::$app->request->isPost) {
			return $this->render('index');
		}

		Yii::$app->response->format = Response::FORMAT_JSON;

		$prompt = trim((string) Yii::$app->request->post('prompt'));
		$conversationToken = trim((string) Yii::$app->request->post('conversation_id'));

		if ($prompt === '') {
			return ['error' => 'No prompt provided'];
		}
		if ($conversationToken === '') {
			return ['error' => 'Missing conversation ID'];
		}

		$conversation = Conversation::findByOpenAIConversationId($conversationToken);
		if ($conversation === null) {
			return ['error' => Yii::t('common', 'Conversation not found.')];
		}

		if (!OpenAIResponsesService::isApiKeyConfigured()) {
			return ['error' => Yii::t('common', 'The chat is not configured yet.')];
		}

		$config = $this->resolveChatConfig();

		try {
			$openaiConversationId = OpenAIResponsesService::ensureConversation($conversation);
			$reply = OpenAIResponsesService::createConversationResponse(
				$config['model'],
				$prompt,
				$openaiConversationId,
				$config['instructions'],
				$config['vectorStoreIds'],
				$config['temperature'],
				$config['topP']
			);
		} catch (\Throwable $e) {
			Yii::error('Embed chat failed: ' . $e->getMessage(), __METHOD__);
			return ['error' => Yii::t('common', 'AI service is temporarily unavailable.')];
		}

		$reply = trim((string) $reply);
		if ($reply === '') {
			$reply = Yii::t('common', 'Could not generate a response. Try rephrasing your request.');
		}

		$transaction = Yii::$app->db->beginTransaction();
		try {
			$this->saveMessage($conversation, $config['assistant'], Message::ROLE_USER, $prompt);
			$this->saveMessage($conversation, $config['assistant'], Message::ROLE_ASSISTANT, $reply);
			if (empty($conversation->summary)) {
				$conversation->updateAttributes(['summary' => mb_substr($prompt, 0, 255)]);
			}
			$transaction->commit();
		} catch (\Throwable $e) {
			$transaction->rollBack();
			Yii::error('Embed chat message save failed: ' . $e->getMessage(), __METHOD__);
			return ['error' => 'Message save failed.'];
		}

		return ['reply' => $reply];
	}

	/**
	 * Persists one turn of the conversation.
	 *
	 * @param Conversation $conversation
	 * @param Assistant|null $assistant
	 * @param string $role
	 * @param string $content
	 * @return Message
	 * @throws \RuntimeException when the row cannot be saved
	 */
	protected function saveMessage(Conversation $conversation, ?Assistant $assistant, $role, $content)
	{
		$message = new Message([
			'conversation_id' => $conversation->id,
			'assistant_id' => $assistant !== null ? $assistant->id : null,
			'role' => $role,
			'content' => $content,
			'completed_at' => date('Y-m-d H:i:s'),
			'status' => Message::STATUS_COMPLETED,
		]);
		if (!$message->save()) {
			throw new \RuntimeException(ucfirst($role) . ' message save failed: ' . json_encode($message->getErrors()));
		}
		return $message;
	}

	/**
	 * Resolves what drives the chat: the default chat {@see Assistant} (model, instructions, sampling,
	 * linked knowledge bases) or, when none is configured, the `chat*` params and the knowledge base
	 * resolved by {@see OpenAiRecordVectorStoreService::resolveKnowledgeBase()}.
	 *
	 * @return array{assistant: Assistant|null, model: string, instructions: string, vectorStoreIds: string[], temperature: float|null, topP: float|null}
	 */
	protected function resolveChatConfig()
	{
		$assistant = Assistant::findChatAssistant();

		$model = $assistant !== null && !empty($assistant->model)
			? (string) $assistant->model
			: (string) (Yii::$app->params['chatModel'] ?? 'gpt-5.4-mini');
		$instructions = $assistant !== null && trim((string) $assistant->instructions) !== ''
			? (string) $assistant->instructions
			: (string) (Yii::$app->params['chatInstructions'] ?? '');

		$vectorStoreIds = $assistant !== null ? $assistant->collectVectorStoreIds() : [];
		if ($vectorStoreIds === []) {
			$knowledgeBase = OpenAiRecordVectorStoreService::resolveKnowledgeBase();
			if ($knowledgeBase !== null && !empty($knowledgeBase->vector_store_id)) {
				$vectorStoreIds = [(string) $knowledgeBase->vector_store_id];
			}
		}

		// GPT-5.x reasoning models reject the sampling parameters — send them only to legacy models.
		$temperature = null;
		$topP = null;
		if ($assistant !== null && !str_starts_with($model, 'gpt-5')) {
			$temperature = $assistant->temperature !== null && $assistant->temperature !== '' ? (float) $assistant->temperature : null;
			$topP = $assistant->top_p !== null && $assistant->top_p !== '' ? (float) $assistant->top_p : null;
		}

		return [
			'assistant' => $assistant,
			'model' => $model,
			'instructions' => $instructions,
			'vectorStoreIds' => $vectorStoreIds,
			'temperature' => $temperature,
			'topP' => $topP,
		];
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
	 * AJAX endpoint: starts a new conversation (an OpenAI conversation object + the local row).
	 *
	 * @return array
	 */
	public function actionConversation()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		if (!OpenAIResponsesService::isApiKeyConfigured()) {
			return ['success' => false, 'error' => Yii::t('common', 'The chat is not configured yet.')];
		}

		try {
			$openaiConversationId = OpenAIResponsesService::createConversation();
		} catch (\Throwable $e) {
			Yii::error('Conversation creation error: ' . $e->getMessage(), __METHOD__);
			return ['success' => false, 'error' => Yii::t('common', 'AI service is temporarily unavailable.')];
		}

		$conversation = new Conversation();
		$conversation->openai_conversation_id = $openaiConversationId;
		$conversation->status = Conversation::STATUS_ACTIVE;
		$conversation->deleted = Conversation::NO;

		if (!$conversation->save()) {
			return [
				'success' => false,
				'error' => 'Failed to save conversation to database.',
				'details' => $conversation->getErrors(),
			];
		}

		return [
			'success' => true,
			'conversation_id' => $conversation->openai_conversation_id,
		];
	}

	/**
	 * AJAX endpoint: whether the stored conversation token still points to an active conversation.
	 *
	 * @param string $id
	 * @return array
	 */
	public function actionValidateConversation($id)
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		return ['valid' => Conversation::findByOpenAIConversationId($id) !== null];
	}

	/**
	 * AJAX endpoint: emails the transcript of a conversation (JSON body: `email`, `conversation_id`).
	 *
	 * @return array
	 */
	public function actionSendConversation()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		$payload = json_decode((string) Yii::$app->request->getRawBody(), true);
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
			return ['success' => false, 'error' => 'Invalid JSON.'];
		}

		$email = trim((string) ($payload['email'] ?? ''));
		$conversationToken = trim((string) ($payload['conversation_id'] ?? ''));

		$validator = new EmailValidator();
		if (!$validator->validate($email)) {
			return ['success' => false, 'error' => 'Invalid email address.'];
		}
		if ($conversationToken === '') {
			return ['success' => false, 'error' => 'Missing conversation ID.'];
		}

		$conversation = Conversation::findByOpenAIConversationId($conversationToken);
		if ($conversation === null) {
			return ['success' => false, 'error' => Yii::t('common', 'Conversation not found.')];
		}

		$messages = Message::find()
			->where(['conversation_id' => $conversation->id, 'deleted' => Message::NO])
			->orderBy(['id' => SORT_ASC])
			->all();
		if (empty($messages)) {
			return ['success' => false, 'error' => 'No messages found.'];
		}

		// Assistant replies are Markdown; the user turns are plain text.
		$converter = new CommonMarkConverter();
		$htmlBody = '<h2>' . Yii::t('common', 'Your conversation with {name}', ['name' => Yii::$app->name]) . '</h2><hr>';
		$plainTextBody = '';

		foreach ($messages as $message) {
			$role = Message::getRoleLabels()[$message->role] ?? ucfirst((string) $message->role);
			$rawContent = trim((string) $message->content);
			$convertedHtml = $message->role === Message::ROLE_ASSISTANT
				? $converter->convertToHtml($rawContent)
				: nl2br(htmlspecialchars($rawContent));

			$htmlBody .= "<p><strong>{$role}:</strong><br>{$convertedHtml}</p><hr>";
			$plainTextBody .= "{$role}:\n{$rawContent}\n\n";
		}

		$sent = Yii::$app->mailer->compose()
			->setTo($email)
			->setSubject(Yii::t('common', 'Your conversation with {name}', ['name' => Yii::$app->name]))
			->setTextBody($plainTextBody)
			->setHtmlBody("<html><body>{$htmlBody}</body></html>")
			->send();

		if (!$sent) {
			return ['success' => false, 'error' => 'Failed to send email.'];
		}

		return ['success' => true];
	}
}
