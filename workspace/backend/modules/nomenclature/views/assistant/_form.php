<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\Assistant */

use common\models\Assistant;
use backend\widgets\ActiveForm;
use common\models\VectorStore;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
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
				<div class="col-sm-3">
					<?= $form->field($model, 'status')->widget(Select2::class, [
						'data' => ArrayHelper::getColumn(Assistant::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'gpt_model')->widget(Select2::class, [
						'data' => Assistant::getGPTModels(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-2">
					<?= $form->field($model, 'temperature')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'max' => 2,
							'step' => 0.01,
							'decimals' => 2,
							'boostat' => 0.1,
							'maxboostedstep' => 1,
							'verticalbuttons' => true,
						],
					])->hint(Yii::t('common', 'Controls randomness: High temperature → More randomness; Low temperature → More deterministic.')) ?>
				</div>
				<div class="col-sm-2">
					<?= $form->field($model, 'top_p')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 0,
							'max' => 1,
							'step' => 0.01,
							'decimals' => 2,
							'boostat' => 0.1,
							'maxboostedstep' => 0.5,
							'verticalbuttons' => true,
						],
					])->hint(Yii::t('common', 'Controls the probability range: Smaller top_p → More focused choices')) ?>
				</div>
				<div class="col-sm-2">
					<div class="control-label hidden-xs">&nbsp;</div>
					<?= $form->field($model, 'default')->checkbox(['uncheck' => null]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'name')->textInput() ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'instructions')->textarea([
						'rows' => 6,
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'vector_store_id')->widget(Select2::class, [
						'options' => [
							'multiple' => false,
						],
						'data' => ArrayHelper::map(VectorStore::findAllVectorStores(), 'id', 'name'),
						'maintainOrder' => true,
						'showToggleAll' => false,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
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
