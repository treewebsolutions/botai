<?php

namespace api\v1\modules\user\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Login form
 */
class LoginForm extends Model
{
	/**
	 * @var string The username.
	 */
	public $username;

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
	public $verifyCode;

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
			[['username', 'password'], 'required'],
			['username', 'safe'],
			['rememberMe', 'boolean'],
			['password', 'validatePassword'],
			['verifyCode', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'username' => Yii::t('label', 'Email'),
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
	 * Finds user by username.
	 *
	 * @return User|null
	 */
	protected function getUser()
	{
		if ($this->_user === null) {
			$user = User::findByUsername($this->username);

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
	 * Logs in a user using the provided username and password.
	 *
	 * @return bool whether the user is logged in successfully
	 */
	public function login()
	{
		if (!empty($this->verifyCode)) {
			return false;
		}

		if ($this->validate()) {
			return Yii::$app->user->login($this->getUser(), $this->rememberMe ? (int) Yii::$app->settings->get('userLoginDuration') : 0);
		}
		return false;
	}
}
