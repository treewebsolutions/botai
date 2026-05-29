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
			[['token', 'password'], 'required'],
			[['token', 'password'], 'trim'],
			['password', 'string', 'min' => 6, 'max' => 255],
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
			self::SCENARIO_PASSWORD => ['password', 'workEmail'],
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
