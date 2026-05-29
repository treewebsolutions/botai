<?php

/* @var $this yii\web\View */

use common\models\VectorStoreFile;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Vector Store Files');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == VectorStoreFile::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => VectorStoreFile::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreVectorStoreFile') && $showTrash,
		'tag' => 'button',
		'icon' => 'fa fa-undo',
		'options' => [
			'class' => 'btn btn-sm btn-success hidden',
			'title' => Yii::t('common', 'Restore'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => 'restore',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['restore']),
				'dt-table' => '#dt-vector-store-files',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteVectorStoreFile'),
		'tag' => 'button',
		'icon' => 'fa fa-trash',
		'options' => [
			'class' => 'btn btn-sm btn-danger hidden',
			'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
			'data' => [
				'toggle' => 'tooltip',
				'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
				'dt-url' => Url::to(['delete']),
				'dt-table' => '#dt-vector-store-files',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createVectorStoreFile'),
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
	'id' => 'dt-vector-store-files',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-vector-store-files']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? VectorStoreFile::YES : null) . ';
			}'),
		],
		'order' => [
			[9, 'desc'],
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
				'data' => 'type',
				'title' => Yii::t('label', 'Type'),
				'filter' => ['text'],
			],
			[
				'data' => 'openai_id',
				'title' => Yii::t('label', 'OpenAI ID'),
				'filter' => ['text'],
			],
			[
				'data' => 'openai_status',
				'title' => Yii::t('label', 'OpenAI Status'),
				'filter' => ['text'],
			],
			[
				'data' => 'openai_message',
				'title' => Yii::t('label', 'OpenAI Message'),
				'filter' => ['text'],
			],
			[
				'data' => 'vector_store',
				'title' => Yii::t('label', 'Vector Store'),
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
				'data' => 'updated_at',
				'title' => Yii::t('label', 'Updated At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(VectorStoreFile::getStatusLabels(), 'label')],
			],
		],
		'rowCallback' => new JsExpression('function (row, data, index) {
			if (data.defaultValue == "' . VectorStoreFile::YES . '") {
				$(row).addClass("success");
			}
		}'),
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-vector-store-files").on("draw.dt", function () {
        $("#dt-vector-store-files").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-vector-store-files").outerWidth());
	});
	$("#dt-vector-store-files").on("column-visibility.dt", function () {
        $("#dt-vector-store-files").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-vector-store-files").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-vector-store-files").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-vector-store-files").outerWidth());
	});
');
?>
