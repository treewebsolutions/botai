<?php

namespace backend\modules\nomenclature\models;

use common\helpers\DateHelper;
use common\models\KnowledgeBaseDocument;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class KnowledgeBaseDocumentSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = KnowledgeBaseDocument::find()
			->alias('d')
			->select([
				'd.id',
				'd.knowledge_base_id',
				'd.name',
				'd.file',
				'd.extension',
				'd.size',
				'd.index_status',
				'd.indexed_at',
				'd.error_message',
				'd.created_by',
				'd.created_at',
				'd.updated_at',
				'd.status',
			])
			->joinWith([
				'knowledgeBase kb' => function (ActiveQuery $query) {
					$query->select([
						'kb.id',
						'kb.name',
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
				'd.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : KnowledgeBaseDocument::NO,
			]);
		if (!empty($this->requestParams['knowledge_base_id'])) {
			$this->query->andWhere(['d.knowledge_base_id' => (int) $this->requestParams['knowledge_base_id']]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			KnowledgeBaseDocument::class => [
				'id',
				'action' => function (KnowledgeBaseDocument $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == KnowledgeBaseDocument::YES) {
						if (Yii::$app->user->can('restoreKnowledgeBaseDocument')) {
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
						if (Yii::$app->user->can('deleteKnowledgeBaseDocument')) {
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
						if (Yii::$app->user->can('viewKnowledgeBaseDocument')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
							$actions[] = Html::a('<span class="fa fa-download"></span>', ['download', 'id' => $model->id], [
								'class' => 'btn btn-xs btn-default',
								'title' => Yii::t('label', 'Download'),
								'target' => '_blank',
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateKnowledgeBaseDocument')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
							$actions[] = Html::a('<span class="fa fa-refresh"></span>', ['reindex', 'id' => $model->id], [
								'class' => 'btn btn-xs btn-warning',
								'title' => Yii::t('common', 'Reindex'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteKnowledgeBaseDocument')) {
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
				'name' => function (KnowledgeBaseDocument $model) {
					return $model->name ? Html::encode($model->name) : '&mdash;';
				},
				'knowledge_base' => function (KnowledgeBaseDocument $model) {
					return $model->knowledgeBase ? Html::encode($model->knowledgeBase->name) : '&mdash;';
				},
				'file' => function (KnowledgeBaseDocument $model) {
					return $model->file ? Html::encode($model->file) : '&mdash;';
				},
				'extension' => function (KnowledgeBaseDocument $model) {
					return $model->extension ? strtoupper($model->extension) : '&mdash;';
				},
				'size' => function (KnowledgeBaseDocument $model) {
					return $model->size ? Yii::$app->formatter->asShortSize($model->size) : '&mdash;';
				},
				'index_status' => function (KnowledgeBaseDocument $model) {
					$status = KnowledgeBaseDocument::getIndexStatusLabels()[$model->index_status] ?? null;
					if ($status === null) {
						return '&mdash;';
					}
					$title = $model->index_status == KnowledgeBaseDocument::INDEX_STATUS_ERROR ? $model->error_message : $model->indexed_at;
					return Html::tag('span', $status['label'], ['class' => 'label label-block label-' . $status['color'], 'title' => $title]);
				},
				'created_by' => function (KnowledgeBaseDocument $model) {
					return $model->creator ? $model->creator->getFullName() : '&mdash;';
				},
				'created_at' => function (KnowledgeBaseDocument $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (KnowledgeBaseDocument $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (KnowledgeBaseDocument $model) {
					$status = KnowledgeBaseDocument::getStatusLabels()[$model->status];
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
				case 'knowledge_base':
					$query->$filterOperator(['LIKE', 'kb.name', $value]);
					break;
				case 'created_by':
					$query->$filterOperator([
						'OR',
						['LIKE', 'cr.first_name', $value],
						['LIKE', 'cr.middle_name', $value],
						['LIKE', 'cr.last_name', $value],
					]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'd.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'd.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'd.' . $column['data'], $value]);
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
				case 'knowledge_base':
					$query->addOrderBy(['kb.name' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'cr.first_name' => $sort,
						'cr.middle_name' => $sort,
						'cr.last_name' => $sort,
					]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->addOrderBy(['d.' . $column['data'] => $sort]);
					}
					break;
			}
		}
		return $query;
	}
}
