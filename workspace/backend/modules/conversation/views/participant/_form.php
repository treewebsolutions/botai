<?php
/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\Participant */

use common\models\Participant;
use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\helpers\Json;
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'enctype' => 'multipart/form-data',
		'novalidate' => true,
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
		<?php if ($model->hasErrors() && empty(array_intersect_key($model->getErrors(), array_flip($model->safeAttributes())))): ?>
			<?= $form->errorSummary($model, [
				'header' => false,
				'class' => 'alert alert-danger alert-icon',
			]) ?>
		<?php endif; ?>
		<div class="row">
			<div class="col-sm-3">
				<?= $form->field($model, 'status')->widget(Select2::class, [
					'data' => ArrayHelper::getColumn(Participant::getStatusLabels(), 'label'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'placeholder' => Yii::t('common', 'Choose'),
					],
				]) ?>
			</div>
			<div class="col-sm-3">
				<?= $form->field($model, 'username')->textInput() ?>
			</div>
			<div class="col-sm-3">
				<?= $form->field($model, 'email')->textInput() ?>
			</div>
			<div class="col-sm-3">
				<?= $form->field($model, 'gender')->inline()->radioList(Participant::getGenderLabels()) ?>
			</div>
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
