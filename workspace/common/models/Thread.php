<?php

namespace common\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%thread}}".
 *
 * @property int $id
 * @property string $openai_id
 * @property int $created_by
 * @property string $created_at
 * @property int $status
 * @property int $deleted
 *
 * @property Message[] $messages
 * @property Participant $creator
 */
class Thread extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%thread}}';
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
            [['openai_id'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'openai_id' => Yii::t('label', 'OpenAI ID'),
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
        return $this->hasMany(Message::class, ['thread_id' => 'id']);
    }

	public function getCreator()
	{
		return $this->hasOne(Participant::class, ['id' => 'created_by']);
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
			->alias('t')
			->select([
				't.*',
			])
			->where([
				't.deleted' => self::NO,
				't.status' => self::STATUS_ACTIVE,
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
				if (Yii::$app->user->can('viewThread') && !in_array(Yii::$app->user->id, $targetedUsers)) {
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
					'n.model' => Thread::class,
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
