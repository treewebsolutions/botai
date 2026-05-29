<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%exchange_rate}}".
 *
 * @property int $id
 * @property string $date
 * @property string $currency
 * @property string $rate_value
 * @property int $multiplier
 * @property int $deleted
 */
class ExchangeRate extends CommonActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%exchange_rate}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['date'], 'safe'],
			[['date'], 'default'],
			[['rate_value'], 'number'],
			[['multiplier', 'deleted'], 'integer'],
			[['currency'], 'string', 'max' => 3],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'date' => Yii::t('label', 'Date'),
			'currency' => Yii::t('label', 'Currency'),
			'rate_value' => Yii::t('label', 'Rate Value'),
			'multiplier' => Yii::t('label', 'Multiplier'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * Finds last exchange rate by currency.
	 *
	 * @param string $currency
	 * @return yii\db\ActiveRecord|ExchangeRate
	 */
	public static function findLastByCurrency($currency)
	{
		return static::find()
			->where([
				'currency' => $currency,
				'deleted' => self::NO,
			])
			->orderBy(['id' => SORT_DESC])
			->limit(1)
			->one();
	}

	/**
	 * Finds exchange rate by date and currency.
	 * Falls back to last exchange rate found by currency.
	 *
	 * @param string $date
	 * @param string $currency
	 * @param bool $fallbackToLast
	 * @return yii\db\ActiveRecord|ExchangeRate
	 */
	public static function findOneByDateAndCurrency($date, $currency, $fallbackToLast = true)
	{
		$model = static::find()
			->where([
				'date' => $date,
				'currency' => $currency,
				'deleted' => self::NO,
			])
			->limit(1)
			->one();

		if ($fallbackToLast === true && $model === null) {
			$model = static::findLastByCurrency($currency);
		}

		return $model;
	}
}
