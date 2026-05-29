<?php
/* @var $this \yii\web\View */

use yii\helpers\Html;
use tws\helpers\Url;

$appLanguages = \common\models\Language::findAllLanguages();
$currentAppLanguage = $appLanguages[Yii::$app->language];
?>

<div class="page-header navbar navbar-fixed-top">
	<div class="page-header-inner">
		<div class="page-logo">
			<a href="<?= implode('', array_filter([
				\tws\helpers\Url::base(true),
				(Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' : '/' . (in_array((mb_substr(Yii::$app->language, 0, 2)), ['en']) && !in_array(Yii::$app->language, ['en-US']) ? Yii::$app->language : (mb_substr(Yii::$app->language, 0, 2)))),
			])) ?>">
				<img class="logo-default" src="<?= Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt')) ?: Url::to('@web/img/logo-alt.png') ?>" alt="<?= Yii::$app->name ?>" style="margin-top: -2px;">
			</a>
			<div class="menu-toggler sidebar-toggler">
				<span></span>
			</div>
		</div>
		<a href="javascript:void(0);" class="menu-toggler responsive-toggler" data-toggle="collapse" data-target=".navbar-collapse">
			<span></span>
		</a>
		<div class="top-menu">
			<ul class="nav navbar-nav pull-right">
				<?php if (count($appLanguages) > 1): ?>
					<li class="dropdown dropdown-language">
						<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
							<img src="<?= Url::to("@web/img/flags/{$currentAppLanguage->country}.png") ?>" alt="<?= Yii::$app->language ?>">
							<span class="langname"><?= mb_strtoupper($currentAppLanguage->language) ?></span>
							<i class="fa fa-angle-down"></i>
						</a>
						<ul class="dropdown-menu dropdown-menu-default">
							<?php foreach ($appLanguages as $appLanguage) : ?>
								<?php if ($appLanguage->language_id != Yii::$app->language) : ?>
									<?php
									$currentRoute = Yii::$app->request->getQueryParams();
									$currentRoute['language'] = $appLanguage->language_id;
									array_unshift($currentRoute, substr(Yii::$app->requestedRoute, 0, 1) == '/' ? Yii::$app->requestedRoute : '/' . Yii::$app->requestedRoute);
									?>
									<li>
										<a href="<?= Url::to(['/site/index', 'language' => $currentRoute['language']]) ?>">
											<img src="<?= Url::to("@web/img/flags/{$appLanguage->country}.png") ?>" alt="<?= $appLanguage->name ?>">
											<span><?= $appLanguage->name ?></span>
										</a>
									</li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; ?>

				<?php if (!Yii::$app->user->isGuest && Yii::$app->authManager->getRolesByUser(Yii::$app->user->id)): ?>
				<li class="dropdown dropdown-user">
					<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown" data-hover="dropdown" data-close-others="true">
						<img class="img-circle" src="<?= Yii::$app->user->identity->imageUrl ?: Url::to('@web/img/img-placeholder-user.png') ?>" alt="<?= Yii::$app->user->identity->fullName ?>">
						<span class="username username-hide-on-mobile"><?= Yii::$app->user->identity->fullName ?: Yii::$app->user->identity->email ?></span>
						<i class="fa fa-angle-down"></i>
					</a>
					<ul class="dropdown-menu dropdown-menu-default">
						<li>
							<a href="<?= implode('', array_filter([
								\tws\helpers\Url::base(true),
								'/admin',
								(Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' : '/' . (in_array((mb_substr(Yii::$app->language, 0, 2)), ['en']) && !in_array(Yii::$app->language, ['en-US']) ? Yii::$app->language : (mb_substr(Yii::$app->language, 0, 2)))),
								])) ?>">
								<i class="icon-speedometer"></i> <?= Yii::t('common', 'Dashboard') ?>
							</a>
						</li>
					</ul>
				</li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>
