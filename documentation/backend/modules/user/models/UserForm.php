<?php

namespace backend\modules\user\models;

use common\models\AuthAssignment;
use common\models\AuthItem;
use common\models\Template;
use common\models\User;
use tws\helpers\Url;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class UserForm extends User
{
	/**
	 * @var UploadedFile The imageFile.
	 */
	public $imageFile;

	/**
	 * @var string The role name.
	 */
	public $role;


	/**
	 * @inheritdoc
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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['role', 'email', 'phone', 'gender', 'first_name', 'last_name'], 'required'],
			[['parent_id', 'gender', 'status'], 'integer'],
			[['email', 'phone', 'first_name', 'middle_name', 'last_name', 'image'], 'string', 'max' => 255],
			[['email', 'phone', 'first_name', 'middle_name', 'last_name'], 'trim'],
			[['email'], 'email'],
			[['gender'], 'in', 'range' => [static::GENDER_MALE, static::GENDER_FEMALE]],
			[['imageFile'], 'file', 'extensions' => ['jpg', 'png'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['role'], 'exist', 'skipOnError' => true, 'targetClass' => AuthItem::class, 'targetAttribute' => ['role' => 'name']],

			[['email', 'phone'], function ($attribute, $params, $validator) {
				if ($user = static::findOne([$attribute => $this->$attribute])) {
					if ($this->getIsNewRecord()) {
						if ($user->authAssignment) {
							// Add unique error if the existing user already has a role or permission associated
							$this->addError($attribute, Yii::t('yii', '{attribute} "{value}" has already been taken.', [
								'attribute' => $this->getAttributeLabel($attribute),
								'value' => $this->$attribute,
							]));
						} else {
							// Switch the current model to the existing user and mark it as not a new record
							$this->setOldAttribute('id', $user->getOldAttribute('id'));

							$attributes = array_filter($this->attributes, function ($value, $key) use ($user) {
								return $value != $user->getAttribute($key);
							}, ARRAY_FILTER_USE_BOTH);
							$this->attributes = array_merge($user->attributes, $attributes);

							// Preserve some attributes from existing user
							$this->id = $user->id;
							$this->status = $user->status;
						}
					} else {
						// Make sure that the existing user is the same with currently updated user
						if ($user->id != $this->id) {
							if ($this->$attribute != $this->oldAttributes[$attribute]) {
								$this->addError($attribute, Yii::t('yii', '{attribute} "{value}" has already been taken.', [
									'attribute' => $this->getAttributeLabel($attribute),
									'value' => $this->$attribute,
								]));
							}
						}
					}
				}
			}],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'imageFile' => Yii::t('label', 'Image'),
			'role' => Yii::t('label', 'Role'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->role = $this->authAssignment->item_name;
	}

	/**
	 * Assigns permissions to the User.
	 *
	 * @return bool
	 * @throws \Exception
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
	 * Saves the user files.
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
	 * Sends account activation email.
	 *
	 * @return bool
	 */
	public function sendActivationEmail()
	{
		$template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_ACCOUNT_ACTIVATION);
		if (!$template || !($templateTranslation = $template->getTranslation())) {
			return false;
		}
		$shortCodeValues = [
			'{{APP_NAME}}' => Yii::$app->name,
			'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
			'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
			'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
			'{{FIRST_NAME}}' => $this->first_name,
			'{{MIDDLE_NAME}}' => $this->middle_name,
			'{{LAST_NAME}}' => $this->last_name,
			'{{ACCOUNT_ACTIVATION_CODE}}' => $this->getSignupToken(),
			'{{ACCOUNT_ACTIVATION_PAGE_URL}}' => Url::to(['/site/activate'], true, '@frontend'),
			'{{ACCOUNT_ACTIVATION_URL}}' => Url::to(['/site/activate', 'token' => $this->getSignupToken()], true, '@frontend'),
		];

		return Yii::$app->mailer->compose()
			->setTo([$this->email => $this->fullName])
			->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
			->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
			->send();
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$isNewRecord = $this->getIsNewRecord();
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			// Run validation first to check the unique attributes
			if (!$this->validate()) {
				throw new \Exception();
			}
			// Check again if is a new record, since the validate method overwrites this property
			if ($isNewRecord = $this->getIsNewRecord()) {
				$this->setPassword(Yii::$app->security->generateRandomString(8));
				$this->generateAuthKey();
				if (Yii::$app->settings->get('userAccountActivation') == static::ACCOUNT_ACTIVATION_CONFIRMATION) {
					$this->signup_token = User::generateSignupToken();
				}
			}
			// Save without performing validation again
			if (!parent::save(false, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->assignPermissions()) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
			if ($isNewRecord && Yii::$app->settings->get('userAccountActivation') == static::ACCOUNT_ACTIVATION_CONFIRMATION) {
				if (!$this->sendActivationEmail()) {
					$this->addError('', Yii::t('common', 'Cannot send the account confirmation message.'));
					throw new \Exception();
				}
			}

			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			if ($isNewRecord) {
				$this->id = null;
				$this->setIsNewRecord(true);
			}
			$dbTransaction->rollBack();
			return false;
		}
	}
}
