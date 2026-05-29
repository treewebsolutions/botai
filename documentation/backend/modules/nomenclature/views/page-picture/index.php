<?php
/* @var $this yii\web\View */

use common\models\Picture;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\JsExpression;

$page_id = Yii::$app->request->get('page_id');

if ($this->context->id != 'page') {
	$this->title = Yii::t('common', 'Pictures');
	$this->params['breadcrumbs'] = [
		[
			'label' => Yii::t('common', 'Nomenclature'),
			'url' => ['default/index'],
		],
		[
			'label' => Yii::t('common', 'Pages'),
			'url' => ['page/index'],
		],
		[
			'label' => \common\models\Page::findOne($page_id)->translation->title,
			'url' => ['page/view', 'id' => $page_id],
		],
		$this->title,
	];

	if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
		$showTrash = Yii::$app->request->get('deleted') == Picture::YES;
		$this->params['breadcrumbs'][] = [
			'template' => '<li class="page-links">{link}</li>',
			'label' => implode('', [
				Html::a(Yii::t('common', 'Current'), ['index', 'page_id' => $page_id], ['class' => $showTrash ? '' : 'active']),
				Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Picture::YES, 'page_id' => $page_id], ['class' => $showTrash ? 'active' : '']),
			]),
		];
	}

	$this->params['actions'] = [
		[
			'visible' => Yii::$app->user->can('restorePage') && $showTrash,
			'tag' => 'button',
			'icon' => 'fa fa-undo',
			'options' => [
				'class' => 'btn btn-sm btn-success hidden',
				'title' => Yii::t('common', 'Restore'),
				'data' => [
					'toggle' => 'tooltip',
					'dt-bulk-operation' => 'restore',
					'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					'dt-url' => Url::to(['restore', 'page_id' => $page_id]),
					'dt-table' => '#dt-page-pictures',
				],
			],
		],
		[
			'visible' => Yii::$app->user->can('deletePage'),
			'tag' => 'button',
			'icon' => 'fa fa-trash',
			'options' => [
				'class' => 'btn btn-sm btn-danger hidden',
				'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
				'data' => [
					'toggle' => 'tooltip',
					'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
					'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					'dt-url' => Url::to(['delete', 'page_id' => $page_id]),
					'dt-table' => '#dt-page-pictures',
				],
			],
		],
		[
			'visible' => Yii::$app->user->can('createPage'),
			'tag' => 'a',
			'url' => ['create', 'page_id' => $page_id],
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
} else {
	if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
		$showTrash = Yii::$app->request->get('deleted') == Picture::YES;
	}
}
?>

<div class="dt-scroll-x">
<?= DataTable::widget([
	'id' => 'dt-page-pictures',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['page-picture/dt-page-pictures', 'page_id' => $page_id]),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.page_id = ' . json_encode($page_id) . ';
				data.deleted = ' . json_encode($showTrash ? Picture::YES : null) . ';
			}'),
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
		'dom' => 'lfrtip',
		'columns' => [
			[
				'class' => 'common\widgets\datatable\CheckboxColumn',
			],
			[
				'data' => 'actions',
				'class' => 'common\widgets\datatable\ActionColumn',
				'title' => Yii::t('common', 'Action'),
			],
			[
				'data' => 'sort_order',
				'className' => 'col-autowidth',
				'title' => '#',
				'filter' => ['text'],
			],
			[
				'data' => 'image',
				'title' => Yii::t('label', 'Image'),
				'className' => 'col-autowidth text-center',
				'orderable' => false,
				'filter' => false,
			],
			[
				'data' => 'title',
				'title' => Yii::t('label', 'Title'),
				'filter' => ['text'],
			],
			[
				'data' => 'url',
				'title' => Yii::t('label', 'Url'),
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
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Picture::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-page-pictures").on("draw.dt", function () {
        $("#dt-page-pictures").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-page-pictures").outerWidth());
	});
	$("#dt-page-pictures").on("column-visibility.dt", function () {
        $("#dt-page-pictures").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-page-pictures").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-page-pictures").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-page-pictures").outerWidth());
	});
');
?>
