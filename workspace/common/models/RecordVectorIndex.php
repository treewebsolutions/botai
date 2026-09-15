<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * OpenAI vector store file mapping for indexed pages (semantic index).
 *
 * One row per {@see Page}: which OpenAI file (`openai_file_id`) carries the page text in which
 * vector store (`vector_store_id`). Rows are written only by
 * {@see \common\services\OpenAiRecordVectorStoreService}.
 *
 * @property int $id
 * @property int $record_id Page PK
 * @property string $openai_file_id `file-…`, or `__withdrawn__` after removal
 * @property string|null $vector_store_file_id
 * @property string $vector_store_id `vs_…`, or `__unknown__`
 * @property string|null $indexed_at
 * @property string|null $error_message
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Page $page
 */
class RecordVectorIndex extends CommonActiveRecord
{
	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_ERROR = 2;

	const FILE_ID_WITHDRAWN = '__withdrawn__';
	const VECTOR_STORE_ID_UNKNOWN = '__unknown__';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%record_vector_index}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime())->format('Y-m-d H:i:s'),
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['record_id', 'openai_file_id', 'vector_store_id'], 'required'],
			[['record_id', 'status', 'deleted'], 'integer'],
			[['status'], 'default', 'value' => static::STATUS_ACTIVE],
			[['status'], 'in', 'range' => [static::STATUS_INACTIVE, static::STATUS_ACTIVE, static::STATUS_ERROR]],
			[['deleted'], 'default', 'value' => static::NO],
			[['openai_file_id', 'vector_store_file_id', 'vector_store_id'], 'string', 'max' => 128],
			[['indexed_at', 'created_at', 'updated_at'], 'safe'],
			[['error_message'], 'string'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'record_id' => Yii::t('label', 'Page ID'),
			'openai_file_id' => Yii::t('label', 'OpenAI File ID'),
			'vector_store_file_id' => Yii::t('label', 'Vector Store File ID'),
			'vector_store_id' => Yii::t('label', 'Vector Store ID'),
			'indexed_at' => Yii::t('label', 'Indexed At'),
			'error_message' => Yii::t('label', 'Error'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPage()
	{
		return $this->hasOne(Page::class, ['id' => 'record_id']);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getStatusLabels()
	{
		return [
			static::STATUS_INACTIVE => [
				'label' => Yii::t('label', 'Not indexed'),
				'color' => 'default',
			],
			static::STATUS_ACTIVE => [
				'label' => Yii::t('label', 'Indexed'),
				'color' => 'success',
			],
			static::STATUS_ERROR => [
				'label' => Yii::t('label', 'Error'),
				'color' => 'danger',
			],
		];
	}
}
