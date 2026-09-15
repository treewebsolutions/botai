<?php

namespace tests\unit;

use backend\modules\nomenclature\models\AssistantForm;
use common\models\Assistant;
use common\models\AssistantKnowledgeBase;
use common\models\KnowledgeBase;
use common\services\OpenAiRecordVectorStoreService;
use tests\DatabaseTestCase;

/**
 * Persistence and resolution rules of the Knowledge Base / Assistant pair ported from
 * masteranunturi: the many-to-many link written by the backend AssistantForm, the vector
 * store ids an assistant searches, the default chat assistant lookup and the knowledge
 * base the page index resolves to.
 */
class KnowledgeBaseAssistantTest extends DatabaseTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();
	}

	protected function tearDown(): void
	{
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();
		parent::tearDown();
	}

	/**
	 * @param array $attributes
	 * @return int
	 */
	private function insertKnowledgeBase(array $attributes = [])
	{
		return $this->insertRow('knowledge_base', array_merge([
			'name' => 'KB ' . substr(bin2hex(random_bytes(3)), 0, 6),
			'provider' => KnowledgeBase::PROVIDER_OPENAI,
			'vector_store_id' => 'vs_' . substr(bin2hex(random_bytes(6)), 0, 12),
			'status' => KnowledgeBase::STATUS_ACTIVE,
			'deleted' => KnowledgeBase::NO,
		], $attributes));
	}

	/**
	 * @param array $attributes
	 * @return int
	 */
	private function insertAssistant(array $attributes = [])
	{
		return $this->insertRow('assistant', array_merge([
			'name' => 'Assistant ' . substr(bin2hex(random_bytes(3)), 0, 6),
			'model' => 'gpt-5.4-mini',
			'provider' => Assistant::PROVIDER_OPENAI,
			'type' => Assistant::TYPE_CHAT,
			'default' => Assistant::NO,
			'status' => Assistant::STATUS_ACTIVE,
			'deleted' => Assistant::NO,
		], $attributes));
	}

	/**
	 * The backend form rejects ids that do not exist, replaces the whole set of links on
	 * every save and silently drops inactive / deleted knowledge bases, so an assistant can
	 * never search a vector store that the backend no longer lists.
	 */
	public function testAssistantFormLinksOnlyActiveKnowledgeBases()
	{
		$active = $this->insertKnowledgeBase();
		$inactive = $this->insertKnowledgeBase(['status' => KnowledgeBase::STATUS_INACTIVE]);
		$deleted = $this->insertKnowledgeBase(['deleted' => KnowledgeBase::YES]);

		$form = new AssistantForm();
		$form->name = 'Site assistant';
		$form->model = 'gpt-5.4-mini';
		$form->knowledge_base_id = [$active, 999999];
		$this->assertFalse($form->save());
		$this->assertArrayHasKey('knowledge_base_id', $form->getErrors());

		$form->knowledge_base_id = [$active, $inactive, $deleted];
		$this->assertNotFalse($form->save(), print_r($form->getErrors(), true));

		$linked = AssistantKnowledgeBase::find()
			->where(['assistant_id' => $form->id])
			->select('knowledge_base_id')
			->column();
		$this->assertSame([(string) $active], array_map('strval', $linked));

		// Re-saving with an empty selection unlinks everything.
		$form = AssistantForm::findOne($form->id);
		$this->assertSame([$active], array_map('intval', (array) $form->knowledge_base_id), 'afterFind exposes the linked ids');
		$form->knowledge_base_id = [];
		$this->assertNotFalse($form->save());
		$this->assertSame(0, (int) AssistantKnowledgeBase::find()->where(['assistant_id' => $form->id])->count());
	}

	/**
	 * collectVectorStoreIds() is what the Responses API `file_search` tool receives: only
	 * provisioned, active, non-deleted knowledge bases count, duplicates collapse, and an
	 * OpenAI assistant with nothing to search must not use the Responses tool at all.
	 */
	public function testCollectVectorStoreIdsSkipsUnusableKnowledgeBases()
	{
		$assistantId = $this->insertAssistant();
		$kbA = $this->insertKnowledgeBase(['vector_store_id' => 'vs_shared']);
		$kbB = $this->insertKnowledgeBase(['vector_store_id' => 'vs_shared']);
		$kbC = $this->insertKnowledgeBase(['vector_store_id' => 'vs_other']);
		$kbNoStore = $this->insertKnowledgeBase(['vector_store_id' => null]);
		$kbInactive = $this->insertKnowledgeBase(['status' => KnowledgeBase::STATUS_INACTIVE]);
		foreach ([$kbA, $kbB, $kbC, $kbNoStore, $kbInactive] as $order => $kbId) {
			$this->insertRow('assistant_knowledge_base', [
				'assistant_id' => $assistantId,
				'knowledge_base_id' => $kbId,
				'sort_order' => $order,
			]);
		}

		$assistant = Assistant::findOne($assistantId);
		$this->assertSame(['vs_shared', 'vs_other'], $assistant->collectVectorStoreIds());
		$this->assertTrue($assistant->useResponsesApi());

		$bare = Assistant::findOne($this->insertAssistant());
		$this->assertSame([], $bare->collectVectorStoreIds());
		$this->assertFalse($bare->useResponsesApi());

		$claude = Assistant::findOne($this->insertAssistant(['provider' => Assistant::PROVIDER_CLAUDE]));
		$this->insertRow('assistant_knowledge_base', ['assistant_id' => $claude->id, 'knowledge_base_id' => $kbA]);
		$this->assertFalse($claude->useResponsesApi(), 'file_search is an OpenAI-only tool');
	}

	/**
	 * The embed chat picks the default active chat assistant; an inactive or deleted default
	 * is ignored, and with none configured the caller falls back to the params-based config.
	 */
	public function testFindChatAssistantPrefersTheActiveDefault()
	{
		$this->assertNull(Assistant::findChatAssistant());

		$this->insertAssistant(['default' => Assistant::YES, 'status' => Assistant::STATUS_INACTIVE]);
		$this->insertAssistant(['default' => Assistant::YES, 'deleted' => Assistant::YES]);
		$this->assertNull(Assistant::findChatAssistant());

		$this->insertAssistant(['default' => Assistant::NO]);
		$defaultId = $this->insertAssistant(['default' => Assistant::YES]);
		$this->assertSame($defaultId, (int) Assistant::findChatAssistant()->id);
	}

	/**
	 * The page index lives in the knowledge base of the chat assistant when there is one,
	 * otherwise in the oldest active OpenAI knowledge base; without any, the index is "not
	 * configured" and every sync is a no-op. The lookup is cached per process, hence the reset.
	 */
	public function testResolveKnowledgeBaseFollowsTheChatAssistant()
	{
		$this->assertFalse(OpenAiRecordVectorStoreService::isConfigured());

		$oldest = $this->insertKnowledgeBase();
		$newer = $this->insertKnowledgeBase();
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();
		$this->assertSame($oldest, (int) OpenAiRecordVectorStoreService::resolveKnowledgeBase()->id);
		$this->assertTrue(OpenAiRecordVectorStoreService::isConfigured());

		$assistantId = $this->insertAssistant(['default' => Assistant::YES]);
		$this->insertRow('assistant_knowledge_base', ['assistant_id' => $assistantId, 'knowledge_base_id' => $newer]);
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();
		$this->assertSame($newer, (int) OpenAiRecordVectorStoreService::resolveKnowledgeBase()->id);
	}

	/**
	 * Both enums use the same numbers today; the mapping is the single place that would
	 * change if they ever diverge, so pin the current contract.
	 */
	public function testProviderMapping()
	{
		$this->assertSame(KnowledgeBase::PROVIDER_OPENAI, KnowledgeBase::providerForAssistantProvider(Assistant::PROVIDER_OPENAI));
		$this->assertSame(KnowledgeBase::PROVIDER_CLAUDE, KnowledgeBase::providerForAssistantProvider(Assistant::PROVIDER_CLAUDE));
		$this->assertNull(KnowledgeBase::providerForAssistantProvider(null));
		$this->assertNull(KnowledgeBase::providerForAssistantProvider(42));
	}
}
