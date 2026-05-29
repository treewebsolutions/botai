<?php

namespace backend\modules\import\models;

use common\models\ImportColumn;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class ColumnSearch extends DataTableAction
{
	/**
	 * @var array The target model attribute labels.
	 */
	protected $targetModelAttributeLabels;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$spreadsheetImport = Yii::$app->session->get('SpreadsheetImport');
		$this->targetModelAttributeLabels = (new $spreadsheetImport['model'])->attributeLabels();

		$this->query = ImportColumn::find()
			->where([
				'sheet_id' => $this->requestParams['sheet_id'],
				'deleted' => ImportColumn::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			ImportColumn::class => [
				'id',
				'target' => function ($model) {
					return $this->targetModelAttributeLabels[$model->target] ?: Yii::t('common', Inflector::humanize($model->target, true));
				},
				'source',
				'source_index',
				'field_type',
				'sort_order',
			],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function applyFilter(ActiveQuery $query, $columns, $search)
	{
		/** @var \yii\db\ActiveRecord $modelClass */
		$modelClass = $query->modelClass;
		$schema = $modelClass::getTableSchema()->columns;

		foreach ($columns as $column) {
			if ($column['searchable'] == 'false') {
				continue;
			}
			if (!empty($search['value'])) {
				$value = trim($search['value']);
				$filterOperator = 'orFilterWhere';
			} else {
				$value = trim($column['search']['value']);
				$filterOperator = 'andFilterWhere';
			}

			switch ($column['data']) {
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', $column['data'], $value]);
					}
					break;
			}
		}
		return $query;
	}

	/**
	 * @inheritdoc
	 */
	public function applyOrder(ActiveQuery $query, $columns, $order)
	{
		foreach ($order as $key => $item) {
			$column = $columns[$item['column']];
			if (array_key_exists('orderable', $column) && $column['orderable'] === 'false') {
				continue;
			}
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;

			switch ($column['data']) {
				default:
					$query->addOrderBy([$column['data'] => $sort]);
					break;
			}
		}
		return $query;
	}
}
