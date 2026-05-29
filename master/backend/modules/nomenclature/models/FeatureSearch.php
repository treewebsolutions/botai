<?php

namespace backend\modules\nomenclature\models;

use common\helpers\DateHelper;
use common\models\Feature;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class FeatureSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Feature::find()
			->alias('f')
			->select([
				'f.id',
				'f.feature_module_id',
				'f.name',
				'f.price',
				'f.currency',
				'f.updated_by',
				'f.updated_at',
				'f.status',
			])
			->joinWith([
				'featureModule fm',
				'updater up' => function (ActiveQuery $query) {
					$query->select([
						'up.id',
						'up.first_name',
						'up.middle_name',
						'up.last_name',
					]);
				},
			])
			->andWhere([
				'f.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Feature::NO,
			])
			->orderBy([
				'f.feature_module_id' => SORT_ASC,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Feature::class => [
				'id',
				'group' => function (Feature $model) {
					return $model->featureModule->name ? Yii::t('common', ucfirst($model->featureModule->name)) : Yii::t('common', 'General');
				},
				'action' => function (Feature $model) {
					$actions = [];

					if (Yii::$app->user->can('viewFeature')) {
						$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id], [
							'class' => 'action-view btn btn-xs btn-info',
							'title' => Yii::t('common', 'View'),
							'data' => [
								'toggle' => 'tooltip',
								'popup-action' => '',
							],
						]);
					}
					if (Yii::$app->user->can('updateFeature')) {
						$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id], [
							'class' => 'action-update btn btn-xs btn-primary',
							'title' => Yii::t('common', 'Update'),
							'data' => [
								'toggle' => 'tooltip',
								'popup-action' => '',
								'popup-done' => ['redrawDataTable' => '#dt-features'],
							],
						]);
					}

					$actions = array_map(function ($actionsChunk) {
						return Html::tag('div', implode('', $actionsChunk));
					}, array_chunk($actions, 3));

					return implode('', $actions);
				},
				'name' => function (Feature $model) {
					return Feature::getFeatureLabels()[$model->name] ?: '&mdash;';
				},
				'price' => function (Feature $model) {
					return Yii::$app->formatter->asCurrency($model->price ?: 0, $model->currency);
				},
				'updated_by' => function (Feature $model) {
					return $model->updater ? $model->updater->fullName : '&mdash;';
				},
				'updated_at' => function (Feature $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Feature $model) {
					$status = Feature::getStatusLabels()[$model->status];
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
						['LIKE', 'up.first_name', $value],
						['LIKE', 'up.middle_name', $value],
						['LIKE', 'up.last_name', $value],
					]);
					break;
				case 'created_at':
					$query->$filterOperator(['LIKE', 'f.created_at', DateHelper::formatAsDate($value)]);
					break;
				case 'updated_at':
					$query->$filterOperator(['LIKE', 'f.updated_at', DateHelper::formatAsDate($value)]);
					break;
				default:
					if (array_key_exists($column['data'], $schema)) {
						$query->$filterOperator(['LIKE', 'f.' . $column['data'], $value]);
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
				case 'updated_by':
					$query->addOrderBy([
						'up.first_name' => $sort,
						'up.middle_name' => $sort,
						'up.last_name' => $sort,
					]);
					break;
				default:
					$query->addOrderBy(['f.' . $column['data'] => $sort]);
					break;
			}
		}
		return $query;
	}
}
