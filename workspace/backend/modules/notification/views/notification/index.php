<?php

/* @var $this yii\web\View */

use common\models\Notification;
use common\widgets\datatable\DataTable;
use tws\helpers\Url;

$this->title = Yii::t('common', 'Notifications');
$this->params['breadcrumbs'][] = $this->title;
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('deleteNotification'),
		'tag' => 'button',
		'icon' => 'fa fa-trash',
		'options' => [
			'class' => 'btn btn-sm btn-danger hidden',
			'title' => Yii::t('common', 'Bulk Delete'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'delete',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['bulk-delete']),
				'dt-table' => '#dt-notifications',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('viewNotification'),
		'tag' => 'button',
		'icon' => 'fa fa-eye',
		'options' => [
			'class' => 'btn btn-sm btn-info hidden',
			'title' => Yii::t('common', 'Mark All as Seen'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'bulk-seen',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['bulk-seen']),
				'dt-table' => '#dt-notifications',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createNotification'),
		'tag' => 'a',
		'url' => ['create'],
		'icon' => 'fa fa-plus',
		'options' => [
			'class' => 'btn btn-sm btn-success',
			'title' => Yii::t('common', 'Create'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
];
?>

<div class="dt-scroll-x">
<?= DataTable::widget([
	'id' => 'dt-notifications',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-notifications']),
			'method' => 'POST',
			'reloadInterval' => 1 * 60000,
		],
		'order' => [
			[6, 'desc'],
			[7, 'asc'],
		],
		'pageLength' => (int) Yii::$app->settings->get('itemsPerPage'),
		'lengthMenu' => [
			'autoCreate' => true,
			'displayAll' => Yii::t('common', 'All'),
		],
		'autoWidth' => true,
		'responsive' => false,
		'scrollX' => false,
		'colReorder' => true,
		'dom' => 'Blfrtip',
		'buttons' => [
			[
				'extend' => 'colvis',
				'text' => '<span class="fa fa-eye"></span>',
			],
		],
		'columns' => [
			[
				'visible' => Yii::$app->user->can('deleteNotification'),
				'class' => 'common\widgets\datatable\CheckboxColumn',
			],
			[
				'class' => 'common\widgets\datatable\ActionColumn',
				'title' => Yii::t('common', 'Action'),
				'buttons' => [
					'view',
					'update' => Yii::$app->user->can('updateNotification'),
					'delete' => Yii::$app->user->can('deleteNotification'),
				],
			],
			[
				'data' => 'type',
				'title' => Yii::t('label', 'Type'),
				'filter' => ['select', Notification::getTypeLabels()],
			],
			[
				'data' => 'title',
				'title' => Yii::t('label', 'Title'),
				'filter' => ['text'],
			],
			[
				'data' => 'message',
				'title' => Yii::t('label', 'Message'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_by',
				'title' => Yii::t('label', 'Created By'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'seen',
				'title' => Yii::t('label', 'Seen'),
				'className' => 'col-autowidth',
				'filter' => ['select', [Yii::t('yii', 'No'), Yii::t('yii', 'Yes')]],
			],
		],
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-notifications").on("draw.dt", function () {
        $("#dt-notifications").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-notifications").outerWidth());
	});
	$("#dt-notifications").on("column-visibility.dt", function () {
        $("#dt-notifications").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-notifications").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-notifications").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-notifications").outerWidth());
	});
');
?>
