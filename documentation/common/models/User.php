<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use tws\helpers\StringHelper;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use tws\helpers\Url;
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
 * @property EventLog[] $eventLogs
 * @property Setting[] $settings
 * @property User $parent
 * @property User[] $users
 * @property AuthAssignment $authAssignment
 * @property User $creator
 * @property User $updater
 *
 * @property Auth[] $auths
 * @property string fullName
 * @property string fullNameInitials
 * @property string shortName
 * @property string|null imageUrl
 */
class User extends CommonActiveRecord implements \yii\web\IdentityInterface
{
	const ACCOUNT_ACTIVATION_AUTOMATIC = 1;
	const ACCOUNT_ACTIVATION_CONFIRMATION = 2;

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
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
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
	 * Finds user by username (it can be any of username, email or phone).
	 *
	 * @param string $username
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findByUsername($username)
	{
		return static::find()
			->andWhere([
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->andWhere([
				'OR',
				['=', 'username', $username],
				['=', 'email', $username],
				['=', 'phone', $username],
			])
			->one();
	}

	/**
	 * Finds user by password reset token.
	 *
	 * @param string $token password reset token
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findByPasswordResetToken($token)
	{
		// Make sure that the token length is correct, since we are using LIKE to find the model
		if (strlen($token) !== 8) {
			return null;
		}

		return static::find()
            ->andWhere(new \yii\db\Expression('SUBSTRING([[password_reset_token]], 1, 8) = :token'), [':token' => $token])
			->andWhere([
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->one();
	}

	/**
	 * Finds user by signup token.
	 *
	 * @param string $token signup token
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findBySignupToken($token)
	{
		// Make sure that the token length is correct, since we are using LIKE to find the model
		if (strlen($token) !== 8) {
			return null;
		}

		return static::find()
            ->andWhere(new \yii\db\Expression('SUBSTRING([[signup_token]], 1, 8) = :token'), [':token' => $token])
			->andWhere([
				'status' => static::STATUS_INACTIVE,
				'deleted' => static::NO,
			])
			->one();
	}

	/**
	 * Generates a unique password reset token.
	 *
	 * @param int $length
	 * @return string
	 */
	public static function generatePasswordResetToken($length = 8)
	{
		$token = StringHelper::generateRandomAlphanumerics($length) . '_' . time();

		if (User::find()->where(['password_reset_token' => $token])->exists()) {
			return static::generateSignupToken($length);
		}

		return $token;
	}

	/**
	 * Finds out if password reset token is valid.
	 *
	 * @param string $token password reset token
	 * @return bool
	 */
	public static function isPasswordResetTokenValid($token)
	{
		if (empty($token)) {
			return false;
		}

		$timestamp = (int) substr($token, strrpos($token, '_') + 1);
		$expire = (int) Yii::$app->settings->get('userPasswordResetTokenExpiration');

		return ($timestamp + $expire) >= time();
	}

	/**
	 * Generates a unique signup token.
	 *
	 * @param int $length
	 * @return string
	 */
	public static function generateSignupToken($length = 8)
	{
		$token = StringHelper::generateRandomAlphanumerics($length) . '_' . time();

		if (User::find()->where(['signup_token' => $token])->exists()) {
			return static::generateSignupToken($length);
		}

		return $token;
	}

	/**
	 * Finds out if signup token is valid.
	 *
	 * @param string $token signup token
	 * @return bool
	 */
	public static function isSignupTokenValid($token)
	{
		if (empty($token)) {
			return false;
		}

		$timestamp = (int) substr($token, strrpos($token, '_') + 1);
		$expire = (int) Yii::$app->settings->get('userSignupTokenExpiration');

		return ($timestamp + $expire) >= time();
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

	/**
	 * Validates password.
	 *
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password)
	{
		return Yii::$app->security->validatePassword($password, $this->password_hash);
	}

	/**
	 * Generates password hash from password and sets it to the model.
	 *
	 * @param string $password
	 * @throws \yii\base\Exception
	 */
	public function setPassword($password)
	{
		$this->password_hash = Yii::$app->security->generatePasswordHash($password);
	}

	/**
	 * Generates "remember me" authentication key.
	 *
	 * @throws \yii\base\Exception
	 */
	public function generateAuthKey()
	{
		$this->auth_key = Yii::$app->security->generateRandomString();
	}

	/**
	 * Gets the password reset token without timestamp.
	 *
	 * @return string|null
	 */
	public function getPasswordResetToken()
	{
		if ($this->password_reset_token) {
			return substr($this->password_reset_token, 0, strrpos($this->password_reset_token, '_'));
		}
		return null;
	}

	/**
	 * Gets the signup token without timestamp.
	 *
	 * @return string|null
	 */
	public function getSignupToken()
	{
		if ($this->signup_token) {
			return substr($this->signup_token, 0, strrpos($this->signup_token, '_'));
		}
		return null;
	}
	//endregion

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuths()
    {
        return $this->hasMany(Auth::class, ['user_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getEventLogs()
	{
		return $this->hasMany(EventLog::class, ['user_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSettings()
	{
		return $this->hasMany(Setting::class, ['user_id' => 'id']);
	}

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
	 * Gets the AuthAssignment role name.
	 * It must return array if the user can have multiple assignments (like multiple roles)
	 *
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthAssignment()
	{
		return $this->hasOne(AuthAssignment::class, ['user_id' => 'id']);
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
	 * Gets the fullName.
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return implode(' ', array_filter([
			$this->first_name,
			$this->middle_name,
			$this->last_name,
		]));
	}

	/**
	 * Gets the fullNameInitials.
	 *
	 * @return string
	 */
	public function getFullNameInitials()
	{
		return implode('', array_filter([
			substr($this->first_name, 0, 1),
			substr($this->last_name, 0, 1),
		]));
	}

	/**
	 * Gets the shortName.
	 *
	 * @return string
	 */
	public function getShortName()
	{
		return implode('. ', array_filter([
			substr($this->first_name, 0, 1),
			$this->last_name,
		]));
	}

	/**
	 * Gets the imageUrl with fallback to a blank image.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return Url::to("@uploads/user/{$this->id}/{$this->image}", $scheme);
	}

	/**
	 * Checks if this user has a Customer model associated.
	 *
	 * @param bool|int $status Flag that indicates if the status of Customer model should be checked.
	 * @param bool|int $deleted Flag that indicates if Customer model soft delete should be checked.
	 * @return bool
	 */
	public function getIsCustomer($status = true, $deleted = true)
	{
		if (!$this->customer) {
			return false;
		}
		$result = true;

		if ($status !== false) {
			$result = $result && ($this->customer->status == ($status === true ? Customer::STATUS_ACTIVE : $status));
		}
		if ($deleted !== false) {
			$result = $result && ($this->customer->deleted == ($deleted === true ? Customer::NO : $deleted));
		}

		return $result;
	}

	/**
	 * Checks if this user has at leas one permission / role associated.
	 *
	 * @return bool
	 */
	public function getHasPermissions()
	{
		return $this->authAssignment !== null;
	}

	/**
	 * Model accountActivation labels.
	 *
	 * @return array
	 */
	public static function getAccountActivationLabels()
	{
		return [
			static::ACCOUNT_ACTIVATION_AUTOMATIC => Yii::t('label', 'Automatic'),
			static::ACCOUNT_ACTIVATION_CONFIRMATION => Yii::t('label', 'Confirmation'),
		];
	}

	/**
	 * Finds all active records.
	 *
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllUsers()
	{
		try {
			return static::getDb()->cache(function ($db) {
				return User::find()
					->alias('u')
					->select([
						'u.id',
						'u.first_name',
						'u.middle_name',
						'u.last_name',
					])
					->andWhere([
						'u.status' => static::STATUS_ACTIVE,
						'u.deleted' => static::NO,
					])
//					->andWhere([
//						'AND',
//						['!=', 'u.id', 1], // TODO: find a way to remove the hardcoded value
//					])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Finds all existing users with no role or permission associated.
	 *
	 * @param string $username
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllUsersWithoutRoleByUsername($username)
	{
		return static::find()
			->alias('u')
			->select([
				'u.id',
				'u.email',
				'u.phone',
				'u.first_name',
				'u.middle_name',
				'u.last_name',
				'u.gender',
				'u.status',
			])
			->joinWith([
				'authAssignment aa' => function (\yii\db\ActiveQuery $query) {
					$query->andWhere(['IS', 'aa.item_name', null]);
				},
			], false)
			->andWhere([
				'OR',
				['=', 'u.username', $username],
				['=', 'u.email', $username],
				['=', 'u.phone', $username],
			])
			->all();
	}

	/**
	 * Finds all existing users that are not customers.
	 *
	 * @param string $username
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllNotCustomerUsersByUsername($username)
	{
		return static::find()
			->alias('u')
			->select([
				'u.id',
				'u.email',
				'u.phone',
				'u.first_name',
				'u.middle_name',
				'u.last_name',
				'u.gender',
				'u.status',
			])
			->joinWith([
				'authAssignment aa' => function (\yii\db\ActiveQuery $query) {
					$query->andWhere(new \yii\db\Expression('IF([[aa.item_name]] = "superAdmin", 1, 0) = 0'));
				},
				'customer c' => function (\yii\db\ActiveQuery $query) {
					$query->andWhere(['IS', 'c.id', null]);
				},
			], false)
			->andWhere([
				'OR',
				['=', 'u.username', $username],
				['=', 'u.email', $username],
				['=', 'u.phone', $username],
			])
			->all();
	}
}
