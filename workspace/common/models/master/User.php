<?php

namespace common\models\master;

use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use tws\behaviors\DateTimeBehavior;
use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

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
 * @property WorkspaceHasUser[] $workspaceHasUsers
 * @property Workspace[] $workspaces
 * @property User $creator
 * @property User $updater
 */
class User extends CommonActiveRecord
{
	const ACCOUNT_ACTIVATION_AUTOMATIC = 1;
	const ACCOUNT_ACTIVATION_CONFIRMATION = 2;

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
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
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getWorkspaceHasUsers()
	{
		return $this->hasMany(WorkspaceHasUser::class, ['user_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getWorkspaces()
	{
		return $this->hasMany(Workspace::class, ['id' => 'workspace_id'])->viaTable('{{%workspace_has_user}}', ['user_id' => 'id']);
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

	//region CRUD
	/**
	 * Creates a new User model in master application.
	 * This method also check for an existing user by its unique attributes.
	 *
	 * @param array $attributes
	 * @return bool|static
	 */
	public static function createModel($attributes, $workspace = true)
	{
		try {
			$user = static::findByAttributes($attributes);
			if (!$user) {
				$user = new static();
				$user->setAttributes($attributes);
				$user->deleted = static::NO;
				if (!$user->save()) {
					throw new \Exception('Cannot save master User model.');
				}
			}

			if ($workspace) {
				/** @var Workspace $currentWorkspace */
				$currentWorkspace = Yii::$app->user->identity->workspace;
				$workspaceHasUser = WorkspaceHasUser::findOne([
					'workspace_id' => $currentWorkspace->id,
					'user_id' => $user->id,
				]);
				if (!$workspaceHasUser) {
					$workspaceHasUser = new WorkspaceHasUser();
				}
				$workspaceHasUser->setAttributes($user->getAttributes());
				$workspaceHasUser->workspace_id = $currentWorkspace->id;
				$workspaceHasUser->user_id = $user->id;
				if (!WorkspaceHasUser::find()->where(['user_id' => $user->id, 'default' => WorkspaceHasUser::YES])->exists()) {
					$workspaceHasUser->default = WorkspaceHasUser::YES;
				}
				$workspaceHasUser->deleted = WorkspaceHasUser::NO;
				if ($workspaceHasUser->isNewRecord && !empty($attributes['signup_token'])) {
					$workspaceHasUser->signup_token = $attributes['signup_token'];
					$workspaceHasUser->last_activity = null;
					$workspaceHasUser->status = $attributes['status'];
				}
				if (!$workspaceHasUser->save()) {
					throw new \Exception('Cannot save master WorkspaceHasUser model.');
				}
			}

			return $user;
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
	public static function updateModel($id, $attributes, $workspace = true)
	{
		try {
			if (!($user = static::findOne($id))) {
				throw new \Exception();
			}

			if ($workspace) {
				/** @var Workspace $currentWorkspace */
				$currentWorkspace = Yii::$app->user->identity->workspace;
				$workspaceHasUser = WorkspaceHasUser::findOne([
					'workspace_id' => $currentWorkspace->id,
					'user_id' => $user->id,
				]);
				if (!$workspaceHasUser) {
					$workspaceHasUser = new WorkspaceHasUser();
				}
				$workspaceHasUser->setAttributes($attributes);
				$workspaceHasUser->workspace_id = $currentWorkspace->id;
				$workspaceHasUser->user_id = $user->id;
				if (!WorkspaceHasUser::find()->where(['user_id' => $user->id, 'default' => WorkspaceHasUser::YES])->exists()) {
					$workspaceHasUser->default = WorkspaceHasUser::YES;
				}
				if (!$workspaceHasUser->save()) {
					throw new \Exception('Cannot save master WorkspaceHasUser model.');
				}
			}

			return $user;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Deletes an existing User model in master application.
	 *
	 * @param int $id
	 * @param bool $delete flag that indicate if the model should be deleted from master database.
	 * @return bool
	 */
	public static function deleteModel($id, $delete = false)
	{
		try {
			// Unlink the user from current workspace
			$user = static::findOne($id);
			$user->unlink('workspaces', Yii::$app->user->identity->workspace, true);

			if ($delete) {
				return $user->delete();
			}
			return true;
		} catch (\Exception $e) {
			return false;
		} catch (\Throwable $e) {
			return false;
		}
	}
	//endregion CRUD

	/**
	 * Finds user by its unique attributes (it can be any of username, email or phone).
	 *
	 * @param array $attributes
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findByAttributes($attributes)
	{
		$model = static::find()
			->andWhere([
				'deleted' => static::NO,
			])
			->andWhere([
				'=', 'email', $attributes['email']
			])
			->one();

		if ($model) {
			return $model;
		}

		return static::find()
			->alias('u')
			->select([
				'u.id',
				'u.parent_id',
				'whu.*',
			])
			->joinWith(['workspaceHasUsers whu'], false, 'RIGHT JOIN')
			->andWhere([
				'whu.status' => WorkspaceHasUser::STATUS_ACTIVE,
				'whu.deleted' => WorkspaceHasUser::NO,
			])
			->andWhere([
				'=', 'whu.email', $attributes['email']
			])
			->limit(1)
			->one();
	}
}
