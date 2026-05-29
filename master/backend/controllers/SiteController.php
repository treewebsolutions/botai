<?php

namespace backend\controllers;

use backend\models\SearchForm;
use backend\models\UserProfileForm;
use common\models\County;
use common\models\Locality;
use kartik\depdrop\DepDropAction;
use tws\helpers\Url;
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
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'actions' => ['login'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'actions' => ['counties', 'localities'],
						'allow' => true,
						'roles' => ['@', '?'],
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
			'counties' => [
				'class' => DepDropAction::class,
				'outputCallback' => function ($selectedId, $params) {
					return County::queryCountiesByCountry($selectedId);
				},
				'selectedCallback' => function ($selectedId) {
					if (Yii::$app->request->post('depdrop_all_params')['dd_country']) {
						return Yii::$app->request->post('depdrop_all_params')['dd_country'];
					}
					if (count(County::queryCountiesByCountry($selectedId)) == 1) {
						return County::queryCountiesByCountry($selectedId)[0];
					}
					return [];
				},
			],
			'localities' => [
				'class' => DepDropAction::class,
				'outputCallback' => function ($selectedId, $params) {
					return Locality::queryLocalitiesByCounty($selectedId);
				},
				'selectedCallback' => function ($selectedId) {
					if (Yii::$app->request->post('depdrop_all_params')['dd_locality']) {
						return Yii::$app->request->post('depdrop_all_params')['dd_locality'];
					}
					if (count(Locality::queryLocalitiesByCounty($selectedId)) == 1) {
						return Locality::queryLocalitiesByCounty($selectedId)[0];
					}
					return [];
				},
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
		$subscriptionReportModel = new \backend\modules\subscriber\modules\report\models\SubscriptionReportForm();
		$subscriptionMonthlyModel = new \backend\modules\subscriber\modules\report\models\SubscriptionMonthlyReportForm();
		$subscriptionStatusReportModel = new \backend\modules\subscriber\modules\report\models\SubscriptionStatusReportForm();
		$workspaceTypeReportModel = new \backend\modules\subscriber\modules\report\models\WorkspaceTypeReportForm();

		return $this->render('index', [
			'dataset' => [
				'subscription' => $subscriptionReportModel->getDataset(),
				'subscriptionMonthly' => $subscriptionMonthlyModel->getDataset(),
				'subscriptionStatus' => $subscriptionStatusReportModel->getDataset(),
				'workspaceType' => $workspaceTypeReportModel->getDataset(),
			],
		]);
	}

	/**
	 * Displays login page.
	 *
	 * @return mixed
	 */
	public function actionLogin()
	{
		return $this->redirect(Url::to(['/site/login'], true, '@frontend'), 301);
	}

	/**
	 * Logs out the current user.
	 *
	 * @return mixed
	 */
	public function actionLogout()
	{
		return $this->redirect(Url::to(['/site/logout'], true, '@frontend'), 301);
	}

	/**
	 * Displays profile page.
	 *
	 * @return mixed
	 */
	public function actionProfile()
	{
		$model = UserProfileForm::findOne(Yii::$app->user->id);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));
			return $this->refresh();
		}

		return $this->render('profile', [
			'model' => $model
		]);
	}

	/**
	 * Searches globally in the site.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{

		$dataset = [];

		if ($criteria = Yii::$app->request->post('county', null)) {
			$params = [
				'country_code' => Yii::$app->request->post('country_code', null),
			];
			$counties = \common\models\County::findByCriteria($criteria, $params);
			foreach ($counties as $county) {
				$dataset[] = [
					'label' => $county->name,
				];
			}
		}

		if ($criteria = Yii::$app->request->post('locality', null)) {
			$params = [
				'county' => Yii::$app->request->post('county', null),
			];
			$localities = \common\models\Locality::findByCriteria($criteria, $params);
			foreach ($localities as $locality) {
				$dataset[] = [
					'label' => $locality->name,
				];
			}
		}

		if ($criteria = Yii::$app->request->post('county', null) || $criteria = Yii::$app->request->post('locality', null)) {
			return $this->asJson([
				'results' => $dataset,
			]);
		}

		$searchModel = new SearchForm();
		$searchModel->load(Yii::$app->request->get());

		$dataProvider = $searchModel->search();

		return $this->render('search', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}
}
