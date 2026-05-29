<?php

namespace backend\modules\nomenclature\models;

use common\models\UnitOfMeasure;
use common\models\UnitOfMeasureTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class UnitOfMeasureForm extends UnitOfMeasure
{
	/**
	 * @var array The multilingual name of the UnitOfMeasure.
	 */
	public $name = [];

	/**
	 * @var array The multilingual symbol of the UnitOfMeasure.
	 */
	public $symbol = [];


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
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
                return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
    	    }'],
			[['symbol'], 'required', 'when' => function ($model) {
				return empty($model->symbol[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
		        return attribute.$form.find("[name*=\"[symbol][' . Yii::$app->language . ']\"]").val() == "";
	        }'],
			[['name', 'symbol'], 'each', 'rule' => ['trim']],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'name' => Yii::t('label', 'Name'),
			'symbol' => Yii::t('label', 'Symbol'),
			'code_id' => Yii::t('label', 'e-Invoice Code'),
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

		$this->name = ArrayHelper::map($this->unitOfMeasureTranslations, 'language_id', 'name');
		$this->symbol = ArrayHelper::map($this->unitOfMeasureTranslations, 'language_id', 'symbol');
	}

	/**
	 * Saves the UnitOfMeasure translations.
	 *
	 * @return bool
	 */
	protected function saveUnitOfMeasureTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$unitOfMeasure = UnitOfMeasureTranslation::findOne([
					'unit_of_measure_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$unitOfMeasure) {
					$unitOfMeasure = new UnitOfMeasureTranslation();
					$unitOfMeasure->unit_of_measure_id = $this->id;
					$unitOfMeasure->language_id = $language->language_id;
				}
				$unitOfMeasure->name = static::ensureTranslationValue($this->name, $language->language_id);
				$unitOfMeasure->symbol = static::ensureTranslationValue($this->symbol, $language->language_id);

				$this->link('unitOfMeasureTranslations', $unitOfMeasure);
			}
			return true;
		} catch (InvalidCallException $e) {
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
			// Reset all default statuses if the default checkbox is checked.
			if ($this->default) {
				static::updateAll(['default' => static::NO], ['default' => static::YES]);
			}
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveUnitOfMeasureTranslations()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
