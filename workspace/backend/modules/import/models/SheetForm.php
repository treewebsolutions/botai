<?php

namespace backend\modules\import\models;

use common\models\ImportSheet;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class SheetForm extends ImportSheet
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->number = 1;
		$this->header = 1;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['number'], 'required'],
			[['header'], 'integer', 'min' => 0],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'number' => Yii::t('import', 'Sheet Name'),
			'header' => Yii::t('import', 'Table head row index'),
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
