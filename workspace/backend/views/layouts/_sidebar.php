<?php

use backend\widgets\Menu;

?>

<div class="page-sidebar-wrapper">
	<?= Menu::widget([
		'activateItems' => true,
		'activateParents' => true,
		'encodeLabels' => false,
		'itemOptions' => [
			'class' => 'nav-item',
		],
		'search' => [
			'visible' => false,
			'form' => [
				'method' => 'GET',
				'action' => ['/site/index'],
			],
			'input' => [
				'name' => 'q',
				'value' => '',
				'options' => [
					'placeholder' => Yii::t('common', 'Search') . '...',
				],
			],
		],
		'items' => array_filter([
			[
				'icon' => 'icon-home',
				'label' => Yii::t('backend', 'Dashboard'),
				'url' => ['/site/index'],
			],
			\backend\modules\nomenclature\Module::getInstance()->getNavMenuItems(),
			\backend\modules\conversation\Module::getInstance()->getNavMenuItems(),
			\backend\modules\user\Module::getInstance()->getNavMenuItems(),
			\backend\modules\setting\Module::getInstance()->getNavMenuItems(),
			\backend\modules\backup\Module::getInstance()->getNavMenuItems(),
			\backend\modules\eventlog\Module::getInstance()->getNavMenuItems(),
		]),
	]) ?>
</div>
