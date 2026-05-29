<?php

namespace backend\modules\nomenclature\models;

use common\helpers\DateHelper;
use common\models\Picture;
use common\models\PictureTranslation;
use common\widgets\datatable\DataTableAction;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class PagePictureSearch extends DataTableAction
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->query = Picture::find()
			->alias('p')
			->select([
				'p.id',
				'p.image',
				'p.sort_order',
				'p.created_by',
				'p.updated_by',
				'p.created_at',
				'p.updated_at',
				'p.status',
			])
			->joinWith([
				'pictureTranslations pt' => function (ActiveQuery $query) {
					return $query->andOnCondition([
						'pt.language_id' => Yii::$app->language,
						'pt.deleted' => PictureTranslation::NO,
					]);
				},
				'pageHasPictures php' => function (ActiveQuery $query) {
					$query->andWhere(['php.page_id' => $this->requestParams['page_id']]);
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
				'p.deleted' => isset($this->requestParams['deleted']) ? $this->requestParams['deleted'] : Picture::NO,
			]);
	}

	/**
	 * @inheritdoc
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		return ArrayHelper::toArray($query->all(), [
			Picture::class => [
				'id',
				'sort_order',
				'actions' => function (Picture $model) {
					$actions = [];

					if ($this->requestParams['deleted'] == Picture::YES) {
						if (Yii::$app->user->can('restorePage')) {
							$actions[] = Html::a('<span class="fa fa-undo"></span>', ['restore', 'id' => $model->id, 'page_id' => $this->requestParams['page_id']], [
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
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id, 'page_id' => $this->requestParams['page_id']], [
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
							$actions[] = Html::a('<span class="fa fa-eye"></span>', ['view', 'id' => $model->id, 'page_id' => $this->requestParams['page_id']], [
								'class' => 'action-view btn btn-xs btn-info',
								'title' => Yii::t('common', 'View'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-action' => '',
								],
							]);
						}
						if (Yii::$app->user->can('updatePage')) {
							$actions[] = Html::a('<span class="fa fa-edit"></span>', ['update', 'id' => $model->id, 'page_id' => $this->requestParams['page_id']], [
								'class' => 'action-update btn btn-xs btn-primary',
								'title' => Yii::t('common', 'Update'),
								'data' => [
									'toggle' => 'tooltip',
									'popup-action' => '',
									'popup-done' => ['redrawDataTable' => '#dt-page-pictures'],
								],
							]);
						}
						if (Yii::$app->user->can('deletePage')) {
							$actions[] = Html::a('<span class="fa fa-trash"></span>', ['delete', 'id' => $model->id, 'page_id' => $this->requestParams['page_id']], [
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
				'image' => function (Picture $model) {
					if ($model->image) {
						$imgTag = Html::img($model->imageUrl, [
							'class' => 'img-responsive',
							'alt' => $model->translation->title,
						]);
						return Html::a($imgTag, $model->imageUrl, [
							'title' => Yii::t('common', 'Open Gallery'),
							'data' => [
								'toggle' => 'tooltip',
								'fancybox' => 'pictures',
								'caption' => $model->translation->title,
							],
						]);
					}
					return '&mdash;';
				},
				'title' => function (Picture $model) {
					return $model->translation->title ?: '&mdash;';
				},
				'url' => function (Picture $model) {
					if ($model->image) {
						return $model->getImageUrl(true);
					}
					return '&mdash;';
				},
				'created_by' => function (Picture $model) {
					return $model->creator ? $model->creator->getFullName() : '&mdash;';
				},
				'created_at' => function (Picture $model) {
					return $model->created_at ? Yii::$app->formatter->asDatetime($model->created_at) : '&mdash;';
				},
				'updated_at' => function (Picture $model) {
					return $model->updated_at ? Yii::$app->formatter->asDatetime($model->updated_at) : '&mdash;';
				},
				'status' => function (Picture $model) {
					$status = Picture::getStatusLabels()[$model->status];
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
				case 'title':
					$query->$filterOperator(['LIKE', 'pt.title', $value]);
					break;
				case 'url':
					$query->$filterOperator(['LIKE', 'p.image', $value]);
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
		foreach ($order as $key => $item) {
			$column = $columns[$item['column']];
			if (array_key_exists('orderable', $column) && $column['orderable'] === 'false') {
				continue;
			}
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;

			switch ($column['data']) {
				case 'sort_order':
					$query->addOrderBy(new Expression('[[p.sort_order]] IS NULL'));
					$query->addOrderBy(['p.sort_order' => $sort]);
					break;
				case 'title':
					$query->addOrderBy(['pt.title' => $sort]);
					break;
				case 'url':
					$query->addOrderBy(['p.image' => $sort]);
					break;
				case 'created_by':
					$query->addOrderBy([
						'cr.first_name' => $sort,
						'cr.middle_name' => $sort,
						'cr.last_name' => $sort,
					]);
					break;
				default:
					$query->addOrderBy(['p.' . $column['data'] => $sort]);
					break;
			}
		}
		return $query;
	}
}
