<?php

namespace common\models;

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
			['password', 'required'],
			['password', 'string', 'min' => 6],
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
