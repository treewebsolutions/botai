<?php

namespace common\models;

use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use tws\helpers\StringHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%notification}}".
 *
 * @property int $id
 * @property string $code
 * @property string $title
 * @property string $message
 * @property string $data
 * @property int $type
 * @property string $icon
 * @property string $color
 * @property string $model_key
 * @property string $model
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property UserHasNotification[] $userHasNotifications
 * @property User[] $users
 * @property User $creator
 * @property User $updater
 */
class Notification extends CommonActiveRecord
{
	const TYPE_APP = 1;
	const TYPE_USER = 2;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%notification}}';
	}

	/**
	 * @inheritdoc
	 * @throws \Exception
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
					'deleted' => self::YES,
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
			[['code', 'title', 'status'], 'required'],
			[['message', 'data'], 'string'],
			[['type', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['code'], 'string', 'max' => 8],
			[['code'], 'unique'],
			[['created_at', 'updated_at'], 'default'],
			[['title', 'icon', 'color', 'model_key', 'model'], 'string', 'max' => 255],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'code' => Yii::t('label', 'Code'),
			'title' => Yii::t('label', 'Title'),
			'message' => Yii::t('label', 'Message'),
			'data' => Yii::t('label', 'Data'),
			'type' => Yii::t('label', 'Type'),
			'icon' => Yii::t('label', 'Icon'),
			'color' => Yii::t('label', 'Color'),
			'model_key' => Yii::t('label', 'Model Key'),
			'model' => Yii::t('label', 'Model'),
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
	public function getUserHasNotifications()
	{
		return $this->hasMany(UserHasNotification::class, ['notification_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::class, ['id' => 'user_id'])->viaTable('{{%user_has_notification}}', ['notification_id' => 'id']);
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
	 * Marks the Notification as seen.
	 *
	 * @param null|int $userId
	 */
	public function markAsSeen($userId = null)
	{
		if ($userId === null) {
			$userId = Yii::$app->user->id;
		}

		/** @var UserHasNotification $userHasNotification */
		$userHasNotification = $this->getUserHasNotifications()->andWhere(['user_id' => $userId])->one();

		if ($userHasNotification) {
			$userHasNotification->seen = self::YES;
			$userHasNotification->save();
		}
	}

	/**
	 * Truncate the message to a specific length of characters.
	 *
	 * @param int $length
	 * @return string
	 */
	public function getMessageExcerpt($stripTags = false, $length = null)
	{
		$data = unserialize($this->data);
		$shortcodes = [];
		if (!empty($data)) {
			foreach ($data as $key => $value) {
				$shortcodes['{' . $key . '}'] = str_replace('{{URL}}', Url::base(true), $value);
			}
		}
		$message = Yii::t('notification', $this->message);
		$message = strtr($message, $shortcodes);
		if ($stripTags) {
			$message = strip_tags($message);
		}
		if ($length) {
			$message = StringHelper::truncate($message, $length);
		}
		return $message;
	}

	/**
	 * Model type labels.
	 *
	 * @return array
	 */
	public static function getTypeLabels()
	{
		return [
			self::TYPE_APP => Yii::t('label', 'Application'),
			self::TYPE_USER => Yii::t('label', 'User'),
		];
	}

	/**
	 * Finds all unseen Notification models for the current user.
	 *
	 * @return array|\yii\db\ActiveRecord[]
	 * @throws \yii\base\InvalidConfigException
	 * @throws \yii\db\Exception
	 */
	public static function findAllUnseen()
	{
		if (empty(Yii::$app->session->get('last_notified'))) {
			Yii::$app->session->set('last_notified', time());
		}
		$sessionDuration = 1 * 60;
		if ((time() - Yii::$app->session->get('last_notified')) > $sessionDuration) {
			Thread::prepareNotifications();
			Yii::$app->session->set('last_notified', time());
		}

		return static::find()
			->alias('n')
			->joinWith([
				'userHasNotifications uhn',
			], false)
			->where([
				'n.status' => self::STATUS_ACTIVE,
				'n.deleted' => self::NO,
				'uhn.user_id' => Yii::$app->user->id,
				'uhn.seen' => self::NO,
			])
			->groupBy(['n.id'])
			->orderBy(['n.created_at' => SORT_DESC])
			->all();
	}

	/**
	 * Creates a new notification.
	 *
	 * @param array $attributes The model attributes.
	 * @param array|null|bool $targetedUsers A false means that no user is targeted.
	 * @return \yii\db\ActiveRecord|static|null
	 */
	public static function create($attributes, $targetedUsers = null, $forceCreate = false)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$model = new static();
			$attributes = array_merge([
				'type' => static::TYPE_APP,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			], $attributes);

			if (empty($forceCreate)) {
				$currentModel = static::find()->where($attributes)->one();

				if (!empty($currentModel)) {
					if (!is_array($targetedUsers)) {
						$targetedUsers = (array) $targetedUsers;
					}
					if (empty($targetedUsers)) {
						$targetedUsers = array_keys(User::findAllNotificationUsers());
					}
					if (in_array(Yii::$app->user->id, $targetedUsers) && (strtotime($currentModel->created_at) > strtotime(Yii::$app->user->identity->created_at))) {
						$userNotification = UserHasNotification::findOne(['user_id' => Yii::$app->user->id, 'notification_id' => $currentModel->id]);
						if (empty($userNotification)) {
							$userNotification = new UserHasNotification();
							$userNotification->user_id = Yii::$app->user->id;
							$userNotification->notification_id = $currentModel->id;
							$userNotification->save(false);
						}
					}
					$dbTransaction->commit();
					return $currentModel;
				}
			}

			if ($model->load($attributes, '') && $model->save()) {
				if ($targetedUsers !== false) {
					if (!is_array($targetedUsers)) {
						$targetedUsers = (array) $targetedUsers;
					}
					if (empty($targetedUsers)) {
						$targetedUsers = array_keys(User::findAllNotificationUsers());
					}
					$targetedUsers = array_map(function ($item) use ($model) {
						return [$item, $model->id];
					}, $targetedUsers);

					static::getDb()->createCommand()->batchInsert(
						UserHasNotification::tableName(),
						['user_id', 'notification_id'],
						$targetedUsers
					)->execute();
				}
				$dbTransaction->commit();
				return $model;
			}
			throw new \Exception();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return null;
		}
	}
}
