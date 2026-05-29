<?php

namespace backend\modules\setting\models;

use common\models\Country;
use common\models\Language;
use common\models\ScheduledTask;
use common\models\Setting;
use common\models\User;
use common\models\VatRate;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use tws\helpers\Url;
use yii\web\UploadedFile;

class GeneralSettingForm extends Setting
{
	/**
	 * @var string The application host info.
	 */
	public $hostInfo;

	/**
	 * @var string The application name.
	 */
	public $appName;

	/**
	 * @var array The multilingual application description.
	 */
	public $appDescription = [];

	/**
	 * @var string The application logo.
	 */
	public $appLogo;

	/**
	 * @var string The application theme.
	 */
	public $theme;

	/**
	 * @var int The number of items displayed on a page.
	 */
	public $itemsPerPage = 25;

	/**
	 * @var string The default country.
	 */
	public $defaultCountry;

	/**
	 * @var string The default language.
	 */
	public $defaultLanguage;

	/**
	 * @var string The time zone.
	 */
	public $timeZone;

	/**
	 * @var string The time format.
	 */
	public $timeFormat;

	/**
	 * @var string The date format.
	 */
	public $dateFormat;

	/**
	 * @var string The date and time format.
	 */
	public $datetimeFormat;

	/**
	 * @var string The currency code.
	 */
	public $currencyCode;

	/**
	 * @var int The type of user account activation.
	 */
	public $userAccountActivation;

	/**
	 * @var int The time after the password reset token expires.
	 */
	public $userPasswordResetTokenExpiration;

	/**
	 * @var int The time for remember me functionality.
	 */
	public $userLoginDuration;

	/**
	 * @var string The maximum file size.
	 */
	public $maxFileSize;

	/**
	 * @var bool Flag that indicates if the app should record event logs.
	 */
	public $enableEventLogs;

	/**
	 * @var bool Flag that indicates if the app should use soft delete.
	 */
	public $enableSoftDelete;

	/**
	 * @var bool Flag that indicates if the app should use enable scraper.
	 */
	public $enableScraper;

	/**
	 * @var string The Google Map API key.
	 */
	public $googleMapKey;

	/**
	 * @var string The Chat Widget Allowed Url.
	 */
	public $chatUrl;

	/**
	 * @var string The Chat Widget Color.
	 */
	public $chatColor;

	/**
	 * @var bool Flag that indicates if The Chat Widget is Visible.
	 */
	public $chatVisible;

	/**
	 * @var bool Flag that indicates if The Chat Widget is Expanded.
	 */
	public $chatExpanded;

	/**
	 * @var bool Flag that indicates ID or class of element to be removed from parent page.
	 */
	public $chatRemove;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$masterSettings = Yii::$app->masterSettings->getCategory('general');

