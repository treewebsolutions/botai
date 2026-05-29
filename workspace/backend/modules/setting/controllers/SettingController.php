<?php

namespace backend\modules\setting\controllers;

use backend\controllers\MainController;
use backend\modules\setting\models\EmailSettingForm;
use backend\modules\setting\models\GeneralSettingForm;
use backend\modules\setting\models\InterfaceSettingForm;
use backend\modules\setting\models\PrintSettingForm;
use backend\modules\setting\models\SmsSettingForm;
use common\models\Setting;
use Yii;
use yii\caching\TagDependency;
use yii\filters\AccessControl;
use yii\web\NotFoundHttpException;

class SettingController extends MainController
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
						'allow' => true,
						'actions' => ['view'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => ['updateGeneralSetting', 'updateEmailSetting', 'updateInterfaceSetting'],
					],
					[
						'allow' => true,
						'actions' => ['index'],
						'roles' => ['updateGeneralSetting'],
					],
					[
						'allow' => true,
						'actions' => ['email'],
						'roles' => ['updateEmailSetting'],
					],
					[
						'allow' => true,
						'actions' => ['interface'],
						'roles' => ['updateInterfaceSetting'],
					],
					[
						'allow' => true,
						'actions' => ['clear-cache'],
						'roles' => ['clearCacheSetting'],
						'verbs' => ['POST'],
					],
				],
			],
		];
	}

	/**
	 * Displays a single Setting model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
        return $this->render('view', [
			'model' => $this->findModel($id),
		]);
	}

	/**
	 * Displays a single Setting model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id);
		$redirectActions = [
			'general' => 'index',
			'email' => 'email',
			'interface' => 'interface',
			'sms' => 'sms',
			'print' => 'print',
		];

		return $this->redirect([$redirectActions[$model->name]], 301);
	}

	/**
	 * Lists general Setting model.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$model = GeneralSettingForm::findOne([
			'name' => 'general',
			'type' => Setting::TYPE_APP,
			'status' => Setting::STATUS_ACTIVE,
			'deleted' => Setting::NO,
		]);
		if (!$model) {
			$model = new GeneralSettingForm();
		}

		Yii::$app->eventLog
			->setData([
				'operation' => (Yii::$app->eventLog)::ACTION_UPDATE,
			])
			->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAppSettings']));
			Yii::$app->eventLog->endRecord();
			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));

			return $this->refresh();
		}

		return $this->render('index', [
			'model' => $model,
		]);
	}

	/**
	 * Lists email Setting model.
	 *
	 * @return mixed
	 */
	public function actionEmail()
	{
		$model = EmailSettingForm::findOne([
			'name' => 'email',
			'type' => Setting::TYPE_APP,
			'status' => Setting::STATUS_ACTIVE,
			'deleted' => Setting::NO,
		]);
		if (!$model) {
			$model = new EmailSettingForm();
		}

		Yii::$app->eventLog
			->setData([
				'operation' => (Yii::$app->eventLog)::ACTION_UPDATE,
			])
			->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAppSettings']));

			Yii::$app->eventLog->endRecord();

			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));

			return $this->refresh();
		}

		return $this->render('email', [
			'model' => $model,
		]);
	}

	/**
	 * Lists interface Setting model.
	 *
	 * @return mixed
	 */
	public function actionInterface()
	{
		$model = InterfaceSettingForm::findOne([
			'name' => 'interface',
			'type' => Setting::TYPE_APP,
			'status' => Setting::STATUS_ACTIVE,
			'deleted' => Setting::NO,
		]);
		if (!$model) {
			$model = new InterfaceSettingForm();
		}

		Yii::$app->eventLog
			->setData([
				'operation' => (Yii::$app->eventLog)::ACTION_UPDATE,
			])
			->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAppSettings']));

			Yii::$app->eventLog->endRecord();

			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));

			return $this->refresh();
		}

		return $this->render('interface', [
			'model' => $model,
		]);
	}

	/**
	 * Clears the application cache.
	 *
	 * @return mixed
	 */
	public function actionClearCache()
	{
		if (Yii::$app->cache->flush()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Cache has been cleared.'));
		} else {
			Yii::$app->session->setFlash('error', Yii::t('common', 'Cannot clear the cache.'));
		}
		Yii::$app->trigger('invalidate.cache');

		return $this->goBack(Yii::$app->request->referrer);
	}

	/**
	 * Finds the Setting model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @return \yii\db\ActiveRecord|Setting the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = Setting::findOne($id)) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
