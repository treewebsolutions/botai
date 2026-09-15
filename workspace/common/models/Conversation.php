<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%conversation}}".
 *
 * A chat conversation of the embed widget. The turns are kept locally in {@see Message};
 * `openai_conversation_id` is the OpenAI Conversations API object that carries the model-side
 * state between turns (created lazily by {@see \common\services\OpenAIResponsesService::ensureConversation()}).
 *
 * @property int $id
 * @property string $summary
 * @property string $openai_conversation_id
 * @property int $created_by
 * @property string $created_at
 * @property int $status
 * @property int $deleted
 *
 * @property Message[] $messages
 * @property Participant $creator
 */
class Conversation extends CommonActiveRecord
{
	/**
	 * @var int|null Aggregate populated by the backend ConversationSearch query (`COUNT(m.id)`).
	 */
	public $messages_count;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%conversation}}';
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
			[['created_by', 'status', 'deleted'], 'integer'],
			[['created_at'], 'safe'],
			[['summary', 'openai_conversation_id'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'summary' => Yii::t('label', 'Summary'),
			'openai_conversation_id' => Yii::t('label', 'OpenAI Conversation ID'),
			'created_by' => Yii::t('label', 'Created By'),
			'created_at' => Yii::t('label', 'Created At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMessages()
	{
		return $this->hasMany(Message::class, ['conversation_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(Participant::class, ['id' => 'created_by']);
	}

	/**
	 * Finds an active conversation by its OpenAI conversation id (the public token used by the embed widget).
	 *
	 * @param string|null $openaiConversationId
	 * @return static|null
	 */
	public static function findByOpenAIConversationId($openaiConversationId): ?self
	{
		$openaiConversationId = trim((string) $openaiConversationId);
		if ($openaiConversationId === '') {
			return null;
		}
		return static::find()
			->where([
				'openai_conversation_id' => $openaiConversationId,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->one();
	}

	/**
	 * Prepares the Notification models.
	 *
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 * @throws \Exception
	 */
	public static function prepareNotifications()
	{
		$models = static::find()
			->alias('c')
			->select([
				'c.*',
			])
			->where([
				'c.deleted' => self::NO,
				'c.status' => self::STATUS_ACTIVE,
			]);
		foreach ($models->each() as $model) {
			if (Yii::$app->user->identity->workspace->subscription) {
				$users = User::find()
					->alias('u')
					->select([
						'u.id',
					])
					->where([
						'u.status' => self::STATUS_ACTIVE,
						'u.deleted' => self::NO,
					])
					->createCommand()
					->queryAll();
				$users = ArrayHelper::index($users, 'id');
				$targetedUsers = array_filter($users, function ($user) use ($model) {
					return $user['id'] == $model->created_by;
				});
				$targetedUsers = array_keys($targetedUsers);
				if (Yii::$app->user->can('viewConversation') && !in_array(Yii::$app->user->id, $targetedUsers)) {
					$targetedUsers[] = Yii::$app->user->id;
				}
			} else {
				$targetedUsers = [];
			}
			$notifications = Notification::find()
				->alias('n')
				->joinWith([
					'userHasNotifications uhn',
				], false)
				->where([
					'n.model_key' => $model->id,
					'n.model' => Conversation::class,
					'n.status' => self::STATUS_ACTIVE,
					'n.deleted' => self::NO,
				])
				->groupBy(['n.id'])
				->all();
			if (!empty($notifications)) {
				foreach ($notifications as $notification) {
					Notification::create(['code' => $notification->code], $targetedUsers);
				}
			}
		}
	}
}
