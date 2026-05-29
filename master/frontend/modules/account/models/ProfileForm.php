<?php

namespace frontend\modules\account\models;

use common\models\Country;
use common\models\MarketingRecipient;
use common\models\Workspace;
use common\models\Subscriber;
use common\models\User;
use common\models\WorkspaceHasUser;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class ProfileForm extends User
{
	/**
	 * @var UploadedFile The imageFile.
	 */
	public $imageFile;

	/**
	 * @var string The personal identification number.
	 */
	public $pin;

	/**
	 * @var string The street name.
	 */
	public $street_name;

	/**
	 * @var string The street number.
	 */
	public $street_number;

	/**
	 * @var string The locality.
	 */
	public $locality;

	/**
	 * @var string The zip code.
	 */
	public $zip_code;

	/**
	 * @var string The county.
	 */
	public $county;

	/**
	 * @var string The country.
	 */
	public $country;

	/**
	 * @var string The date of birth.
	 */
	public $date_of_birth;

	/**
	 * @var string The new password.
	 */
	public $new_password;

	/**
	 * @var string The new password confirm.
	 */
	public $new_password_confirm;

	/**
	 * @var bool Indicates if the user is a marketing recipient.
	 */
	public $marketing_recipient;


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'phone', 'gender'], 'required'],
//			[['phone'], 'unique'],
			[['gender'], 'integer'],
			['gender', 'in', 'range' => [1, 2]],
			[['date_of_birth'], 'safe'],
			['marketing_recipient', 'boolean'],
			[['new_password', 'new_password_confirm', 'first_name', 'middle_name', 'last_name', 'phone', 'pin', 'street_name', 'street_number', 'locality', 'zip_code', 'county'], 'string', 'max' => 255],
			[['new_password', 'new_password_confirm', 'first_name', 'middle_name', 'last_name', 'phone', 'pin', 'street_name', 'street_number', 'locality', 'zip_code', 'county'], 'trim'],
			['new_password', 'string', 'min' => 6],
			['new_password_confirm', 'required', 'when' => function ($model) {
				return !empty($model->new_password);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[new_password]\"]").val() != "";
			}'],
			['new_password_confirm', 'compare', 'compareAttribute' => 'new_password', 'message' => Yii::t('common', 'Passwords don\'t match.')],
			[['imageFile'], 'file', 'extensions' => ['jpeg', 'jpg', 'png', 'gif'], 'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['country'], 'exist', 'skipOnError' => true, 'targetClass' => Country::class, 'targetAttribute' => ['country' => 'iso_alpha2']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'imageFile' => Yii::t('label', 'Image'),
			'pin' => Yii::t('label', 'Personal Identification Number'),
			'street_name' => Yii::t('label', 'Street Name'),
			'street_number' => Yii::t('label', 'Street Number'),
			'locality' => Yii::t('label', 'Locality'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'county' => Yii::t('label', 'County'),
			'country' => Yii::t('label', 'Country'),
			'date_of_birth' => Yii::t('label', 'Date Of Birth'),
			'new_password' => Yii::t('label', 'New Password'),
			'new_password_confirm' => Yii::t('label', 'Confirm New Password'),
			'marketing_recipient' => Yii::t('label', 'Receive newsletter emails'),
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

		$this->pin = $this->subscriber->pin;
		$this->street_name = $this->subscriber->street_name;
		$this->street_number = $this->subscriber->street_number;
		$this->locality = $this->subscriber->locality;
		$this->zip_code = $this->subscriber->zip_code;
		$this->county = $this->subscriber->county;
		$this->country = $this->subscriber->country;
		$this->date_of_birth = $this->subscriber->date_of_birth;

		$this->marketing_recipient = MarketingRecipient::find()
			->andWhere([
				'email' => $this->email,
				'status' => MarketingRecipient::STATUS_ACTIVE,
				'deleted' => MarketingRecipient::NO,
			])
			->exists();
	}

	/**
	 * Saves the Subscriber model.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function saveSubscriber()
	{
		try {
			if (!$subscriber = $this->subscriber) {
				$subscriber = new Subscriber();
				$subscriber->user_id = $this->id;
				$subscriber->code = Subscriber::generateUniqueCode();
				$subscriber->status = Subscriber::STATUS_ACTIVE;
			}
			$subscriber->pin = $this->pin;
			$subscriber->street_name = $this->street_name;
			$subscriber->street_number = $this->street_number;
			$subscriber->locality = $this->locality;
			$subscriber->county = $this->county;
			$subscriber->country = $this->country;
			$subscriber->zip_code = $this->zip_code;
			$subscriber->date_of_birth = $this->date_of_birth;

			if (!$subscriber->save()) {
				throw new \Exception($subscriber->getErrorSummary(false)[0]);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the MarketingRecipient model.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function saveMarketingRecipient()
	{
		try {
			/** @var MarketingRecipient $marketingRecipient */
			$marketingRecipient = MarketingRecipient::find()
				->andWhere([
					'email' => $this->email,
					'status' => MarketingRecipient::STATUS_ACTIVE,
					'deleted' => MarketingRecipient::NO,
				])
				->one();
			if (!$marketingRecipient) {
				$marketingRecipient = new MarketingRecipient();
			}
			$marketingRecipient->status = $this->marketing_recipient ? MarketingRecipient::STATUS_ACTIVE : MarketingRecipient::STATUS_INACTIVE;
			if (!$marketingRecipient->save()) {
				throw new \Exception($marketingRecipient->getErrorSummary(false)[0]);
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
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!empty($this->new_password)) {
				$this->setPassword($this->new_password);
				$this->generateAuthKey();
				$workspaceUsers = WorkspaceHasUser::find()
					->andWhere(['user_id' => $this->id])
					->all();
				foreach ($workspaceUsers as $model) {
					$model->auth_key = $this->auth_key;
					$model->password_hash = $this->password_hash;
					if (!$model->save(false)) {
						$this->addError('username', Yii::t('common', 'Cannot reset password for this user.'));
						throw new \Exception();
					} else {
						$workspace = Workspace::findOne(['id' => $model->workspace_id]);
						$workspaceDb = $workspace->getWorkspaceDb();
						$workspaceDb->createCommand()->update('{{%user}}', [
							'auth_key' => $this->auth_key,
							'password_hash' => $this->password_hash,
						],
						[
							'id' => $this->id
						])->execute();
					}
				}
			}
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveSubscriber()) {
				throw new \Exception();
			}
			if (!$this->saveMarketingRecipient()) {
				throw new \Exception();
			}
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
