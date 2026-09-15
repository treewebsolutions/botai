<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\KnowledgeBase */

use common\models\KnowledgeBase;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
		'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
	],
	'validateOnType' => true,
]); ?>
	<div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
		<?php if (Yii::$app->request->isAjax): ?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div class="modal-title"><?= $this->title ?></div>
			</div>
		<?php endif; ?>

		<div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
			<?php if ($model->hasErrors()): ?>
				<div class="alert alert-danger alert-dismissible" role="alert">
					<div class="alert-table">
						<div class="row">
							<div class="icon">
								<span class="glyphicon glyphicon-remove-sign"></span>
							</div>
							<?= $form->errorSummary($model, ['header' => '']) ?>
							<div class="close">
								<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'status')->widget(Select2::class, [
						'data' => ArrayHelper::getColumn(KnowledgeBase::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'provider')->widget(Select2::class, [
						'data' => [null => Yii::t('label', 'Generic')] + KnowledgeBase::getProviderLabels(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'expire_at')->widget(DateTimePicker::class, [
						'options' => [
							'value' => Yii::$app->formatter->asDatetime($model->expire_at),
						],
						'clientOptions' => [
							'format' => 'icu:' . Yii::$app->settings->get('datetimeFormat'),
							'ignoreReadonly' => true,
							'showTodayButton' => true,
							'showClear' => true,
							'showClose' => true,
							'allowInputToggle' => true,
							'useCurrent' => false,
						],
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'name')->textInput() ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'embedding_model')->textInput() ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chunk_size')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 100,
							'max' => 10000,
							'step' => 100,
							'decimals' => 0,
							'boostat' => 500,
							'maxboostedstep' => 1000,
							'verticalbuttons' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chunk_overlap')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'max' => 1000,
							'step' => 50,
							'decimals' => 0,
							'boostat' => 100,
							'maxboostedstep' => 200,
							'verticalbuttons' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'tokens_per_file')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'step' => 1,
							'decimals' => 0,
							'boostat' => 100,
							'maxboostedstep' => 500,
							'verticalbuttons' => true,
						],
					]) ?>
				</div>
			</div>

		<?php if (Yii::$app->request->isAjax): ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
				<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success']) ?>
			</div>
		<?php else: ?>
			<div class="form-actions floating">
				<?= Html::submitButton('<span class="fa fa-check"></span>', [
					'class' => 'btn btn-xlg btn-fab btn-success',
					'title' => Yii::t('common', 'Save'),
					'data' => [
						'toggle' => 'tooltip',
					],
				]) ?>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>

