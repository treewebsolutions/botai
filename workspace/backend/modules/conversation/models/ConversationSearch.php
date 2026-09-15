<?php

namespace backend\modules\conversation\models;

use common\helpers\DateHelper;
use common\helpers\StringHelper;
use common\models\Conversation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class ConversationSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Conversation::find()
			->alias('c')
			->select([
				'c.id',
				'c.summary',
				'c.openai_conversation_id',
				'messages_count' => 'COUNT(m.id)',
				'c.created_by',
				'c.created_at',
				'c.status',
			])
			->joinWith([
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.username',
					]);
				},
				'messages m' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'm.deleted' => Conversation::NO,
					]);
				},
			], false)
			->groupBy(['c.id'])
			->andWhere([
				'c.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Conversation::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Conversation::class => [
				'id',
				'action' => function (Conversation $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Conversation::YES) {
						if (Yii::$app->user->can('restoreConversation')) {
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
						if (Yii::$app->user->can('deleteConversation')) {
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
						if (Yii::$app->user->can('viewConversation')) {
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('updateConversation')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
								],
							]);
						}
						if (Yii::$app->user->can('deleteConversation')) {
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
				'summary' => function (Conversation $model) {
					return $model->summary ? Html::encode(StringHelper::truncate($model->summary, 140)) : '&mdash;';
				},
				'openai_conversation_id' => function (Conversation $model) {
					return $model->openai_conversation_id ?: '&mdash;';
				},
				'messages_count' => function (Conversation $model) {
					return (int) $model->messages_count;
				},
				'created_by' => function (Conversation $model) {
					return $model->creator ? $model->creator->username : '&mdash;';
				},
				'created_at' => function (Conversation $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'status' => function (Conversation $model) {
					$status = Conversation::getStatusLabels()[$model->status];
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
				case 'messages_count':
					if (is_numeric($value)) {
						$query->andHaving(['messages_count' => (int) $value]);
					}
					break;
				case 'created_by':
					$query->$filterOperator(['LIKE', 'cr.username', $value]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'c.created_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'c.' . $column['data'], $value]);
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
				case 'messages_count':
					$query->addOrderBy(['messages_count' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy(['cr.username' => $sort]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->addOrderBy(['c.' . $column['data'] => $sort]);
					}
					break;
			}
		}
		return $query;
	}
}
