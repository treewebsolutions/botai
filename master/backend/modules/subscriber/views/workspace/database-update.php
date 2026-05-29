<?php

/* @var $this yii\web\View */
/* @var $model common\models\Workspace */
/* @var $form backend\widgets\ActiveForm */

use yii\helpers\Html;
use backend\widgets\ActiveForm;


$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Workspace')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Subscribers'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('common', 'Workspaces'),
		'url' => ['workspace/index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewWorkspace'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'Workspaces'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
	[
		'visible' => Yii::$app->user->can('createWorkspace'),
		'tag' => 'a',
		'url' => ['create'],
		'icon' => 'fa fa-plus',
		'options' => [
			'class' => 'btn btn-sm btn-success',
			'title' => Yii::t('common', 'Create'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
];
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
<div class="row">
	<div class="col-sm-12">
		<?= $form->field($model, "query")->textarea(['rows' => 25]) ?>
	</div>
</div>
<div class="form-actions floating">
	<?= Html::submitButton('<span class="fa fa-check"></span>', [
		'class' => 'btn btn-xlg btn-fab btn-success',
		'title' => Yii::t('common', 'Save'),
		'data' => [
			'toggle' => 'tooltip',
		]
	]) ?>
</div>
<?php ActiveForm::end(); ?>
