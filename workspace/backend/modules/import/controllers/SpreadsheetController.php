<?php

namespace backend\modules\import\controllers;

use backend\controllers\MainController;
use backend\modules\import\models\ColumnForm;
use backend\modules\import\models\ColumnSearch;
use backend\modules\import\models\FileForm;
use backend\modules\import\models\ImportForm;
use backend\modules\import\models\SheetForm;
use common\models\ImportFile;
use common\models\ImportSheet;
use Yii;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use tws\helpers\Url;
use yii\web\NotFoundHttpException;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

/**
 * SpreadsheetController implements the CRUD actions for File model.
 */
class SpreadsheetController extends MainController
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
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'start' => ['GET'],
					'delete' => ['POST'],
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
			'dt-columns' => ColumnSearch::class,
		];
	}

	/**
	 * Renders a specific action or the last action from the session.
	 *
	 * @return mixed
	 * @throws \Throwable
	 * @throws \yii\web\NotFoundHttpException
	 */
	public function actionIndex()
	{
		if (!Yii::$app->request->isAjax) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}
		// Valid controller actions pairs
		$actions = [
			'upload' => 'actionUpload',
			'sheet' => 'actionSheet',
			'map' => 'actionMap',
			'import' => 'actionImport',
		];
		$action = $actions['upload'];
		$id = null;
		// Check the session and render the last step
		if ($spreadsheetSteps = Yii::$app->session->get('SpreadsheetSteps', [])) {
			$currentAction = $spreadsheetSteps[count($spreadsheetSteps) - 1];
			$action = $actions[$currentAction['name']];
			$id = $currentAction['id'];
		}
		// Exit if the action does not exist in this controller
		if (!method_exists($this, $action)) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		return $this->$action($id);
	}

	/**
	 * Creates a new ImportFile model.
	 * If creation is successful, the browser will be redirected to the 'sheet' page.
	 *
	 * @return mixed
	 * @throws \yii\db\Exception
	 * @throws \Throwable
	 */
	public function actionUpload()
	{
		// Get the widget configuration from the current session
		$spreadsheetImport = Yii::$app->session->get('SpreadsheetImport');
		$spreadsheetSteps = Yii::$app->session->get('SpreadsheetSteps');

		$model = new FileForm();
		$model->model = $spreadsheetImport['model'];
		if ($spreadsheetSteps && Yii::$app->request->post('FileForm[file_id]')) {
			$model->file_id = $spreadsheetSteps[0]['id'];
		}

		if ($model->load(Yii::$app->request->post()) && ($newModel = $model->saveModel())) {
			TagDependency::invalidate(Yii::$app->cache, 'findAllFiles');

			// Save the next step to the current session
			self::updateStepsSession(0, [
				'name' => 'upload',
				'url' => Url::to(['upload'], true),
				'id' => $newModel->id,
			]);
			// Save the next step to the current session
			self::updateStepsSession(1, [
				'name' => 'sheet',
				'url' => Url::to(['sheet', 'id' => $newModel->id], true),
				'id' => $newModel->id,
			]);

			return $this->actionSheet($newModel->id);
		}

		return $this->asJson([
			'success' => true,
			'data' => $this->renderAjax('upload', [
				'model' => $model,
			]),
			'steps' => Yii::$app->session->get('SpreadsheetSteps'),
		]);
	}

	/**
	 * Updates or create a new ImportSheet model.
	 * If update or creation is successful, the browser will be redirected to the 'map' page.
	 *
	 * @param integer $id The file id.
	 * @return mixed
	 * @throws \Throwable
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	public function actionSheet($id)
	{
		$fileModel = ImportFile::findOne([
			'id' => $id,
			'status' => ImportFile::STATUS_ACTIVE,
			'deleted' => ImportFile::NO,
		]);
		if (!$fileModel) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		$documentSheets = [];
		$file = Yii::getAlias("@uploads/import/file/{$fileModel->id}/{$fileModel->file}");
		if (is_file($file)) {
			$reader = self::getReader($file);
			$reader->open($file);
			foreach ($reader->getSheetIterator() as $spreadsheetIndex => $spreadsheet) {
				if (strpos($spreadsheet->getName(), 'Hidden_') === false) {
					$documentSheets[$spreadsheetIndex] = $spreadsheet->getName();
				}
			}
			$reader->close();
		}

		$model = new SheetForm();
		$model->file_id = $id;

		// Use the existing ImportSheet model
		if ($bodyParams = Yii::$app->request->post($model->formName())) {
			$existingModel = SheetForm::findOne([
				'file_id' => $id,
				'number' => $bodyParams['number'],
				'deleted' => SheetForm::NO,
			]);
			if ($existingModel) {
				$model = $existingModel;
			}
		}

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			// Save the next step to the current session
			self::updateStepsSession(2, [
				'name' => 'map',
				'url' => Url::to(['map', 'id' => $model->id], true),
				'id' => $model->id,
			]);

			return $this->actionMap($model->id);
		}

		return $this->asJson([
			'success' => true,
			'data' => $this->renderAjax('sheet', [
				'model' => $model,
				'documentSheets' => $documentSheets,
				'file_id' => $id,
			]),
			'steps' => Yii::$app->session->get('SpreadsheetSteps'),
		]);
	}

	/**
	 * Updates or create multiple ImportColumn models.
	 * If update or creation is successful, the browser will be redirected to the 'import' page.
	 *
	 * @param integer $id The sheet id.
	 * @return mixed
	 * @throws \Throwable
	 */
	public function actionMap($id)
	{
		$spreadsheetImport = Yii::$app->session->get('SpreadsheetImport');
		$sheetModel = ImportSheet::findOne($id);

		$sheetColumns = [];
		$file = Yii::getAlias("@uploads/import/file/{$sheetModel->file_id}/{$sheetModel->file->file}");
		if (is_file($file)) {
			$reader = self::getReader($file);
			$reader->open($file);
			foreach ($reader->getSheetIterator() as $spreadsheetIndex => $spreadsheet) {
				if ($spreadsheetIndex == $sheetModel->number) {
					foreach ($spreadsheet->getRowIterator() as $rowIndex => $row) {
						if ($rowIndex == $sheetModel->header) {
							$sheetColumns = $row;
							break 2;
						}
					}
				}
			}
			$reader->close();
		}

		// Find or create new models
		$models = ColumnForm::findAll([
			'sheet_id' => $id,
			'deleted' => ColumnForm::NO,
		]);

		if (empty($models)) {
			foreach ($spreadsheetImport['columns'] as $index => $column) {
				$model = new ColumnForm();
				$model->sheet_id = $id;
				$model->target = $column;
				$model->source = $sheetColumns[$index];
				$model->source_index = $index;
				$model->field_type = ColumnForm::FIELD_TYPE_STRING;
				$model->sort_order = 0;

				$models[] = $model;
			}
		}

		if (Model::loadMultiple($models, Yii::$app->request->post()) && Model::validateMultiple($models)) {
			foreach ($models as $model) {
				$model->save();
			}
			// Save the next step to the current session
			self::updateStepsSession(3, [
				'name' => 'import',
				'url' => Url::to(['import', 'id' => $id], true),
				'id' => $id,
			]);

			return $this->actionImport($id);
		}

		return $this->asJson([
			'success' => true,
			'data' => $this->renderAjax('map', [
				'models' => $models,
				'sheet_id' => $id,
				'sheetColumns' => $sheetColumns,
				'spreadsheetImport' => $spreadsheetImport,
			]),
			'steps' => Yii::$app->session->get('SpreadsheetSteps'),
		]);
	}

	/**
	 * Creates multiple ActiveRecord models based on the model provided as the SpreadsheetImport widget parameter.
	 * If creation is successful, the browser will be redirected to the 'returnUrl' provided as widget parameter.
	 *
	 * @param integer $id The sheet id.
	 * @return mixed
	 * @throws \Throwable
	 */
	public function actionImport($id)
	{
		$spreadsheetImport = Yii::$app->session->get('SpreadsheetImport');

		$model = new ImportForm();
		$model->sheet_id = $id;
		$model->spreadsheetImport = $spreadsheetImport;

		if ($model->load(Yii::$app->request->post())) {
			if ($model->save()) {
				// Remove the configurations from the current session
				Yii::$app->session->remove('SpreadsheetImport');
				Yii::$app->session->remove('SpreadsheetSteps');

				Yii::$app->session->setFlash('success', Yii::t('import', 'Records have been imported.'));

				return $this->asJson([
					'success' => true,
					'returnUrl' => $spreadsheetImport['returnUrl'],
				]);
			} else {
				return $this->asJson([
					'success' => false,
					'message' => $model->hasErrors() ?
						implode('<br/>', $model->getErrorSummary(true)) :
						Yii::t('import', 'Cannot import the file. Please review the data mapping.'),
				]);
			}
		}

		return $this->asJson([
			'success' => true,
			'data' => $this->renderAjax('import', [
				'model' => $model,
				'sheet_id' => $id,
			]),
			'steps' => Yii::$app->session->get('SpreadsheetSteps'),
		]);
	}

	/**
	 * Updates the input steps session.
	 *
	 * @param int $stepIndex
	 * @param $data
	 * @return array|mixed
	 */
	protected function updateStepsSession($stepIndex, $data)
	{
		// Get steps from the current session
		$steps = Yii::$app->session->get('SpreadsheetSteps', []);

		// Remove all steps attributes starting from a specific step
		for ($i = $stepIndex, $stepsCount = count($steps); $i < $stepsCount; $i++) {
			unset($steps[$i]);
		}
		// Update the steps session data
		$steps[$stepIndex] = $data;
		Yii::$app->session->set('SpreadsheetSteps', $steps);

		return $steps;
	}

	/**
	 * Gets the spreadsheet reader instance.
	 *
	 * @param string $fileType
	 * @return \Box\Spout\Reader\ReaderInterface|null
	 * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
	 */
	protected static function getReader($fileType)
	{
		$fileType = mb_strtolower(end(explode('.', $fileType)));
		$reader = null;

		switch ($fileType) {
			case Type::XLSX:
				$reader = ReaderFactory::create(Type::XLSX);
				break;
			case Type::CSV:
				$reader = ReaderFactory::create(Type::CSV);
				break;
			case Type::ODS:
				$reader = ReaderFactory::create(Type::ODS);
				break;
			default:
				$reader = ReaderFactory::create(Type::XLSX);
				break;
		}

		return $reader;
	}
}
