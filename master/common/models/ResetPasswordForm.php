<?php

namespace common\models;

use Yii;
use yii\base\Model;

class ResetPasswordForm extends Model
{
	const SCENARIO_TOKEN = 'token';
	const SCENARIO_PASSWORD = 'password';

	/**
	 * @var string The account activation token.
	 */
	public $token;

	/**
	 * @var string The new password.
	 */
	public $password;

	/**
	 * @var string The new password confirm.
	 */
	public $password_confirm;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;

	/**
	 * @var User The User model.
	 */
	private $_user;


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['token', 'password', 'password_confirm'], 'required'],
			[['token', 'password', 'password_confirm'], 'trim'],
			[['password', 'password_confirm'], 'string', 'min' => 6, 'max' => 255],
			['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => Yii::t('common', 'Passwords don\'t match.')],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return [
			self::SCENARIO_TOKEN => ['token', 'workEmail'],
			self::SCENARIO_PASSWORD => ['password', 'password_confirm', 'workEmail'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'token' => Yii::t('label', 'Password Reset Code'),
			'password' => Yii::t('label', 'New Password'),
			'password_confirm' => Yii::t('label', 'Confirm New Password'),
		];
	}

	/**
	 * Finds user by password reset token.
	 *
	 * @return array|\yii\db\ActiveRecord[]|\yii\db\ActiveRecord|User[]|User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = User::findByPasswordResetToken($this->token);
		}
		return $this->_user;
	}

	public function saveDocumentationUser()
	{
		try {
			$user = $this->getUser();
			$model = \common\models\documentation\User::findOne(['id' => $user->id]);
			if (empty($model)) {
				$model = new \common\models\documentation\User();
			}
			$model->setAttributes($user->attributes, false);
			if (!$model->save(false)) {
				throw new \Exception();
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Resets password.
	 *
	 * @return bool if password was reset.
	 */
	public function resetPassword()
	{
		if (!empty($this->workEmail)) {
			return false;
		}
        if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
            if (!empty($this->captchaResponse)) {
                $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
                $response = json_decode($result);
                if (empty($response->success)) {
                    return false;
                }
            }
        }
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!($user = $this->getUser())) {
				$this->addError('token', Yii::t('yii', '{attribute} is invalid.', [
					'attribute' => $this->getAttributeLabel('token'),
				]));
				throw new \Exception();
			}

			if (is_array($user)) {
				// Use the first User model in array, since the ID is the same
				/** @var User $user */
				$user = reset($user);
			} else {
				// Set the password hash for User model and remove the password reset token
				$user->setPassword($this->password);
				$user->password_reset_token = null;
				if (!$user->save(false)) {
					$this->addError('password', Yii::t('common', 'Cannot reset password for this user.'));
					throw new \Exception();
				}
			}

			// Set the password hash for WorkspaceHasUser models (if any) and remove the password reset token
			/** @var WorkspaceHasUser[] $workspaceUsers */
			$workspaceUsers = WorkspaceHasUser::find()
				->andWhere(['user_id' => $user->id])
				->all();
			foreach ($workspaceUsers as $model) {
                $model->auth_key = $user->auth_key;
                $model->password_hash = $user->password_hash;
                $model->password_reset_token = null;
				if (!$model->save(false)) {
					$this->addError('username', Yii::t('common', 'Cannot reset password for this user.'));
					throw new \Exception();
				} else {
                    $workspace = Workspace::findOne(['id' => $model->workspace_id]);
                    $workspaceDb = $workspace->getWorkspaceDb();
                    $workspaceDb->createCommand()->update('{{%user}}', [
                        'auth_key' => $user->auth_key,
                        'password_hash' => $user->password_hash,
                        'password_reset_token' => null,
                    ],
                    [
                        'id' => $user->id
                    ])->execute();
                }
			}

			if ($user->signup_token && $user->status == User::STATUS_INACTIVE) {
				$account = new ActivateAccountForm();
				$token = explode('_', $user->signup_token)[0];
				$account->token = $token;
				if (!$account->activate()) {
					throw new \Exception();
				}
			}

			if (!$this->saveDocumentationUser()) {
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
