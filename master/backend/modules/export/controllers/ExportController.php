<?php

namespace backend\modules\export\controllers;

use backend\controllers\MainController;
use backend\modules\export\widgets\export\Export;
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use common\helpers\Inflector;
use common\widgets\datatable\DataTableAction;
use kartik\mpdf\Pdf;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use tws\helpers\Url;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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
			$model = $this->getDataTableActionModel(Yii::$app->security->unmaskToken($bodyParams['model']));
			$model->applyFilter($model->query, $dataTable['columns'], $dataTable['search']);
			$model->applyOrder($model->query, $dataTable['columns'], $dataTable['order']);

			// Get data, then filter the columns to be exported
			$records = $model->formatData($model->query, []);
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
			$result = Url::to(['download', 'file' => $this->signDownload($result)]);
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
	 * Downloads a specific file then deletes it.
	 *
	 * @param string $file
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException if the file does not exist.
	 */
	public function actionDownload($file) {
		$file = $this->verifyDownload($file);

		if (!is_file($file['path'])) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		return Yii::$app->response->sendFile($file['path'], $file['name'])->on(Response::EVENT_AFTER_SEND, function ($event) {
			@unlink($event->data);
		}, $file['path']);
	}

	/**
	 * Signs the descriptor of a freshly generated export so the download link
	 * cannot be pointed at another file.
	 *
	 * maskToken() only defeats BREACH - it is reversible by anyone - so on its
	 * own it let a caller mint a token for any path the web user can read, have
	 * it sent back and then deleted.
	 *
	 * @param array $file the `path` and `name` of the generated file
	 * @return string
	 */
	protected function signDownload($file)
	{
		$file['user_id'] = Yii::$app->user->id;

		return base64_encode(Yii::$app->security->hashData(
			Json::encode($file),
			$this->getDownloadSignatureKey()
		));
	}

	/**
	 * Verifies a download descriptor produced by {@see signDownload()}.
	 *
	 * @param string $token
	 * @return array the `path` and `name` of the generated file
	 * @throws NotFoundHttpException if the token is forged, expired or belongs
	 * to another user, or if the path escaped the export directory
	 */
	protected function verifyDownload($token)
	{
		$data = base64_decode((string) $token, true);
		if ($data === false) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		$data = Yii::$app->security->validateData($data, $this->getDownloadSignatureKey());
		if ($data === false) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		try {
			$file = Json::decode($data);
		} catch (\Exception $e) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		// An export belongs to the account that asked for it.
		if (empty($file['path']) || empty($file['name']) || ($file['user_id'] ?? null) !== Yii::$app->user->id) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}

		// Defence in depth: whatever was signed, only serve from @runtime.
		$path = realpath($file['path']);
		$root = realpath(Yii::getAlias('@runtime'));
		if ($path === false || $root === false || strpos($path, $root . DIRECTORY_SEPARATOR) !== 0) {
			throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
		}
		$file['path'] = $path;

		return $file;
	}

	/**
	 * The key the export download signature is derived from.
	 *
	 * @return string
	 */
	protected function getDownloadSignatureKey()
	{
		return Yii::$app->request->cookieValidationKey . '|export-download';
	}

	/**
	 * Export as CSV.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return array|null
	 */
	protected function exportAsCsv($bodyParams, $columns, $records)
	{
		try {
            $title = Inflector::slug($bodyParams['title']);
            $fileName = "{$title}-" . date('Y-m-d') . '.csv';
			$fileTempName = Yii::$app->security->generateRandomString() . '.csv';

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

			// Write data to the file
			$writer->openToFile(Yii::getAlias("@runtime/{$fileTempName}"));
			$writer->addRow(ArrayHelper::getColumn($columns, 'title'));

			foreach ($records as $record) {
				$row = [];
				foreach ($columns as $column) {
					$row[] = $this->neutralizeFormula(strip_tags(html_entity_decode($record[$column['name']])));
				}
				$writer->addRow($row);
			}

			$writer->close();

			return [
				'path' => Yii::getAlias("@runtime/{$fileTempName}"),
				'name' => $fileName,
			];
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Export as VCF.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return array|null
	 */
	protected function exportAsVcf($bodyParams, $columns, $records)
	{
		try {
            $title = Inflector::slug($bodyParams['title']);
			$fileName = "{$title}-" . date('Y-m-d') . '.vcf';
			$fileTempName = Yii::$app->security->generateRandomString();
			$formatter = new \JeroenDesloovere\VCard\Formatter\Formatter(new \JeroenDesloovere\VCard\Formatter\VcfFormatter(), $fileTempName);

			foreach ($records as $i => $record) {
				$vCard = new \JeroenDesloovere\VCard\VCard();
				foreach ($bodyParams['config']['map'] as $key => $attributes) {
					foreach ((array) $attributes as $attribute) {
						$value = $this->neutralizeFormula(strip_tags(html_entity_decode($record[$attribute])));
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

			$formatter->save(Yii::getAlias('@runtime'));

			return [
				'path' => Yii::getAlias("@runtime/{$fileTempName}.vcf"),
				'name' => $fileName,
			];
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Export as XLSX.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return array|null
	 */
	protected function exportAsXlsx($bodyParams, $columns, $records)
	{
		try {
            $title = Inflector::slug($bodyParams['title']);
			$fileName = "{$title}-" . date('Y-m-d') . '.xlsx';
			$fileTempName = Yii::$app->security->generateRandomString() . '.xlsx';

			/** @var \Box\Spout\Writer\XLSX\Writer $writer */
			$writer = WriterFactory::create(Type::XLSX);

			if (isset($bodyParams['config']['shouldCreateNewSheetsAutomatically'])) {
				$writer->setShouldCreateNewSheetsAutomatically($bodyParams['config']['shouldCreateNewSheetsAutomatically']);
			}
			if (isset($bodyParams['config']['shouldUseInlineStrings'])) {
				$writer->setShouldUseInlineStrings($bodyParams['config']['shouldUseInlineStrings']);
			}

			// Write data to the file
			$writer->openToFile(Yii::getAlias("@runtime/{$fileTempName}"));
			$writer->addRow(ArrayHelper::getColumn($columns, 'title'));

			foreach ($records as $record) {
				$row = [];
				foreach ($columns as $column) {
					$row[] = $this->neutralizeFormula(strip_tags(html_entity_decode($record[$column['name']])));
				}
				$writer->addRow($row);
			}

			$writer->close();

			return [
				'path' => Yii::getAlias("@runtime/{$fileTempName}"),
				'name' => $fileName,
			];
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Export as PDF.
	 *
	 * @param array $bodyParams
	 * @param array $columns
	 * @param array $records
	 * @return array|null
	 */
	protected function exportAsPdf($bodyParams, $columns, $records)
	{
		try {
            $title = Inflector::slug($bodyParams['title']);
			$fileName = "{$title}-" . date('Y-m-d') . '.pdf';
			$fileTempName = Yii::$app->security->generateRandomString() . '.pdf';
			$allowHtml = $bodyParams['config']['allowHtml'] == 'true' ? true : false;
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
				'orientation' => Pdf::ORIENT_LANDSCAPE,
				'destination' => Pdf::DEST_FILE,
				'content' => implode('', $content),
				'filename' => Yii::getAlias("@runtime/{$fileTempName}"),
				'options' => [
					'title' => $bodyParams['title'],
				],
				'methods' => [
					'SetFooter' => ['{PAGENO}'],
				],
			]);

			$pdf->render();

			return [
				'path' => Yii::getAlias("@runtime/{$fileTempName}"),
				'name' => $fileName,
			];
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Creates a new instance of a DataTableAction.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param string $className
	 * @return \common\widgets\datatable\DataTableAction the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function getDataTableActionModel($className)
	{
		// The class name reaches us from the client, so constrain it to the
		// datatable actions instead of instantiating anything that autoloads.
		if (is_string($className) && class_exists($className) && is_subclass_of($className, DataTableAction::class)) {
			return new $className('dt-action', $this->id);
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}

	/**
	 * Neutralises a value that a spreadsheet would otherwise evaluate.
	 *
	 * Box\Spout writes what it is given, so a record whose text starts with
	 * =, +, - or @ becomes a live formula when the recipient opens the export.
	 * Prefixing a single quote pins the cell to text; leading control
	 * characters are stripped first so they cannot hide the trigger.
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	protected function neutralizeFormula($value)
	{
		if (!is_string($value) || $value === '') {
			return $value;
		}

		// Leading whitespace still counts: a spreadsheet trims it before parsing, and so
		// would a check that did not.
		$trimmed = ltrim($value, " \t\n\r\0\x0B");
		if ($trimmed === '') {
			return $value;
		}

		// Amounts arrive from DECIMAL columns as strings, and a negative one starts with
		// a trigger character. Prefixing those would turn every "-19.04" into text and
		// break the totals in the invoice exports, so a value that is simply a number
		// passes through - it cannot carry an expression.
		if (is_numeric($trimmed)) {
			return $value;
		}

		// The full-width variants are the same characters to a spreadsheet's parser.
		$triggers = ['=', '+', '-', '@', "\t", "\r", "\n", "\u{FF1D}", "\u{FF0B}", "\u{FF0D}", "\u{FF20}"];
		foreach ($triggers as $trigger) {
			if (mb_strpos($trimmed, $trigger) === 0) {
				return "'" . $value;
			}
		}

		return $value;
	}

}
