<?php

namespace api\v1\modules\workspace\controllers;

use api\v1\modules\workspace\models\Workspace;
use api\v1\modules\workspace\models\WorkspaceForm;
use api\v1\modules\workspace\services\WorkspaceService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;

class WorkspaceController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $modelClass = 'api\v1\modules\workspace\models\Workspace';

	private WorkspaceService $workspaceService;

	public function __construct($id, $module, WorkspaceService $workspaceService, $config = [])
	{
		parent::__construct($id, $module, $config);
		$this->workspaceService = $workspaceService;
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$role = Yii::$app->request->bodyParams['bypass'] ? '@' : '';
		return ArrayHelper::merge(parent::behaviors(), [
			'authenticator' => [
				'class' => HttpBearerAuth::class,
			],
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['sync'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['index', 'view'],
						'roles' => [$role ?: 'viewWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => [$role ?: 'createWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => [$role ?: 'updateWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['delete'],
						'roles' => [$role ?: 'deleteWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => [$role ?: 'restoreWorkspace'],
					],
				],
			],
		]);
	}

	/**
	 * Sync [[Workspace]] models.
	 *
	 * @return mixed
	 */
	public function actionSync()
	{
		$requestParams = Yii::$app->request->bodyParams;
		return Workspace::syncData($requestParams);
	}

	/**
	 * Displays all [[Workspace]] models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$requestParams = Yii::$app->request->queryParams;
		if (empty($requestParams)) {
			$requestParams = Yii::$app->request->bodyParams;
		}

		return $this->workspaceService->list($requestParams);
	}

	/**
	 * Displays a single [[Workspace]] model.
	 *
	 * @param int|string $id The Workspace model ID.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 * @throws ForbiddenHttpException if workspace does not have the proper permissions
	 */
	public function actionView($id)
	{
		return $this->findModel($id);
	}

	/**
	 * Creates a new [[Workspace]] model.
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to create the model
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionCreate()
	{
		$model = new WorkspaceForm();

		try {
			if ($model->load(Yii::$app->request->bodyParams, '') && $model->save()) {
				Yii::$app->response->statusCode = 201;
				$data = $model->attributes;
				return [
					'message' => Yii::t('api', 'Record successfully created.'),
					'data' => $data,
				];
			}

			if ($model->hasErrors()) {
				throw new UnprocessableEntityHttpException();
			}

			throw new ServerErrorHttpException();
		} catch (\Throwable $e) {
			Yii::$app->response->statusCode = $e->statusCode ?? 500;
			Yii::$app->response->data['message'] = Yii::t('api', 'Record creation failed.');
			Yii::$app->response->data['errors'] = $model->getErrors();
		}

		return Yii::$app->response;
	}

	/**
	 * Updates an existing [[Workspace]] model.
	 *
	 * @param int|string $id The Workspace model ID.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to update the model
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, WorkspaceForm::class);
		try {
			if ($model->load(Yii::$app->request->bodyParams, '') && $model->save()) {
				$data = $model->attributes;
				return [
					'message' => Yii::t('api', 'Record successfully updated.'),
					'data' => $data,
				];
			}

			if ($model->hasErrors()) {
				throw new UnprocessableEntityHttpException();
			}

			throw new ServerErrorHttpException();
		} catch (\Exception $e) {
			Yii::$app->response->statusCode = $e->statusCode ?? 500;
			Yii::$app->response->data['message'] = Yii::t('api', 'Record update failed.');
			Yii::$app->response->data['errors'] = $model->getErrors();
		}
		return Yii::$app->response;
	}

	/**
	 * Deletes an existing [[Workspace]] model.
	 * Handles bulk delete by sending an array of model IDs in bodyParams.
	 *
	 * @param int|null $id The [[Workspace]] model ID. If this is null, the request bodyParams will be used instead.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model(s) cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to delete the [[Workspace]]
	 */
	public function actionDelete($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->bodyParams;
		}
		$models = $this->findModel($id, null, true);

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$deletedModels = [];
			/** @var Workspace $model */
			foreach ($models->each() as $model) {
				$isPermanent = $model->deleted ?: !Yii::$app->settings->get('enableSoftDelete');
				if ($model->delete($isPermanent)) {
					$deletedModels[] = $model->id;
				} else {
					throw new \Exception();
				}
			}

			Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllWorkspaces']));
			$dbTransaction->commit();

			Yii::$app->response->statusCode = 204;
			return [
				'message' => count($deletedModels) === 1 ?
					Yii::t('api', 'Record successfully deleted.') :
					Yii::t('api', 'Records successfully deleted.')
			];
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
		}

		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Restores [[Workspace]] models that are marked as deleted.
	 * @param int|null $id The [[Workspace]] model ID. If this is null, the request bodyParams will be used instead.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model(s) with deleted = 1 cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to restore the [[Workspace]]
	 */
	public function actionRestore($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->bodyParams;
		}
		$models = $this->findModel($id, null, true);

		if (!Workspace::find()->andWhere([
			'id' => $id,
			'deleted' => Workspace::YES,
		])->count()) {
			throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
		}

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$restoredModels = [];
			/** @var Workspace $model */
			foreach ($models->each() as $model) {
				if ($model->deleted = Workspace::YES) {
					if ($model->restore()) {
						$restoredModels[] = $model->id;
						$data[] = $model;
					} else {
						throw new \Exception();
					}
				}
			}

			$dbTransaction->commit();

			return [
				'message' => count($restoredModels) === 1 ?
					Yii::t('api', 'Record successfully restored.') :
					Yii::t('api', 'Records successfully restored.'),
				'data' => $data,
			];
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
		}

		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Finds the [[Workspace]] model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param int $id
	 * @param \yii\db\ActiveRecord|string|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Workspace|WorkspaceForm the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : Workspace::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => Workspace::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
	}
}
