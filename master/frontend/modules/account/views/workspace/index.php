<?php
/* @var $this yii\web\View */

use common\models\Workspace;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<?= DataTable::widget([
			'id' => 'dt-workspaces',
			'options' => [
				'class' => 'table table-bordered table-hover',
			],
			'showColumnFilters' => false,
			'clientOptions' => [
				'deferRender' => true,
				'processing' => true,
				'serverSide' => true,
				'ajax' => [
					'url' => Url::to(['workspace/dt-workspaces']),
					'method' => 'POST',
				],
				'order' => [
					[2, 'asc'],
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
					],
					[
						'data' => 'code',
						'title' => Yii::t('label', 'Code'),
						'filter' => ['text'],
						'className' => 'col-autowidth',
					],
					[
						'data' => 'subscription',
						'title' => Yii::t('label', 'Subscription'),
						'filter' => ['text'],
					],
					[
						'data' => 'url',
						'title' => Yii::t('label', 'URL'),
						'filter' => ['text'],
					],
					[
						'data' => 'created_at',
						'title' => Yii::t('label', 'Created At'),
						'className' => 'col-autowidth',
						'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
					],
				],
			],
		]) ?>

		<div class="text-center gap-t-xlg">
			<?= Html::a(Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Workspace')]), ['create'], [
				'class' => 'btn btn-lg btn-default btn-slide-right',
				'data' => [
					'popup-action' => '',
					'popup-done' => ['redrawDataTable' => '#dt-workspaces'],
				],
			]) ?>
		</div>
	</div>
</div>
