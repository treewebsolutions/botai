<?php

namespace common\models;

use tws\helpers\StringHelper;
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
	 * Getter flag that indicates if the username is a valid email.
	 *
	 * @return bool
	 */
	public function getIsUsernameValidEmail()
	{
		return (new \yii\validators\EmailValidator)->validate($this->username);
	}

	/**
	 * Gets the User model.
	 *
	 * @return array|\yii\db\ActiveRecord[]|\yii\db\ActiveRecord|User[]|User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$user = User::findByUsername($this->username);
			$this->_user = is_array($user) ? reset($user) : $user;
		}
		return $this->_user;
	}

	/**
	 * Sends reset password email.
	 *
	 * @return bool whether the email was sent.
	 */
	public function sendEmail()
	{
		try {
			$user = $this->getUser();
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_PASSWORD_RESET);
			if (!$template) {
				$this->addError('username', Yii::t('common', 'Cannot send reset password message for this user.'));
				throw new \Exception();
			}
			if (!($templateTranslation = $template->getTranslation()) || !($page = Page::findPageByRoute(['/site/reset-password'])->getTranslation())) {
				$templateTranslation = $template->getTranslation(Yii::$app->settings->get('defaultLanguage'));
				$page = Page::findPageByRoute(['/site/reset-password'])->getTranslation(Yii::$app->settings->get('defaultLanguage'));
				$language = mb_substr(Yii::$app->settings->get('defaultLanguage'), 0, 2);
				if (empty($templateTranslation) || empty($page)) {
					return false;
				}
			} else {
				$language = mb_substr(Yii::$app->language, 0, 2);
			}
			$appUrl = Yii::$app->request->hostInfo;
			$passwordResetPageUrl = implode('/', array_filter([
				$appUrl,
				$language,
				$page->slug
			]));
			$passwordResetUrl = implode('', [
				$passwordResetPageUrl,
				'?token=' . $user->getPasswordResetToken()
			]);
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $user->first_name,
				'{{MIDDLE_NAME}}' => $user->middle_name,
				'{{LAST_NAME}}' => $user->last_name,
				'{{PASSWORD_RESET_CODE}}' => $user->getPasswordResetToken(),
				'{{PASSWORD_RESET_PAGE_URL}}' => $passwordResetPageUrl,
				'{{PASSWORD_RESET_URL}}' => $passwordResetUrl,
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
			$passwordResetToken = User::generatePasswordResetToken();

			if (is_array($user)) {
				// Use the first User model in array, since the ID is the same
				/** @var User $user */
				$user = reset($user);
			} else {
				// Set password reset token for User model
				$user->password_reset_token = $passwordResetToken;
				if (!$user->save(false)) {
					$this->addError('username', Yii::t('common', 'Cannot reset password for this user.'));
					throw new \Exception();
				}
			}

			// Set password reset token for WorkspaceHasUser models (if any)
			/** @var WorkspaceHasUser[] $workspaceUsers */
			$workspaceUsers = WorkspaceHasUser::find()
				->andWhere(['user_id' => $user->id])
				->andWhere([
					'OR',
					['=', 'username', $this->username],
					['=', 'email', $this->username],
					['=', 'phone', $this->username],
				])
				->all();
			foreach ($workspaceUsers as $workspaceUser) {
				$workspaceUser->password_reset_token = $passwordResetToken;
				if (!$workspaceUser->save(false)) {
					$this->addError('username', Yii::t('common', 'Cannot reset password for this user.'));
					throw new \Exception();
				}
			}

			$dbTransaction->commit();

			if ($this->getIsUsernameValidEmail()) {
				return $this->sendEmail();
			}
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
