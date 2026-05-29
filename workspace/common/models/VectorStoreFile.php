<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%vector_store_file}}".
 *
 * @property int $id
 * @property int $vector_store_id
 * @property string $openai_id
 * @property string $openai_status
 * @property string $openai_message
 * @property string $file
 * @property string $type
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property VectorStore $vectorStore
 * @property User $creator
 * @property User $updater
 */
class VectorStoreFile extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%vector_store_file}}';
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
            [['vector_store_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
            [['status'], 'required'],
            [['openai_id', 'openai_status', 'openai_message', 'file', 'type'], 'string', 'max' => 255],
            [['vector_store_id'], 'exist', 'skipOnError' => true, 'targetClass' => VectorStore::class, 'targetAttribute' => ['vector_store_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'vector_store_id' => Yii::t('label', 'Vector Store ID'),
            'openai_id' => Yii::t('label', 'OpenAI ID'),
            'openai_status' => Yii::t('label', 'OpenAI Status'),
            'openai_message' => Yii::t('label', 'OpenAI Message'),
            'file' => Yii::t('label', 'File'),
            'type' => Yii::t('label', 'Type'),
            'created_by' => Yii::t('label', 'Created By'),
            'updated_by' => Yii::t('label', 'Updated By'),
            'created_at' => Yii::t('label', 'Created At'),
            'updated_at' => Yii::t('label', 'Updated At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVectorStore()
    {
        return $this->hasOne(VectorStore::class, ['id' => 'vector_store_id']);
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
}
