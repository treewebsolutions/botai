<?php

namespace tests\unit;

use common\models\KnowledgeBase;
use common\services\OpenAiRecordVectorStoreService;
use tests\DatabaseTestCase;
use Yii;

/**
 * Which knowledge base scraped pages are indexed into.
 *
 * It used to be whichever base was linked to the chat assistant, or failing that the one
 * with the lowest id - the order things happened to be created in. A base can now be
 * marked default, the way the OpenAI key comes from the integration marked default, and
 * that is a decision rather than an accident.
 */
class DefaultKnowledgeBaseTest extends DatabaseTestCase
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
	private function knowledgeBase(array $attributes = [])
	{
		$kb = new KnowledgeBase(array_merge([
			'name' => 'kb-' . uniqid('', true),
			'provider' => KnowledgeBase::PROVIDER_OPENAI,
			'vector_store_id' => 'vs_' . uniqid('', true),
			'status' => KnowledgeBase::STATUS_ACTIVE,
			'deleted' => KnowledgeBase::NO,
			'default' => KnowledgeBase::NO,
		], $attributes));

		$this->assertTrue($kb->save(), json_encode($kb->getErrors()));

		return (int) $kb->id;
	}

	/**
	 * The base marked default wins, whatever it was created after.
	 */
	public function testTheDefaultBaseIsChosen()
	{
		$this->knowledgeBase();
		$default = $this->knowledgeBase(['default' => KnowledgeBase::YES]);
		$this->knowledgeBase();

		$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();

		$this->assertNotNull($kb);
		$this->assertSame($default, (int) $kb->id, 'the lowest id would have won before');
	}

	/**
	 * With nothing marked, the oldest active base is still taken, so an installation that
	 * never sets one keeps working exactly as it did.
	 */
	public function testTheOldestIsUsedWhenNothingIsMarked()
	{
		$first = $this->knowledgeBase();
		$this->knowledgeBase();

		$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();

		$this->assertNotNull($kb);
		$this->assertSame($first, (int) $kb->id);
	}

	/**
	 * A default that is inactive or deleted is not a default.
	 */
	public function testAnUnusableDefaultIsSkipped()
	{
		$this->knowledgeBase(['default' => KnowledgeBase::YES, 'status' => KnowledgeBase::STATUS_INACTIVE]);
		$this->knowledgeBase(['default' => KnowledgeBase::YES, 'deleted' => KnowledgeBase::YES]);
		$usable = $this->knowledgeBase();

		$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();

		$this->assertNotNull($kb);
		$this->assertSame($usable, (int) $kb->id);
	}

	/**
	 * The explicit id in params still outranks everything, so an override stays an
	 * override.
	 */
	public function testTheParamsOverrideStillWins()
	{
		$override = $this->knowledgeBase();
		$this->knowledgeBase(['default' => KnowledgeBase::YES]);

		$previous = Yii::$app->params['pageVectorKnowledgeBaseId'] ?? null;
		try {
			Yii::$app->params['pageVectorKnowledgeBaseId'] = $override;
			OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();

			$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();

			$this->assertNotNull($kb);
			$this->assertSame($override, (int) $kb->id);
		} finally {
			Yii::$app->params['pageVectorKnowledgeBaseId'] = $previous;
		}
	}
}
