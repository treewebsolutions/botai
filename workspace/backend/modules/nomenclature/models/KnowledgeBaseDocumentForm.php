<?php

namespace backend\modules\nomenclature\models;

use common\models\KnowledgeBaseDocument;
use common\services\OpenAiDocumentVectorStoreService;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\web\UploadedFile;

/**
 * Backend form of a knowledge base document: stores the uploaded file under
 * `@uploads/knowledge-base-document/<id>/` and queues the (re)indexing into the knowledge
 * base's vector store after the response.
 */
class KnowledgeBaseDocumentForm extends KnowledgeBaseDocument
{
	/**
	 * @var UploadedFile|null The uploaded document (required on create, optional on update).
	 */
	public $upload;

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
			[['file'], 'safe'],
			[['upload'], 'file',
				'extensions' => static::ALLOWED_EXTENSIONS,
				'checkExtensionByMimeType' => false,
				'maxSize' => static::MAX_FILE_SIZE,
				'skipOnEmpty' => true,
			],
			[['upload'], 'required', 'when' => function ($model) {
				return $model->isNewRecord;
			}, 'whenClient' => 'function (attribute, value) { return ' . ($this->isNewRecord ? 'true' : 'false') . '; }'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'upload' => Yii::t('label', 'File'),
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
	public function beforeValidate()
	{
		if ($this->upload === null) {
			$this->upload = UploadedFile::getInstance($this, 'upload');
		}
		if ($this->upload !== null) {
			// The stored file name is derived from the upload; the row needs a value to pass `required`.
			$this->file = $this->upload->name;
			$this->extension = strtolower((string) $this->upload->extension);
			$this->size = (int) $this->upload->size;
			if (trim((string) $this->name) === '') {
				$this->name = $this->upload->baseName;
			}
		}
		return parent::beforeValidate();
	}

	/**
	 * Moves the uploaded file into the document directory (replacing the previous one).
	 *
	 * @return bool
	 */
	protected function saveFile()
	{
		if ($this->upload === null) {
			return true;
		}

		$dirPath = $this->getDirectoryPath();
		$previousFile = $this->getOldAttribute('file');
		$fileName = mb_substr(Inflector::slug($this->upload->baseName) ?: 'document', 0, 255 - (mb_strlen($this->upload->extension) + 1)) . '.' . strtolower($this->upload->extension);
		$filePath = "{$dirPath}/{$fileName}";

		FileHelper::createDirectory($dirPath);
		if (!$this->upload->saveAs($filePath)) {
			$this->addError('upload', Yii::t('common', 'The file could not be uploaded.'));
			return false;
		}
		if ($previousFile && $previousFile !== $fileName && is_file("{$dirPath}/{$previousFile}")) {
			FileHelper::unlink("{$dirPath}/{$previousFile}");
		}

		return $this->updateAttributes([
			'file' => $fileName,
			'extension' => strtolower($this->upload->extension),
			'size' => (int) $this->upload->size,
		]) !== false;
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$fileChanged = $this->upload !== null;
			$stateChanged = $this->isNewRecord || $this->isAttributeChanged('status') || $this->isAttributeChanged('knowledge_base_id');

			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveFile()) {
				throw new \Exception();
			}
			$dbTransaction->commit();

			// (Re)index after the response: a new/changed file, a status flip or a move to another
			// knowledge base all change what must live in the vector store. syncDocument() withdraws
			// by itself when the document is no longer indexable.
			if ($fileChanged || $stateChanged) {
				OpenAiDocumentVectorStoreService::scheduleSync($this->id);
			}
			return $this;
		} catch (\Exception $e) {
			if ($e->getMessage() !== '') {
				$this->addError('', $e->getMessage());
			}
			$dbTransaction->rollBack();
			return false;
		}
	}
}
