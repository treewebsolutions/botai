<?php

/* @var $this yii\web\View */

use common\models\KnowledgeBase;
use common\models\KnowledgeBaseDocument;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Knowledge Base Documents');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == KnowledgeBaseDocument::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => KnowledgeBaseDocument::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$knowledgeBaseId = (int) Yii::$app->request->get('knowledge_base_id');

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreKnowledgeBaseDocument') && $showTrash,
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
				'dt-table' => '#dt-knowledge-base-documents',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteKnowledgeBaseDocument'),
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
				'dt-table' => '#dt-knowledge-base-documents',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createKnowledgeBaseDocument'),
		'tag' => 'a',
		'url' => $knowledgeBaseId ? ['create', 'knowledge_base_id' => $knowledgeBaseId] : ['create'],
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
	'id' => 'dt-knowledge-base-documents',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-knowledge-base-documents']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? KnowledgeBaseDocument::YES : null) . ';
				data.knowledge_base_id = ' . json_encode($knowledgeBaseId ?: null) . ';
			}'),
		],
		'order' => [
			[8, 'desc'],
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
				'data' => 'knowledge_base',
				'title' => Yii::t('label', 'Knowledge Base'),
				'filter' => ['select', ArrayHelper::map(KnowledgeBase::findAllKnowledgeBases(), 'name', 'name')],
			],
			[
				'data' => 'file',
				'title' => Yii::t('label', 'File'),
				'filter' => ['text'],
			],
			[
				'data' => 'extension',
				'title' => Yii::t('label', 'Extension'),
				'className' => 'col-autowidth',
				'filter' => ['select', array_combine(KnowledgeBaseDocument::ALLOWED_EXTENSIONS, array_map('strtoupper', KnowledgeBaseDocument::ALLOWED_EXTENSIONS))],
			],
			[
				'data' => 'size',
				'title' => Yii::t('label', 'Size'),
				'className' => 'col-autowidth',
				'searchable' => false,
			],
			[
				'data' => 'index_status',
				'title' => Yii::t('label', 'Index Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(KnowledgeBaseDocument::getIndexStatusLabels(), 'label')],
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
				'filter' => ['select', ArrayHelper::getColumn(KnowledgeBaseDocument::getStatusLabels(), 'label')],
			],
		],
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-knowledge-base-documents").on("draw.dt", function () {
        $("#dt-knowledge-base-documents").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-base-documents").outerWidth());
	});
	$("#dt-knowledge-base-documents").on("column-visibility.dt", function () {
        $("#dt-knowledge-base-documents").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-base-documents").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-knowledge-base-documents").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-knowledge-base-documents").outerWidth());
	});
');
?>
