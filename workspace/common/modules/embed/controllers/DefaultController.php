<?php

namespace common\modules\embed\controllers;

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
