<?php

namespace backend\modules\import\models;

ini_set('memory_limit',-1);
ini_set('max_execution_time', 0);

use common\models\ImportColumn;
use common\models\ImportSheet;
use Yii;
use yii\base\Model;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class ImportForm extends Model
{
	/**
	 * @var int The sheet id.
	 */
	public $sheet_id;

	/**
	 * @var array The SpreadsheetImport widget configuration.
	 */
	public $spreadsheetImport;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['sheet_id'], 'exist', 'targetClass' => ImportSheet::class, 'targetAttribute' => ['sheet_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'sheet_id' => Yii::t('common', 'Sheet ID'),
		];
	}

	/**
	 * Gets the spreadsheet reader instance.
	 *
	 * @param string $fileType
	 * @return \Box\Spout\Reader\ReaderInterface|\Box\Spout\Reader\XLSX\Reader|null
	 * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
	 */
	protected static function getReader($fileType)
	{
		$fileType = mb_strtoupper(end(explode('.', $fileType)));
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

	/**
	 * Saves the model.
	 *
	 * @return bool
	 * @throws \yii\db\Exception
	 * @throws \Exception
	 */
	public function save()
	{
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$sheet = ImportSheet::findOne([
				'id' => $this->sheet_id,
				'deleted' => ImportSheet::NO,
			]);
			$sheetColumns = ImportColumn::findAll([
				'sheet_id' => $this->sheet_id,
				'deleted' => ImportColumn::NO,
			]);

			$file = Yii::getAlias("@uploads/import/file/{$sheet->file->id}/{$sheet->file->file}");
			if (!is_file($file)) {
				throw new \Exception('The file does not exist.');
			}

			$reader = self::getReader($file);
			$reader->setShouldFormatDates(true);
			$reader->open($file);
			foreach ($reader->getSheetIterator() as $spreadsheetIndex => $spreadsheet) {
				if ($spreadsheetIndex != $sheet->number) {
					continue;
				}
				foreach ($spreadsheet->getRowIterator() as $rowIndex => $row) {
					if ($rowIndex <= $sheet->header) {
						continue;
					}
					$row = array_map('trim', $row);
					if (empty(array_filter($row))) {
						continue;
					}
					/** @var $model \yii\base\Model|\yii\db\ActiveRecord */
					$model = new $this->spreadsheetImport['model'];

					foreach ($sheetColumns as $columnIndex => $sheetColumn) {
						$model->{$sheetColumn->target} = $row[$sheetColumn->source_index];
					}
					if (!$model->save()) {
						$this->addErrors($model->getErrors());

						throw new \Exception($model->getErrorSummary(false)[0]);
					}
				}
			}
			$reader->close();

			$transaction->commit();

			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
