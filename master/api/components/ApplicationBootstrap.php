<?php

namespace api\components;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;

class ApplicationBootstrap extends Component implements BootstrapInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function bootstrap($app)
	{
		$app->on(Application::EVENT_BEFORE_REQUEST, function ($event) {
			/** @var Application $app */
			$app = $event->sender;

			$app->response->headers->set('Access-Control-Allow-Origin', '*');
			$app->response->headers->set('Access-Control-Allow-Headers', '*');
			$app->response->headers->set('Access-Control-Allow-Methods', '*');

			if ($app->request->method === 'OPTIONS') {
				$app->response->statusCode = 200;
				$app->response->send();
				$app->end();
			}

			$this->setAppLanguage($app->request->headers->get('Accept-Language'));
		});
	}

	/**
	 * Sets the application language.
	 *
	 * @return void
	 */
	protected function setAppLanguage($language): void
	{
		if (!isset($language)) {
			return;
		}

		if (in_array($language, Yii::$app->params['languages'])) {
			Yii::$app->language = $language;
		}
	}
}
