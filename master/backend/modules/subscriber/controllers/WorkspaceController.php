<?php

namespace backend\modules\subscriber\controllers;

use backend\controllers\MainController;
use backend\modules\subscriber\models\WorkspaceForm;
use backend\modules\subscriber\models\WorkspaceSearch;
use common\models\AuthAssignment;
use common\models\Workspace;
use common\models\WorkspaceDatabaseUpdateForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class WorkspaceController extends MainController
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
						'actions' => ['index', 'view', 'dt-workspaces'],
						'roles' => ['viewWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => ['createWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['update', 'reinstall', 'database-update', 'symlink-update'],
						'roles' => ['updateWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['delete', 'delete-file'],
						'roles' => ['deleteWorkspace'],
						'verbs' => ['POST'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => ['restoreWorkspace'],
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
			'dt-workspaces' => WorkspaceSearch::class,
		];
	}

	/**
	 * @inheritdoc
	 * @throws NotFoundHttpException if no model was found or the permissions are incorrect
	 */
	public function beforeAction($action)
	{
		return parent::beforeAction($action);
	}

	/**
	 * Lists all Workspace models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		if (Yii::$app->request->get('deleted') == Workspace::YES) {
			if (!Yii::$app->settings->get('enableSoftDelete') || !Yii::$app->user->can('restoreWorkspace')) {
				return $this->redirect(['index']);
			}
		}
		return $this->render('index');
	}

	/**
	 * Displays a single Workspace model.
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
	 * Creates a new Workspace model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new WorkspaceForm();
		$result = true;

		Yii::$app->eventLog->beginRecord($model);
		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			Yii::$app->eventLog->endRecord();

			$message = Yii::t('common', 'Record has been created.');
			if (Yii::$app->request->isAjax) {
				return $this->asJson([
					'success' => true,
					'message' => $message,
				]);
			}
			Yii::$app->session->setFlash('success', $message);

			return $this->redirect(['view', 'id' => $model->id]);
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => (bool) $result,
				'data' => $this->renderAjax('create', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('create', [
			'model' => $model,
		]);
	}

	/**
	 * Updates an existing Workspace model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, WorkspaceForm::class);
		$result = true;

		Yii::$app->eventLog->beginRecord($model);
		if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			Yii::$app->eventLog->endRecord();

			$message = Yii::t('common', 'Record has been updated.');
			if (Yii::$app->request->isAjax) {
				return $this->asJson([
					'success' => true,
					'message' => $message,
				]);
			}
			Yii::$app->session->setFlash('success', $message);

			return $this->redirect(['view', 'id' => $model->id]);
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => (bool) $result,
				'data' => $this->renderAjax('update', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('update', [
			'model' => $model,
		]);
	}


	/**
	 * Deletes existing Workspace models.
	 * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param null|int $id
	 * @return mixed
	 * @throws \Throwable
	 * @throws NotFoundHttpException if no model was found
	 */
	public function actionDelete($id = null)
	{
		$bodyParams = Yii::$app->request->post();
		$isPermanent = !Yii::$app->settings->get('enableSoftDelete') || ($bodyParams['dt_operation'] == 'delete-permanently' || $bodyParams['dt_bulk_operation'] == 'delete-permanently');
		if ($id === null) {
			$id = Yii::$app->request->post('selection');
		}
		$models = $this->findModel($id, null, true);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The delete operation was successful.'),
				'body' => [],
			],
		];
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$deletedModels = [];
			/** @var Workspace $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog
					->setData([
						'operation' => $isPermanent ? (Yii::$app->eventLog)::ACTION_DELETE : (Yii::$app->eventLog)::ACTION_SOFT_DELETE,
					])
					->beginRecord($model);
				if ($isPermanent && !$model->uninstall()) {
					throw new \Exception();
				}
				if ($model->delete($isPermanent)) {
					$deletedModels[] = $model->id;
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			if ($isPermanent) {
				foreach ($deletedModels as $deletedModel) {
					FileHelper::removeDirectory(Yii::getAlias("@uploads/{$this->id}/{$deletedModel}"));
				}
			}
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			$dbTransaction->commit();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'The delete operation was unsuccessful.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}
		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['index']);
	}

    /**
     * Update existing Workspace databases.
     *
     */
    public function actionDatabaseUpdate()
    {
        if (Yii::$app->user->identity->authAssignment->item_name != 'superAdmin') {
            return $this->redirect(['index']);
        }

        $model = new WorkspaceDatabaseUpdateForm();
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));

            $message = Yii::t('common', 'Record has been updated.');
            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['index']);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool) $result,
                'data' => $this->renderAjax('', [
                    'model' => $model,
                ]),
            ]);
        }

        return $this->render('database-update', [
            'model' => $model,
        ]);
    }

	/**
	 * Update existing Workspace symbolic links for static assets.
	 *
	 */
	public function actionSymlinkUpdate()
	{
		if (Yii::$app->user->identity->authAssignment->item_name != 'superAdmin') {
			return $this->redirect(['index']);
		}

		$models = Workspace::find()
			->where([
				'status' => Workspace::STATUS_ACTIVE,
				'deleted' => Workspace::NO,
			])
			->all();
		if (!empty($models)) {
			foreach ($models as $model) {
				try {
					$dirPath = $model->getDirectoryPath();
					$targets = [
						Yii::getAlias("@workspace/backend/web/assets") => "{$dirPath}/backend/web/assets",
						Yii::getAlias("@workspace/backend/web/audio") => "{$dirPath}/backend/web/audio",
						Yii::getAlias("@workspace/backend/web/img") => "{$dirPath}/backend/web/img",
						Yii::getAlias("@workspace/backend/web/img/flags") => "{$dirPath}/backend/web/img/flags",
						Yii::getAlias("@workspace/backend/web/img/ico") => "{$dirPath}/backend/web/img/ico",
						Yii::getAlias("@workspace/backend/web/img/tpl") => "{$dirPath}/backend/web/img/tpl",
						Yii::getAlias("@workspace/backend/web/css") => "{$dirPath}/backend/web/css",
						Yii::getAlias("@workspace/backend/web/js") => "{$dirPath}/backend/web/js",
						Yii::getAlias("@workspace/frontend/web/assets") => "{$dirPath}/frontend/web/assets",
						Yii::getAlias("@workspace/frontend/web/img") => "{$dirPath}/frontend/web/img",
						Yii::getAlias("@workspace/frontend/web/img/flags") => "{$dirPath}/frontend/web/img/flags",
						Yii::getAlias("@workspace/frontend/web/img/ico") => "{$dirPath}/frontend/web/img/ico",
						Yii::getAlias("@workspace/frontend/web/css") => "{$dirPath}/frontend/web/css",
						Yii::getAlias("@workspace/frontend/web/fonts") => "{$dirPath}/frontend/web/fonts",
						Yii::getAlias("@workspace/frontend/web/js") => "{$dirPath}/frontend/web/js",
					];
					foreach (array_values($targets) as $target) {
						// Get all items (files and directories) in the specified directory
						$items = FileHelper::findFiles($target, [
							'only' => ['*'], // You can specify file extensions or patterns if needed
							'recursive' => true, // Set to true if you want to search in subdirectories
						]);
						// Filter out only symbolic links
						$symlinks = array_filter($items, 'is_link');
						// Remove each symbolic link
						foreach ($symlinks as $symlink) {
							unlink($symlink);
						}
					}
					// Create symbolic links for static assets
					\tws\helpers\FileHelper::symlink($targets);
					Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
				} catch(\Exception $e) {
					$errors[] = $e->getMessage();
					continue;
				}
			}
		}
		if (!empty($errors)) {
			$message = implode('<br>', $errors);
			Yii::$app->session->setFlash('error', $message);
		} else {
			$message = Yii::t('common', 'Records successfully updated.');
			if (Yii::$app->request->isAjax) {
				return $this->asJson([
					'success' => true,
					'message' => $message,
				]);
			}
			Yii::$app->session->setFlash('success', $message);
		}
		return $this->redirect(['index']);
	}

	/**
	 * Restores Workspace models that are marked as deleted.
	 * If restoration is successful, JSON is returned or the browser will be redirected to the 'index' page.
	 *
	 * @param null|int $id
	 * @return mixed
	 * @throws NotFoundHttpException if no model was found
	 */
	public function actionRestore($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->post('selection');
		}
		$models = $this->findModel($id, null, true);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The restore operation was successful.'),
				'body' => [],
			],
		];
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			/** @var Workspace $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog->beginRecord($model);
				if ($model->restore()) {
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			$dbTransaction->commit();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'The restore operation was unsuccessful.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}
		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['index']);
	}

	/**
	 * Reinstalls the Workspace application.
	 * If update is successful or not, the browser will refresh the current page.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws \Exception
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionReinstall($id)
	{
		$model = $this->findModel($id);
		$response = [
			'success' => true,
			'message' => [
				'title' => Yii::t('common', 'The workspace has been reinstalled.'),
			],
		];

		Yii::$app->eventLog->beginRecord($model);
		if ($model->install(true)) {
			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			Yii::$app->trigger('workspace.afterReinstall', new \yii\base\Event(['sender' => $model]));
			Yii::$app->eventLog->endRecord();
		} else {
			$response['success'] = false;
			$response['message']['title'] = Yii::t('common', 'Cannot reinstall the workspace.');
		}

		if (Yii::$app->request->isAjax) {
			return $this->asJson($response);
		}

		Yii::$app->session->setFlash($response['success'] ? 'success' : 'error', [$response['message']]);

		return $this->redirect(['view', 'id' => $model->id]);
	}

	/**
	 * Finds the Workspace model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Workspace|WorkspaceForm
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Workspace::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => Workspace::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
