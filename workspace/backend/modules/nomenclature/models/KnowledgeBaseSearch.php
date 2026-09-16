<?php

namespace backend\modules\nomenclature\models;

use common\helpers\DateHelper;
use common\models\KnowledgeBase;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class KnowledgeBaseSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = KnowledgeBase::find()
			->alias('kb')
			->select([
				'kb.id',
				'kb.name',
				'kb.description',
				'kb.provider',
				'kb.vector_store_id',
				'kb.embedding_model',
				'kb.chunk_size',
				'kb.chunk_overlap',
				'kb.tokens_per_file',
				'kb.default',
				'kb.expire_at',
				'kb.created_by',
				'kb.created_at',
				'kb.updated_at',
				'kb.status',
			])
			->joinWith([
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
				'kb.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : KnowledgeBase::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			KnowledgeBase::class => [
				'id',
				'action' => function (KnowledgeBase $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == KnowledgeBase::YES) {
						if (Yii::$app->user->can('restoreKnowledgeBase')) {
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
						if (Yii::$app->user->can('deleteKnowledgeBase')) {
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
						if (Yii::$app->user->can('viewKnowledgeBase')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateKnowledgeBase')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteKnowledgeBase')) {
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
				'name' => function (KnowledgeBase $model) {
					return $model->name ?: '&mdash;';
				},
				'description' => function (KnowledgeBase $model) {
					return $model->description ?: '&mdash;';
				},
				'provider' => function (KnowledgeBase $model) {
					$labels = [null => Yii::t('label', 'Generic')] + KnowledgeBase::getProviderLabels();
					return $labels[$model->provider] ?? '&mdash;';
				},
				'vector_store_id' => function (KnowledgeBase $model) {
					return $model->vector_store_id ?: '&mdash;';
				},
				'embedding_model' => function (KnowledgeBase $model) {
					return $model->embedding_model ?: '&mdash;';
				},
				'chunk_size' => function (KnowledgeBase $model) {
					return $model->chunk_size ?: '&mdash;';
				},
				'chunk_overlap' => function (KnowledgeBase $model) {
					return $model->chunk_overlap ?: '&mdash;';
				},
				'tokens_per_file' => function (KnowledgeBase $model) {
					return $model->tokens_per_file ?: '&mdash;';
				},
				'default' => function (KnowledgeBase $model) {
					// Where scraped pages are indexed, so it is worth seeing at a glance
					// which base that is without opening each one.
					return $model->default
						? Html::tag('span', Yii::t('label', 'Default'), ['class' => 'label label-primary'])
						: '&mdash;';
				},
				'expire_at' => function (KnowledgeBase $model) {
					return $model->expire_at ? Yii::$app->formatter->asDatetime($model->expire_at) : '&mdash;';
				},
				'created_by' => function (KnowledgeBase $model) {
					return $model->creator ? $model->creator->getFullName() : '&mdash;';
				},
				'created_at' => function (KnowledgeBase $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (KnowledgeBase $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (KnowledgeBase $model) {
					$status = KnowledgeBase::getStatusLabels()[$model->status];
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
					$query->$filterOperator(['LIKE', 'kb.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'kb.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'kb.' . $column['data'], $value]);
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
						$query->addOrderBy(['kb.' . $column['data'] => $sort]);
					}
					break;
			}
		}
		return $query;
	}
}

