<?php

namespace backend\modules\subscriber\models;

use common\models\Feature;
use common\models\SubscriptionFeature;
use common\models\Workspace;
use common\models\WorkspaceHasSubscriptionFeature;
use common\models\WorkspaceHasUser;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class WorkspaceForm extends Workspace
{
	/**
	 * @var int The Subscriber model ID.
	 */
	public $subscriber_id;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		$this->type = static::TYPE_SUBSCRIBER;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['subscriber_id'], 'required'],
			['url', 'string', 'min' => 3, 'max' => 255],
			['url', 'match', 'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i'],
			['url', 'trim'],
			['url', 'filter', 'filter' => function ($value) {
				return mb_strtolower($value);
			}],
			['url', 'unique', 'targetClass' => Workspace::class, 'targetAttribute' => ['url' => 'url'], 'when' => function () {
				return $this->isAttributeChanged('url');
			}],
			['domain', 'trim'],
			['domain', 'default', 'value' => null],
			['domain', 'validateDomain'],
			['domain', 'validateDirectoryKey'],
		]);
	}

	/**
	 * The domain must be a real registrable name.
	 *
	 * It may be typed as a bare host or as a full URL - both are stored as given and
	 * reduced the same way by {@see Workspace::normalizeHost()}. What is rejected is
	 * anything without a TLD label: the tenant directory is named after this, and on
	 * cPanel it becomes the addon domain, which refuses a name like "localhost".
	 *
	 * @param string $attribute
	 */
	public function validateDomain($attribute)
	{
		$value = trim((string) $this->$attribute);
		if ($value === '') {
			return;
		}

		$host = Workspace::normalizeHost($value);
		if ($host === '' || !preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $host)) {
			$this->addError($attribute, Yii::t('common', 'Enter a full domain name, for example example.ro or https://www.example.ro/.'));
		}
	}

	/**
	 * No two workspaces may resolve to the same directory under `workspaces/`.
	 *
	 * The directory is keyed by the domain and falls back to the URL slug, so a plain
	 * unique rule on each column is not enough: a domain reducing to "example.ro" would
	 * still collide with a domain-less workspace whose slug is "example.ro", and the two
	 * tenants would end up sharing one directory.
	 *
	 * @param string $attribute
	 */
	public function validateDirectoryKey($attribute)
	{
		if ($this->hasErrors($attribute) || ($key = $this->getDirectoryName()) === null) {
			return;
		}

		$others = Workspace::find()
			->andFilterWhere(['not', ['id' => $this->id]])
			->all();

		foreach ($others as $other) {
			if ($other->getDirectoryName() === $key) {
				$this->addError($attribute, Yii::t('common', 'Another workspace already uses the directory "{directory}".', [
					'directory' => $key,
				]));
				return;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'subscriber_id' => Yii::t('label', 'Subscriber'),
			'subscription_id' => Yii::t('label', 'Subscription'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->subscriber_id = $this->subscription->subscriber_id;
	}

	/**
	 * Saves the WorkspaceHasUser model.
	 *
	 * @return bool
	 */
	public function saveWorkspaceHasUser()
	{
		try {
			if (!($user = $this->subscription->subscriber->user)) {
				throw new \Exception();
			}

			$workspaceHasUser = WorkspaceHasUser::findOne([
				'workspace_id' => $this->id,
				'user_id' => $user->id,
			]);
			if (!$workspaceHasUser) {
				$workspaceHasUser = new WorkspaceHasUser();
			}
			$workspaceHasUser->setAttributes($user->getAttributes());
			$workspaceHasUser->workspace_id = $this->id;
			$workspaceHasUser->user_id = $user->id;
			if (!WorkspaceHasUser::find()->where(['user_id' => $user->id, 'default' => WorkspaceHasUser::YES])->exists()) {
				$workspaceHasUser->default = WorkspaceHasUser::YES;
			}
			if (!$workspaceHasUser->save()) {
				$this->addErrors($workspaceHasUser->getErrors());
				throw new \Exception('Cannot save WorkspaceHasUser model.');
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the WorkspaceHasSubscriptionFeature models.
	 *
	 * @return bool
	 */
	public function saveWorkspaceHasSubscriptionFeatures()
	{
		try {
			/** @var SubscriptionFeature[] $subscriptionFeatures */
			$subscriptionFeatures = array_diff_key(
				$this->subscription->getSubscriptionFeatures()->indexBy('id')->all(),
				$this->getWorkspaceHasSubscriptionFeatures()->indexBy('subscription_feature_id')->all()
			);

			if (!empty($subscriptionFeatures)) {
				$attributes = ['workspace_id', 'subscription_feature_id'];
				$rows = [];
				foreach ($subscriptionFeatures as $subscriptionFeature) {
					$rows[] = [
						'workspace_id' => $this->id,
						'subscription_feature_id' => $subscriptionFeature->id,
					];
				}
				if (!Yii::$app->db->createCommand()->batchInsert(WorkspaceHasSubscriptionFeature::tableName(), $attributes, $rows)->execute()) {
					throw new \Exception();
				}
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Checks if the workspaces count has reached the limit for its subscription.
	 *
	 * @return bool
	 */
	public function isWorkspacesLimitReached()
	{
		$subscription = $this->subscription;
		return $subscription->getWorkspaces()->count() >= $subscription->getSubscriptionFeatureByName(Feature::WORKSPACES)->value;
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 */
	public function saveModel()
	{
		$isNewRecord = $this->getIsNewRecord();

		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!$isNewRecord) {
				$workspace = static::findOne(['id' => $this->id]);
			}
//			if ($isNewRecord && $this->isWorkspacesLimitReached()) {
//				$this->addError('subscription_id', Yii::t('common', 'The workspaces limit for this subscription has been reached.'));
//				throw new \Exception();
//			}
			if ($isNewRecord || !$this->code) {
				$this->code = static::generateUniqueCode();
			}
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveWorkspaceHasUser()) {
				throw new \Exception();
			}
			if (!$this->saveWorkspaceHasSubscriptionFeatures()) {
				throw new \Exception();
			}
			if ($isNewRecord) {
				if (!$this->install()) {
					throw new \Exception();
				}
			} else {
				// The domain outranks the URL when the tenant directory is named, so a change
				// to either one can move it. Pass the previous domain as a string: a tenant
				// that had none must send '' rather than null, or saveUrl() would read the
				// new domain back and leave the old directory behind.
				if ($workspace->url != $this->url || $workspace->domain != $this->domain) {
					$this->saveUrl($workspace->url, (string) $workspace->domain);
				}
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
