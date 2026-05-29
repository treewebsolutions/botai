<?php

/* @var $this yii\web\View */
/* @var $model common\models\ImportSheet */
/* @var $documentSheets array */
/* @var $file_id int */

use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Html;

$this->title = Yii::t('import', 'Select The Sheet');
?>

<?= $this->render('_nav', [
	'activeStep' => 'sheet',
]) ?>

<?php $form = ActiveForm::begin([
	'id' => 'spreadsheet-form-sheet',
	'action' => ['sheet', 'id' => $file_id],
	'options' => [
		'novalidate' => true,
	],
]); ?>

	<?= $form->field($model, 'number')->widget(Select2::class, [
		'data' => $documentSheets,
		'pluginLoading' => false,
		'pluginOptions' => [
			'allowClear' => false,
			'placeholder' => Yii::t('common', 'Choose'),
		],
	]) ?>

	<?= $form->field($model, 'header')->input('number', [
		'min' => 0,
		'step' => 1,
	])->hint(Yii::t('import', 'The sheet\'s row index that contains the table head. Leave it blank if the sheet does not have a header.')) ?>

	<div class="form-actions floating">
		<?= Html::submitButton('<span class="fa fa-arrow-right"></span>', [
			'class' => 'btn btn-xlg btn-fab btn-success',
			'title' => Yii::t('common', 'Continue'),
			'data' => [
				'toggle' => 'tooltip',
			],
		]) ?>
	</div>
<?php ActiveForm::end(); ?>
