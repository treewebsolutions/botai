<?php

namespace common\modules\embed\controllers;

use common\models\Integration;
use Yii;

class ChatController extends DefaultController
{
	/**
	 * Renders the index view for this controller.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$this->layout = 'embed';

		if (Yii::$app->request->isAjax) {
			// Retrieve the prompt from the POST request
			$prompt = Yii::$app->request->post('prompt');
			if (empty($prompt)) {
				return $this->asJson(['error' => 'No prompt provided']);
			}

			// OpenAI API endpoint and credentials
			$integration = Integration::find()
				->where([
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type' => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();
			$apiKey = $integration->data; // Replace with your actual API key
			$endpoint = 'https://api.openai.com/v1/chat/completions';

			// Build the request payload
			$data = [
				'model' => 'gpt-4-turbo',
				'messages' => [
					['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
					['role' => 'user', 'content' => $prompt],
				],
				'temperature' => 0.7,
			];

			$jsonData = json_encode($data);

			// Initialize cURL
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey,
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				return $this->asJson(['error' => $error]);
			}

			$resultData = json_decode($response, true);
			$reply = isset($resultData['choices'][0]['message']['content'])
				? $resultData['choices'][0]['message']['content']
				: 'No reply received';

			return $this->asJson(['reply' => $reply]);
		}

		return $this->render('index');
	}
}
