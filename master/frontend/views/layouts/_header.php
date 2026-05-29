<?php
/* @var $this \yii\web\View */

use common\models\User;
use yii\helpers\Html;
use tws\helpers\Url;

/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
$appLanguages = \common\models\Language::findAllLanguages();
$appLanguagesCount = count($appLanguages);
$currentAppLanguage = $appLanguages[Yii::$app->language];
?>

<header id="page-header" class="page-header page-header--fixed page-header--bg">
	<nav class="navbar navbar-default navbar-top page-header-top-bar">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed btn btn-white btn-outline btn-slide-right" data-toggle="collapse" data-target="#top-navbar-collapse" aria-expanded="false">
					<span class="sr-only"><?= Yii::t('frontend', 'Toggle navigation') ?></span>
					<span class="fa fa-bars"></span>
				</button>
				<div class="navbar-brand">
					<?php if (Yii::$app->settings->get('fixedPhone', 'contact') || Yii::$app->settings->get('mobilePhone', 'contact')) :?>
						<span><?= Yii::t('frontend', 'Questions?') ?></span>
						<span><?= Yii::t('frontend', 'Call: {0}', [Html::a(Html::tag('strong', Yii::$app->settings->get('fixedPhone', 'contact') ?: Yii::$app->settings->get('mobilePhone', 'contact')), 'tel:' . (Yii::$app->settings->get('fixedPhone', 'contact') ?: Yii::$app->settings->get('mobilePhone', 'contact')))]) ?></span>
					<?php endif; ?>
				</div>
				<?php if ($appLanguagesCount > 1): ?>
					<div class="dropdown dropdown-language">
						<button type="button" class="dropdown-toggle collapsed" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<img src="<?= Url::to("@web/img/flags/{$currentAppLanguage->country}.png") ?>" alt="<?= Yii::$app->language ?>">
							<?= mb_strtoupper($currentAppLanguage->language) ?>
						</button>
						<?php
						$items = [];
						foreach ($appLanguages as $language) {
							if ($language->language_id != Yii::$app->language) {
								$img = Html::img(Url::to("@web/img/flags/{$language->country}.png"), [
									'alt' => $language->name,
								]);
								$items[] = [
									'active' => Yii::$app->language == $language->language_id,
									'label' => $img . mb_strtoupper($language->language),
									'url' => ['/site/index', 'language' => $language->language_id],
								];
							}
						}
						?>
						<?= \common\widgets\sitemenu\SiteNav::widget([
							'options' => [
								'class' => 'dropdown-menu dropdown-menu-right',
							],
							'activateParents' => true,
							'encodeLabels' => false,
							'items' => $items,
						]) ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="collapse navbar-collapse <?= $appLanguagesCount > 1 ? 'right-padded' : '' ?>" id="top-navbar-collapse">
				<?php if ($topBarMenu = \common\models\Menu::findDefaultTopBarMenu()): ?>
					<?= \common\widgets\sitemenu\SiteNav::widget([
						'options' => [
							'class' => 'nav navbar-nav navbar-right list-link-underline',
						],
						'activateParents' => true,
						'encodeLabels' => false,
						'items' => $topBarMenu->getNestedItems(null, \common\models\MenuItem::STATUS_ACTIVE),
					]) ?>
				<?php endif; ?>
			</div>
		</div>
	</nav>

	<nav class="navbar navbar-main">
		<div class="container-fluid">
			<?php $headerMenu = \common\models\Menu::findDefaultHeaderMenu(); ?>
			<div class="navbar-header">
				<?php if ($headerMenu): ?>
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#main-navbar-collapse" aria-expanded="false">
						<span class="sr-only"><?= Yii::t('frontend', 'Toggle navigation') ?></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
				<?php endif; ?>
				<a class="navbar-brand" href="<?= Url::to(['/site/index']) ?>">
					<img class="img-responsive" src="<?= Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt')) ?: Url::to('@web/img/logo-alt.png') ?>" alt="<?= Yii::$app->name ?>"/>
				</a>
				<div class="navbar-quicknav">
					<?php if (Yii::$app->user->isGuest): ?>
						<ul class="list-inline">
							<li>
								<a class="btn btn-default btn-slide-right <?= Yii::$app->requestedRoute == 'site/login' ? 'active' : '' ?>" href="<?= Url::to(['/site/login']) ?>">
									<span class="fa fa-sign-in visible-xs"></span>
									<span class="hidden-xs"><?= Yii::t('common', 'Log In') ?></span>
								</a>
							</li>
							<li>
								<a class="btn btn-default btn-slide-right <?= Yii::$app->requestedRoute == 'site/signup' ? 'active' : '' ?>" href="<?= Url::to(['/site/signup']) ?>">
									<span class="fa fa-user-plus visible-xs"></span>
									<span class="hidden-xs"><?= Yii::t('common', 'Sign Up') ?></span>
								</a>
							</li>
						</ul>
					<?php else: ?>
						<div class="dropdown dropdown-user">
							<button type="button" class="dropdown-toggle collapsed" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<?php if ($imageUrl = $user->imageUrl): ?>
									<img class="dropdown-icon" src="<?= $imageUrl ?>" alt="<?= $user->fullNameInitials ?>">
								<?php else: ?>
									<span class="dropdown-icon fa fa-user-o"></span>
								<?php endif; ?>
								<span class="dropdown-text hidden-xs hidden-sm"><?= $user->shortName ?></span>
							</button>
							<?php
							$items = [];

							if ($user->getIsSubscriber() || $user->getHasPermissions()) {
								if ($profilePage = \common\models\Page::findPageByRoute(['/account/profile/index'])) {
									$items[] = [
										'label' => $profilePage->translation->title,
										'url' => [$profilePage->getRoute()],
									];
								}
							}

							if ($workspaces = \common\models\Workspace::findAllWorkspacesByUser($user->id)) {
								$items[] = Html::tag('li', null, ['class' => 'divider']);
								foreach ($workspaces as $workspace) {
									$items[] = Html::tag('li', $workspace->url, ['class' => 'dropdown-header', 'style' => 'text-align: center; background-color: #ffffff;']);
									// First workspace link
									$items[] = Html::a('<span class="fa fa-link"></span>', $workspace->getAbsoluteUrl(),
										[
											'title' => Yii::t('frontend', 'Preview'),
											'class' => 'workspace-item',
											'target' => '_blank',
										]);

									// Second workspace link with dashboard icon
									$items[] = Html::a(
										'<span class="fa fa-dashboard"></span>',
										implode('/', [$workspace->getAbsoluteUrl(), 'admin']),
										[
											'title' => Yii::t('frontend', 'Dashboard'),
											'class' => 'workspace-item',
											'target' => '_blank',
										]
									);
								}
							} elseif ($user->getIsSubscriber()) {
								if ($workspacePage = \common\models\Page::findPageByRoute(['/account/workspace/index'])) {
									$items[] = [
										'label' => $workspacePage->translation->title,
										'url' => [$workspacePage->getRoute()],
									];
								}
							}

							if ($user->status == User::STATUS_ACTIVE && $user->getHasPermissions()) {
								$items[] = [
									'label' => Yii::t('frontend', 'Admin Panel'),
									'url' => Url::to('/admin'),
									'linkOptions' => [
										'target' => '_blank',
									],
								];
							}

							if ($items) {
								$items[] = Html::tag('li', null, ['class' => 'divider']);
							}
							$items[] = [
								'label' => Yii::t('common', 'Log Out'),
								'url' => ['/site/logout'],
								'linkOptions' => [
									'data' => [
										'confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
									],
								],
							];
							?>
							<?= \common\widgets\sitemenu\SiteNav::widget([
								'options' => [
									'class' => 'dropdown-menu dropdown-menu-right',
								],
								'activateParents' => true,
								'encodeLabels' => false,
								'items' => $items,
							]) ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<?php if ($headerMenu): ?>
				<div class="collapse navbar-collapse" id="main-navbar-collapse">
					<?= \common\widgets\sitemenu\SiteNav::widget([
						'options' => [
							'class' => 'nav navbar-nav navbar-right',
						],
						'activateParents' => true,
						'encodeLabels' => false,
						'items' => $headerMenu->getNestedItems(null, \common\models\MenuItem::STATUS_ACTIVE),
					]) ?>
				</div>
			<?php endif; ?>
		</div>
	</nav>
</header>
