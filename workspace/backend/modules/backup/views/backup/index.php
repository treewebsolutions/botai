<?php
/* @var $this yii\web\View */

use common\models\Backup;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('backend', 'Backups');
$this->params['breadcrumbs'] = [
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Backup::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Backup::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreBackup') && $showTrash,
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
				'dt-table' => '#dt-backups',
			],
		],
	],
//	[
//		'visible' => Yii::$app->user->can('deleteBackup'),
//		'tag' => 'button',
//		'icon' => 'fa fa-trash',
//		'options' => [
//			'class' => 'btn btn-sm btn-danger hidden',
//			'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
//			'data' => [
//				'toggle' => 'tooltip',
//				'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
//				'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
//				'dt-url' => Url::to(['delete']),
//				'dt-table' => '#dt-backups',
//			],
//		],
//	],
	[
		'visible' => Yii::$app->user->can('createBackup'),
		'tag' => 'a',
		'url' => ['create'],
		'icon' => 'fa fa-plus',
		'options' => [
			'class' => 'btn btn-sm btn-success',
			'title' => Yii::t('common', 'Create'),
			'data' => [
				'toggle' => 'tooltip',
				'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
			],
		],
	],
];
?>

<div class="dt-scroll-x">
<?= DataTable::widget([
	'id' => 'dt-backups',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-backups']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? Backup::YES : null) . ';
			}'),
			'reloadInterval' => 1 * 60000,
		],
		'order' => [
			[3, 'desc'],
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
				'data' => 'file_size',
				'title' => Yii::t('label', 'Size'),
				'filter' => false,
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'created_by',
				'title' => Yii::t('label', 'Created By'),
				'filter' => ['text'],
			],
			[
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Backup::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-backups").on("draw.dt", function () {
        $("#dt-backups").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-backups").outerWidth());
	});
	$("#dt-backups").on("column-visibility.dt", function () {
        $("#dt-backups").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-backups").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-backups").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-backups").outerWidth());
	});
');
?>
