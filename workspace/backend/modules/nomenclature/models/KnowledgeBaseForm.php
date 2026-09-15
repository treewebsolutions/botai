<?php

namespace backend\modules\nomenclature\models;

use common\models\KnowledgeBase;
use common\services\OpenAIResponsesService;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class KnowledgeBaseForm extends KnowledgeBase
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		
		$this->status = static::STATUS_ACTIVE;
		// Default 2000 chars per chunk; fits in 100k RAG context with TOP_K 40
		$this->chunk_size = 2000;
		$this->chunk_overlap = 250;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['provider'], 'safe'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios[Model::SCENARIO_DEFAULT][] = 'provider';
		return $scenarios;
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}

			if ((int)$this->provider === static::PROVIDER_OPENAI && empty($this->vector_store_id)) {
				try {
					OpenAIResponsesService::ensureVectorStore($this);
				} catch (\Throwable $e) {
					Yii::warning('Vector store auto-provision failed: ' . $e->getMessage(), __METHOD__);
				}
			}

			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$this->addError('', $e->getMessage());
			$dbTransaction->rollBack();
			return false;
		}
	}
}

