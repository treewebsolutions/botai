<?php

namespace common\components;

use tws\sms\Sms;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\helpers\Json;

/**
 * This component implements basic workspace app checks.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class WorkspaceBootstrap extends Component implements BootstrapInterface
{
	/**
	 * @var bool Flag that indicates if the current app is a console app.
	 */
	public $isConsoleApp = false;

	/**
	 * @var \common\models\master\Workspace|null The workspace model.
	 */
	private $_workspace;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->isConsoleApp = (Yii::$app instanceof \yii\console\Application);
		$this->bindEvents();
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{

	}

	/**
	 * Gets the workspace model.
	 *
	 * @return \common\models\master\Workspace|null
	 */
	public function getWorkspace()
	{
		if (!$this->_workspace) {
			if ($this->isConsoleApp || !Yii::$app->user->identity->workspace) {
				// Get the workspace from app ID
				$this->_workspace = \common\models\master\Workspace::findOne(end(explode('-', Yii::$app->id)));
			} else {
				// Get the workspace from app URL
				$this->_workspace = Yii::$app->user->identity->workspace;
			}
		}
		return $this->_workspace;
	}

	/**
	 * Binds app events.
	 * This method binds events that handle app limits based on the current subscription.
	 */
	protected function bindEvents()
	{
		Event::on(Application::class, Application::EVENT_BEFORE_ACTION, function ($event) {
			$excludedRoutes = [
				'debug/default/toolbar',
				'commercial/company/find',
				'embed/default/api',
				'embed/chat/index',
			];
//			if (!in_array(Yii::$app->requestedRoute, $excludedRoutes)) {
//				$this->checkSubscription();
//			}
		});

		// Prevent actions based on subscription feature quota
		Event::on(ActiveRecord::class, ActiveRecord::EVENT_BEFORE_INSERT, function ($event) {
			$event->isValid = $this->isWorkspaceSubscriptionFeatureQuotaValid($event);
			if (!$event->isValid) {
				$event->sender->addError('limitReached', Yii::t('common', 'The subscription feature limit has been reached.'));
			}
		});

		// Update subscription feature quota
		Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
			// TODO: if there are multiple queries enclosed into a DB transaction, if one fails, then do not call the below method (do not increment the quota)???
			$this->updateWorkspaceSubscriptionFeatureQuota($event);
		});
		Event::on(ActiveRecord::class, ActiveRecord::EVENT_AFTER_DELETE, function ($event) {
			$this->updateWorkspaceSubscriptionFeatureQuota($event);
		});
	}

	/**
	 * Check the current workspace user against the master app limits.
	 * This method prevents app access based on subscription or workspace state.
	 *
	 * @return mixed
	 */
	protected function checkSubscription()
	{
		$response = Yii::$app->getResponse();

		try {
			$workspace = $this->getWorkspace();
			if ($workspace->status != \common\models\master\Workspace::STATUS_ACTIVE || $workspace->deleted == \common\models\master\Workspace::YES) {
				throw new \Exception('workspace.inactive');
			}

			if ($subscription = $workspace->subscription) {
				if ($subscription->status != \common\models\master\Subscription::STATUS_ACTIVE || $subscription->deleted == \common\models\master\Subscription::YES) {
					throw new \Exception('subscription.inactive');
				}
			}

			return true;
		} catch (\Exception $e) {
			if ($this->isConsoleApp) {
				exit(\yii\console\ExitCode::UNSPECIFIED_ERROR);
			}

			$response->data = Json::encode(['category' => $e->getMessage()]);
			if (Yii::$app->requestedRoute != 'site/info') {
				return $response->redirect(['/site/info'], 302, false)->send();
			}

			return false;
		}
	}

	/**
	 * Checks the quota of limited services of the current workspace subscription.
	 *
	 * @param Event $event
	 * @return bool the result of the quota and value comparison.
	 */
	protected function isWorkspaceSubscriptionFeatureQuotaValid($event)
	{
		if (!($workspaceSubscriptionFeature = $this->getWorkspaceSubscriptionFeatureByEvent($event))) {
			return true;
		}
		return $workspaceSubscriptionFeature->subscriptionFeature->value > $workspaceSubscriptionFeature->quota;
	}

	/**
	 * Updates the quota of limited services of the current workspace subscription.
	 *
	 * @param Event $event
	 * @return bool
	 */
	protected function updateWorkspaceSubscriptionFeatureQuota($event)
	{
		if (!($workspaceSubscriptionFeature = $this->getWorkspaceSubscriptionFeatureByEvent($event))) {
			return true;
		}
		$incrementQuota = false;

		if ($event->sender instanceof ActiveRecord) {
			$incrementQuota = $event->name == ActiveRecord::EVENT_AFTER_INSERT;
		} elseif ($event->sender instanceof Sms) {
			$incrementQuota = true;
		}

		if ($incrementQuota) {
			$workspaceSubscriptionFeature->quota++;
		} else {
			$workspaceSubscriptionFeature->quota--;
		}

		return $workspaceSubscriptionFeature->save(false, ['quota']);
	}

	/**
	 * Gets the WorkspaceHasSubscriptionFeature model by an app Event.
	 *
	 * @param Event $event
	 * @return \yii\db\ActiveRecord|\common\models\master\WorkspaceHasSubscriptionFeature|null
	 */
	protected function getWorkspaceSubscriptionFeatureByEvent($event)
	{
		$featureName = null;
		if ($event->sender instanceof ActiveRecord) {
			$modelClass = static::getCommonModelClass($event->sender);
			$targetedARModels = [
				'common\models\User' => \common\models\master\Feature::USERS,
			];
            if (!array_key_exists($modelClass, $targetedARModels)) {
                return null;
            }
            $featureName = $targetedARModels[$modelClass];
		}

		return $this->getWorkspace()->getWorkspaceSubscriptionFeature($featureName);
	}

	/**
	 * Gets the owner model ActiveRecord subclass.
	 *
	 * @param $model
	 * @return string
	 */
	public static function getCommonModelClass($model)
	{
		$model = is_string($model) ? $model : get_class($model);

		if (strpos($model, 'common\models') !== false) {
			return $model;
		}

		return static::getCommonModelClass(get_parent_class($model));
	}
}
