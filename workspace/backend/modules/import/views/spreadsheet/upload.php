<?php

/* @var $this yii\web\View */
/* @var $model common\models\ImportFile */

use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = Yii::t('common', 'Upload');
?>

<?= $this->render('_nav', [
	'activeStep' => 'upload',
]) ?>

<?php $form = ActiveForm::begin([
	'id' => 'spreadsheet-form-upload',
	'action' => ['upload'],
	'options' => [
		'enctype' => 'multipart/form-data',
		'novalidate' => true,
	],
]); ?>

	<?php if ($files = \common\models\ImportFile::findAllFiles()) : ?>
		<?= $form->field($model, 'file_id')->widget(Select2::class, [
			'data' => ArrayHelper::map($files, 'id', 'fullName'),
			'pluginLoading' => false,
			'pluginOptions' => [
				'allowClear' => true,
				'placeholder' => Yii::t('common', 'Choose'),
			],
			'pluginEvents' => [
				'change' => new \yii\web\JsExpression('function () {
					$("#' . Html::getInputId($model, 'file') . '").val("").trigger("change");
				}'),
			],
		])->label(Yii::t('common', 'Choose from a list of existing files')) ?>

		<p><?= Yii::t('common', 'Or') ?></p>
	<?php endif; ?>

	<?= $form->field($model, 'file')->widget(FileInput::class, [
		'options' => [
			'accept' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel'
		],
		'resizeImages' => false,
		'sortThumbs' => false,
		'purifyHtml' => false,
		'pluginOptions' => [
			'allowedFileExtensions' => ['xls', 'xlsx', 'xml', 'ods', 'slk', 'gnumeric', 'csv'],
			'maxFileSize' => 100 * 1024 * 1024,
			'dropZoneEnabled' => true,
			'showClose' => false,
			'showUpload' => false,
			'showCaption' => true,
			'showRemove' => true,
			'showPreview' => true,
			'hideThumbnailContent' => false,
			'fileActionSettings' => [
				'showDownload' => false,
				'showRemove' => false,
				'showUpload' => false,
				'showZoom' => false,
				'showDrag' => false,
			],
			'overwriteInitial' => true,
		],
		'pluginEvents' => [
			'fileselect' => new \yii\web\JsExpression('function () {
				$("#' . Html::getInputId($model, 'file_id') . '").val("").trigger("change.select2");
			}'),
		],
	])->label(Yii::t('common', 'Upload a new file')) ?>

	<?= $form->field($model, 'name')->textInput()->hint(Yii::t('common', 'The new uploaded file name')) ?>

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
