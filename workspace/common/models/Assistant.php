<?php

namespace common\models;

use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%assistant}}".
 *
 * An assistant is a backend-configurable chat profile (model, instructions, sampling) that
 * searches the vector stores of its linked {@see KnowledgeBase} rows through the OpenAI
 * Responses API `file_search` tool. Nothing is created on the OpenAI side for an assistant.
 *
 * @property int $id
 * @property string $name
 * @property string $model
 * @property string $instructions
 * @property string $temperature
 * @property string $top_p
 * @property int $max_tokens
 * @property int $provider
 * @property int $type
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property AssistantKnowledgeBase[] $assistantKnowledgeBases
 * @property KnowledgeBase[] $knowledgeBases
 * @property Message[] $messages
 * @property User $creator
 * @property User $updater
 */
class Assistant extends CommonActiveRecord
{
	/** Provider values mirror {@see Integration::TYPE_OPENAI}. */
	const PROVIDER_OPENAI = 1;
	const PROVIDER_CLAUDE = 2;

	const TYPE_CHAT = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%assistant}}';
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
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'ensureDefaultValue' => true,
				'groupAttributes' => ['type'],
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
			[['default', 'created_by', 'updated_by', 'status', 'deleted', 'max_tokens', 'provider', 'type'], 'integer'],
			[['max_tokens'], 'integer', 'min' => 1, 'max' => 8192],
			[['name', 'status', 'provider', 'type'], 'required'],
			[['instructions'], 'string'],
			[['temperature', 'top_p'], 'number'],
			[['created_at', 'updated_at'], 'safe'],
			[['name', 'model'], 'string', 'max' => 255],
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
			'model' => Yii::t('label', 'Model'),
			'instructions' => Yii::t('label', 'Instructions'),
			'temperature' => Yii::t('label', 'Temperature'),
			'top_p' => Yii::t('label', 'Top P'),
			'max_tokens' => Yii::t('label', 'Max Tokens'),
			'provider' => Yii::t('label', 'Provider'),
			'type' => Yii::t('label', 'Type'),
			'default' => Yii::t('label', 'Default'),
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
		return $this->hasMany(AssistantKnowledgeBase::class, ['assistant_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getKnowledgeBases()
	{
		return $this->hasMany(KnowledgeBase::class, ['id' => 'knowledge_base_id'])
			->via('assistantKnowledgeBases');
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMessages()
	{
		return $this->hasMany(Message::class, ['assistant_id' => 'id']);
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
	 * Whether this assistant should use the Responses API with file_search.
	 * Enabled when provider is OpenAI and at least one KB has a vector_store_id.
	 *
	 * @return bool
	 */
	public function useResponsesApi(): bool
	{
		if ((int) $this->provider !== self::PROVIDER_OPENAI) {
			return false;
		}
		return $this->collectVectorStoreIds() !== [];
	}

	/**
	 * Vector store IDs of all knowledge bases linked to this assistant (deduplicated, in link order).
	 *
	 * @return string[]
	 */
	public function collectVectorStoreIds(): array
	{
		$ids = [];
		foreach ($this->knowledgeBases as $kb) {
			if ((int) $kb->status !== KnowledgeBase::STATUS_ACTIVE || (int) $kb->deleted !== KnowledgeBase::NO) {
				continue;
			}
			if (!empty($kb->vector_store_id)) {
				$ids[] = (string) $kb->vector_store_id;
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Resolves the Assistant that drives the embed chat.
	 *
	 * Order: the `chatAssistantId` param (optional PK override) when it points to an active
	 * assistant, otherwise the default active chat Assistant from DB. Returns null when
	 * neither exists — callers fall back to params-based config.
	 *
	 * @return static|null
	 */
	public static function findChatAssistant(): ?self
	{
		$id = (int) (Yii::$app->params['chatAssistantId'] ?? 0);
		if ($id > 0) {
			$assistant = static::find()
				->where(['id' => $id, 'status' => static::STATUS_ACTIVE, 'deleted' => static::NO])
				->with('knowledgeBases')
				->one();
			if ($assistant !== null) {
				return $assistant;
			}
		}
		return static::find()
			->where([
				'type' => static::TYPE_CHAT,
				'default' => static::YES,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->with('knowledgeBases')
			->orderBy(['id' => SORT_ASC])
			->one();
	}

	/**
	 * Provider type labels (OpenAI, Claude, etc.)
	 *
	 * @return array
	 */
	public static function getProviderTypeLabels()
	{
		return [
			static::PROVIDER_OPENAI => Yii::t('label', 'OpenAI'),
			static::PROVIDER_CLAUDE => Yii::t('label', 'Claude (Anthropic)'),
		];
	}

	/**
	 * Assistant type labels.
	 *
	 * @return array
	 */
	public static function getAssistantTypeLabels()
	{
		return [
			static::TYPE_CHAT => Yii::t('label', 'Chat'),
		];
	}

	/**
	 * Get available models for a specific provider or all models
	 *
	 * @param int|null $provider Provider type (PROVIDER_OPENAI, PROVIDER_CLAUDE, or null for all)
	 * @return array
	 */
	public static function getGPTModels($provider = null)
	{
		// Curated entries: the ones worth recommending, with a description. Anything the
		// provider has published since is appended by [[getLiveModels()]] below, so the
		// list stays current without this file being edited every time a model ships.
		$openaiModels = [
			'gpt-5.5' => Yii::t('backend', 'GPT-5.5 – Flagship model for complex reasoning and coding'),
			'gpt-5.5-pro' => Yii::t('backend', 'GPT-5.5 Pro – Highest accuracy for high-stakes work'),
			'gpt-5.4' => Yii::t('backend', 'GPT-5.4 – Affordable general-purpose model'),
			'gpt-5.4-mini' => Yii::t('backend', 'GPT-5.4 Mini – Fast, cost-efficient model for everyday tasks'),
			// Legacy models kept for existing assistants.
			'gpt-4o' => Yii::t('backend', 'GPT-4o – Multimodal model for text and vision (legacy)'),
			'gpt-4.1' => Yii::t('backend', 'GPT-4.1 – General-purpose text model (legacy)'),
			'gpt-4o-mini' => Yii::t('backend', 'GPT-4o Mini – Cost-efficient model for everyday tasks (legacy)'),
		];
		$claudeModels = [
			'claude-fable-5-1' => Yii::t('backend', 'Claude Fable 5.1 – Most capable model for demanding reasoning and agentic work'),
			'claude-opus-5' => Yii::t('backend', 'Claude Opus 5 – Frontier model for complex reasoning and coding'),
			'claude-sonnet-5' => Yii::t('backend', 'Claude Sonnet 5 – Balanced speed and intelligence'),
			'claude-haiku-4-5' => Yii::t('backend', 'Claude Haiku 4.5 – Fast and lightweight model'),
			// Previous generation, kept for existing assistants.
			'claude-fable-5' => Yii::t('backend', 'Claude Fable 5 – Previous flagship (legacy)'),
			'claude-opus-4-8' => Yii::t('backend', 'Claude Opus 4.8 – Previous frontier model (legacy)'),
			'claude-sonnet-4-6' => Yii::t('backend', 'Claude Sonnet 4.6 – Previous balanced model (legacy)'),
		];
		$provider = $provider !== null ? (int) $provider : null;

		if ($provider === static::PROVIDER_OPENAI) {
			return static::withLiveModels($openaiModels, static::PROVIDER_OPENAI);
		}
		if ($provider === static::PROVIDER_CLAUDE) {
			return $claudeModels;
		}

		return array_merge(static::withLiveModels($openaiModels, static::PROVIDER_OPENAI), $claudeModels);
	}

	/**
	 * Appends whatever the provider currently publishes that the curated list does not
	 * already name.
	 *
	 * A curated list goes stale the moment a model ships, and nobody notices until an
	 * assistant is pointed at a name the API no longer serves. Asking the provider keeps
	 * the dropdown honest; the curated entries stay first and keep their descriptions.
	 *
	 * @param array $curated
	 * @param int $provider
	 * @return array
	 */
	protected static function withLiveModels(array $curated, $provider)
	{
		$live = static::getLiveModels($provider);
		foreach ($live as $id) {
			if (!array_key_exists($id, $curated)) {
				$curated[$id] = $id;
			}
		}

		return $curated;
	}

	/**
	 * Model ids the provider currently serves, or an empty list when it cannot be asked.
	 *
	 * Cached, because this renders on a form: a provider that is slow or down must not
	 * hold the page, and the list changes on the order of weeks. Any failure - no key, a
	 * timeout, a refused request - returns nothing and leaves the curated list standing.
	 *
	 * @param int $provider
	 * @return string[]
	 */
	protected static function getLiveModels($provider)
	{
		if ((int) $provider !== static::PROVIDER_OPENAI) {
			return [];
		}

		$cacheKey = ['assistant.live-models', 'provider' => (int) $provider];

		return Yii::$app->cache->getOrSet($cacheKey, function () {
			$integration = Integration::resolveOpenAI();
			$apiKey = $integration !== null ? (string) $integration->getApiKey() : '';
			if ($apiKey === '') {
				return [];
			}

			try {
				$response = (new \yii\httpclient\Client())
					->createRequest()
					->setMethod('GET')
					->setUrl('https://api.openai.com/v1/models')
					->addHeaders(['Authorization' => "Bearer {$apiKey}"])
					->setOptions(['timeout' => 5])
					->send();

				if (!$response->isOk || !isset($response->data['data'])) {
					return [];
				}

				// Only the chat-completion families: the endpoint also lists embeddings,
				// audio, moderation and image models, none of which an assistant can use.
				$ids = [];
				foreach ($response->data['data'] as $model) {
					$id = (string) ($model['id'] ?? '');
					if ($id !== '' && preg_match('/^(gpt|o[0-9])/i', $id) && !preg_match('/(audio|realtime|transcribe|tts|image|search|embedding|moderation)/i', $id)) {
						$ids[] = $id;
					}
				}
				sort($ids);

				return $ids;
			} catch (\Throwable $e) {
				Yii::warning('Could not list the OpenAI models: ' . $e->getMessage(), __METHOD__);

				return [];
			}
		}, 6 * 3600);
	}
}
