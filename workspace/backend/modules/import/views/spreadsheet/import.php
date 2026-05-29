<?php

/* @var $this yii\web\View */
/* @var $model yii\base\Model */
/* @var $sheet_id int */

use backend\widgets\ActiveForm;
use common\widgets\datatable\DataTable;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Import');
?>

<?= $this->render('_nav', [
	'activeStep' => 'import',
]) ?>

<?php $form = ActiveForm::begin([
	'id' => 'spreadsheet-form-import',
	'action' => ['import', 'id' => $sheet_id],
	'options' => [
		'novalidate' => true,
	],
]); ?>

	<?= $form->field($model, 'sheet_id', [
		'template' => '{input}',
		'options' => [
			'tag' => false,
		],
	])->hiddenInput() ?>

	<?= DataTable::widget([
		'id' => 'dt-files',
		'options' => [
			'class' => 'table table-bordered table-hover',
		],
		'showColumnFilters' => true,
		'clientOptions' => [
			'deferRender' => true,
			'processing' => true,
			'serverSide' => true,
			'ajax' => [
				'url' => Url::to(['dt-columns']),
				'method' => 'POST',
				'data' => new JsExpression('function (data) {
				 	data.sheet_id = "' . $sheet_id . '"; 
				}'),
			],
			'order' => [
				[5, 'asc'],
			],
			'pageLength' => 100,
			'lengthMenu' => [
				'autoCreate' => true,
				'displayAll' => Yii::t('common', 'All'),
			],
			'autoWidth' => false,
			'columns' => [
				[
					'class' => 'common\widgets\datatable\SerialColumn',
					'filter' => ['clear'],
				],
				[
					'data' => 'target',
					'title' => Yii::t('label', 'Target'),
					'filter' => ['text'],
				],
				[
					'data' => 'source',
					'title' => Yii::t('label', 'Source'),
					'filter' => ['text'],
				],
				[
					'data' => 'source_index',
					'title' => Yii::t('label', 'Source Index'),
					'filter' => ['text'],
				],
				[
					'data' => 'field_type',
					'title' => Yii::t('label', 'Field Type'),
					'filter' => ['text'],
				],
				[
					'data' => 'sort_order',
					'title' => Yii::t('label', 'Sort Order'),
					'filter' => ['text'],
				],
			],
		],
	]) ?>

	<div class="form-actions floating">
		<?= Html::submitButton('<span class="fa fa-check"></span>', [
			'class' => 'btn btn-xlg btn-fab btn-success',
			'title' => Yii::t('common', 'Save'),
			'data' => [
				'toggle' => 'tooltip',
			],
		]) ?>
	</div>
<?php ActiveForm::end(); ?>
