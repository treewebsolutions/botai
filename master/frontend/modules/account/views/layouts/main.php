<?php
/* @var $this \yii\web\View */
/* @var $content string */

use common\models\Page;
use common\widgets\Alert;
use tws\helpers\Url;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

\frontend\assets\AppAsset::register($this);
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta charset="<?= Yii::$app->charset ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $this->registerCsrfMetaTags() ?>
	<title><?= Html::encode($this->title) . ' - ' . Html::encode(Yii::$app->name) ?></title>
	<?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?= $this->render('@app/views/layouts/_widgets') ?>
<?= $this->render('@app/views/layouts/_header') ?>

<div class="page-content">
	<?php if ($navMenu = \common\models\Menu::findDefaultSidebarMenu()): ?>
		<nav class="navbar navbar-light navbar-account">
			<div class="container-fluid">
				<div class="navbar-header">
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-account-menu-collapse" aria-expanded="false">
						<span class="sr-only"><?= Yii::t('common', 'Toggle Navigation') ?></span>
						<span class="fa fa-bars"></span>
					</button>
					<span class="navbar-brand"><?= Yii::$app->user->identity->shortName ?></span>
				</div>
				<div class="collapse navbar-collapse" id="navbar-account-menu-collapse">
					<?= \common\widgets\sitemenu\SiteNav::widget([
						'options' => [
							'class' => 'nav navbar-nav ul-nav',
						],
						'activateParents' => true,
						'encodeLabels' => false,
						'items' => $navMenu->getNestedItems(null, \common\models\MenuItem::STATUS_ACTIVE),
					]) ?>
				</div>
			</div>
		</nav>
	<?php endif; ?>

	<?= $content ?>
</div>

<?= $this->render('@app/views/layouts/_footer') ?>

<?= $this->render('@app/views/layouts/_scripts') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
