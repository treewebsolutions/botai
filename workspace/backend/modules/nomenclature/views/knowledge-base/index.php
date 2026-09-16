<?php

/* @var $this yii\web\View */

use common\models\KnowledgeBase;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Knowledge Bases');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == KnowledgeBase::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => KnowledgeBase::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreKnowledgeBase') && $showTrash,
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
				'dt-table' => '#dt-knowledge-bases',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteKnowledgeBase'),
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
				'dt-table' => '#dt-knowledge-bases',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createKnowledgeBase'),
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
	'id' => 'dt-knowledge-bases',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-knowledge-bases']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? KnowledgeBase::YES : null) . ';
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
				'data' => 'name',
				'title' => Yii::t('label', 'Name'),
				'filter' => ['text'],
			],
			[
				'data' => 'description',
				'title' => Yii::t('label', 'Description'),
				'filter' => ['text'],
			],
			[
				'data' => 'provider',
				'title' => Yii::t('label', 'Provider'),
				'className' => 'col-autowidth',
				'filter' => ['select', ['' => Yii::t('label', 'Generic')] + KnowledgeBase::getProviderLabels()],
			],
			[
				'data' => 'vector_store_id',
				'title' => Yii::t('label', 'Vector Store ID'),
				'filter' => ['text'],
			],
			[
				'data' => 'embedding_model',
				'title' => Yii::t('label', 'Embedding Model'),
				'filter' => ['text'],
			],
			[
				'data' => 'chunk_size',
				'title' => Yii::t('label', 'Chunk Size'),
				'filter' => ['text'],
			],
			[
				'data' => 'chunk_overlap',
				'title' => Yii::t('label', 'Chunk Overlap'),
				'filter' => ['text'],
			],
			[
				'data' => 'tokens_per_file',
				'title' => Yii::t('label', 'Tokens Per File'),
				'filter' => ['text'],
			],
			[
				'data' => 'default',
				'title' => Yii::t('label', 'Default'),
				'filter' => ['text'],
			],
			[
				'data' => 'expire_at',
				'title' => Yii::t('label', 'Expire At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
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
				'filter' => ['select', ArrayHelper::getColumn(KnowledgeBase::getStatusLabels(), 'label')],
			],
		],
		'rowCallback' => new JsExpression('function (row, data, index) {
			if (data.defaultValue == "' . KnowledgeBase::YES . '") {
				$(row).addClass("success");
			}
		}'),
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-knowledge-bases").on("draw.dt", function () {
        $("#dt-knowledge-bases").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-bases").outerWidth());
	});
	$("#dt-knowledge-bases").on("column-visibility.dt", function () {
        $("#dt-knowledge-bases").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-bases").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-knowledge-bases").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-bases").outerWidth());
	});
');
?>

