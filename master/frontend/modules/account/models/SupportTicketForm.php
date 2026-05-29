<?php

namespace frontend\modules\account\models;

use common\models\SupportTicket;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class SupportTicketForm extends SupportTicket
{
	/**
	 * @var UploadedFile[] The attachment files.
	 */
	public $attachmentFiles;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['support_ticket_status_id', 'support_ticket_priority_id', 'support_ticket_department_id', 'subject', 'content'], 'required'],
			[['subject', 'content'], 'trim'],
			[['attachmentFiles'], 'file', 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'maxFiles' => 5, 'skipOnEmpty' => true],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'support_ticket_status_id' => Yii::t('label', 'Status'),
			'support_ticket_priority_id' => Yii::t('label', 'Priority'),
			'support_ticket_department_id' => Yii::t('label', 'Department'),
			'subscription_id' => Yii::t('label', 'Subscription'),
			'content' => Yii::t('label', 'Message'),
			'attachmentFiles' => Yii::t('label', 'Attachments'),
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
	 * Saves the files.
	 *
	 * @return bool
	 */
	protected function saveFiles()
	{
		try {
			if (!($files = UploadedFile::getInstances($this, 'attachmentFiles'))) {
				return true;
			}

			$serializedFiles = [];
			$dirPath = Yii::getAlias("@uploads/support-ticket/{$this->id}");
			FileHelper::createDirectory($dirPath);

			foreach ($files as $file) {
				$fileName = StringHelper::truncate(implode('_', array_filter([
					Inflector::slug($file->baseName),
					Yii::$app->security->generateRandomString(8),
				])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";

				if (!$file->saveAs("{$dirPath}/{$fileName}")) {
					throw new \Exception();
				}

				$serializedFiles[] = $fileName;
			}

			$this->setSerializedValue('attachment', $serializedFiles);
			if (!$this->updateAttributes(['attachment'])) {
				throw new \Exception();
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Sends a notification.
	 *
	 * @param null|\common\models\SupportTicketComment $supportTicketCommentModel
	 * @return bool
	 */
	public function sendNotification($supportTicketCommentModel = null)
	{
		try {
			return Yii::$app->mailer->compose([
					'html' => '@account/views/support-ticket/email-notification-html',
					'text' => '@account/views/support-ticket/email-notification-text',
				], [
					'model' => $this,
					'supportTicketCommentModel' => $supportTicketCommentModel,
				])
				->setTo(Yii::$app->settings->get('email', 'contact'))
				->setSubject("[" . Yii::$app->name . " #{$this->getDocumentSeriesNumber()}]: " . Yii::t('common', 'Support ticket from {0}', [$this->creator->fullName]))
				->send();
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
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
