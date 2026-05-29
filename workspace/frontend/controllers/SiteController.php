<?php

namespace frontend\controllers;

use Yii;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class SiteController extends MainController
{
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'only' => ['logout'],
				'rules' => [
					[
						'actions' => ['logout'],
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
			],
		];
	}

	/**
	 * Displays homepage.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$url = Yii::$app->request->get('url', (Yii::$app->settings->get('chatUrl', 'interface') ?: Yii::$app->settings->get('chatUrl')));
		return $this->render('index', ['url' => $url]);
	}
}
