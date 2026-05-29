<?php
/* @var $this yii\web\View */
/* @var $model common\models\Picture */

use yii\helpers\Html;

$page_id = Yii::$app->request->get('page_id');

$this->title = Yii::t('common', 'Create {item}', ['item' => Yii::t('common', 'Picture')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('common', 'Pages'),
		'url' => ['page/index'],
	],
	[
		'label' => \common\models\Page::findOne($page_id)->translation->title,
		'url' => ['page/view', 'id' => $page_id],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewPage'),
		'tag' => 'a',
		'url' => ['index', 'page_id' => $page_id],
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
