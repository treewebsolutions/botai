<?php

namespace tests\unit;

use common\models\Conversation;
use common\models\Message;
use common\models\Page;
use common\models\RecordVectorIndex;
use tests\DatabaseTestCase;

/**
 * Round-trip coverage for the tables renamed / added by the vector store port:
 * `conversation` (ex `thread`), `message.conversation_id` and `record_vector_index`.
 */
class ConversationPersistenceTest extends DatabaseTestCase
{
	/**
	 * The embed widget stores the OpenAI conversation id as its session token; the lookup
	 * must only return live conversations, and blank tokens never hit the database.
	 */
	public function testFindByOpenAIConversationIdReturnsOnlyActiveRows()
	{
		$this->assertNull(Conversation::findByOpenAIConversationId(''));
		$this->assertNull(Conversation::findByOpenAIConversationId(null));
		$this->assertNull(Conversation::findByOpenAIConversationId('conv_missing'));

		$live = $this->insertRow('conversation', [
			'openai_conversation_id' => 'conv_live',
			'status' => Conversation::STATUS_ACTIVE,
			'deleted' => Conversation::NO,
		]);
		$this->insertRow('conversation', [
			'openai_conversation_id' => 'conv_inactive',
			'status' => Conversation::STATUS_INACTIVE,
			'deleted' => Conversation::NO,
		]);
		$this->insertRow('conversation', [
			'openai_conversation_id' => 'conv_deleted',
			'status' => Conversation::STATUS_ACTIVE,
			'deleted' => Conversation::YES,
		]);

		$this->assertSame($live, (int) Conversation::findByOpenAIConversationId(' conv_live ')->id);
		$this->assertNull(Conversation::findByOpenAIConversationId('conv_inactive'));
		$this->assertNull(Conversation::findByOpenAIConversationId('conv_deleted'));
	}

	/**
	 * A conversation created by the widget has no OpenAI object yet until the first turn;
	 * the model stamps created_at itself and messages hang off `conversation_id`.
	 */
	public function testConversationAndMessagesRoundTrip()
	{
		$conversation = new Conversation();
		$conversation->status = Conversation::STATUS_ACTIVE;
		$this->assertTrue($conversation->save(), print_r($conversation->getErrors(), true));
		$this->assertNotNull($this->fetchColumn('conversation', $conversation->id, 'created_at'));
		$this->assertNull($conversation->openai_conversation_id);

		foreach ([[Message::ROLE_USER, 'Hello'], [Message::ROLE_ASSISTANT, 'Hi, how can I help?']] as [$role, $content]) {
			$message = new Message([
				'conversation_id' => $conversation->id,
				'role' => $role,
				'content' => $content,
				'status' => Message::STATUS_COMPLETED,
			]);
			$this->assertTrue($message->save(), print_r($message->getErrors(), true));
		}

		$conversation = Conversation::findOne($conversation->id);
		$this->assertCount(2, $conversation->messages);
		$this->assertSame([Message::ROLE_USER, Message::ROLE_ASSISTANT], array_column($conversation->messages, 'role'));
		$this->assertSame($conversation->id, (int) $conversation->messages[0]->conversation->id);

		// A message must point to an existing conversation.
		$orphan = new Message(['conversation_id' => 999999, 'role' => Message::ROLE_USER, 'content' => 'x']);
		$this->assertFalse($orphan->save());
		$this->assertArrayHasKey('conversation_id', $orphan->getErrors());
	}

	/**
	 * One index row per page: the record_id is unique, the page relation resolves it and
	 * the status enum drives the labels shown in the backend.
	 */
	public function testRecordVectorIndexIsOnePerPage()
	{
		$pageId = $this->insertRow('page', [
			'url' => 'https://example.test/about',
			'content' => 'About us',
			'status' => Page::STATUS_ACTIVE,
			'deleted' => Page::NO,
		]);

		$row = new RecordVectorIndex([
			'record_id' => $pageId,
			'openai_file_id' => 'file-abc',
			'vector_store_file_id' => 'vsf-abc',
			'vector_store_id' => 'vs_abc',
			'indexed_at' => date('Y-m-d H:i:s'),
		]);
		$this->assertTrue($row->save(), print_r($row->getErrors(), true));
		$this->assertSame(RecordVectorIndex::STATUS_ACTIVE, (int) $row->status, 'defaults to indexed');
		$this->assertSame($pageId, (int) Page::findOne($pageId)->recordVectorIndex->record_id);

		$this->expectException(\yii\db\IntegrityException::class);
		$this->insertRow('record_vector_index', [
			'record_id' => $pageId,
			'openai_file_id' => 'file-dup',
			'vector_store_id' => 'vs_abc',
		]);
	}

	/**
	 * Every status value has a label + colour, so the backend never renders an undefined index.
	 */
	public function testStatusLabelsCoverEveryStatus()
	{
		$labels = RecordVectorIndex::getStatusLabels();
		foreach ([RecordVectorIndex::STATUS_INACTIVE, RecordVectorIndex::STATUS_ACTIVE, RecordVectorIndex::STATUS_ERROR] as $status) {
			$this->assertArrayHasKey($status, $labels);
			$this->assertArrayHasKey('label', $labels[$status]);
			$this->assertArrayHasKey('color', $labels[$status]);
		}
		foreach ([Message::STATUS_IN_PROGRESS, Message::STATUS_COMPLETED, Message::STATUS_INCOMPLETE] as $status) {
			$this->assertArrayHasKey($status, Message::getStatusLabels());
		}
	}
}
