<?php

namespace common\models;

use common\validators\PasswordValidator;
use InvalidArgumentException;
use Yii;
use yii\base\Model;

class ResetPasswordForm extends Model
{
	/**
	 * @var string The new password.
	 */
	public $password;

	/**
	 * @var string The new password confirm.
	 */
	public $password_confirm;

	/**
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * Creates a form model given a token.
	 *
	 * @param string $token
	 * @param array $config name-value pairs that will be used to initialize the object properties.
	 * @throws \yii\base\InvalidArgumentException if token is empty or not valid.
	 */
	public function __construct($token, $config = [])
	{
		if (empty($token) || !is_string($token)) {
			throw new InvalidArgumentException(Yii::t('common', 'Password reset token cannot be blank.'));
		}

		$this->_user = User::findByPasswordResetToken($token);

		if (!$this->_user) {
			throw new InvalidArgumentException(Yii::t('common', 'Wrong password reset token.'));
		}

		parent::__construct($config);
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['password', 'password_confirm'], 'required'],
			[['password', 'password_confirm'], 'trim'],
			[['password', 'password_confirm'], 'string'],
			[['password'], PasswordValidator::class],
			['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => Yii::t('common', 'Passwords don\'t match.')],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
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
		return $this->_user;
	}

	/**
	 * Resets password.
	 *
	 * @return bool if password was reset.
	 * @throws \yii\base\Exception
	 */
	public function resetPassword()
	{
		$user = $this->_user;
		$user->setPassword($this->password);
		$user->password_reset_token = null;

		return $user->save(false);
	}
}
