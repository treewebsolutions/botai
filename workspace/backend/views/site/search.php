<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $searchModel backend\models\SearchForm */
/* @var $dataProvider \yii\data\ActiveDataProvider */

use common\models\ServiceRepairStatusCategory;
use backend\widgets\ActiveForm;
use common\helpers\DateHelper;
use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('common', 'Search');
$this->params['breadcrumbs'][] = $this->title;
$this->params['bodyAttributes']['class'][] = 'page-container-bg-solid';

$totalCount = $dataProvider->getTotalCount();
?>

<?php $form = ActiveForm::begin([
	'method' => 'GET',
	'action' => ['/site/search'],
	'validateOnType' => true,
]); ?>
	<div class="search-page search-content-4">
		<div class="search-bar margin-bottom-20">
			<?= $form->field($searchModel, 'q', [
				'options' => [
					'class' => 'm0',
				],
				'template' => '
					<div class="input-group">
						{input}
						<span class="input-group-btn">
							<button type="submit" class="btn blue uppercase bold">' . Yii::t('common', 'Search') . '</button>
						</span>
					</div>
					{error}
					{hint}',
			])->textInput([
				'placeholder' => Yii::t('common', 'Search') . '...',
			])->label(false) ?>
		</div>

		<?php if ($totalCount) : ?>
			<h3 class="font-lg bold">
				<?php if ($totalCount === 1) : ?>
					<?= Yii::t('common', '{n} record was found', ['n' => $totalCount]) ?>
				<?php else : ?>
					<?= Yii::t('common', '{n} records were found', ['n' => $totalCount]) ?>
				<?php endif; ?>
			</h3>
			<div class="search-table table-responsive">
				<table class="table table-bordered table-striped table-condensed table-hover">
					<thead class="bg-blue">
						<tr>
							<th class="font-white"><?= Yii::t('label', 'ID') ?></th>
							<th class="font-white"><?= Yii::t('label', 'Authors') ?></th>
							<th class="font-white"><?= Yii::t('label', 'Description') ?></th>
							<th class="font-white"><?= Yii::t('label', 'Status') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($dataProvider->getModels() as $model) : ?>
							<tr>
								<td class="col-autowidth font-blue bold">
									<?= Html::a($model->name, ['/contractor-manager/view', 'id' => $model->id]) ?>
								</td>
								<td class="table-title">
									<p>
										<?= Yii::t('label', 'Created By') ?>:
										<?= Html::a($model->creator->fullName, ['/user-manager/user/view', 'id' => $model->creator->id]) ?> -
										<?= DateHelper::formatAsRelativeTime($model->created_at) ?>
									</p>
									<p>
										<?= Yii::t('label', 'Last Activity') ?>:
										<?= Html::a($model->updater->fullName, ['/user-manager/user/view', 'id' => $model->updater->id]) ?> -
										<?= DateHelper::formatAsRelativeTime($model->updated_at) ?>
									</p>
								</td>
								<td>
									<?= implode(' - ', array_filter([
										Html::a($model->name, ['/contractor-manager/view', 'id' => $model->id]),
										$model->email,
										$model->phone,
									])) ?>
								</td>
								<td class="col-autowidth">
                                    <?php $status =  $model::getStatusLabels()[$model->status]; ?>
                                    <?= Html::tag('span', $status['label'], ['class' => 'label label-' . $status['color']]) ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<div class="search-pagination">
				<?= \yii\widgets\LinkPager::widget([
					'pagination' => $dataProvider->getPagination(),
					'activePageCssClass' => 'page-active',
				]) ?>
			</div>
		<?php else : ?>
			<div class="note note-info">
				<h4 class="block"><?= Yii::t('common', 'No records found.') ?></h4>
				<p><?= Yii::t('common', 'No records found matching the filters.') ?></p>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>

<?php
$this->registerCssFile(Url::home(true) . 'template/pages/css/search.min.css');
?>
