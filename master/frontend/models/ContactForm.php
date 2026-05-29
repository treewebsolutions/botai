<?php

namespace frontend\models;

use Yii;
use yii\base\Model;

class ContactForm extends Model
{
	/**
	 * @var string The contact name.
	 */
	public $name;

	/**
	 * @var string The email address.
	 */
	public $email;

	/**
	 * @var string The phone number.
	 */
	public $phone;

	/**
	 * @var string The subject of the email.
	 */
	public $subject;

	/**
	 * @var string The message of the email.
	 */
	public $message;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;


    /**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name', 'email', 'phone', 'subject', 'message'], 'required'],
			['email', 'email'],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name' => Yii::t('label', 'Name'),
			'email' => Yii::t('label', 'Email'),
			'phone' => Yii::t('label', 'Phone'),
			'subject' => Yii::t('label', 'Subject'),
			'message' => Yii::t('label', 'Message'),
		];
	}

	/**
	 * Sends an email to the specified email address using the information collected by this model.
	 *
	 * @return bool whether the email was sent.
	 */
	public function sendEmail()
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
		return Yii::$app->mailer
			->compose(
				['html' => 'contact-html', 'text' => 'contact-text'],
				['model' => $this]
			)
			->setTo(Yii::$app->settings->get('email', 'contact'))
			->setReplyTo([$this->email => $this->name])
			->setSubject($this->subject)
			->send();
	}
}