		$this->hostInfo = Yii::$app->request->hostInfo;
		$this->appName = $masterSettings['appName'];
		$this->theme = 'default.css';
		$this->timeZone = Yii::$app->formatter->defaultTimeZone;
		$this->timeFormat = Yii::$app->formatter->timeFormat;
		$this->dateFormat = Yii::$app->formatter->dateFormat;
		$this->datetimeFormat = Yii::$app->formatter->datetimeFormat;
		$this->currencyCode = Yii::$app->formatter->currencyCode;
		$this->userAccountActivation = User::ACCOUNT_ACTIVATION_AUTOMATIC;
		$this->userPasswordResetTokenExpiration = 1 * 60;
		$this->userLoginDuration = 3600 * 24 * 30;
		$this->maxFileSize = 100 * 1024 * 1024;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['appName'], 'required'],
			[['appName', 'theme'], 'string'],
			[['appName'], 'trim'],
			[['appDescription'], 'each', 'rule' => ['trim']],
			[['appLogo'], 'file', 'extensions' => ['jpeg', 'jpg', 'png', 'gif'], 'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'maxFiles' => 1, 'skipOnEmpty' => true],
			[['enableEventLogs', 'enableSoftDelete', 'enableScraper', 'chatVisible', 'chatExpanded'], 'boolean'],
			[['timeZone', 'timeFormat', 'dateFormat', 'datetimeFormat'], 'required'],
			[['timeZone', 'timeFormat', 'dateFormat', 'datetimeFormat'], 'string'],
			[['timeZone', 'timeFormat', 'dateFormat', 'datetimeFormat'], 'trim'],
			['itemsPerPage', 'integer', 'min' => 5, 'max' => 300],
			['maxFileSize', 'integer', 'min' => 5],
			[['currencyCode', 'chatUrl', 'chatColor', 'chatRemove'], 'string'],
			[['currencyCode', 'chatUrl', 'chatColor', 'chatRemove'], 'trim'],
			[['defaultCountry'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['defaultCountry' => 'iso_alpha2']],
			[['defaultLanguage'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['defaultLanguage' => 'language_id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'appName' => Yii::t('label', 'App Name'),
			'theme' => Yii::t('label', 'Theme'),
			'appLogo' => Yii::t('label', 'App Logo'),
			'itemsPerPage' => Yii::t('label', 'Items Per Page'),
			'maxFileSize' => Yii::t('label', 'Max File Size'),
			'defaultCountry' => Yii::t('label', 'Default Country'),
			'defaultLanguage' => Yii::t('label', 'Default Language'),
			'timeZone' => Yii::t('label', 'Time Zone'),
			'timeFormat' => Yii::t('label', 'Time Format'),
			'dateFormat' => Yii::t('label', 'Date Format'),
			'datetimeFormat' => Yii::t('label', 'Datetime Format'),
			'currencyCode' => Yii::t('label', 'Currency Code'),
			'userAccountActivation' => Yii::t('label', 'User Account Activation'),
			'enableEventLogs' => Yii::t('label', 'Enable Event Logs'),
			'enableSoftDelete' => Yii::t('label', 'Enable Soft Delete'),
			'enableScraper' => Yii::t('label', 'Enable Scraper'),
			'googleMapKey' => Yii::t('label', 'Google Map Key'),
			'chatUrl' => Yii::t('label', 'Allowed Domain Url'),
			'chatColor' => Yii::t('label', 'Color'),
			'chatVisible' => Yii::t('label', 'Visible'),
			'chatExpanded' => Yii::t('label', 'Expanded'),
			'chatRemove' => Yii::t('label', 'Remove Element By ID Or Class'),
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

		$this->setAttributes($this->getUnserializedValue('setting'));
		$this->userPasswordResetTokenExpiration = $this->userPasswordResetTokenExpiration / 60;
		$this->userLoginDuration = $this->userLoginDuration / 24 / 3600;
		$this->maxFileSize = $this->maxFileSize / 1024;
	}

	/**
	 * Gets the appLogoUrl.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getAppLogoUrl($scheme = false)
	{
		return Url::to("@uploads/{$this->appLogo}", $scheme);
	}

	/**
	 * Gets the appLogoAltUrl.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getAppLogoAltUrl($scheme = false)
	{
		return Url::to("@uploads/{$this->appLogoAlt}", $scheme);
	}

	/**
	 * Saves the files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
			$appLogo = UploadedFile::getInstance($this, 'appLogo');
			if (!$appLogo) {
				return true;
			}

			if ($appLogo) {
				$fileName = "logo.{$appLogo->extension}";
				$filePath = Yii::getAlias("@uploads/{$fileName}");
				if (!$appLogo->saveAs($filePath)) {
					throw new \Exception();
				}
				$this->setSerializedValue('setting', [
					'appLogo' => $fileName,
				]);
			}
			if (!$this->updateAttributes(['setting'])) {
				throw new \Exception();
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

    /**
     * Saves the ScheduledTask model for backup.
     *
     * @return bool
     */
    protected function saveBackupScheduledTask(): bool
    {
        $scheduledTask = ScheduledTask::findOne([
            'resource' => __CLASS__ . '::' . __FUNCTION__,
            'resource_key' => $this->id,
            'deleted' => ScheduledTask::NO,
        ]);
        if (!$scheduledTask) {
            $scheduledTask = new ScheduledTask();
        }
        $scheduledTask->cron_expression = '0 0 * * * *';
        $scheduledTask->app_command = 'backup/run';
        $scheduledTask->resource = __CLASS__ . '::' . __FUNCTION__;
        $scheduledTask->resource_key = $this->id;
        $scheduledTask->application = str_replace('app-', '', Yii::$app->id);
        $scheduledTask->type = ScheduledTask::TYPE_APP;
        $scheduledTask->status = ScheduledTask::STATUS_ACTIVE;

        return $scheduledTask->save();
    }

    /**
     * Saves the ScheduledTask model for backup.
     *
     * @return bool
     */
    protected function saveBackupRemoveScheduledTask(): bool
    {
        $scheduledTask = ScheduledTask::findOne([
            'resource' => __CLASS__ . '::' . __FUNCTION__,
            'resource_key' => $this->id,
            'deleted' => ScheduledTask::NO,
        ]);
        if (!$scheduledTask) {
            $scheduledTask = new ScheduledTask();
        }
        $scheduledTask->cron_expression = '0 0 * * * *';
        $scheduledTask->app_command = 'backup/remove';
        $scheduledTask->resource = __CLASS__ . '::' . __FUNCTION__;
        $scheduledTask->resource_key = $this->id;
        $scheduledTask->application = str_replace('app-', '', Yii::$app->id);
        $scheduledTask->type = ScheduledTask::TYPE_APP;
        $scheduledTask->status = ScheduledTask::STATUS_ACTIVE;

        return $scheduledTask->save();
    }

	/**
	 * Saves the ScheduledTask model for scraper.
	 *
	 * @return bool
	 */
	protected function saveScraperScheduledTask($enabled = ScheduledTask::STATUS_INACTIVE): bool
	{
		$scheduledTask = ScheduledTask::findOne([
			'resource' => __CLASS__ . '::' . __FUNCTION__,
			'resource_key' => $this->id,
			'deleted' => ScheduledTask::NO,
		]);
		if (!$scheduledTask) {
			$scheduledTask = new ScheduledTask();
		}
		$scheduledTask->cron_expression = '* * * * * *';
		$scheduledTask->app_command = 'scraper/run';
		$scheduledTask->resource = __CLASS__ . '::' . __FUNCTION__;
		$scheduledTask->resource_key = $this->id;
		$scheduledTask->application = str_replace('app-', '', Yii::$app->id);
		$scheduledTask->type = ScheduledTask::TYPE_APP;
		$scheduledTask->status = $enabled ? ScheduledTask::STATUS_ACTIVE : ScheduledTask::STATUS_INACTIVE;

		return $scheduledTask->save();
	}

	/**
	 * Saves the setting.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			$this->name = 'general';
			$this->type = static::TYPE_APP;
			$this->status = static::STATUS_ACTIVE;
			$this->setSerializedValue('setting', [
				'hostInfo' => $this->hostInfo,
				'appName' => $this->appName,
				'theme' => $this->theme,
				'itemsPerPage' => $this->itemsPerPage,
				'defaultCountry' => $this->defaultCountry,
				'defaultLanguage' => $this->defaultLanguage,
				'timeZone' => $this->timeZone,
				'timeFormat' => $this->timeFormat,
				'dateFormat' => $this->dateFormat,
				'datetimeFormat' => $this->datetimeFormat,
				'currencyCode' => $this->currencyCode,
				'userAccountActivation' => $this->userAccountActivation,
				'userPasswordResetTokenExpiration' => $this->userPasswordResetTokenExpiration * 60,
				'userLoginDuration' => $this->userLoginDuration * 24 * 3600,
				'maxFileSize' => $this->maxFileSize * 1024,
				'enableEventLogs' => $this->enableEventLogs,
				'enableSoftDelete' => $this->enableSoftDelete,
				'enableScraper' => $this->enableScraper,
				'googleMapKey' => $this->googleMapKey,
				'chatUrl' => $this->chatUrl,
				'chatColor' => $this->chatColor,
				'chatVisible' => $this->chatVisible,
				'chatExpanded' => $this->chatExpanded,
				'chatRemove' => $this->chatRemove,
			]);
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
            if (!$this->saveBackupScheduledTask()) {
                throw new \Exception();
            }
            if (!$this->saveBackupRemoveScheduledTask()) {
                throw new \Exception();
            }
			if (!$this->saveScraperScheduledTask($this->enableScraper)) {
				throw new \Exception();
			}
			$transaction->commit();
			return $this;
		} catch (\Exception $e) {
			$transaction->rollBack();
			return null;
		}
	}
}
