<?php

/* @var $this yii\web\View */

use backend\modules\conversation\models\MessageSearch;
use backend\modules\export\widgets\export\Export;
use common\models\Message;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Messages');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Conversations'),
		'url' => ['default/index'],
	],
	$this->title,
];

if ($showTrash = Yii::$app->settings->get('enableSoftDelete')) {
	$showTrash = Yii::$app->request->get('deleted') == Message::YES;
	$this->params['breadcrumbs'][] = [
		'template' => '<li class="page-links">{link}</li>',
		'label' => implode('', [
			Html::a(Yii::t('common', 'Current'), ['index'], ['class' => $showTrash ? '' : 'active']),
			Html::a(Yii::t('common', 'Trash'), ['index', 'deleted' => Message::YES], ['class' => $showTrash ? 'active' : '']),
		]),
	];
}

$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('restoreMessage') && $showTrash,
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
				'dt-table' => '#dt-messages',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('deleteMessage'),
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
				'dt-table' => '#dt-messages',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createMessage'),
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
	[
		'visible' => true,
		'content' => Export::widget([
			'buttonOptions' => [
				'class' => 'btn btn-sm btn-default',
				'style' => 'margin: 0 5px;',
				'title' => Yii::t('common', 'Export'),
				'label' => '<span class="fa fa-file-pdf-o"></span>',
			],
			'dropdownOptions' => [
				'class' => 'pull-right',
			],
			'items' => [
				Export::FORMAT_PDF => [
					'visible' => true,
					'label' => 'PDF',
					'url' => '#',
					'clientOptions' => [
						'allowHtml' => false,
					],
				],
			],
			'clientOptions' => [
				'url' => Url::to(['/export-manager/export']),
				'dataTable' => '#dt-messages',
				'model' => MessageSearch::class,
				'title' => Yii::t('common', 'Messages'),
				'excludedColumns' => ['action'],
			],
		]),
	],
];
?>

<div class="dt-scroll-x">
<?= DataTable::widget([
	'id' => 'dt-messages',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => true,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['dt-messages']),
			'method' => 'POST',
			'data' => new JsExpression('function (data) {
				data.deleted = ' . json_encode($showTrash ? Message::YES : null) . ';
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
				'data' => 'content',
				'title' => Yii::t('label', 'Content'),
				'className' => 'col-autowidth',
				'filter' => ['text'],
			],
			[
				'data' => 'role',
				'title' => Yii::t('label', 'Role'),
				'className' => 'col-autowidth',
				'filter' => ['select', array_keys(Message::getRoleLabels())],
			],
			[
				'data' => 'thread',
				'title' => Yii::t('label', 'Thread'),
				'filter' => ['text'],
			],
			[
				'data' => 'assistant',
				'title' => Yii::t('label', 'Assistant'),
				'filter' => ['text'],
			],
			[
				'data' => 'openai_id',
				'title' => Yii::t('label', 'OpenAI ID'),
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
				'data' => 'completed_at',
				'title' => Yii::t('label', 'Completed At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'incomplete_at',
				'title' => Yii::t('label', 'Incomplete At'),
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'incomplete_reason',
				'title' => Yii::t('label', 'Incomplete Reason'),
				'className' => 'col-autowidth',
				'filter' => ['text'],
			],
			[
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['select', ArrayHelper::getColumn(Message::getStatusLabels(), 'label')],
			],
		],
		'rowCallback' => new JsExpression('function (row, data, index) {
			if (data.defaultValue == "' . Message::YES . '") {
				$(row).addClass("success");
			}
		}'),
	],
]) ?>
</div>

<?php
$this->registerJs('
	$("#dt-messages").on("draw.dt", function () {
        $("#dt-messages").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-messages").outerWidth());
	});
	$("#dt-messages").on("column-visibility.dt", function () {
        $("#dt-messages").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-messages").outerWidth());
	});
	$(window).resize(function () {
        $("#dt-messages").parent().doubleScroll();
		$(".doubleScroll-scroll").width($("#dt-messages").outerWidth());
	});
');
?>
