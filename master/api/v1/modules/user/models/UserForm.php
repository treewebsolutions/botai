<?php

namespace api\v1\modules\user\models;

use common\helpers\StringHelper;
use common\models\AuthAssignment;
use common\models\Page;
use tws\helpers\Url;
use Yii;
use common\models\User;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;
use common\models\Template;

class UserForm extends User
{
	// Scenarios
	const SCENARIO_CREATE = 'create';

	// Custom
	public $password;
	public $password_confirm;
	public $role;
	public $permissions;

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		if (Yii::$app->settings->get('userAccountActivation') == static::ACCOUNT_ACTIVATION_CONFIRMATION) {
			$this->status = static::STATUS_INACTIVE;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['password', 'password_confirm'], 'string', 'max' => 255],
			[['email', 'phone', 'password', 'password_confirm', 'first_name', 'middle_name', 'last_name'], 'trim'],
			['password_confirm', 'required', 'when' => function ($model) {
				return !empty($model->password);
			}],
			['password_confirm', 'compare', 'compareAttribute' => 'password', 'message' => Yii::t('api', 'Passwords don\'t match.')],
			[['password', 'password_confirm'], 'required', 'on' => self::SCENARIO_CREATE],
			[['image'], 'file', 'extensions' => ['jpeg', 'jpg', 'png', 'gif'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'password' => Yii::t('api', 'Password'),
			'password_confirm' => Yii::t('api', 'Password Confirm'),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * Assigns permissions to the User.
	 *
	 * @return bool
	 */
	protected function assignPermissions()
	{
		try {
			AuthAssignment::deleteAll(['user_id' => $this->id]);

			if (!empty($this->role)) {
				$authManager = Yii::$app->authManager;

				$role = $authManager->getRole($this->role);
				$authManager->assign($role, $this->id);
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
			if (!($file = UploadedFile::getInstance($this, 'imageFile'))) {
				return true;
			}

			$dirPath = Yii::getAlias("@uploads/user/{$this->id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
					Inflector::slug($this->fullName),
					Yii::$app->security->generateRandomString(8),
				])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
			if (!$this->updateAttributes(['image' => $fileName])) {
				throw new \Exception();
			}
			if (is_file($oldFilePath) && $oldFilePath != $filePath) {
				FileHelper::unlink($oldFilePath);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sends welcome email.
	 *
	 * @return bool
	 */
	public function sendWelcomeEmail()
	{
		try {
			$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_WELCOME);
			if (!$template || !($templateTranslation = $template->getTranslation())) {
				throw new \Exception();
			}
			$shortCodeValues = [
				'{{APP_NAME}}' => Yii::$app->name,
				'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
				'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
				'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
				'{{FIRST_NAME}}' => $this->first_name,
				'{{MIDDLE_NAME}}' => $this->middle_name,
				'{{LAST_NAME}}' => $this->last_name,
				'{{EMAIL}}' => $this->email,
				'{{PHONE}}' => $this->phone,
			];

			return Yii::$app->mailer->compose()
				->setTo([$this->email => $this->fullName])
				->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
				->send();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sends account activation email.
	 *
	 * @return bool
	 */
	public function sendActivationEmail()
	{
		$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_ACCOUNT_ACTIVATION);
		if (!$template) {
			return false;
		}
		if (!($templateTranslation = $template->getTranslation()) || !($page = Page::findPageByRoute(['/site/activate'])->getTranslation())) {
			$templateTranslation = $template->getTranslation(Yii::$app->settings->get('defaultLanguage'));
			$page = Page::findPageByRoute(['/site/activate'])->getTranslation(Yii::$app->settings->get('defaultLanguage'));
			$language = mb_substr(Yii::$app->settings->get('defaultLanguage'), 0, 2);
			if (empty($templateTranslation) || empty($page)) {
				return false;
			}
		} else {
			$language = mb_substr(Yii::$app->language, 0, 2);
		}
		$appUrl = Yii::$app->request->hostInfo;
		$activationPageUrl = implode('/', array_filter([
			$appUrl,
			$language,
			$page->slug
		]));
		$activationUrl = implode('', [
			$activationPageUrl,
			'?token=' . $this->getSignupToken()
		]);
		$shortCodeValues = [
			'{{APP_NAME}}' => Yii::$app->name,
			'{{APP_URL}}' => $appUrl,
			'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
			'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
			'{{FIRST_NAME}}' => $this->first_name,
			'{{MIDDLE_NAME}}' => $this->middle_name,
			'{{LAST_NAME}}' => $this->last_name,
			'{{ACCOUNT_ACTIVATION_CODE}}' => $this->getSignupToken(),
			'{{ACCOUNT_ACTIVATION_PAGE_URL}}' => $activationPageUrl,
			'{{ACCOUNT_ACTIVATION_URL}}' => $activationUrl,
		];

		return Yii::$app->mailer->compose()
			->setTo([$this->email => $this->fullName])
			->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
			->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
			->send();
	}

	/**
	 * {@inheritdoc}
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$isNewRecord = $this->getIsNewRecord();
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			unset($this->image);
			if (!$this->validate()) {
				throw new \Exception();
			}
			if ($isNewRecord) {
				$this->setPassword($this->password ?: Yii::$app->security->generateRandomString(8));
				$this->generateAuthKey();
				if (Yii::$app->settings->get('userAccountActivation') == static::ACCOUNT_ACTIVATION_CONFIRMATION) {
					$this->signup_token = User::generateSignupToken();
				}
			}
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
			if ($isNewRecord && Yii::$app->settings->get('userAccountActivation') == static::ACCOUNT_ACTIVATION_CONFIRMATION) {
				$this->sendWelcomeEmail();
				$emailSent = $this->sendActivationEmail();
				if (!$emailSent) {
					$this->addError('', Yii::t('common', 'Cannot send the account confirmation message.'));
					throw new \Exception();
				}
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
