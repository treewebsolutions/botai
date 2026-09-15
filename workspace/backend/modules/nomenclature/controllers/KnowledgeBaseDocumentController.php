<?php

namespace backend\modules\nomenclature\controllers;

use backend\controllers\MainController;
use backend\modules\nomenclature\models\KnowledgeBaseDocumentForm;
use backend\modules\nomenclature\models\KnowledgeBaseDocumentSearch;
use common\models\KnowledgeBaseDocument;
use common\services\OpenAiDocumentVectorStoreService;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;

class KnowledgeBaseDocumentController extends MainController
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
						'actions' => ['index', 'view', 'download', 'dt-knowledge-base-documents'],
						'roles' => ['viewKnowledgeBaseDocument'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => ['createKnowledgeBaseDocument'],
					],
					[
						'allow' => true,
						'actions' => ['update', 'reindex'],
						'roles' => ['updateKnowledgeBaseDocument'],
					],
					[
						'allow' => true,
						'actions' => ['delete'],
						'roles' => ['deleteKnowledgeBaseDocument'],
						'verbs' => ['POST'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => ['restoreKnowledgeBaseDocument'],
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
			'dt-knowledge-base-documents' => KnowledgeBaseDocumentSearch::class,
		];
	}

	/**
	 * Lists all KnowledgeBaseDocument models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		if (Yii::$app->request->get('deleted') == KnowledgeBaseDocument::YES) {
			if (!Yii::$app->settings->get('enableSoftDelete') || !Yii::$app->user->can('restoreKnowledgeBaseDocument')) {
				return $this->redirect(['index']);
			}
		}
		return $this->render('index');
	}

	/**
	 * Displays a single KnowledgeBaseDocument model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionView($id)
	{
		$model = $this->findModel($id);

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => true,
				'data' => $this->renderAjax('view', [
					'model' => $model,
				]),
			]);
		}

		return $this->render('view', [
			'model' => $model,
		]);
	}

	/**
	 * Sends the stored file to the browser.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model or its file cannot be found
	 */
	public function actionDownload($id)
	{
		$model = $this->findModel($id);
		$filePath = $model->getFilePath();
		if ($filePath === null || !is_file($filePath)) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		return Yii::$app->response->sendFile($filePath, $model->file);
	}

	/**
	 * Creates a new KnowledgeBaseDocument model (uploads the file and queues its indexing).
	 *
	 * @return mixed
	 */
	public function actionCreate()
	{
		$model = new KnowledgeBaseDocumentForm();
		$model->knowledge_base_id = Yii::$app->request->get('knowledge_base_id');
		$result = true;

		Yii::$app->eventLog->beginRecord($model);
		if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
			Yii::$app->eventLog->endRecord();

			$message = Yii::t('common', 'Record has been created.') . ' ' . Yii::t('common', 'The document has been queued for indexing.');
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
	 * Updates an existing KnowledgeBaseDocument model.
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, KnowledgeBaseDocumentForm::class);
		$result = true;

		Yii::$app->eventLog->beginRecord($model);
		if ($model->load(Yii::$app->request->post()) && ($result = $model->save())) {
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
	 * Uploads the document to its vector store again (after an error, or after the knowledge
	 * base got its vector store).
	 *
	 * @param integer $id
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionReindex($id)
	{
		$model = $this->findModel($id);
		OpenAiDocumentVectorStoreService::scheduleSync($model->id);

		$message = Yii::t('common', 'The document has been queued for indexing.');
		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => true,
				'message' => $message,
			]);
		}
		Yii::$app->session->setFlash('success', $message);

		return $this->redirect(['view', 'id' => $model->id]);
	}

	/**
	 * Deletes existing KnowledgeBaseDocument models. A soft delete withdraws the document from the
	 * vector store; a permanent delete also removes the file and purges the OpenAI objects.
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
			$purgeMeta = [];
			/** @var KnowledgeBaseDocument $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog
					->setData([
						'operation' => $isPermanent ? (Yii::$app->eventLog)::ACTION_DELETE : (Yii::$app->eventLog)::ACTION_SOFT_DELETE,
					])
					->beginRecord($model);
				$purgeMeta[$model->id] = $isPermanent ? OpenAiDocumentVectorStoreService::describeRemote($model) : null;
				if ($model->delete($isPermanent)) {
					$deletedModels[] = $model->id;
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			if ($isPermanent) {
				foreach ($deletedModels as $deletedModel) {
					FileHelper::removeDirectory(Yii::getAlias("@uploads/knowledge-base-document/{$deletedModel}"));
				}
			}
			$dbTransaction->commit();
			foreach ($deletedModels as $deletedModel) {
				if ($isPermanent) {
					OpenAiDocumentVectorStoreService::scheduleRemotePurge($purgeMeta[$deletedModel] ?? null);
				} else {
					OpenAiDocumentVectorStoreService::scheduleWithdraw($deletedModel);
				}
			}
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
	 * Restores KnowledgeBaseDocument models that are marked as deleted and queues their indexing.
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
			$restoredModels = [];
			/** @var KnowledgeBaseDocument $model */
			foreach ($models->each() as $model) {
				Yii::$app->eventLog->beginRecord($model);
				if ($model->restore()) {
					$restoredModels[] = $model->id;
					Yii::$app->eventLog->endRecord();
				} else {
					throw new \Exception();
				}
			}
			$dbTransaction->commit();
			foreach ($restoredModels as $restoredModel) {
				OpenAiDocumentVectorStoreService::scheduleSync($restoredModel);
			}
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
	 * Finds the KnowledgeBaseDocument model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|KnowledgeBaseDocument|KnowledgeBaseDocumentForm
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : KnowledgeBaseDocument::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => KnowledgeBaseDocument::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
