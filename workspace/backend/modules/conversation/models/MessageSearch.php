<?php

namespace backend\modules\conversation\models;

use common\helpers\DateHelper;
use common\helpers\StringHelper;
use common\models\Message;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class MessageSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Message::find()
			->alias('m')
			->select([
				'm.id',
				'm.thread_id',
				'm.assistant_id',
				'm.openai_id',
				'm.role',
				'm.content',
				'm.completed_at',
				'm.incomplete_at',
				'm.incomplete_reason',
				'm.created_by',
				'm.created_at',
				'm.status',
			])
			->joinWith([
				'thread t' => function (ActiveQuery $query) {
					$query->andOnCondition([
						't.deleted' => Message::NO,
					]);
				},
				'assistant a' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'a.deleted' => Message::NO,
					]);
				},
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.username',
					]);
				},
			])
			->andWhere([
				'm.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Message::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Message::class => [
				'id',
				'action' => function (Message $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Message::YES) {
						if (Yii::$app->user->can('restoreMessage')) {
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
						if (Yii::$app->user->can('deleteMessage')) {
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
						if (Yii::$app->user->can('viewMessage')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-action' => '',
								],
							]);
						}
						if (Yii::$app->user->can('updateMessage')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteMessage')) {
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
				'content' => function (Message $model) {
					return $model->content ? StringHelper::truncate($model->content, 140): '&mdash;';
				},
				'role' => function (Message $model) {
					return $model->role ? Message::getRoleLabels()[$model->role] : '&mdash;';
				},
				'thread' => function (Message $model) {
					return $model->thread->openai_id ?: '&mdash;';
				},
				'assistant' => function (Message $model) {
					return $model->assistant->name ?: '&mdash;';
				},
				'openai_id' => function (Message $model) {
					return $model->openai_id ?: '&mdash;';
				},
				'created_by' => function (Message $model) {
					return $model->creator ? $model->creator->username : '&mdash;';
				},
				'created_at' => function (Message $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'completed_at' => function (Message $model) {
					return $model->completed_at ? Yii::$app->formatter->asDatetime($model->completed_at) : '&mdash;';
				},
				'incomplete_at' => function (Message $model) {
					return $model->incomplete_at ? Yii::$app->formatter->asDatetime($model->incomplete_at) : '&mdash;';
				},
				'incomplete_reason' => function (Message $model) {
					return $model->incomplete_reason ?: '&mdash;';
				},
				'status' => function (Message $model) {
					$status = Message::getStatusLabels()[$model->status];
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
				case 'thread':
					$query->$filterOperator(['LIKE', 't.openai_id', $value]);
					break;
				case 'assistant':
					$query->$filterOperator(['LIKE', 'a.name', $value]);
					break;
				case 'created_by':
					$query->$filterOperator(['LIKE', 'cr.username', $value]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'm.created_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'm.' . $column['data'], $value]);
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
				case 'thread':
					$query->addOrderBy(['t.openai_id' => $sort]);
					break;
				case 'assistant':
					$query->addOrderBy(['a.name' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy(['cr.username' => $sort]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->addOrderBy(['m.' . $column['data'] => $sort]);
					}
					break;
			}
		}
		return $query;
	}
}
