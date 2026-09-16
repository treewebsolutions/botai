<?php

namespace backend\controllers;

use common\filters\RateLimit;
use backend\models\SearchForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class SiteController extends MainController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			// The admin login was the one credential form with nothing in front of it,
			// and it is the most valuable one in the system.
			'rateLimit' => [
				'class' => RateLimit::class,
				'only' => ['login'],
				'limit' => 10,
				'window' => 900,
				// Also counted per account named, which is the shape an address counter
				// cannot see: many addresses working on one login.
				'identityParams' => ['username', 'email'],
				'identityLimit' => 10,
				'identityWindow' => 3600,
				'keyPrefix' => 'ratelimit:admin',
			],
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'actions' => ['login'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'logout' => ['POST'],
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
				'layout' => 'blank',
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
		return $this->render('index');
	}

	/**
	 * Displays login page.
	 *
	 * @return mixed
	 */
	public function actionLogin()
	{
		/** @var \yii\web\UrlManager $frontendAppUrlManager */
		$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
		return $this->redirect($frontendAppUrlManager->createAbsoluteUrl(['/site/login']), 301);
	}

	/**
	 * Logs out the current user.
	 *
	 * @return mixed
	 */
	public function actionLogout()
	{
		/** @var \yii\web\UrlManager $frontendAppUrlManager */
		$frontendAppUrlManager = Yii::$app->instance->get('@frontend')->getUrlManager();
		return $this->redirect($frontendAppUrlManager->createAbsoluteUrl(['/site/logout']), 301);
	}

	/**
	 * Searches globally in the site.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{
		$searchModel = new SearchForm();
		$searchModel->load(Yii::$app->request->get());

		$dataProvider = $searchModel->search();

		return $this->render('search', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
}
