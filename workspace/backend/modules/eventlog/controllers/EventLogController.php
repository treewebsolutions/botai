<?php

namespace backend\modules\eventlog\controllers;

use backend\controllers\MainController;
use backend\modules\eventlog\models\EventLogSearch;
use common\models\EventLog;
use Yii;
use yii\caching\TagDependency;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class EventLogController extends MainController
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
						'actions' => ['index', 'view', 'dt-event-logs'],
						'roles' => ['viewEventLog'],
					],
					[
						'allow' => true,
						'actions' => ['delete', 'bulk-delete'],
						'roles' => ['deleteEventLog'],
						'verbs' => ['POST'],
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
			'dt-event-logs' => EventLogSearch::class,
		];
	}

	/**
	 * Lists all EventLog models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		return $this->render('index');
	}

	/**
	 * Displays a single EventLog model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$eventLog = $this->findModel($id);
		$model = $eventLog->model ? ($eventLog->model)::findOne($eventLog->model_key) : null;
		$module = Yii::$app->getModule($eventLog->module);

		// Get the targeted module event-log view or fallback to the default view from this controller
		$viewPath = "{$module->viewPath}/{$eventLog->controller}";
		$viewName = "{$this->module->viewName}.php";
		$view = FileHelper::normalizePath("{$viewPath}/{$viewName}");
		if (is_file($view)) {
			$view = str_replace(Yii::getAlias('@app'), '@app/', $viewPath) . "/{$viewName}";
		} else {
			$view = 'view';
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => true,
				'data' => $this->renderAjax($view, [
					'model' => $model,
					'eventLog' => $eventLog,
					'viewPath' => $viewPath,
				]),
			]);
		}

		return $this->render($view, [
			'model' => $model,
			'eventLog' => $eventLog,
			'viewPath' => $viewPath,
		]);
	}

	/**
	 * Deletes an existing EventLog model.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param $id
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionDelete($id)
	{
		$model = $this->findModel($id);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'Record successfully deleted.'),
			],
		];

		try {
			Yii::$app->eventLog->beginRecord($model);

			if ($result = $model->delete()) {
				FileHelper::removeDirectory(Yii::getAlias("@runtime/eventlogs/{$id}"));

				TagDependency::invalidate(Yii::$app->cache, 'findAllEventLogs');

				Yii::$app->eventLog->endRecord();
			}
		} catch (\Exception $e) {
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'Cannot delete the record.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}

		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['index']);
	}

	/**
	 * Bulk deletes existing EventLog models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionBulkDelete()
	{
		$models = EventLog::find()->where(['id' => Yii::$app->request->post('selection')]);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'Records successfully deleted.'),
			],
		];

		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$deletedModels = [];

			foreach ($models->each() as $model) {
				Yii::$app->eventLog
					->setData([
						'operation' => (Yii::$app->eventLog)::ACTION_DELETE,
					])
					->beginRecord($model);

				if ($model->delete()) {
					$deletedModels[] = $model->id;

					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}

			foreach ($deletedModels as $deletedModel) {
				FileHelper::removeDirectory(Yii::getAlias("@runtime/eventlogs/{$deletedModel}"));
			}

			TagDependency::invalidate(Yii::$app->cache, 'findAllEventLogs');

			$transaction->commit();
		} catch (\Exception $e) {
			$transaction->rollBack();

			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'Cannot delete the records.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}

		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['index']);
	}

	/**
	 * Finds the EventLog model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @return \yii\db\ActiveRecord|EventLog the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		$model = EventLog::find()->where([
			'id' => $id,
			'deleted' => EventLog::NO,
		]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
