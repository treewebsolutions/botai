<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%knowledge_base_document}}".
 *
 * A file uploaded by the operator (PDF, Word, text...) that extends a {@see KnowledgeBase}
 * beyond the scraped pages. The file lives under `@uploads/knowledge-base-document/<id>/`
 * and is pushed to the knowledge base's OpenAI vector store by
 * {@see \common\services\OpenAiDocumentVectorStoreService}; the `openai_file_id` /
 * `vector_store_file_id` / `vector_store_id` columns and `index_status` mirror that state,
 * the same way {@see RecordVectorIndex} does for pages.
 *
 * @property int $id
 * @property int $knowledge_base_id
 * @property string $name
 * @property string $file
 * @property string|null $extension
 * @property int $size
 * @property string|null $openai_file_id
 * @property string|null $vector_store_file_id
 * @property string|null $vector_store_id
 * @property int $index_status
 * @property string|null $indexed_at
 * @property string|null $error_message
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property KnowledgeBase $knowledgeBase
 * @property User $creator
 * @property User $updater
 */
class KnowledgeBaseDocument extends CommonActiveRecord
{
	const INDEX_STATUS_NONE = 0;
	const INDEX_STATUS_INDEXED = 1;
	const INDEX_STATUS_ERROR = 2;

	/**
	 * File extensions the OpenAI `file_search` tool accepts (text-bearing documents).
	 */
	const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'pptx', 'txt', 'md', 'html', 'json'];

	/**
	 * Upload size limit in bytes (OpenAI accepts up to 512 MB; keep uploads reasonable).
	 */
	const MAX_FILE_SIZE = 50 * 1024 * 1024;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%knowledge_base_document}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['indexed_at'],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['knowledge_base_id', 'name', 'file', 'status'], 'required'],
			[['knowledge_base_id', 'size', 'index_status', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['index_status'], 'default', 'value' => static::INDEX_STATUS_NONE],
			[['index_status'], 'in', 'range' => [static::INDEX_STATUS_NONE, static::INDEX_STATUS_INDEXED, static::INDEX_STATUS_ERROR]],
			[['indexed_at', 'created_at', 'updated_at'], 'safe'],
			[['error_message'], 'string'],
			[['name', 'file'], 'string', 'max' => 255],
			[['extension'], 'string', 'max' => 16],
			[['openai_file_id', 'vector_store_file_id', 'vector_store_id'], 'string', 'max' => 128],
			[['knowledge_base_id'], 'exist', 'skipOnError' => true, 'targetClass' => KnowledgeBase::class, 'targetAttribute' => ['knowledge_base_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'knowledge_base_id' => Yii::t('label', 'Knowledge Base'),
			'name' => Yii::t('label', 'Name'),
			'file' => Yii::t('label', 'File'),
			'extension' => Yii::t('label', 'Extension'),
			'size' => Yii::t('label', 'Size'),
			'openai_file_id' => Yii::t('label', 'OpenAI File ID'),
			'vector_store_file_id' => Yii::t('label', 'Vector Store File ID'),
			'vector_store_id' => Yii::t('label', 'Vector Store ID'),
			'index_status' => Yii::t('label', 'Index Status'),
			'indexed_at' => Yii::t('label', 'Indexed At'),
			'error_message' => Yii::t('label', 'Error'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getKnowledgeBase()
	{
		return $this->hasOne(KnowledgeBase::class, ['id' => 'knowledge_base_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * The directory holding this document's file.
	 *
	 * @return string
	 */
	public function getDirectoryPath()
	{
		return Yii::getAlias("@uploads/knowledge-base-document/{$this->id}");
	}

	/**
	 * The absolute path of the stored file (null while the row has no file yet).
	 *
	 * @return string|null
	 */
	public function getFilePath()
	{
		return $this->file ? $this->getDirectoryPath() . '/' . $this->file : null;
	}

	/**
	 * Whether the document belongs in the vector store: active, not deleted and with a file on disk.
	 *
	 * @return bool
	 */
	public function getIsIndexable()
	{
		return (int) $this->status === static::STATUS_ACTIVE
			&& (int) $this->deleted === static::NO
			&& $this->getFilePath() !== null
			&& is_file($this->getFilePath());
	}

	/**
	 * Index status labels (label + badge colour), like {@see RecordVectorIndex::getStatusLabels()}.
	 *
	 * @return array
	 */
	public static function getIndexStatusLabels()
	{
		return [
			static::INDEX_STATUS_NONE => [
				'label' => Yii::t('label', 'Not indexed'),
				'color' => 'default',
			],
			static::INDEX_STATUS_INDEXED => [
				'label' => Yii::t('label', 'Indexed'),
				'color' => 'success',
			],
			static::INDEX_STATUS_ERROR => [
				'label' => Yii::t('label', 'Error'),
				'color' => 'danger',
			],
		];
	}
}
