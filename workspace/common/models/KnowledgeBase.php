<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%knowledge_base}}".
 *
 * A Knowledge Base hosts one OpenAI vector store (`vector_store_id`); the indexed pages are
 * mapped to the vector store files through {@see RecordVectorIndex}.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $provider
 * @property string $embedding_model
 * @property string|null $vector_store_id
 * @property int $chunk_size
 * @property int $chunk_overlap
 * @property int $tokens_per_file
 * @property string $expire_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property AssistantKnowledgeBase[] $assistantKnowledgeBases
 * @property Assistant[] $assistants
 * @property User $creator
 * @property User $updater
 */
class KnowledgeBase extends CommonActiveRecord
{
	const PROVIDER_GENERIC = null;
	/** Provider values mirror {@see Integration::TYPE_OPENAI} so the same labels apply. */
	const PROVIDER_OPENAI = 1;
	const PROVIDER_CLAUDE = 2;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%knowledge_base}}';
	}

	/**
	 * Maps an {@see Assistant} provider value to the corresponding KnowledgeBase provider value.
	 * Both enums use the same numbers in this project; kept as a single mapping point.
	 *
	 * @param int|null $assistantProvider
	 * @return int|null
	 */
	public static function providerForAssistantProvider(?int $assistantProvider): ?int
	{
		$map = [
			Assistant::PROVIDER_OPENAI => self::PROVIDER_OPENAI,
			Assistant::PROVIDER_CLAUDE => self::PROVIDER_CLAUDE,
		];
		return $map[$assistantProvider] ?? null;
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
				'attributes' => ['expire_at'],
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
			[['name', 'status'], 'required'],
			[['provider', 'chunk_size', 'chunk_overlap', 'tokens_per_file', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['expire_at', 'created_at', 'updated_at'], 'safe'],
			[['description'], 'string'],
			[['name', 'embedding_model', 'vector_store_id'], 'string', 'max' => 255],
			[['chunk_size'], 'integer', 'min' => 100, 'max' => 10000],
			[['chunk_overlap'], 'integer', 'min' => 0, 'max' => 1000],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'name' => Yii::t('label', 'Name'),
			'description' => Yii::t('label', 'Description'),
			'provider' => Yii::t('label', 'Provider'),
			'embedding_model' => Yii::t('label', 'Embedding Model'),
			'vector_store_id' => Yii::t('label', 'Vector Store ID'),
			'chunk_size' => Yii::t('label', 'Chunk Size'),
			'chunk_overlap' => Yii::t('label', 'Chunk Overlap'),
			'tokens_per_file' => Yii::t('label', 'Tokens Per File'),
			'expire_at' => Yii::t('label', 'Expire At'),
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
	public function getAssistantKnowledgeBases()
	{
		return $this->hasMany(AssistantKnowledgeBase::class, ['knowledge_base_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getAssistants()
	{
		return $this->hasMany(Assistant::class, ['id' => 'assistant_id'])
			->via('assistantKnowledgeBases');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getRecordVectorIndexes()
	{
		return $this->hasMany(RecordVectorIndex::class, ['vector_store_id' => 'vector_store_id']);
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
	 * Provider labels (Generic + the integration types).
	 *
	 * @return array
	 */
	public static function getProviderLabels()
	{
		return [
			static::PROVIDER_OPENAI => Yii::t('label', 'OpenAI'),
			static::PROVIDER_CLAUDE => Yii::t('label', 'Claude (Anthropic)'),
		];
	}

	/**
	 * Finds all active records.
	 *
	 * @return static[]|array
	 */
	public static function findAllKnowledgeBases()
	{
		try {
			return static::getDb()->cache(function ($db) {
				return static::find()
					->alias('kb')
					->where([
						'kb.status' => static::STATUS_ACTIVE,
						'kb.deleted' => static::NO,
					])
					->orderBy(['kb.name' => SORT_ASC])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}
}
