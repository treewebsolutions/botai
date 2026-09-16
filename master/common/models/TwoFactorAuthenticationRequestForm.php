<?php

namespace common\models;

use tws\helpers\StringHelper;
use Yii;
use yii\base\Model;
use tws\helpers\Url;

class TwoFactorAuthenticationRequestForm extends Model
{
	/**
	 * @var string The email address the confirmation code is sent to.
	 */
	public $email;

	/**
	 * @var string The honeypot field.
	 */
	public $verifyCode;

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
			[['email'], 'required'],
			[['email'], 'trim'],
			['email', 'email'],
			['verifyCode', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'email' => Yii::t('label', 'Email'),
		];
	}

	/**
	 * Gets the User model.
	 *
	 * @return array|\yii\db\ActiveRecord[]|\yii\db\ActiveRecord|User[]|User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$user = User::findByEmail($this->email);
			$this->_user = is_array($user) ? reset($user) : $user;
		}
		return $this->_user;
	}

	/**
	 * Sends two factor authentication form.
	 *
	 * @return bool whether the email was send.
	 */
	public function sendEmail()
	{
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_TWO_FACTOR_AUTHENTICATION);
			if (!$template || !($templateTranslation = $template->getTranslation())) {
				$this->addError('email', Yii::t('common', 'Cannot send confirm login message for this user.'));
				throw new \Exception();
			}
			$user = $this->getUser();
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to('/', true),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $user->first_name,
				'{{MIDDLE_NAME}}' => $user->middle_name,
				'{{LAST_NAME}}' => $user->last_name,
				'{{TWO_FACTOR_AUTHENTICATION_CODE}}' => $user->getLoginToken(),
				'{{TWO_FACTOR_AUTHENTICATION_PAGE_URL}}' => Url::to(['/site/confirm-login'], true),
				'{{TWO_FACTOR_AUTHENTICATION_URL}}' => Url::to(['/site/confirm-login', 'token' => $user->getLoginToken()], true),
			];

			return Yii::$app->mailer->compose()
				->setTo([$user->email => $user->fullName])
				->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
				->send();
		} catch (\Exception $e) {
			// Was print_r() followed by die(), which answered the login with a raw
			// exception message and no page at all.
			Yii::warning('Could not send the two-factor authentication message: ' . $e->getMessage(), __METHOD__);
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
		if (!empty($this->verifyCode)) {
			return false;
		}

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!($user = $this->getUser())) {
				$this->addError('email', Yii::t('yii', '{attribute} is invalid.', [
					'attribute' => $this->getAttributeLabel('email'),
				]));
				throw new \Exception();
			}


			$currentDate = date('Y-m-d H:i:s');
			$days = (Yii::$app->settings->get('twoFactorAuthenticationDuration') ? ' -' . Yii::$app->settings->get('twoFactorAuthenticationDuration') . ' days' : '');
			$limitDate = new \DateTime(date('Y-m-d H:i:s', strtotime($currentDate . $days)));
			$lastLogin = new \DateTime($user->last_login);

			if ($user->last_login && $limitDate < $lastLogin) {
				return true;
			}

			$loginToken = User::generateLoginToken();

			if (is_array($user)) {
				// Use the first User model in array, since the ID is the same
				/** @var User $user */
				$user = reset($user);
			} else {
				// Set login token for User model
				$user->login_token = $loginToken;
				if (!$user->save(false)) {
					$this->addError('email', Yii::t('common', 'Cannot confirm login for this user.'));
					throw new \Exception();
				}
			}

			// Set password reset token for WorkspaceHasUser models (if any)
			/** @var WorkspaceHasUser[] $workspaceUsers */
			$workspaceUsers = WorkspaceHasUser::find()
				->andWhere(['user_id' => $user->id])
				->andWhere(['=', 'email', $this->email])
				->all();
			foreach ($workspaceUsers as $workspaceUser) {
				$workspaceUser->login_token = $loginToken;
				if (!$workspaceUser->save(false)) {
					$this->addError('email', Yii::t('common', 'Cannot confirm login for this user.'));
					throw new \Exception();
				}
				// Set login token in workspace database
				$workspaceUser->workspace->getWorkspaceDb()->createCommand()->update(User::tableName(),
					[
						'login_token' => $loginToken,
					],
					[
						'id' => $user->id,
					]
				)->execute();
			}

			$dbTransaction->commit();

			return $this->sendEmail();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
