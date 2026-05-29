<?php

namespace backend\modules\nomenclature\models;

use common\models\Assistant;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class AssistantForm extends Assistant
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		
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
			[['gpt_model', 'temperature', 'top_p', 'vector_store_id'], 'required'],
			[['temperature'], 'number', 'min' => 0, 'max' => 2],
			[['top_p'], 'number', 'min' => 0, 'max' => 1],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'vector_store_id' => Yii::t('label', 'Vector Store'),
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
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (empty($this->openai_id)) {
				$record = $this->createInOpenAI();
				if (!$record) {
					throw new \Exception(Yii::t('backend', 'Failed to create record in OpenAI.'));
				}
				$this->openai_id = $record['id'];
			} else {
				$record = $this->updateInOpenAI($this->openai_id);
				if (!$record) {
					throw new \Exception(Yii::t('backend', 'Failed to update record in OpenAI.'));
				}
			}
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$this->addError('', $this->getCustomErrorMessage($e));
			$dbTransaction->rollBack();
			return false;
		}
	}
}
