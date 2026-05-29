<?php

namespace common\models\master;

use common\helpers\DebugHelper;
use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use tws\helpers\DbHelper;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%workspace}}".
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $code
 * @property string $url
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Subscription $subscription
 * @property WorkspaceHasSubscriptionFeature[] $workspaceHasSubscriptionFeatures
 * @property SubscriptionFeature[] $subscriptionFeatures
 * @property WorkspaceHasUser[] $workspaceHasUsers
 * @property User[] $users
 * @property User $creator
 * @property User $updater
 */
class Workspace extends CommonActiveRecord
{
	const TYPE_SUBSCRIBER = 1;
	const TYPE_DEMO = 2;

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
	}

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%workspace}}';
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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['subscription_id', 'type', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['code', 'url', 'status'], 'required'],
			[['created_at', 'updated_at'], 'safe'],
			[['code', 'url'], 'string', 'max' => 255],
			[['subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscription::class, 'targetAttribute' => ['subscription_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'subscription_id' => Yii::t('label', 'Subscription ID'),
			'code' => Yii::t('label', 'Code'),
			'url' => Yii::t('label', 'Url'),
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
	public function getSubscription()
	{
		return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getWorkspaceHasSubscriptionFeatures()
	{
		return $this->hasMany(WorkspaceHasSubscriptionFeature::class, ['workspace_id' => 'id']);
	}

    /**
     * @return \yii\db\ActiveQuery|CommonActiveQuery
     */
    public function getWorkspaceHasUsers()
    {
        return $this->hasMany(WorkspaceHasUser::class, ['workspace_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscriptionFeatures()
	{
		return $this->hasMany(SubscriptionFeature::class, ['id' => 'subscription_feature_id'])->viaTable('{{%workspace_has_subscription_feature}}', ['workspace_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::class, ['id' => 'user_id'])->viaTable('{{%workspace_has_user}}', ['workspace_id' => 'id']);
	}

	/**
	 * Gets the absoluteUrl.
	 *
	 * @param bool $administration Flag that indicates if the administration URL should be returned.
	 * @return string
	 */
	public function getAbsoluteUrl($administration = false)
	{
		$segments = [Yii::$app->urlManager->hostInfo, $this->url];

		if ($administration === true) {
			$segments[] = 'admin';
		}

		return implode('/', $segments);
	}

	/**
	 * Gets if isDefault.
	 *
	 * @return bool
	 */
	public function getIsDefault()
	{
		return WorkspaceHasUser::find()
			->where([
				'workspace_id' => $this->id,
				'default' => WorkspaceHasUser::YES,
			])
			->exists();
	}

	/**
	 * Gets the WorkspaceHasSubscriptionFeature model by SubscriptionFeature name property.
	 *
	 * @param string $featureName
	 * @return \yii\db\ActiveRecord|WorkspaceHasSubscriptionFeature|null
	 */
	public function getWorkspaceSubscriptionFeature($featureName)
	{
		return WorkspaceHasSubscriptionFeature::find()
			->alias('whsf')
			->joinWith([
				'subscriptionFeature sf' => function (ActiveQuery $query) use ($featureName) {
					$query->andWhere([
						'sf.subscription_id' => $this->subscription_id,
						'sf.name' => $featureName,
						'sf.deleted' => SubscriptionFeature::NO,
					]);
				},
			])
			->andWhere([
				'whsf.workspace_id' => $this->id,
			])
			->one();
	}

	/**
	 * Gets the WorkspaceHasSubscriptionFeature usage statistics.
	 *
	 * @param $featureName
	 * @return array
	 */
	public function getWorkspaceSubscriptionFeatureStatistics($featureName)
	{
		$quota = 0;
		$value = 0;
		$usagePercentage = 0;
		$color = 'primary';

		if ($workspaceHasSubscriptionFeature = $this->getWorkspaceSubscriptionFeature($featureName)) {
			$quota = $workspaceHasSubscriptionFeature->quota;
			$value = $workspaceHasSubscriptionFeature->subscriptionFeature->subscription->getSubscriptionFeatureByName($featureName)->value ?: $quota;
			$usagePercentage = round($quota * 100 / ($value ?: 1));

			if ($usagePercentage < 50) {
				$color = 'info';
			} elseif ($usagePercentage >= 50 && $usagePercentage < 70) {
				$color = 'warning';
			} elseif ($usagePercentage >= 70) {
				$color = 'danger';
			}
		}

		return [
			'quota' => $quota,
			'value' => $value,
			'usagePercentage' => $usagePercentage,
			'color' => $color,
		];
	}

	/**
	 * Finds all records by user model ID.
	 *
	 * @param int $user_id
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllWorkspacesByUser($user_id)
	{
		return static::find()
			->alias('w')
			->joinWith([
				'workspaceHasUsers whu' => function (\yii\db\ActiveQuery $query) use ($user_id) {
					$query->andWhere([
						'whu.user_id' => $user_id,
						'whu.status' => WorkspaceHasUser::STATUS_ACTIVE,
						'whu.deleted' => WorkspaceHasUser::NO,
					]);
				}
			], false)
			->andWhere([
				'w.status' => static::STATUS_ACTIVE,
				'w.deleted' => static::NO,
			])
			->all();
	}

	/**
	 * Gets the Workspace database name.
	 *
	 * @return string|null
	 */
	public function getWorkspaceDbName()
	{
		if ($this->isNewRecord) {
			return null;
		}

		$dbNameParts = preg_split('/_|-/', DbHelper::getDsnAttribute('dbname', static::getDb()));
		$dbNameParts[count($dbNameParts) - 1] = $this->code;

		return implode('_', $dbNameParts);
	}

	/**
	 * Gets the Workspace database instance.
	 *
	 * @return null|object|\yii\db\Connection
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getWorkspaceDb()
	{
		if ($this->isNewRecord) {
			return null;
		}

		$db = static::getDb();

		return Yii::createObject([
			'class' => 'yii\db\Connection',
			'dsn' => str_replace(DbHelper::getDsnAttribute('dbname', $db), $this->getWorkspaceDbName(), $db->dsn),
			'username' => $db->username,
			'password' => $db->password,
			'charset' => $db->charset,
		]);
	}
}
