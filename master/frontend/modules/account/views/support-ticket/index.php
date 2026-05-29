<?php
/* @var $this yii\web\View */

use common\widgets\datatable\DataTable;
use yii\helpers\Html;
use tws\helpers\Url;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<?= DataTable::widget([
	'id' => 'dt-support-tickets',
	'options' => [
		'class' => 'table table-bordered table-hover',
	],
	'showColumnFilters' => false,
	'clientOptions' => [
		'deferRender' => true,
		'processing' => true,
		'serverSide' => true,
		'ajax' => [
			'url' => Url::to(['support-ticket/dt-support-tickets']),
			'method' => 'POST',
		],
		'order' => [
			[1, 'desc'],
		],
		'pageLength' => Yii::$app->settings->get('itemsPerPage'),
		'lengthMenu' => [
			'autoCreate' => true,
			'displayAll' => Yii::t('common', 'All'),
		],
		'autoWidth' => false,
		'responsive' => true,
		'columns' => [
			[
				'data' => 'action',
				'class' => 'common\widgets\datatable\ActionColumn',
				'title' => Yii::t('common', 'Action'),
				'className' => 'action-column col-autowidth text-center',
			],
			[
				'data' => 'number',
				'title' => '#',
				'className' => 'col-autowidth',
				'filter' => ['text'],
			],
			[
				'data' => 'subject',
				'title' => Yii::t('label', 'Subject'),
				'filter' => ['text'],
			],
			[
				'data' => 'department',
				'title' => Yii::t('label', 'Department'),
				'filter' => ['text'],
			],
			[
				'data' => 'priority',
				'title' => Yii::t('label', 'Priority'),
				'filter' => ['text'],
			],
			[
				'data' => 'subscription',
				'title' => Yii::t('label', 'Subscription'),
				'filter' => ['text'],
			],
			[
				'data' => 'created_at',
				'title' => Yii::t('label', 'Created At'),
				'className' => 'col-autowidth',
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'updated_at',
				'title' => Yii::t('label', 'Updated At'),
				'className' => 'col-autowidth',
				'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
			],
			[
				'data' => 'status',
				'title' => Yii::t('label', 'Status'),
				'className' => 'col-autowidth',
				'filter' => ['text'],
			],
		],
	],
]) ?>

<div class="text-center gap-t-xlg">
	<?= Html::a(Yii::t('common', 'Open a Ticket'), ['support-ticket/create'], [
		'class' => 'btn btn-lg btn-default btn-slide-right',
		'data' => [
			'popup-action' => '',
			'popup-done' => ['redrawDataTable' => '#dt-support-tickets'],
		],
	]) ?>
</div>
