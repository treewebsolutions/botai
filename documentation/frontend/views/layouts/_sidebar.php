<?php
/* @var $this \yii\web\View */

use backend\widgets\Menu;
?>

<div class="page-sidebar-wrapper">
	<div class="page-sidebar navbar-collapse collapse">
		<?php if (Yii::$app->controller->action->id != 'error' && !Yii::$app->user->isGuest): ?>
			<?php if ($sidebar = \common\models\Menu::findDefaultSidebarMenu()): ?>
				<?= Menu::widget([
					'activateItems' => true,
					'activateParents' => true,
					'encodeLabels' => false,
					'itemOptions' => [
						'class' => 'nav-item',
					],
					'search' => [
						'visible' => true,
						'form' => [
							'method' => 'POST',
							'action' => ['/site/search'],
						],
						'input' => [
							'name' => 'search',
							'value' => Yii::$app->request->cookies->getValue('search'),
							'options' => [
								'placeholder' => Yii::t('common', 'Search') . '...',
							],
						],
					],
					'items' => \common\models\MenuItem::tree(null, null, null, $sidebar->id, ['search' => Yii::$app->request->cookies->getValue('search')]),
				]) ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
