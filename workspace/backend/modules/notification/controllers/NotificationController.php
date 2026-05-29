<?php

namespace backend\modules\notification\controllers;

use backend\controllers\MainController;
use backend\modules\notification\models\NotificationForm;
use backend\modules\notification\models\NotificationSearch;
use common\helpers\DateHelper;
use common\models\Notification;
use common\models\UserHasNotification;
use Yii;
use yii\caching\TagDependency;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class NotificationController extends MainController
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
						'actions' => ['index', 'view', 'dt-notifications', 'list', 'bulk-seen', 'mark-as-seen'],
						'roles' => ['viewNotification'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => ['createNotification'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => ['updateNotification'],
					],
					[
						'allow' => true,
						'actions' => ['delete', 'bulk-delete'],
						'roles' => ['deleteNotification'],
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
			'dt-notifications' => NotificationSearch::class,
		];
	}

	/**
	 * Lists all Notification models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		return $this->render('index');
	}

	/**
	 * Displays a list of notifications.
	 *
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException
	 * @throws \yii\base\InvalidConfigException
	 */
	public function actionList()
	{
		if (!Yii::$app->request->isAjax) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		$models = \common\models\Notification::findAllUnseen();

		return $this->asJson([
			'success' => true,
			'unseenNotifications' => count($models),
			'data' => $this->renderAjax('list', [
				'models' => $models,
			]),
		]);
	}

	/**
	 * Displays a single Notification model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$model = $this->findModel($id);
		$model->markAsSeen();

		return $this->render('view', [
			'model' => $model,
		]);
	}

	/**
	 * Creates a new Notification model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 * @throws \yii\db\Exception
	 */
	public function actionCreate()
	{
		$model = new NotificationForm();
		$model->created_by = Yii::$app->user->id;

		Yii::$app->eventLog->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->eventLog->endRecord();

			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been created.'));

			return $this->redirect(['view', 'id' => $model->id]);
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}

	/**
	 * Updates an existing Notification model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws \yii\db\Exception
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, NotificationForm::class);
		$model->updated_by = Yii::$app->user->id;

		Yii::$app->eventLog->beginRecord($model);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->eventLog->endRecord();

			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));

			return $this->redirect(['view', 'id' => $model->id]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}

	/**
	 * Deletes an existing Notification model.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param $id
	 * @return mixed
	 * @throws \Exception
	 * @throws \Throwable
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionDelete($id)
	{
		$model = $this->findModel($id);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'Record has been created.'),
			],
		];

		try {
			Yii::$app->eventLog->beginRecord($model);

			if ($result = $model->delete()) {
				FileHelper::removeDirectory(Yii::getAlias("@uploads/{$this->getParentModuleId()}/{$this->id}/{$id}"));

				TagDependency::invalidate(Yii::$app->cache, 'findAllNotifications');

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
	 * Bulk deletes existing Notification models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionBulkDelete()
	{
		$models = Notification::find()->where(['id' => Yii::$app->request->post('selection')]);
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
				FileHelper::removeDirectory(Yii::getAlias("@uploads/{$this->getParentModuleId()}/{$this->id}/{$deletedModel}"));
			}

			TagDependency::invalidate(Yii::$app->cache, 'findAllNotifications');

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
	 * Bulk marks as seen existing Notification models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionBulkSeen()
	{
		$result = UserHasNotification::updateAll([
			'seen' => Notification::YES,
			'updated_at' => DateHelper::formatAsDateTime('now'),
		], [
			'user_id' => Yii::$app->user->id,
			'notification_id' => Yii::$app->request->post('selection'),
		]);

		$responseData = [
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Records successfully updated.') :
				Yii::t('common', 'Cannot update the records.'),
		];

		if (Yii::$app->request->isAjax) {
			return $this->asJson($responseData);
		}

		Yii::$app->session->setFlash($responseData['success'] ? 'success' : 'error', $responseData['message']);

		return $this->redirect(['index']);
	}

	/**
	 * Bulk marks as seen all existing Notification models for the current user.
	 * If marking is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionMarkAsSeen()
	{
		$result = UserHasNotification::updateAll([
			'seen' => Notification::YES,
			'updated_at' => DateHelper::formatAsDateTime('now'),
		], [
			'user_id' => Yii::$app->user->id,
		]);

		$responseData = [
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Records successfully updated.') :
				Yii::t('common', 'Cannot update the records.'),
		];

		if (Yii::$app->request->isAjax) {
			return $this->asJson($responseData);
		}

		Yii::$app->session->setFlash($responseData['success'] ? 'success' : 'error', $responseData['message']);

		return $this->redirect(['index']);
	}

	/**
	 * Finds the Notification model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @return \yii\db\ActiveRecord|NotificationForm the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null)
	{
		$modelName = class_exists($modelName) ? $modelName : Notification::class;

		if (($model = $modelName::findOne($id)) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
