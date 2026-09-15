<?php

namespace backend\modules\nomenclature\models;

use common\models\Assistant;
use common\models\KnowledgeBase;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class AssistantForm extends Assistant
{
	/**
	 * @var int|int[] The linked KnowledgeBase ids (multi-select).
	 */
	public $knowledge_base_id;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->isNewRecord && !$this->provider) {
			$this->provider = static::PROVIDER_OPENAI;
		}
		if ($this->isNewRecord && !$this->type) {
			$this->type = static::TYPE_CHAT;
		}
		$this->temperature = 1.00;
		$this->top_p = 1.00;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['model', 'temperature', 'top_p'], 'required'],
			[['temperature'], 'number', 'min' => 0, 'max' => 2],
			[['top_p'], 'number', 'min' => 0, 'max' => 1],
			[['max_tokens'], 'required', 'when' => function ($model) {
				return (int) $model->provider === static::PROVIDER_CLAUDE;
			}, 'whenClient' => "function (attribute, value) {
				var providerValue = attribute.\$form.find('[name*=\"[provider]\"]').val();
				return providerValue == '" . static::PROVIDER_CLAUDE . "';
			}", 'message' => Yii::t('yii', '{attribute} cannot be blank.')],
			[['knowledge_base_id'], 'each', 'rule' => ['exist', 'targetClass' => KnowledgeBase::class, 'targetAttribute' => ['knowledge_base_id' => 'id'], 'skipOnError' => true]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'knowledge_base_id' => Yii::t('label', 'Knowledge Bases'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->knowledge_base_id = ArrayHelper::getColumn($this->knowledgeBases, 'id');
	}

	/**
	 * Links the KnowledgeBase models (only active, not deleted ones are kept).
	 *
	 * @return bool
	 */
	protected function linkKnowledgeBases()
	{
		try {
			$this->unlinkAll('knowledgeBases', true);
			$ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->knowledge_base_id))));
			if (empty($ids)) {
				return true;
			}
			$knowledgeBases = KnowledgeBase::findAll([
				'id' => $ids,
				'status' => KnowledgeBase::STATUS_ACTIVE,
				'deleted' => KnowledgeBase::NO,
			]);
			foreach ($knowledgeBases as $knowledgeBase) {
				$this->link('knowledgeBases', $knowledgeBase);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
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
			if (!$this->linkKnowledgeBases()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch (\Exception $e) {
			if ($e->getMessage() !== '') {
				$this->addError('', $e->getMessage());
			}
			$dbTransaction->rollBack();
			return false;
		}
	}
}
