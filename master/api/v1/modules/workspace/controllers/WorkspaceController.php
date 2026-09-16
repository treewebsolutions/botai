<?php

namespace api\v1\modules\workspace\controllers;

use api\v1\modules\workspace\models\Workspace;
use api\v1\modules\workspace\services\WorkspaceService;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

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
						'roles' => ['viewWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['delete'],
						'roles' => ['deleteWorkspace'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => ['restoreWorkspace'],
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
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|Workspace the loaded model
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
