<?php

namespace common\models;

use Yii;
use yii\base\Model;

class PasswordResetRequestForm extends Model
{
	/**
	 * @var string The email address where the reset password token/link will be sent.
	 */
	public $email;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['email', 'trim'],
			['email', 'required'],
			['email', 'email'],
			['email', 'exist',
				'targetClass' => '\common\models\User',
				'filter' => ['status' => User::STATUS_ACTIVE],
				'message' => 'There is no user with this email address.'
			],
		];
	}

	/**
	 * Sends an email with a link, for resetting the password.
	 *
	 * @return bool whether the email was send
	 * @throws \yii\base\Exception
	 */
	public function sendEmail()
	{
		/* @var $user User */
		$user = User::findOne([
			'email' => $this->email,
			'status' => User::STATUS_ACTIVE,
			'deleted' => User::NO,
		]);
		// Exit if there is no user with the provided email address
		if (!$user) {
			return false;
		}
		// Ensure tha the User model has a password reset token set
		if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
			// Generate a new password reset token
			$user->password_reset_token = User::generatePasswordResetToken();
			// Save the User model
			if (!$user->save()) {
				return false;
			}
		}
		// Send the email
		return Yii::$app->mailer
			->compose(
				['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
				['user' => $user]
			)
			->setTo([$this->email => $user->fullName])
			->setSubject(Yii::t('label', 'Password reset for {name}', ['name' => Yii::$app->name]))
			->send();
	}
}
