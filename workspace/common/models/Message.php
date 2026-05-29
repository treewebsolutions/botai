<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%message}}".
 *
 * @property int $id
 * @property int $thread_id
 * @property int $assistant_id
 * @property string $openai_id
 * @property string $role
 * @property string $content
 * @property string $completed_at
 * @property string $incomplete_at
 * @property string $incomplete_reason
 * @property int $created_by
 * @property string $created_at
 * @property string $status
 * @property int $deleted
 *
 * @property Assistant $assistant
 * @property Thread $thread
 * @property Participant $creator
 */
class Message extends CommonActiveRecord
{
	const STATUS_IN_PROGRESS = 'in_progress';
	const STATUS_COMPLETED = 'incomplete';
	const STATUS_INCOMPLETE = 'completed';

	const ROLE_USER = 'user';
	const ROLE_ASSISTANT = 'assistant';

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%message}}';
    }

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'createdAtAttribute' => 'created_at',
				'updatedAtAttribute' => false,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['completed_at', 'incomplete_at'],
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
            [['thread_id'], 'required'],
            [['thread_id', 'assistant_id', 'created_by', 'deleted'], 'integer'],
            [['content'], 'string'],
            [['completed_at', 'incomplete_at', 'created_at'], 'safe'],
            [['openai_id', 'role', 'incomplete_reason', 'status'], 'string', 'max' => 255],
            [['assistant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Assistant::class, 'targetAttribute' => ['assistant_id' => 'id']],
            [['thread_id'], 'exist', 'skipOnError' => true, 'targetClass' => Thread::class, 'targetAttribute' => ['thread_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'thread_id' => Yii::t('label', 'Thread ID'),
            'assistant_id' => Yii::t('label', 'Assistant ID'),
            'openai_id' => Yii::t('label', 'OpenAI ID'),
            'role' => Yii::t('label', 'Role'),
            'content' => Yii::t('label', 'Content'),
            'completed_at' => Yii::t('label', 'Completed At'),
            'incomplete_at' => Yii::t('label', 'Incomplete At'),
            'incomplete_reason' => Yii::t('label', 'Incomplete Reason'),
            'created_by' => Yii::t('label', 'Created By'),
            'created_at' => Yii::t('label', 'Created At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssistant()
    {
        return $this->hasOne(Assistant::class, ['id' => 'assistant_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getThread()
    {
        return $this->hasOne(Thread::class, ['id' => 'thread_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(Participant::class, ['id' => 'created_by']);
	}

	/**
	 * Model status labels.
	 *
	 * @return array
	 */
	public static function getStatusLabels()
	{
		return [
			self::STATUS_IN_PROGRESS => [
				'label' => Yii::t('label', 'In Progress'),
				'color' => 'warning',
			],
			self::STATUS_COMPLETED => [
				'label' => Yii::t('label', 'Completed'),
				'color' => 'success',
			],
			self::STATUS_INCOMPLETE => [
				'label' => Yii::t('label', 'Incomplete'),
				'color' => 'success',
			],
		];
	}

	/**
	 * Model roles.
	 *
	 * @return array
	 */
	public static function getRoleLabels()
	{
		return [
			self::ROLE_USER => Yii::t('label', 'User'),
			self::ROLE_ASSISTANT => Yii::t('label', 'Assistant'),
		];
	}
}
