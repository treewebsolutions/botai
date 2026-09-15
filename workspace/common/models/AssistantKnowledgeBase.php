<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%assistant_knowledge_base}}".
 *
 * @property int $assistant_id
 * @property int $knowledge_base_id
 * @property int $sort_order
 * @property string $created_at
 *
 * @property Assistant $assistant
 * @property KnowledgeBase $knowledgeBase
 */
class AssistantKnowledgeBase extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%assistant_knowledge_base}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
				'attributes' => [
					self::EVENT_BEFORE_INSERT => ['created_at'],
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
			[['assistant_id', 'knowledge_base_id'], 'required'],
			[['assistant_id', 'knowledge_base_id', 'sort_order'], 'integer'],
			[['created_at'], 'safe'],
			[['assistant_id', 'knowledge_base_id'], 'unique', 'targetAttribute' => ['assistant_id', 'knowledge_base_id']],
			[['assistant_id'], 'exist', 'skipOnError' => true, 'targetClass' => Assistant::class, 'targetAttribute' => ['assistant_id' => 'id']],
			[['knowledge_base_id'], 'exist', 'skipOnError' => true, 'targetClass' => KnowledgeBase::class, 'targetAttribute' => ['knowledge_base_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'assistant_id' => Yii::t('label', 'Assistant ID'),
			'knowledge_base_id' => Yii::t('label', 'Knowledge Base ID'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'created_at' => Yii::t('label', 'Created At'),
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
	public function getKnowledgeBase()
	{
		return $this->hasOne(KnowledgeBase::class, ['id' => 'knowledge_base_id']);
	}
}
