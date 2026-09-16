<?php

namespace common\models;

use common\validators\PasswordValidator;
use common\helpers\CaptchaHelper;
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
			[['password', 'password_confirm'], 'string'],
			[['password'], PasswordValidator::class],
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
	 * @return User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = User::findByPasswordResetToken($this->token);
		}
		return $this->_user;
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
		if (!CaptchaHelper::verify($this->captchaResponse)) {
			$this->addError('captchaResponse', Yii::t('common', 'The captcha verification failed. Please try again.'));
			return false;
		}

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!($user = $this->getUser())) {
				$this->addError('token', Yii::t('yii', '{attribute} is invalid.', [
					'attribute' => $this->getAttributeLabel('token'),
				]));
				throw new \Exception();
			}
			$user->setPassword($this->password);
			$user->password_reset_token = null;
			if (!$user->save(false)) {
				$this->addError('password', Yii::t('common', 'Cannot reset password for this user.'));
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
