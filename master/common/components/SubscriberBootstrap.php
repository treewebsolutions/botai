<?php

namespace common\components;

use common\models\Feature;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;

/**
 * This handles subscriber related app events.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class SubscriberBootstrap extends Component implements BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		// User events
		$app->on('user.afterSignup', function ($event) {
			// Do something here
		});
		$app->on('user.afterAccountActivation', function ($event) {
			// Do something here
		});

		// Workspace events
		$app->on('workspace.afterReinstall', function ($event) {
			$this->updateWorkspaceSubscriptionFeatureQuota($event->sender, -1);
		});

		// Subscription events
		$app->on('subscription.afterSuspend', function ($event) {
			$this->createNotificationForSuspendedSubscription($event->sender);
		});
		$app->on('subscription.afterPayment', function ($event) {
			$this->renewSubscriptionFeaturesQuota($event->sender);
		});
	}

	/**
	 * Updates subscription feature quota for a workspace.
	 *
	 * @param \common\models\Workspace $workspace
	 * @param int $increment
	 * @return bool
	 */
	protected function updateWorkspaceSubscriptionFeatureQuota($workspace, $increment = 1)
	{
		$workspaceSubscriptionFeature = $workspace->getWorkspaceSubscriptionFeature(Feature::WORKING_POINTS);

		// Prevent negative quota
		if ($increment < 0 && $workspaceSubscriptionFeature->quota <= 0) {
			return true;
		}

		$workspaceSubscriptionFeature->quota += $increment;
		return $workspaceSubscriptionFeature->save(false, ['quota']);
	}

	/**
	 * Creates a notification for suspended subscription.
	 * This notification will be only available to dashboard users.
	 *
	 * @param \common\models\Subscription $subscription
	 */
	protected function createNotificationForSuspendedSubscription($subscription)
	{
		\common\models\Notification::create([
			'title' => Yii::t('notification', 'Subscription suspension'),
			'message' => Yii::t('notification', "{0}'s {1} subscription was suspended due to nonpayment.", [
				\yii\helpers\Html::a($subscription->subscriber->user->fullName, ['/admin/subscriber-manager/subscriber/view', 'id' => $subscription->subscriber_id]),
				$subscription->package->translation->name,
			]),
			'icon' => 'fa fa-hourglass-end',
			'color' => '#f1bb15',
		]);
	}

	/**
	 * Renews the subscription features quota.
	 *
	 * @param \common\models\Subscription $subscription
	 */
	protected function renewSubscriptionFeaturesQuota($subscription)
	{
		try {
			$renewableSubscriptionFeatures = $subscription->getSubscriptionFeatures()
				->select(['id'])
				->where(['renewable' => \common\models\SubscriptionFeature::YES])
				->indexBy('id')
				->all();

			if ($renewableSubscriptionFeatures) {
				\common\models\WorkspaceHasSubscriptionFeature::updateAll([
					'quota' => 0,
					'renewed_at' => (new \DateTime)->format('Y-m-d H:i:s'),
				], [
					'subscription_feature_id' => array_keys($renewableSubscriptionFeatures),
				]);
			}
		} catch (\Exception $e) {
		}
	}
}
