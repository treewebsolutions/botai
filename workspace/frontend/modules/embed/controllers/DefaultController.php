<?php

namespace frontend\modules\embed\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

class DefaultController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $layout = 'embed';

	/**
	 * @inheritdoc
	 *
	 * The widget is framed on the customer's own site, so SameSite keeps the
	 * session cookie - and with it the CSRF token - away from these requests.
	 * There is no ambient authority to abuse either: a conversation is
	 * addressed by the token the widget keeps in localStorage, which a
	 * cross-site form cannot read or replay.
	 */
	public $enableCsrfValidation = false;

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'roles' => ['?', '@'],
					],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 * @throws \yii\web\BadRequestHttpException
	 */
	public function beforeAction($action)
	{
		$queryParams = Yii::$app->getRequest()->getQueryParams();

		if (!empty($queryParams['language'])) {
			Yii::$app->language = $queryParams['language'];
		}

		return parent::beforeAction($action);
	}

	/**
	 * Automatically detects which view of the module should be rendered.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		$queryParams = Yii::$app->getRequest()->getQueryParams();

		if ($queryParams['type'] == 'chat') {
			return $this->redirect(['chat/index'], 301);
		}

		return Yii::t('common', 'The requested page does not exist.');
	}

	/**
	 * Returns the JavaScript API file.
	 *
	 * @return mixed
	 */
	public function actionApi()
	{
		return Yii::$app->response->sendFile(Yii::getAlias('@embed/web/js/embed.js'));
	}
}
