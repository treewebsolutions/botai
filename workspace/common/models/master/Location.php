<?php

namespace common\models\master;

use common\models\Order;
use Yii;
use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%location}}".
 *
 * @property int $id
 * @property int $contractor_id
 * @property string $code
 * @property string $name
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Contractor $contractor
 * @property User $creator
 * @property User $updater
 */
class Location extends CommonActiveRecord
{

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
	}

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%location}}';
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
            [['contractor_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['code', 'name', 'status'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
            [['code'], 'string', 'max' => 8],
            [['name'], 'string', 'max' => 255],
            [['contractor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Contractor::class, 'targetAttribute' => ['contractor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'contractor_id' => Yii::t('label', 'Contractor ID'),
            'code' => Yii::t('label', 'Code'),
            'name' => Yii::t('label', 'Name'),
            'created_by' => Yii::t('label', 'Created By'),
            'updated_by' => Yii::t('label', 'Updated By'),
            'created_at' => Yii::t('label', 'Created At'),
            'updated_at' => Yii::t('label', 'Updated At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery|CommonActiveQuery
     */
    public function getContractor()
    {
        return $this->hasOne(Contractor::class, ['id' => 'contractor_id']);
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
	 * Generates an unique code.
	 *
	 * @param int $length
	 * @return string
	 * @throws \yii\base\Exception
	 */
	public static function generateUniqueCode($length = 8)
	{
		$code = Yii::$app->security->generateRandomString($length);

		// Ensure that the generated string is alphanumeric
		if (!preg_match('/^[a-zA-Z0-9]*$/', $code)) {
			return static::generateUniqueCode($length);
		}

		// Ensure that the generated string is unique
		if (static::find()->where(['code' => $code])->limit(1)->exists()) {
			return static::generateUniqueCode($length);
		}

		return $code;
	}

	/**
	 * Finds by its unique attributes.
	 *
	 * @param array $attributes
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findByAttributes($attributes)
	{
		$model = static::find()
			->andWhere([
				'=', 'code', $attributes['code']
			])
			->one();

		if ($model) {
			return $model;
		}
	}

	/**
	 * Creates a new User model in master application.
	 * This method also check for an existing user by its unique attributes.
	 *
	 * @param array $attributes
	 * @return bool|static
	 */
	public static function createModel($attributes)
	{
		try {
			$model = static::findByAttributes($attributes);
			if (!$model) {
				$model = new static();
				$model->setAttributes($attributes);
				if (!$model->save()) {
					throw new \Exception('Cannot save master model.');
				}
			}
			return $model;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Updates an existing User model in master application.
	 *
	 * @param int $id
	 * @param array $attributes
	 * @return bool|self
	 */
	public static function updateModel($id, $attributes)
	{
		try {
			if (!($model = static::findOne($id))) {
				throw new \Exception();
			}
			unset($attributes['id']);
			$model->setAttributes($attributes);
			if (!$model->save()) {
				throw new \Exception('Cannot save master model.');
			}
			return $model;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function queryLocationsByOrder($order_id)
	{
		try {
			$order = Order::findOne(['id' => $order_id]);
			return static::find()
				->alias('l')
				->select([
					"l.name AS id",
					"l.name",
				])
				->andWhere([
					'l.status' => self::STATUS_ACTIVE,
					'l.deleted' => self::NO,
					'l.contractor_id' => $order->contractor_id,
				])
				->orderBy([
					'l.name' => SORT_ASC,
				])
				->createCommand()
				->queryAll();
		} catch (\Exception $e) {
			return [];
		}
	}

	public static function queryLocationsByContractor($contractor_id)
	{
		try {
			return static::find()
				->alias('l')
				->select([
					"l.name AS id",
					"l.name",
				])
				->andWhere([
					'l.status' => self::STATUS_ACTIVE,
					'l.deleted' => self::NO,
					'l.contractor_id' => $contractor_id,
				])
				->orderBy([
					'l.name' => SORT_ASC,
				])
				->createCommand()
				->queryAll();
		} catch (\Exception $e) {
			return [];
		}
	}

	public static function findLocationsByOrder($order_id)
	{
		try {
			$order = Order::findOne(['id' => $order_id]);
			return static::find()
				->alias('l')
				->select([
					"l.id",
					"l.name",
				])
				->andWhere([
					'l.status' => self::STATUS_ACTIVE,
					'l.deleted' => self::NO,
					'l.contractor_id' => $order->contractor_id,
				])
				->orderBy([
					'l.name' => SORT_ASC,
				])
				->all();
		} catch (\Exception $e) {
			return [];
		}
	}
}
