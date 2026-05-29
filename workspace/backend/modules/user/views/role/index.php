<?php

/* @var $this yii\web\View */

use common\widgets\datatable\DataTable;
use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('common', 'Roles');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('backend', 'Users'),
		'url' => ['user/index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('deleteUserRole'),
		'tag' => 'button',
		'icon' => 'fa fa-trash',
		'options' => [
			'class' => 'btn btn-sm btn-danger hidden',
			'title' => Yii::t('common', 'Delete'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'delete',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['delete']),
				'dt-table' => '#dt-roles',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createUserRole'),
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
	'id' => 'dt-roles',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-roles']),
			'method' => 'POST',
		],
		'order' => [
			[2, 'asc'],
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
				'class' => 'common\widgets\datatable\CheckboxColumn',
			],
			[
				'class' => 'common\widgets\datatable\ActionColumn',
				'data' => 'action',
				'title' => Yii::t('common', 'Action'),
			],
			[
				'data' => 'description',
				'title' => Yii::t('label', 'Name'),
				'filter' => ['text'],
			],
		],
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-roles").on("draw.dt", function () {
        $("#dt-roles").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-roles").outerWidth());
	});
	$("#dt-roles").on("column-visibility.dt", function () {
        $("#dt-roles").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-roles").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-roles").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-roles").outerWidth());
	});
');
?>
