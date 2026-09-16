<?php

namespace backend\modules\helpdesk\models;

use common\helpers\HtmlSanitizer;
use common\models\SupportTicketComment;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class SupportTicketCommentForm extends SupportTicketComment
{
	/**
	 * @var UploadedFile The attachment file.
	 */
	public $attachmentFile;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['content'], 'trim'],
			[['attachmentFile'], 'file', 'extensions' => ['jpeg', 'jpg', 'png', 'gif'], 'mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'skipOnEmpty' => true],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'attachmentFile' => Yii::t('label', 'Attachment'),
			'content' => Yii::t('label', 'Message'),
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
			if (!($file = UploadedFile::getInstance($this, 'attachmentFile'))) {
				return true;
			}

			$dirPath = Yii::getAlias("@uploads/support-ticket/{$this->support_ticket_id}");
			$oldFilePath = "{$dirPath}/{$this->oldAttributes['attachment']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
				Inflector::slug($file->baseName),
				Yii::$app->security->generateRandomString(8),
			])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
			if (!$this->updateAttributes(['attachment' => $fileName])) {
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
			if (!$this->save()) {
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

	/**
	 * {@inheritdoc}
	 *
	 * The ticket editor is open to every registered customer and what it stores is
	 * printed as HTML in the helpdesk screens an administrator reads, which makes a
	 * ticket the shortest path from an ordinary account into an admin session. Reduced
	 * on the way in; the views reduce again on the way out, for what is already stored.
	 */
	public function beforeValidate()
	{
		$this->content = HtmlSanitizer::richText($this->content);

		return parent::beforeValidate();
	}

}
