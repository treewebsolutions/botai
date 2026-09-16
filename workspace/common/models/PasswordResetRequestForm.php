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
			// No `exist` rule: reporting that an address has no account here turned
			// this form into a membership oracle, and the hub's equivalent stopped
			// doing that. An address we do not know is answered exactly like one we do.
		];
	}

	/**
	 * Sends an email with a link, for resetting the password.
	 *
	 * Always reports success. Whether the address is known, whether the token could be
	 * stored and whether the message went out are operational facts, logged for an
	 * operator; handing any of them back to the requester would say which addresses
	 * hold an account here just as plainly as the old "there is no user with this
	 * email address" did.
	 *
	 * @return bool always true
	 * @throws \yii\base\Exception
	 */
	public function sendEmail()
	{
		/* @var $user User */
		$user = User::findByEmail($this->email);
		if (!$user) {
			Yii::info('Password reset requested for an unknown address.', __METHOD__);
			return true;
		}

		// Ensure that the User model has a password reset token set
		if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
			$user->password_reset_token = User::generatePasswordResetToken();
			if (!$user->save()) {
				Yii::warning('Could not store the password reset token.', __METHOD__);
				return true;
			}
		}

		$sent = Yii::$app->mailer
			->compose(
				['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
				['user' => $user]
			)
			->setTo([$this->email => $user->fullName])
			->setSubject(Yii::t('label', 'Password reset for {name}', ['name' => Yii::$app->name]))
			->send();

		if (!$sent) {
			// Worth an operator's attention, but not the requester's: a delivery failure
			// reported back distinguishes a known address from an unknown one.
			Yii::warning('Could not send the password reset message.', __METHOD__);
		}

		return true;
	}
}
