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
use yii\db\ActiveQuery;
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
 * @property AuthAssignment $authAssignment
 * @property Setting[] $settings
 * @property UserHasNotification[] $userHasNotifications
 * @property Notification[] $notifications
 * @property User $creator
 * @property User $updater
 * @property master\Workspace[] $workspaces
 * @property master\Workspace $workspace
 *
 * @property string $fullName
 * @property string $fullNameInitials
 * @property string $shortName
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
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['last_activity'],
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

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}

		if ($insert) {
			if (!($masterUser = master\User::createModel($this->attributes))) {
				return false;
			}
			$this->id = $masterUser->id;
		} else {
			if (!($masterUser = master\User::updateModel($this->id, $this->attributes))) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function afterDelete()
	{
		master\User::deleteModel($this->id, false);

		parent::afterDelete();
	}

	//region IDENTITY
	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id)
	{
		return static::findOne([
			'id' => $id,
			'status' => self::STATUS_ACTIVE,
			'deleted' => self::NO,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentityByAccessToken($token, $type = null)
	{
		return static::findOne([
			'auth_key' => $token,
			'deleted' => self::NO,
			'status' => self::STATUS_ACTIVE,
		]);
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
			->where([
				'status' => self::STATUS_ACTIVE,
				'deleted' => self::NO,
			])
			->andWhere(['=', 'email', $username])
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
				'deleted' => self::NO,
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
			return static::generatePasswordResetToken($length);
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
	 * Validates password
	 *
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password)
	{
		return Yii::$app->security->validatePassword($password, $this->password_hash);
	}

	/**
	 * Generates password hash from password and sets it to the model
	 *
	 * @param string $password
	 * @throws \yii\base\Exception
	 */
	public function setPassword($password)
	{
		$this->password_hash = Yii::$app->security->generatePasswordHash($password);
	}

	/**
	 * Generates "remember me" authentication key
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

	//region MASTER
	/**
	 * Gets the current User master Tenant models.
	 *
	 * @return array|\yii\db\ActiveRecord[]|master\Workspace[]
	 */
	public function getWorkspaces()
	{
		return master\Workspace::findAllWorkspacesByUser($this->id);
	}

	/**
	 * Gets the current User's master Workspace model.
	 *
	 * @return array|null|\yii\db\ActiveRecord|master\Workspace
	 */
	public function getWorkspace()
	{
		$model = null;
		$workspaceUrl = reset(explode('/', ltrim(Yii::$app->request->baseUrl, '/')));

		if (empty($workspaceUrl)) {
			return $model;
		}

		foreach ($this->getWorkspaces() as $workspace) {
			if ($workspace->url == $workspaceUrl) {
				$model = $workspace;
				break;
			}
		}

		return $model;
	}
	//endregion MASTER

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

	// TODO: Make it return array if the user can have multiple assignments (like multiple roles)
	/**
	 * Custom added.
	 *
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthAssignment()
	{
		return $this->hasOne(AuthAssignment::class, ['user_id' => 'id']);
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
	 * Model accountActivation labels.
	 *
	 * @return array
	 */
	public static function getAccountActivationLabels()
	{
		return [
			self::ACCOUNT_ACTIVATION_AUTOMATIC => Yii::t('label', 'Automatic'),
			self::ACCOUNT_ACTIVATION_CONFIRMATION => Yii::t('label', 'Manual'),
		];
	}

	/**
	 * Finds all active records.
	 *
	 * @return array|\yii\db\ActiveRecord[]|self[]
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
						'u.status' => self::STATUS_ACTIVE,
						'u.deleted' => self::NO,
					])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}

	public static function findAllNotificationUsers()
	{
		return static::find()
			->alias('u')
			->joinWith([
				'authAssignment aa' => function (ActiveQuery $query) {
					$query->andWhere(['IS NOT', 'aa.item_name', null]);
				},
				'authAssignment.itemName.authItemChildren aic' => function (ActiveQuery $query) {
					$query->andWhere(['aic.child' => 'viewNotification']);
				},
			], false)
			->andWhere([
				'u.status' => static::STATUS_ACTIVE,
				'u.deleted' => static::NO,
			])
			->indexBy('id')
			->all();
	}
}
