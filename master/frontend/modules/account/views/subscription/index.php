<?php
/* @var $this yii\web\View */

use common\models\Subscription;
use common\widgets\datatable\DataTable;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\helpers\Inflector;
use yii\web\JsExpression;

$this->params['breadcrumbs'][] = Html::encode($this->title);

$tabPackages = Inflector::slug(Yii::t('common', 'Packages'));
$tabFeatures = Inflector::slug(Yii::t('common', 'Features'));
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<div class="tabs-custom">
			<ul class="nav nav-tabs nav-justified nav-tabs-hash" role="tablist">
				<li class="active" role="presentation">
					<a class="font-medium" href="#<?= $tabPackages ?>" data-toggle="tab" aria-controls="<?= $tabPackages ?>" role="tab"><?= Yii::t('common', 'Packages') ?></a>
				</li>
				<li role="presentation">
					<a class="font-medium" href="#<?= $tabFeatures ?>" data-toggle="tab" aria-controls="<?= $tabFeatures ?>" role="tab"><?= Yii::t('common', 'Features') ?></a>
				</li>
			</ul>
			<div class="tab-content">
				<div id="<?= $tabPackages ?>" class="tab-pane active" role="tabpanel">
					<?= DataTable::widget([
						'id' => 'dt-package-subscriptions',
						'options' => [
							'class' => 'table table-bordered table-hover',
						],
						'showColumnFilters' => false,
						'clientOptions' => [
							'deferRender' => true,
							'processing' => true,
							'serverSide' => true,
							'ajax' => [
								'url' => Url::to(['dt-package-subscriptions']),
								'method' => 'POST',
								'reloadInterval' => 5 * 60000,
							],
							'order' => [
								[4, 'asc'],
								[5, 'desc'],
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
									'data' => 'package',
									'title' => Yii::t('label', 'Package'),
									'filter' => ['text'],
								],
								[
									'data' => 'price',
									'title' => Yii::t('label', 'Price'),
									'filter' => ['text'],
								],
								[
									'data' => 'end_at',
									'title' => Yii::t('label', 'Next Due At'),
									'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
								],
								[
									'data' => 'status',
									'title' => Yii::t('label', 'Status'),
									'className' => 'col-autowidth',
									'filter' => ['select', ArrayHelper::getColumn(Subscription::getStatusLabels(), 'label')],
								],
							],
						],
					]) ?>
				</div>
				<div id="<?= $tabFeatures ?>" class="tab-pane" role="tabpanel">
					<?= DataTable::widget([
						'id' => 'dt-feature-subscriptions',
						'options' => [
							'class' => 'table table-bordered table-hover',
						],
						'showColumnFilters' => false,
						'clientOptions' => [
							'deferRender' => true,
							'processing' => true,
							'serverSide' => true,
							'ajax' => [
								'url' => Url::to(['dt-feature-subscriptions']),
								'method' => 'POST',
								'reloadInterval' => 5 * 60000,
							],
							'order' => [
								[4, 'asc'],
								[5, 'desc'],
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
									'data' => 'price',
									'title' => Yii::t('label', 'Price'),
									'filter' => ['text'],
								],
								[
									'data' => 'end_at',
									'title' => Yii::t('label', 'Next Due At'),
									'filter' => ['date', 'icu:' . Yii::$app->settings->get('dateFormat')],
								],
								[
									'data' => 'status',
									'title' => Yii::t('label', 'Status'),
									'className' => 'col-autowidth',
									'filter' => ['select', ArrayHelper::getColumn(Subscription::getStatusLabels(), 'label')],
								],
							],
						],
					]) ?>
				</div>
			</div>
		</div>

		<div class="text-center gap-t-xlg">
			<?= Html::a(Yii::t('common', 'Buy Packages'), ['/account/payment/package'], ['class' => 'btn btn-lg btn-default btn-slide-right']) ?>
		</div>
	</div>
</div>
