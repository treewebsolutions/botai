<?php

namespace common\models;

use common\helpers\CaptchaHelper;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Login form
 */
class LoginForm extends Model
{
	/**
	 * @var string The email address that identifies the account.
	 */
	public $email;

	/**
	 * @var string The password.
	 */
	public $password;

	/**
	 * @var bool Flag that indicates if login credentials should be remembered for a period of time.
	 */
	public $rememberMe = true;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;

	/**
	 * @var User The user model.
	 */
	private $_user;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['email', 'password'], 'required'],
			[['email'], 'trim'],
			// Rejected here rather than looked up: an address that is not an address
			// cannot name an account, and saying so costs one less query than finding
			// out from the database.
			['email', 'email'],
			['rememberMe', 'boolean'],
			['password', 'validatePassword'],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'email' => Yii::t('label', 'Email'),
			'password' => Yii::t('label', 'Password'),
			'rememberMe' => Yii::t('label', 'Remember Me'),
		]);
	}

	/**
	 * Validates the password.
	 * This method serves as the inline validation for password.
	 *
	 * @param string $attribute the attribute currently being validated
	 * @param array $params the additional name-value pairs given in the rule
	 */
	public function validatePassword($attribute, $params)
	{
		if (!$this->hasErrors()) {
			$user = $this->getUser();
			if (!$user || !$user->validatePassword($this->password)) {
				$this->addError('', Yii::t('common', 'The provided credentials are invalid.'));
			}
		}
	}

	/**
	 * Finds the account the given email and password identify.
	 *
	 * An address can name more than one account - the hub user and a row per workspace,
	 * each with its own password - so the password decides which of them is meant.
	 *
	 * @return User|null
	 */
	protected function getUser()
	{
		if ($this->_user === null) {
			$user = User::findByEmail($this->email);

			if (is_array($user)) {
				foreach ($user as $userModel) {
					if ($userModel->validatePassword($this->password)) {
						$this->_user = $userModel;
						return $this->_user;
					}
				}
				$this->_user = null;
				return $this->_user;
			}

			$this->_user = $user;
		}
		return $this->_user;
	}

	/**
	 * Logs in a user using the provided email and password.
	 *
	 * @return bool whether the user is logged in successfully
	 */
	public function login()
	{
		if (!empty($this->workEmail)) {
			return false;
		}
		if (!CaptchaHelper::verify($this->captchaResponse)) {
			$this->addError('captchaResponse', Yii::t('common', 'The captcha verification failed. Please try again.'));
			return false;
		}

		if ($this->validate()) {
			return Yii::$app->user->login($this->getUser(), $this->rememberMe ? (int) Yii::$app->settings->get('userLoginDuration') : 0);
		}
		return false;
	}
}
