<?php

namespace console\components;

use Yii;

class ApplicationBootstrap extends \common\components\ApplicationBootstrap
{
	/**
	 * @var \yii\web\Application The frontend web application instance.
	 */
	protected $frontendApp;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$generalSettings = Yii::$app->settings->getCategory('general');

		// Set missing environment variables since we are running a console application
		if (!isset($_SERVER['SERVER_NAME'])) {
			$_SERVER['SERVER_NAME'] = parse_url($generalSettings['hostInfo'], PHP_URL_HOST);
		}
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = '';
		}

		$this->frontendApp = Yii::$app->instance->get('@frontend');
		if ($this->frontendApp->has('urlManager') && $this->frontendApp->has('request')) {
			$this->frontendApp->getUrlManager()->setHostInfo($this->frontendApp->getRequest()->getHostInfo());
			$this->frontendApp->getUrlManager()->setBaseUrl($this->frontendApp->getRequest()->getBaseUrl());
		}
	}

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function bootstrap($app)
	{
		$this->initUrlManager();
	}

	/**
	 * Initializes UrlManager component.
	 * @link http://www.yiiframework.com/doc-2.0/yii-web-urlmanager.html
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initUrlManager()
	{
		/** @var \codemix\localeurls\UrlManager $urlManager */
		if ($urlManager = Yii::$app->get('urlManager')) {
			// Use the urlManager component from frontend web application.
			Yii::$app->set('urlManager', $this->frontendApp->getUrlManager());
		}
	}
}
