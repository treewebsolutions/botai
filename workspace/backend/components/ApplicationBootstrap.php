<?php

namespace backend\components;

use Yii;

class ApplicationBootstrap extends \common\components\ApplicationBootstrap
{
	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function bootstrap($app)
	{
		$this->initUrlManager();
		$this->initEventLog();
		$this->initGoogleMap();
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
			$eventLog->enabled = true;
//			$eventLog->enabled = (bool) Yii::$app->settings->get('enableEventLogs'); // TODO: check if such option should exsit in settings

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
