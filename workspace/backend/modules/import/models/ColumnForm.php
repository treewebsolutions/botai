<?php

namespace backend\modules\import\models;

use common\models\ImportColumn;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class ColumnForm extends ImportColumn
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->field_type = static::FIELD_TYPE_STRING;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['target'], 'required'],
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
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}

			$transaction->commit();

			return $this;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
