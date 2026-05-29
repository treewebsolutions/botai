<?php

/* @var $this yii\web\View */
/* @var $models common\models\ImportColumn[] */
/* @var $sheet_id int */
/* @var $sheetColumns array */
/* @var $spreadsheetImport array */

use common\models\ImportColumn;
use backend\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\helpers\Inflector;

$this->title = Yii::t('import', 'Map Data');

/** @var \yii\base\Model $targetModel */
$targetModel = new $spreadsheetImport['model'];
$targetModelAttributeLabels = $targetModel->attributeLabels();
?>

<?= $this->render('_nav', [
	'activeStep' => 'map',
]) ?>

<?php $form = ActiveForm::begin([
	'id' => 'spreadsheet-form-map',
	'action' => ['map', 'id' => $sheet_id],
	'options' => [
		'novalidate' => true,
	],
]); ?>

	<fieldset class="fieldset">
		<legend><?= Yii::t('common', 'Columns') ?></legend>
		<?php $i = 0; foreach ($models as $model) : ?>
			<div class="row">
				<div class="col-sm-5">
					<?= $form->field($model, "[$i]source", [
						'template' => '{input}',
						'options' => [
							'tag' => false,
						],
					])->hiddenInput() ?>
					<?= $form->field($model, "[$i]source_index")->widget(Select2::class, [
						'options' => [
							'data' => [
								'autofill-target' => '#' . Html::getInputId($model, "[$i]source"),
								'autofill-data' => ':text',
							],
						],
						'data' => $sheetColumns,
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					])->label($targetModelAttributeLabels[$spreadsheetImport['columns'][$i]] ?: Yii::t('common', Inflector::humanize(str_replace('['. Yii::$app->language . ']', '', $spreadsheetImport['columns'][$i]), true))) ?>
				</div>
				<div class="col-sm-5">
					<?= $form->field($model, "[$i]field_type")->widget(Select2::class, [
						'data' => ImportColumn::getFieldTypeLabels(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					]) ?>
				</div>
				<div class="col-sm-2">
					<?= $form->field($model, "[$i]sort_order")->input('number', ['min' => 0]) ?>
				</div>
			</div>
		<?php $i++; endforeach; ?>
	</fieldset>

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
