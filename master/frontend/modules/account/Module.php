<?php

namespace frontend\modules\account;

use common\models\Subscription;
use tws\helpers\Url;
use Yii;
use yii\helpers\Inflector;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public $layout = 'main';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		Yii::configure($this, require __DIR__ . '/config/main.php');
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		Yii::setAlias("@{$this->id}", __DIR__);

		$app->i18n->translations[$this->id] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'basePath' => "@{$this->id}/messages",
			'forceTranslation' => true,
			'fileMap' => [
				$this->id => 'i18n.php',
			],
		];

		if ($app instanceof \yii\web\Application) {
			if ($urlRules = $this->buildUrlRules()) {
				$app->getUrlManager()->addRules([$urlRules], false);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action)
	{
		if (!Yii::$app->user->isGuest) {
			/** @var \common\models\User $user */
			$user = Yii::$app->user->identity;

			// Prevent access to this module if the current user is not a subscriber
			if (!$user->getIsSubscriber()) {
				if ($user->getHasPermissions() && Yii::$app->requestedRoute == 'account/profile/index') {
					return Yii::$app->response->redirect(Url::to(['/site/profile'], false, '@backend'))->send();
				}
				Yii::$app->session->setFlash('error', Yii::t('yii', 'You are not allowed to perform this action.'));
				return Yii::$app->response->redirect(['/site/index'])->send();
			}

			// Display workspace reminder
			if (!Yii::$app->session->has('isWorkspaceReminderVisible')) {
				$activeSubscriptionsQuery = $user->subscriber->getSubscriptions()->andWhere([
					'type' => [Subscription::TYPE_STANDARD, Subscription::TYPE_CUSTOM],
					'status' => Subscription::STATUS_ACTIVE,
				]);
				if ($activeSubscriptionsQuery->exists()) {
					if (!$user->subscriber->getWorkspaces()) {
						Yii::$app->session->set('isWorkspaceReminderVisible', false);
						Yii::$app->session->setFlash('info', Yii::t('frontend', 'It seems that you don\'t have any workspaces configured yet. Add at least one workspace in order to use the app.'));
					}
				}
			}
		}

		return parent::beforeAction($action);
	}

	/**
	 * Builds the rules for all pages of this module.
	 *
	 * @return array
	 */
	protected function buildUrlRules()
	{
		$pages = \common\models\Page::findPagesByModule($this->id);
		$urlRules = [
			[
				'pattern' => '',
				'route' => 'default/index',
			],
		];

		foreach ($pages as $page) {
			$pageTranslation = $page->getTranslation();

			// Custom routes
			if ($page->controller == 'profile') {
				$urlRules = array_merge($urlRules, $this->buildProfileUrlRules($page, $pageTranslation));
				continue;
			} elseif ($page->controller == 'workspace') {
				$urlRules = array_merge($urlRules, $this->buildWorkspaceUrlRules($page, $pageTranslation));
			} elseif ($page->controller == 'helpdesk') {
				$urlRules = array_merge($urlRules, $this->buildHelpdeskUrlRules($page, $pageTranslation));
			} elseif ($page->controller == 'payment') {
				$urlRules = array_merge($urlRules, $this->buildPaymentUrlRules($page, $pageTranslation));
				continue;
			}

			// Default routes
			$urlRules[] = [
				'pattern' => $pageTranslation->slug,
				'route' => "{$page->controller}/{$page->action}",
			];
			$urlRules[] = [
				'pattern' => "{$pageTranslation->slug}/<id>/<action>",
				'route' => "{$page->controller}/<action>",
			];
			$urlRules[] = [
				'pattern' => "{$pageTranslation->slug}/<action>",
				'route' => "{$page->controller}/<action>",
			];
		}

		return [
			'class' => 'yii\web\GroupUrlRule',
			'routePrefix' => $this->id,
			'prefix' => Inflector::slug(Yii::t($this->id, 'Account')),
			'rules' => $urlRules,
		];
	}

	/**
	 * Builds URL Rules for ProfileController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildProfileUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/upload-file",
			'route' => "{$page->controller}/upload-file",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for WorkspaceController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildWorkspaceUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/<id>/<action>",
			'route' => "{$page->controller}/<action>",
		];

		return $urlRules;
	}

	/**
	 * Builds URL Rules for SupportController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildHelpdeskUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => "support-ticket/<id>/<action>",
			'route' => "support-ticket/<action>",
		];
		$urlRules[] = [
			'pattern' => "support-ticket/<action>",
			'route' => "support-ticket/<action>",
		];

        $urlRules[] = [
            'pattern' => "shipping/<id>/<action>",
            'route' => "shipping/<action>",
        ];
        $urlRules[] = [
            'pattern' => "shipping/<action>",
            'route' => "shipping/<action>",
        ];

        $urlRules[] = [
            'pattern' => "billing/<id>/<action>",
            'route' => "billing/<action>",
        ];
        $urlRules[] = [
            'pattern' => "billing/<action>",
            'route' => "billing/<action>",
        ];

        $urlRules[] = [
            'pattern' => "order/<id>/<action>",
            'route' => "order/<action>",
        ];
        $urlRules[] = [
            'pattern' => "order/<action>",
            'route' => "order/<action>",
        ];
		return $urlRules;
	}

	/**
	 * Builds URL Rules for PaymentController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildPaymentUrlRules($page, $pageTranslation)
	{
		$urlRules[] = [
			'pattern' => $pageTranslation->slug,
			'route' => "{$page->controller}/{$page->action}",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Package')),
			'route' => "{$page->controller}/package",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Subscription')),
			'route' => "{$page->controller}/subscription",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Invoice')),
			'route' => "{$page->controller}/invoice",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Features')),
			'route' => "{$page->controller}/features",
		];
		$urlRules[] = [
			'pattern' => "{$pageTranslation->slug}/" . Inflector::slug(Yii::t($this->id, 'Result')),
			'route' => "{$page->controller}/result",
		];

		return $urlRules;
	}
}
