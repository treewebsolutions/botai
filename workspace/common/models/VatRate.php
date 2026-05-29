<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%vat_rate}}".
 *
 * @property int $id
 * @property string $name
 * @property string $vat
 * @property string $start_date
 * @property string $end_date
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property CashRegisterVatGroup[] $cashRegisterVatGroups
 * @property User $creator
 * @property User $updater
 */
class VatRate extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%vat_rate}}';
	}

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => self::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['name', 'vat', 'status'], 'required'],
			[['vat'], 'number'],
			[['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
			[['start_date', 'end_date', 'created_at', 'updated_at'], 'default'],
			[['default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['name'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'name' => Yii::t('label', 'Name'),
			'vat' => Yii::t('label', 'Vat'),
			'start_date' => Yii::t('label', 'Start Date'),
			'end_date' => Yii::t('label', 'End Date'),
			'default' => Yii::t('label', 'Default'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCashRegisterVatGroups()
    {
        return $this->hasMany(CashRegisterVatGroup::class, ['vat_rate_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Gets the formatted vat value.
	 *
	 * @return string
	 */
	public function getFormattedVat()
	{
		return Yii::$app->formatter->asDecimal($this->vat, 2) . '%';
	}

	/**
	 * Gets the formattedName.
	 *
	 * @return string
	 */
	public function getFormattedName()
	{
		return "{$this->name} ({$this->getFormattedVat()})";
	}

	/**
	 * Finds all active records.
	 *
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 */
	public static function findAllVatRates()
	{
		return static::getDb()->cache(function ($db) {
			return static::find()
				->where([
					'status' => self::STATUS_ACTIVE,
					'deleted' => self::NO,
				])
				->orderBy(['name' => SORT_ASC])
				->indexBy('id')
				->all();
		}, 0, new TagDependency(['tags' => __FUNCTION__]));
	}

	/**
	 * Gets the default record.
	 *
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findDefaultVatRate()
	{
		return static::find()
			->select([
				'id',
				'vat',
			])
			->where([
				'default' => self::YES,
				'status' => self::STATUS_ACTIVE,
				'deleted' => self::NO,
			])
			->limit(1)
			->one();
	}
}
