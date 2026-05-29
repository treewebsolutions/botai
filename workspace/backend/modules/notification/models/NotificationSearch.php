<?php

namespace backend\modules\notification\models;

use common\helpers\DateHelper;
use common\models\Notification;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\StringHelper;

class NotificationSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Notification::find()
			->alias('n')
			->select([
				'n.id',
				'n.code',
				'n.type',
				'n.title',
				'n.message',
				'n.data',
				'n.created_by',
				'n.updated_by',
				'n.created_at',
				'n.updated_at',
				'n.status',
			])
			->joinWith([
				'userHasNotifications uhn',
				'creator c',
			])
			->where([
				'n.status' => Notification::STATUS_ACTIVE,
				'n.deleted' => Notification::NO,
			])
			->andWhere([
				'OR',
				['=', 'n.created_by', Yii::$app->user->id],
				['=', 'uhn.user_id', Yii::$app->user->id],
			])
			->groupBy(['n.id']);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Notification::class => [
				'id',
				'type' => function ($model) {
					return Notification::getTypeLabels()[$model->type];
				},
				'title' => function ($model) {
					return $model->title ? Html::encode(Yii::t('notification', $model->title)) : '&mdash;';
				},
				'message' => function ($model) {
					return $model->message ? $model->getMessageExcerpt() : '&mdash;';
				},
				'created_by' => function ($model) {
					return $model->creator ? $model->creator->fullName : '&mdash;';
				},
				'created_at' => function ($model) {
					return Yii::$app->formatter->asDatetime($model->created_at);
				},
				'updated_at' => function ($model) {
					return Yii::$app->formatter->asDatetime($model->updated_at);
				},
				'seen' => function ($model) {
					$seen = in_array(1, ArrayHelper::getColumn($model->userHasNotifications, 'seen'));

					return Yii::$app->formatter->asBoolean($seen);
				},
				'status' => function ($model) {
					$status = Notification::getStatusLabels()[$model->status];

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
						['LIKE', 'c.first_name', $value],
						['LIKE', 'c.middle_name', $value],
						['LIKE', 'c.last_name', $value],
						['LIKE', new Expression('CONCAT(c.first_name, " ", c.middle_name, " ", c.last_name)'), $value],
					]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'n.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'n.updated_at', DateHelper::formatAsDate($value)]);
					break;
				case 'seen':
					$query->$filterOperator(['=', 'uhn.seen', $value]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'n.' . $column['data'], $value]);
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
				case 'created_by':
					$query->addOrderBy([
						'c.first_name' => $sort,
						'c.middle_name' => $sort,
						'c.last_name' => $sort,
					]);
					break;
				case 'seen':
					$query->addOrderBy(['uhn.seen' => $sort]);
					break;
				default:
					$query->addOrderBy(['n.' . $column['data'] => $sort]);
					break;
			}
		}
		return $query;
	}
}
