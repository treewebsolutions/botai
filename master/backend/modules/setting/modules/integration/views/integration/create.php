<?php

/* @var $this yii\web\View */
/* @var $model backend\modules\setting\modules\integration\models\IntegrationForm */

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Integration')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Settings'),
		'url' => ['/setting/default/index'],
	],
	[
		'label' => Yii::t('common', 'Integrations'),
		'url' => ['index'],
	],
	Yii::t('common', 'Create'),
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewIntegration'),
		'tag' => 'a',
		'url' => ['index'],
		'icon' => 'fa fa-list',
		'options' => [
			'class' => 'btn btn-sm btn-default',
			'title' => Yii::t('common', 'List'),
			'data' => [
				'toggle' => 'tooltip',
			],
		],
	],
];
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>
