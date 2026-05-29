<?php

namespace common\models;

use Yii;
use yii\base\Model;
use tws\helpers\Url;

class ResetPasswordRequestForm extends Model
{
	/**
	 * @var string The email/phone where the reset password token/link will be sent.
	 */
	public $username;

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
	public $_user;


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['username'], 'required'],
			[['username'], 'trim'],
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
			'username' => Yii::t('label', 'Email'),
		];
	}

	/**
	 * Gets the User model.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = User::findByUsername($this->username);
		}
		return $this->_user;
	}

	/**
	 * Sends reset password email.
	 *
	 * @return bool whether the email was send.
	 */
	public function sendEmail()
	{
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_PASSWORD_RESET);
			if (!$template || !($templateTranslation = $template->getTranslation())) {
				$this->addError('username', Yii::t('common', 'Cannot send reset password message for this user.'));
				throw new \Exception();
			}
			$user = $this->getUser();
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $user->first_name,
				'{{MIDDLE_NAME}}' => $user->middle_name,
				'{{LAST_NAME}}' => $user->last_name,
				'{{EMAIL}}' => $user->email,
				'{{PHONE}}' => $user->phone,
				'{{PASSWORD_RESET_CODE}}' => $user->getPasswordResetToken(),
				'{{PASSWORD_RESET_PAGE_URL}}' => Url::to(['/site/reset-password'], true, '@frontend'),
				'{{PASSWORD_RESET_URL}}' => Url::to(['/site/reset-password', 'token' => $user->getPasswordResetToken()], true, '@frontend'),
			];

			return Yii::$app->mailer->compose()
				->setTo([$user->email => $user->fullName])
				->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
				->send();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sends the reset password request.
	 *
	 * @return bool
	 */
	public function sendRequest()
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
				$this->addError('username', Yii::t('yii', '{attribute} is invalid.', [
					'attribute' => $this->getAttributeLabel('username'),
				]));
				throw new \Exception();
			}
			$user->password_reset_token = User::generatePasswordResetToken();
			if (!$user->save(false)) {
				$this->addError('username', Yii::t('common', 'Cannot reset password for this user.'));
				throw new \Exception();
			}

			$dbTransaction->commit();
			return $this->sendEmail();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
