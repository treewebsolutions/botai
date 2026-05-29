<?php

/* @var $this yii\web\View */
/* @var $model common\models\Page */

use common\models\Page;
use tws\helpers\Url;
use yii\helpers\Html;

$this->title = Yii::t('common', 'Update {item}', ['item' => Yii::t('common', 'Page')]);
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Nomenclature'),
		'url' => ['default/index'],
	],
	[
		'label' => Yii::t('common', 'Pages'),
		'url' => ['index'],
	],
	$this->title,
];
$this->params['actions'] = [
	[
		'visible' => Yii::$app->user->can('viewPage'),
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
	[
		'visible' => Yii::$app->user->can('createPage'),
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

$requestQueryParams = Yii::$app->request->getQueryParams();
$requestQueryParams['page_id'] = $model->id;
Yii::$app->request->setQueryParams($requestQueryParams);
?>

<?= $this->render('_form', [
	'model' => $model,
]) ?>

<div class="panel blue-hoki">
	<div class="panel-title">
		<div class="panel-heading">
			<?= Yii::t('label', 'Images') ?>
			<?php if ($showTrash = Yii::$app->settings->get('enableSoftDelete')): ?>
				<?php $showTrash = Yii::$app->request->get('deleted') == Page::YES; ?>
				<span class="page-links">
					<?= implode('', [
						Html::a(Yii::t('common', 'Current'), ['update', 'id' => $model->id], ['class' => $showTrash ? '' : 'active']),
						Html::a(Yii::t('common', 'Trash'), ['update', 'id' => $model->id, 'deleted' => Page::YES], ['class' => $showTrash ? 'active' : '']),
					]); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="panel-actions">
			<?php if (Yii::$app->user->can('restorePage') && $showTrash): ?>
				<?= Html::button('<span class="fa fa-undo"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success hidden',
					'title' => Yii::t('common', 'Restore'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => 'restore',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['page-picture/restore', 'page_id' => $model->id]),
						'dt-table' => '#dt-page-pictures',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('deletePage')): ?>
				<?= Html::button('<span class="fa fa-trash"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-danger hidden',
					'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['page-picture/delete', 'page_id' => $model->id]),
						'dt-table' => '#dt-page-pictures',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('createPage')): ?>
				<?= Html::a('<span class="fa fa-plus"></span>', ['page-picture/create', 'page_id' => $model->id], [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success',
					'title' => Yii::t('common', 'Create'),
					'data' => [
						'toggle' => 'tooltip',
						'popup-action' => '',
						'popup-done' => ['redrawDataTable' => '#dt-page-pictures'],
					],
				]) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="panel-body">
		<?= $this->render('/page-picture/index') ?>
	</div>
</div>

<div class="panel blue-hoki">
	<div class="panel-title">
		<div class="panel-heading">
			<?= Yii::t('label', 'Files') ?>
			<?php if ($showTrash = Yii::$app->settings->get('enableSoftDelete')): ?>
				<?php $showTrash = Yii::$app->request->get('deleted') == Page::YES; ?>
				<span class="page-links">
					<?= implode('', [
						Html::a(Yii::t('common', 'Current'), ['update', 'id' => $model->id], ['class' => $showTrash ? '' : 'active']),
						Html::a(Yii::t('common', 'Trash'), ['update', 'id' => $model->id, 'deleted' => Page::YES], ['class' => $showTrash ? 'active' : '']),
					]); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="panel-actions">
			<?php if (Yii::$app->user->can('restorePage') && $showTrash): ?>
				<?= Html::button('<span class="fa fa-undo"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success hidden',
					'title' => Yii::t('common', 'Restore'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => 'restore',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['page-file/restore', 'page_id' => $model->id]),
						'dt-table' => '#dt-page-files',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('deletePage')): ?>
				<?= Html::button('<span class="fa fa-trash"></span>', [
					'type' => 'button',
					'class' => 'btn btn-sm btn-danger hidden',
					'title' => $showTrash ? Yii::t('common', 'Delete Permanently') : Yii::t('common', 'Delete'),
					'data' => [
						'toggle' => 'tooltip',
						'dt-bulk-operation' => $showTrash ? 'delete-permanently' : 'delete',
						'dt-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						'dt-url' => Url::to(['page-file/delete', 'page_id' => $model->id]),
						'dt-table' => '#dt-page-files',
					],
				]) ?>
			<?php endif; ?>
			<?php if (Yii::$app->user->can('createPage')): ?>
				<?= Html::a('<span class="fa fa-plus"></span>', ['page-file/create', 'page_id' => $model->id], [
					'type' => 'button',
					'class' => 'btn btn-sm btn-success',
					'title' => Yii::t('common', 'Create'),
					'data' => [
						'toggle' => 'tooltip',
						'popup-action' => '',
						'popup-done' => ['redrawDataTable' => '#dt-page-files'],
					],
				]) ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="panel-body">
		<?= $this->render('/page-file/index') ?>
	</div>
</div>
