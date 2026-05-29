<?php

/* @var $this yii\web\View */
/* @var $model common\models\Participant */

use yii\helpers\Html;

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('backend', 'Participant')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Conversations'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('backend', 'Participants'),
		'url' => ['index'],
	],
	Yii::t('common', 'Create'),
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewParticipant'),
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
