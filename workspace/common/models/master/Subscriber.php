<?php

namespace common\models\master;

use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use common\models\Country;
use common\models\Invoice;
use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%subscriber}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property int $user_id
 * @property string $pin
 * @property string $date_of_birth
 * @property string $street_name
 * @property string $street_number
 * @property string $staircase
 * @property string $block
 * @property string $floor
 * @property string $apartment
 * @property string $locality
 * @property string $zip_code
 * @property string $county
 * @property string $country
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Invoice[] $invoices
 * @property Subscriber $parent
 * @property Subscriber[] $subscribers
 * @property User $user
 * @property Subscription[] $subscriptions
 * @property User $creator
 * @property User $updater
 * @property string fullAddress
 */
class Subscriber extends CommonActiveRecord
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
		return '{{%subscriber}}';
	}

	/**
	 * @inheritdoc
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
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['date_of_birth'],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
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
			[['parent_id', 'user_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['user_id', 'status'], 'required'],
			[['date_of_birth', 'created_at', 'updated_at'], 'safe'],
			[['date_of_birth', 'created_at', 'updated_at'], 'default'],
			[['pin', 'street_name', 'street_number', 'staircase', 'block', 'floor', 'apartment', 'locality', 'zip_code', 'county', 'country'], 'string', 'max' => 255],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscriber::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'parent_id' => Yii::t('label', 'Parent ID'),
			'user_id' => Yii::t('label', 'User ID'),
			'pin' => Yii::t('label', 'Pin'),
			'date_of_birth' => Yii::t('label', 'Date Of Birth'),
			'street_name' => Yii::t('label', 'Street Name'),
			'street_number' => Yii::t('label', 'Street Number'),
			'staircase' => Yii::t('label', 'Staircase'),
			'block' => Yii::t('label', 'Block'),
			'floor' => Yii::t('label', 'Floor'),
			'apartment' => Yii::t('label', 'Apartment'),
			'locality' => Yii::t('label', 'Locality'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'county' => Yii::t('label', 'County'),
			'country' => Yii::t('label', 'Country'),
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
	public function getInvoices()
	{
		return $this->hasMany(Invoice::class, ['subscriber_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Subscriber::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscribers()
	{
		return $this->hasMany(Subscriber::class, ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(User::class, ['id' => 'user_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscriptions()
	{
		return $this->hasMany(Subscription::class, ['subscriber_id' => 'id']);
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
	 * Returns all Workspace models that are related to this Subscriber model.
	 *
	 * @return array|\yii\db\ActiveRecord[]|Workspace[]
	 */
	public function getWorkspaces()
	{
		return Workspace::find()
			->alias('w')
			->joinWith([
				'subscription sub' => function (CommonActiveQuery $query) {
					$query->andWhere(['sub.subscriber_id' => $this->id]);
				},
			])
			->all();
	}

	/**
	 * Gets the fullAddress.
	 *
	 * @return string
	 */
	public function getFullAddress()
	{
		return implode(', ', array_filter([
			$this->street_name,
			$this->street_number,
			$this->locality,
			$this->zip_code,
			$this->county,
			$this->country ? Country::findAllCountries()[$this->country]->translation->name : null,
		]));
	}
}
