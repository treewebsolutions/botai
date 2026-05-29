<?php

namespace backend\components;

use Yii;
use yii\web\Application;

class ApplicationBootstrap extends \common\components\ApplicationBootstrap
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
		$app->on(Application::EVENT_AFTER_REQUEST, function ($event) {
			$this->checkUserAccess();
		});
		$this->updateUserActivity();
		$this->initUrlManager();
		$this->initEventLog();
		$this->initGoogleMap();
	}

	/**
	 * Checks the current user access.
	 *
	 * @return mixed
	 */
	protected function checkUserAccess()
	{
		if (Yii::$app->user->isGuest) {
			$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
			return Yii::$app->response->redirect($frontendAppUrlManager->createUrl(['/site/login']))->send();
		}

		/** @var \common\models\User $user */
		$user = Yii::$app->user->identity;

		// Block user access if is not active or does not have any role/permission associated
		if ($user->status != \common\models\User::STATUS_ACTIVE || !$user->getHasPermissions()) {
			Yii::$app->session->setFlash('error', Yii::t('yii', 'You are not allowed to perform this action.'));

			$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
			return Yii::$app->response->redirect($frontendAppUrlManager->createUrl(['/site/index']))->send();
		}

		return true;
	}

	/**
	 * Checks the current user access.
	 *
	 * @throws \Exception
	 */
	protected function updateUserActivity()
	{
		if (!Yii::$app->user->isGuest) {
			/** @var \common\models\User $user */
			$user = Yii::$app->user->identity;
			$user->updateAttributes(['last_activity' => (new \DateTime)->format('Y-m-d H:i:s')]);
		}
	}

	/**
	 * Initializes UrlManager component.
	 * @link http://www.yiiframework.com/doc-2.0/yii-web-urlmanager.html
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initUrlManager()
	{
		if (Yii::$app->has('urlManager')) {
			/** @var \codemix\localeurls\UrlManager $urlManager */
			$urlManager = Yii::$app->get('urlManager');
			$urlManager->languages = $this->languages;

			Yii::$app->set('urlManager', $urlManager);
		}
	}

	/**
	 * Initializes EventLog component.
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initEventLog()
	{
		if (Yii::$app->has('eventLog')) {
			/** @var \backend\modules\eventlog\components\EventLog $eventLog */
			$eventLog = Yii::$app->get('eventLog');
			$eventLog->enabled = (bool) Yii::$app->settings->get('enableEventLogs');

			Yii::$app->set('eventLog', $eventLog);
		}
	}

	/**
	 * Initializes the GoogleMap extension.
	 * @link https://github.com/2amigos/yii2-google-maps-library
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initGoogleMap()
	{
		if (Yii::$app->has('assetManager')) {
			/** @var \yii\web\AssetManager $assetManager */
			$assetManager = Yii::$app->get('assetManager');
			$settings = Yii::$app->settings->getCategory('general');

			$assetManager->bundles['dosamigos\google\maps\MapAsset'] = [
				'options' => [
					'key' => $settings['googleMapKey'],
					'language' => Yii::$app->language,
				],
			];

			Yii::$app->set('assetManager', $assetManager);
		}
	}
}
