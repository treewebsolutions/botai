<?php

namespace backend\modules\nomenclature\models;

use common\models\VectorStore;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class VectorStoreForm extends VectorStore
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [

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
				if (!empty($record['expires_at'])) {
					$this->expire_at = date('Y-m-d H:i:s', $record['expires_at']);
				}
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
