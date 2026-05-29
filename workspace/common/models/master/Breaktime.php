<?php

namespace common\models\master;

use Yii;
use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%breaktime}}".
 *
 * @property int $id
 * @property int $contractor_id
 * @property string $code
 * @property string $start_at
 * @property string $end_at
 * @property int $break_time
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
class Breaktime extends CommonActiveRecord
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
        return '{{%breaktime}}';
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
            [['contractor_id', 'break_time', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['code', 'status'], 'required'],
            [['created_at', 'updated_at'], 'safe'],
	        [['start_at', 'end_at'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 8],
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
            'start_at' => Yii::t('label', 'Start At'),
            'end_at' => Yii::t('label', 'End At'),
            'break_time' => Yii::t('label', 'Break Time'),
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

	public static function findBreakTimeByContractor($contractor_id) {
		$currentDate = date('Y-m-d H:i:s');
		$startTime = date('Y-m-d H:i:s', strtotime('+60 minutes', strtotime($currentDate)));
		$endTime = date('Y-m-d H:i:s', strtotime('-60 minutes', strtotime($currentDate)));
		$query = static::find()
			->select([
				'*'
			])
			->from([
				'{{%breaktime}}'
			])
			->where([
				'contractor_id' => $contractor_id,
			])
			->andWhere([
				'OR',
				[
					'AND',
					['<', 'start_at', new Expression("[[end_at]]")],
					['<', new Expression("STR_TO_DATE(CONCAT(CURDATE(), ' ', [[start_at]]), '%Y-%m-%d %H:%i:%s')"), new Expression("STR_TO_DATE('$startTime', '%Y-%m-%d %H:%i:%s')")],
					['>', new Expression("STR_TO_DATE(CONCAT(CURDATE(), ' ', [[end_at]]), '%Y-%m-%d %H:%i:%s')"), new Expression("STR_TO_DATE('$endTime', '%Y-%m-%d %H:%i:%s')")],
				],
				[
					'AND',
					['>', 'start_at', new Expression("[[end_at]]")],
					['<', new Expression("STR_TO_DATE(CONCAT(DATE_SUB(CURDATE(),INTERVAL 1 DAY), ' ', [[start_at]]), '%Y-%m-%d %H:%i:%s')"), new Expression("STR_TO_DATE('$startTime', '%Y-%m-%d %H:%i:%s')")],
					['>', new Expression("STR_TO_DATE(CONCAT(CURDATE(), ' ', [[end_at]]), '%Y-%m-%d %H:%i:%s')"), new Expression("STR_TO_DATE('$endTime', '%Y-%m-%d %H:%i:%s')")],
				],
			])
			->orderBy([
				'end_at' => SORT_ASC,
			]);
		return $query->one();
	}
}
