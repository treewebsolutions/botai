<?php

namespace tests\unit;

use common\models\Conversation;
use common\models\Message;
use tests\WebTestCase;

/**
 * The embed chat endpoints without an OpenAI key: every guard that must answer before an
 * API call is attempted (unknown / missing conversation, missing prompt, chat not
 * configured), the session-token validation used by the widget on load, and the email
 * transcript. The happy path of a turn calls OpenAI and is covered manually.
 */
class EmbedChatFlowTest extends WebTestCase
{
	/**
	 * @return int the conversation id
	 */
	private function createConversation($token = 'conv_test')
	{
		return $this->insertRow('conversation', [
			'openai_conversation_id' => $token,
			'status' => Conversation::STATUS_ACTIVE,
			'deleted' => Conversation::NO,
		]);
	}

	/**
	 * On load the widget checks its stored token; a stale one makes it start a new conversation.
	 */
	public function testValidateConversation()
	{
		$this->createConversation('conv_ok');

		$this->assertTrue($this->jsonPayload($this->runControllerAction('embed/chat/validate-conversation', ['id' => 'conv_ok']))['valid']);
		$this->assertFalse($this->jsonPayload($this->runControllerAction('embed/chat/validate-conversation', ['id' => 'conv_stale']))['valid']);
	}

	/**
	 * A turn needs a prompt and a live conversation before anything else is looked at.
	 */
	public function testTurnRequiresPromptAndConversation()
	{
		$payload = $this->jsonPayload($this->runControllerAction('embed/chat/index', [], ['conversation_id' => 'conv_x']));
		$this->assertSame('No prompt provided', $payload['error']);

		$payload = $this->jsonPayload($this->runControllerAction('embed/chat/index', [], ['prompt' => 'Hello']));
		$this->assertSame('Missing conversation ID', $payload['error']);

		$payload = $this->jsonPayload($this->runControllerAction('embed/chat/index', [], ['prompt' => 'Hello', 'conversation_id' => 'conv_unknown']));
		$this->assertSame('Conversation not found.', $payload['error']);
		$this->assertSame(0, (int) Message::find()->count(), 'nothing is persisted for a rejected turn');
	}

	/**
	 * Without an active OpenAI integration the turn is refused with a clear message and no
	 * message row is written; the same guard stops the widget from creating conversations.
	 */
	public function testChatRefusesWhenNoApiKeyIsConfigured()
	{
		$this->createConversation('conv_live');

		$payload = $this->jsonPayload($this->runControllerAction('embed/chat/index', [], ['prompt' => 'Hello', 'conversation_id' => 'conv_live']));
		$this->assertSame('The chat is not configured yet.', $payload['error']);
		$this->assertSame(0, (int) Message::find()->count());

		$payload = $this->jsonPayload($this->runControllerAction('embed/chat/conversation', [], ['_' => '1']));
		$this->assertFalse($payload['success']);
		$this->assertSame('The chat is not configured yet.', $payload['error']);
		$this->assertSame(1, (int) Conversation::find()->count(), 'no conversation row without an API key');
	}

	/**
	 * The transcript email validates its JSON body, the address and the conversation, then
	 * goes through the mailer (file transport in tests).
	 */
	public function testSendConversationTranscript()
	{
		$conversationId = $this->createConversation('conv_mail');

		$send = function (array $body) {
			\Yii::$app->request->setRawBody(json_encode($body));
			return $this->jsonPayload($this->runControllerAction('embed/chat/send-conversation', [], ['_' => '1']));
		};

		$this->assertSame('Invalid email address.', $send(['email' => 'nope', 'conversation_id' => 'conv_mail'])['error']);
		$this->assertSame('Missing conversation ID.', $send(['email' => 'visitor@test.local'])['error']);
		$this->assertSame('Conversation not found.', $send(['email' => 'visitor@test.local', 'conversation_id' => 'conv_other'])['error']);
		$this->assertSame('No messages found.', $send(['email' => 'visitor@test.local', 'conversation_id' => 'conv_mail'])['error']);

		foreach ([[Message::ROLE_USER, 'What are your opening hours?'], [Message::ROLE_ASSISTANT, "**Mon-Fri** 9-17"]] as [$role, $content]) {
			$this->insertRow('message', [
				'conversation_id' => $conversationId,
				'role' => $role,
				'content' => $content,
				'status' => Message::STATUS_COMPLETED,
				'deleted' => Message::NO,
			]);
		}

		$payload = $send(['email' => 'visitor@test.local', 'conversation_id' => 'conv_mail']);
		$this->assertTrue($payload['success'], print_r($payload, true));
	}
}
