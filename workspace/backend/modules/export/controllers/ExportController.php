<?php

namespace backend\modules\export\controllers;

use backend\controllers\MainController;
use backend\modules\export\widgets\export\Export;
use Box\Spout\Common\Type;
use Box\Spout\Writer\Style\StyleBuilder;
use Box\Spout\Writer\WriterFactory;
use common\helpers\Inflector;
use common\helpers\UploadHelper;
use JeroenDesloovere\VCard\Formatter\VcfFormatter;
use JeroenDesloovere\VCard\VCard;
use kartik\mpdf\Pdf;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class ExportController extends MainController
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
					'index' => ['POST'],
				],
			],
		];
	}

	/**
	 * Exports data to a specific file format.
	 *
	 * @return \yii\web\Response
	 */
	public function actionIndex()
	{
		try {
			$bodyParams = Yii::$app->request->post();
			$dataTable = $bodyParams['dataTable'];
			$result = null;

			// Create a new DataTableAction model
			$model = $this->getDataTableActionModel($bodyParams['model']);
			// Set the externalFilters
			if (is_array($dataTable['external_filters']) && !empty($dataTable['external_filters'])) {
				$model->externalFilters = ArrayHelper::map($dataTable['external_filters'], 'name', 'value');
			}
			$model->applyFilter($model->query, $dataTable['columns'], $dataTable['search']);
			$model->applyOrder($model->query, $dataTable['columns'], $dataTable['order']);

			// Get data, then filter the columns to be exported
			if (method_exists($model, 'getExportData')) {
				$records = $model->getExportData($model->query, []);
			} else {
				$records = $model->formatData($model->query, []);
			}
			$columns = array_filter($dataTable['columns'], function ($item) {
				return !empty($item['name']) && $item['visible'] == 'true';
			});

			// Export data to a specific format
			if ($bodyParams['format'] == Export::FORMAT_CSV) {
				$result = $this->exportAsCsv($bodyParams, $columns, $records);
			} elseif ($bodyParams['format'] == Export::FORMAT_VCF) {
				$result = $this->exportAsVcf($bodyParams, $columns, $records);
			} elseif ($bodyParams['format'] == Export::FORMAT_XLSX) {
				$result = $this->exportAsXlsx($bodyParams, $columns, $records);
			} elseif ($bodyParams['format'] == Export::FORMAT_PDF) {
				$result = $this->exportAsPdf($bodyParams, $columns, $records);
			}

			if (empty($result)) {
				throw new \Exception(Yii::t('common', 'Cannot export the requested data.'));
			}

			return $this->asJson([
				'success' => true,
				'returnUrl' => $result,
			]);
		} catch (\Exception $e) {
			return $this->asJson([
				'success' => false,
				'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 * Export as CSV.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return string|bool
	 * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
	 * @throws \Box\Spout\Common\Exception\IOException
	 * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
	 * @throws \Box\Spout\Common\Exception\SpoutException
	 * @throws \yii\base\ErrorException
	 */
	protected function exportAsCsv($bodyParams, $columns, $records)
	{
		/** @var \Box\Spout\Writer\CSV\Writer $writer */
		$writer = WriterFactory::create(Type::CSV);

		if (isset($bodyParams['config']['fieldDelimiter'])) {
			$writer->setFieldDelimiter($bodyParams['config']['fieldDelimiter']);
		}
		if (isset($bodyParams['config']['fieldEnclosure'])) {
			$writer->setFieldEnclosure($bodyParams['config']['fieldEnclosure']);
		}
		if (isset($bodyParams['config']['shouldAddBom'])) {
			$writer->setShouldAddBOM($bodyParams['config']['shouldAddBom'] == 'true' ? true : false);
		}

		// Ensure the file path
		// Ensure the file path
		$filePath = 'export/' . Yii::$app->user->id;
		$title = Inflector::slug($bodyParams['title']);
		$fileName = "{$title}-" . date('Y-m-d') . ".xlsx";
		FileHelper::removeDirectory(Yii::getAlias("@uploads/{$filePath}"));
		FileHelper::createDirectory(Yii::getAlias("@uploads/{$filePath}"));

		// Write data to the file
		$writer->openToFile(Yii::getAlias("@uploads/{$filePath}/{$fileName}"));
		$writer->addRow(ArrayHelper::getColumn($columns, 'title'));

		foreach ($records as $record) {
			$row = [];

			foreach ($columns as $column) {
				$row[] = strip_tags(html_entity_decode($record[$column['name']]));
			}

			$writer->addRow($row);
		}

		$writer->close();

		return UploadHelper::getFileUrl("{$filePath}/{$fileName}");
	}

	/**
	 * Export as VCF.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return string|bool
	 * @throws \yii\base\ErrorException
	 * @throws \JeroenDesloovere\VCard\Exception\VCardException
	 */
	protected function exportAsVcf($bodyParams, $columns, $records)
	{
		// Ensure the file path
		$filePath = 'export/' . Yii::$app->user->id;
		$fileName = "{$bodyParams['title']}-" . date('Y-m-d');
		FileHelper::removeDirectory(Yii::getAlias("@uploads/{$filePath}"));
		UploadHelper::ensureDirectoryTree($filePath);

		// Write data to the file
		$formatter = new \JeroenDesloovere\VCard\Formatter\Formatter(new VcfFormatter(), $fileName);

		foreach ($records as $i => $record) {
			$vCard = new VCard();

			foreach ($bodyParams['config']['map'] as $key => $attributes) {
				$attributes = is_array($attributes) ? $attributes : (array) $attributes;

				foreach ($attributes as $attribute) {
					$value = strip_tags(html_entity_decode($record[$attribute]));

					if (empty($value) || htmlentities($value) == '&mdash;') {
						continue;
					}

					switch ($key) {
						case 'name':
							$vCard->add(new \JeroenDesloovere\VCard\Property\FullName($value));
							break;
						case 'phone':
							$vCard->add(new \JeroenDesloovere\VCard\Property\Telephone($value));
							break;
						case 'email':
							$vCard->add(new \JeroenDesloovere\VCard\Property\Email($value));
							break;
						default:
							break;
					}
				}
			}

			$formatter->addVCard($vCard);
		}

		$formatter->save(Yii::getAlias("@uploads/{$filePath}"));

		return UploadHelper::getFileUrl("{$filePath}/{$fileName}.vcf");
	}

	/**
	 * Export as XLSX.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return string|bool
	 * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
	 * @throws \Box\Spout\Common\Exception\IOException
	 * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
	 * @throws \Box\Spout\Common\Exception\SpoutException
	 * @throws \yii\base\ErrorException
	 */
	protected function exportAsXlsx($bodyParams, $columns, $records)
	{
		/** @var \Box\Spout\Writer\XLSX\Writer $writer */
		$writer = WriterFactory::create(Type::XLSX);

		if (isset($bodyParams['config']['shouldCreateNewSheetsAutomatically'])) {
			$writer->setShouldCreateNewSheetsAutomatically($bodyParams['config']['shouldCreateNewSheetsAutomatically']);
		}
		if (isset($bodyParams['config']['shouldUseInlineStrings'])) {
			$writer->setShouldUseInlineStrings($bodyParams['config']['shouldUseInlineStrings']);
		}

		// Ensure the file path
		$filePath = 'export/' . Yii::$app->user->id;
		$fileName = "{$bodyParams['title']}-" . date('Y-m-d') . ".xlsx";
		FileHelper::removeDirectory(Yii::getAlias("@uploads/{$filePath}"));
		UploadHelper::ensureDirectoryTree($filePath);

		// Write data to the file
		$writer->openToFile(Yii::getAlias("@uploads/{$filePath}/{$fileName}"));
		$writer->addRowWithStyle(ArrayHelper::getColumn($columns, 'title'), (new StyleBuilder())->setFontBold()->build());
		foreach ($records as $record) {
			$row = [];
			foreach ($columns as $column) {
				$value = strip_tags(html_entity_decode($record[$column['name']]));
				if (is_numeric($value)) {
					$value = (double)$value;
				}
				$row[] = $value;
			}

			$writer->addRow($row);
		}

		$writer->close();

		return UploadHelper::getFileUrl("{$filePath}/{$fileName}");
	}

	/**
	 * Export as PDF.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return string|bool
	 * @throws \yii\base\ErrorException
	 */
	protected function exportAsPdf($bodyParams, $columns, $records)
	{
		$allowHtml = $bodyParams['config']['allowHtml'] == 'true' ? true : false;

		// Ensure the file path
		$filePath = 'export/' . Yii::$app->user->id;
		$fileName = "{$bodyParams['title']}-" . date('Y-m-d') . ".pdf";
		FileHelper::removeDirectory(Yii::getAlias("@uploads/{$filePath}"));
		UploadHelper::ensureDirectoryTree($filePath);

		$content[] = Html::beginTag('table', [
			'cellspacing' => 0,
			'cellpadding' => 5,
			'border' => 1,
			'style' => 'width: 100%; border-collapse: collapse;',
		]);

		// Compose table header
		$content[] = Html::beginTag('tr', ['style' => 'background-color: #eeeeee']);
		foreach ($columns as $column) {
			$content[] = Html::tag('th', $column['title']);
		}
		$content[] = Html::endTag('tr');

		// Compose table body
		foreach ($records as $record) {
			$content[] = Html::beginTag('tr');
			foreach ($columns as $column) {
				$content[] = Html::tag('td', $allowHtml ? $record[$column['name']] : strip_tags($record[$column['name']]));
			}
			$content[] = Html::endTag('tr');
		}

		$content[] = Html::endTag('table');

		// Generate the PDF file
		$pdf = new Pdf([
			'mode' => Pdf::MODE_UTF8,
			'format' => Pdf::FORMAT_A4,
			'orientation' => Pdf::ORIENT_PORTRAIT,
			'destination' => Pdf::DEST_FILE,
			'content' => implode('', $content),
			'filename' => Yii::getAlias("@uploads/{$filePath}/{$fileName}"),
			'options' => [
				'title' => $bodyParams['title'],
			],
			'methods' => [
				'SetFooter' => ['{PAGENO}'],
			],
		]);

		$pdf->render();

		return is_file($pdf->filename) ? UploadHelper::getFileUrl("{$filePath}/{$fileName}") : false;
	}

	/**
	 * Creates a new instance of a DataTableAction.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param \common\widgets\datatable\DataTableAction $className
	 * @return \common\widgets\datatable\DataTableAction the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function getDataTableActionModel($className)
	{
		if (class_exists($className)) {
			return new $className('dt-action', $this->id);
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
