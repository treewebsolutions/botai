<?php

namespace backend\modules\nomenclature\models;

use common\helpers\DateHelper;
use common\models\Page;
use common\models\RecordVectorIndex;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class PageSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Page::find()
			->alias('p')
			->select([
				'p.id',
				'p.url',
				'p.characters',
				'p.text',
				'p.counter',
				'p.created_by',
				'p.created_at',
				'p.updated_at',
				'p.status',
			])
			->joinWith([
				// Whether the page reached the vector store, and under which file. Left
				// join: a page that was never indexed simply has no row here.
				'recordVectorIndex rvi' => function (ActiveQuery $query) {
					$query->select([
						'rvi.id',
						'rvi.record_id',
						'rvi.openai_file_id',
						'rvi.status',
						'rvi.indexed_at',
						'rvi.error_message',
					]);
				},
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.first_name',
						'cr.middle_name',
						'cr.last_name',
					]);
				},
			])
			->andWhere([
				'p.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Page::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Page::class => [
				'id',
				'action' => function (Page $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Page::YES) {
						if (Yii::$app->user->can('restorePage')) {
							$actions[] = Html::a('<span class="fa fa-undo"></span>', ['restore', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-success',
								'title' => Yii::t('common', 'Restore'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'restore',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
						if (Yii::$app->user->can('deletePage')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id], [
								'class' => 'action-delete btn btn-xs btn-danger',
								'title' => Yii::t('common', 'Delete Permanently'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'delete-permanently',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
					} else {
						if (Yii::$app->user->can('viewPage')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updatePage')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deletePage')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id], [
								'class' => 'action-delete btn btn-xs btn-danger',
								'title' => Yii::t('common', 'Delete'),
								'data' => [
									'toggle' => 'tooltip',
									'dt-operation' => 'delete',
									'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
								],
							]);
						}
					}

					$actions = array_map(function ($actionsChunk) {
						return Html::tag('div', implode('', $actionsChunk));
					}, array_chunk($actions, 3));

					return implode('', $actions);
				},
				'url' => function (Page $model) {
					return $model->url ?: '&mdash;';
				},
				'counter' => function (Page $model) {
					return $model->counter ?: '&mdash;';
				},
				'text' => function (Page $model) {
					// What will actually be indexed. HTML length says a response arrived;
					// this says there was something readable in it.
					$length = mb_strlen(trim((string) $model->text));

					return $length
						? Yii::$app->formatter->asInteger($length)
						: Html::tag('span', Yii::t('label', 'No text'), ['class' => 'label label-warning']);
				},
				'characters' => function (Page $model) {
					// What the last fetch actually captured. A page the scraper reached but
					// that came back empty is the difference between "it ran" and "it worked".
					return $model->characters
						? Yii::$app->formatter->asInteger($model->characters)
						: Html::tag('span', Yii::t('label', 'Empty'), ['class' => 'label label-warning']);
				},
				'indexed' => function (Page $model) {
					$index = $model->recordVectorIndex;
					if ($index === null) {
						return Html::tag('span', Yii::t('label', 'Not indexed'), ['class' => 'label label-block label-default']);
					}

					$status = RecordVectorIndex::getStatusLabels()[$index->status] ?? null;
					$label = Html::tag('span', $status['label'] ?? $index->status, [
						'class' => 'label label-block label-' . ($status['color'] ?? 'default'),
						// The file the page became in the vector store, and when - the
						// answer to "is this page actually in there".
						'title' => trim(($index->openai_file_id ?: '') . ' ' . ($index->indexed_at ? '(' . Yii::$app->formatter->asDatetime($index->indexed_at) . ')' : '')),
					]);

					if ($index->error_message) {
						$label .= ' ' . Html::tag('span', '<span class="fa fa-exclamation-triangle"></span>', [
							'title' => Html::encode($index->error_message),
						]);
					}

					return $label;
				},
				'openai_file_id' => function (Page $model) {
					$index = $model->recordVectorIndex;

					return $index && $index->openai_file_id
						? Html::tag('code', Html::encode($index->openai_file_id))
						: '&mdash;';
				},
				'created_by' => function (Page $model) {
					return $model->creator ? $model->creator->getFullName() : '&mdash;';
				},
				'created_at' => function (Page $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Page $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Page $model) {
					$status = Page::getStatusLabels()[$model->status];
					return Html::tag('span', $status['label'], ['class' => 'label label-block label-' . $status['color']]);
				},
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
				case 'created_by':
					$query->$filterOperator([
						'OR',
						['LIKE', 'cr.first_name', $value],
						['LIKE', 'cr.middle_name', $value],
						['LIKE', 'cr.last_name', $value],
					]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'p.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'p.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'p.' . $column['data'], $value]);
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
		/** @var \yii\db\ActiveRecord $modelClass */
		$modelClass = $query->modelClass;
		$schema = $modelClass::getTableSchema()->columns;

		foreach ($order as $key => $item) {
			$column = $columns[$item['column']];
			if (array_key_exists('orderable', $column) && $column['orderable'] === 'false') {
				continue;
			}
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;

			switch ($column['data']) {
				case 'created_by':
					$query->addOrderBy([
						'cr.first_name' => $sort,
						'cr.middle_name' => $sort,
						'cr.last_name' => $sort,
					]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->addOrderBy(['p.' . $column['data'] => $sort]);
					}
					break;
			}
		}
		return $query;
	}
}
