<?php

namespace common\models\documentation;

use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property int $id
 * @property int $parent_id
 * @property string $auth_key
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $signup_token
 * @property string $login_token
 * @property string $username
 * @property string $email
 * @property string $phone
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $image
 * @property int $gender
 * @property string $signature
 * @property string $last_activity
 * @property string $last_login
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property User $parent
 * @property User[] $users
 * @property User $creator
 * @property User $updater
 *
 * @property string fullName
 * @property string fullNameInitials
 * @property string shortName
 * @property string|null imageUrl
 */
class User extends CommonActiveRecord implements \yii\web\IdentityInterface
{
	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('documentationDb');
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%user}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['last_activity', 'last_login'],
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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['parent_id', 'gender', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['first_name', 'last_name', 'status'], 'required'],
			[['signature'], 'string'],
			[['created_at', 'updated_at', 'last_activity', 'last_login'], 'safe'],
			[['created_at', 'updated_at', 'last_activity', 'last_login'], 'default'],
			[['auth_key'], 'string', 'max' => 32],
			[['password_hash', 'password_reset_token', 'signup_token', 'login_token', 'username', 'email', 'phone', 'first_name', 'middle_name', 'last_name', 'image'], 'string', 'max' => 255],
			[['username', 'email', 'phone', 'password_reset_token', 'signup_token', 'login_token'], 'unique'],
			[['email'], 'email'],
			['gender', 'in', 'range' => [static::GENDER_MALE, static::GENDER_FEMALE]],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['parent_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'parent_id' => Yii::t('label', 'Parent ID'),
			'auth_key' => Yii::t('label', 'Auth Key'),
			'password_hash' => Yii::t('label', 'Password Hash'),
			'password_reset_token' => Yii::t('label', 'Password Reset Token'),
			'signup_token' => Yii::t('label', 'Signup Token'),
			'login_token' => Yii::t('label', 'Login Token'),
			'email' => Yii::t('label', 'Email'),
			'username' => Yii::t('label', 'Username'),
			'phone' => Yii::t('label', 'Phone'),
			'image' => Yii::t('label', 'Image'),
			'first_name' => Yii::t('label', 'First Name'),
			'middle_name' => Yii::t('label', 'Middle Name'),
			'last_name' => Yii::t('label', 'Last Name'),
			'gender' => Yii::t('label', 'Gender'),
			'signature' => Yii::t('label', 'Signature'),
			'last_activity' => Yii::t('label', 'Last Activity'),
			'last_login' => Yii::t('label', 'Last Login'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	//region IDENTITY
	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id)
	{
		return static::findOne([
			'id' => $id,
			'status' => static::STATUS_ACTIVE,
			'deleted' => static::NO,
		]);
	}

	/**
	 * @inheritdoc
	 * @throws NotSupportedException
	 */
	public static function findIdentityByAccessToken($token, $type = null)
	{
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}


	/**
	 * @inheritdoc
	 */
	public function getId()
	{
		return $this->getPrimaryKey();
	}

	/**
	 * @inheritdoc
	 */
	public function getAuthKey()
	{
		return $this->auth_key;
	}

	/**
	 * @inheritdoc
	 */
	public function validateAuthKey($authKey)
	{
		return $this->getAuthKey() === $authKey;
	}
	//endregion

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(User::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::class, ['parent_id' => 'id']);
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
}
