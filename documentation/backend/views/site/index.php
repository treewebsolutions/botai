<?php

/* @var $this yii\web\View */

use tws\helpers\Url;

$this->title = Yii::t('common', 'Dashboard');
$this->params['breadcrumbs'][] = $this->title;
$this->params['bodyAttributes'] = [
	'class' => ['page-container-bg-solid'],
];
?>

<div class="row">
	<?php if (Yii::$app->user->can('viewUser')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/user-manager/default/index']) ?>">
				<div class="visual">
					<i class="icon-people"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Users') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewUserRole')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/user-manager/role/index']) ?>">
				<div class="visual">
					<i class="icon-key"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Roles') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewEmailTemplate')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/nomenclature-manager/default/index']) ?>">
				<div class="visual">
					<i class="icon-list"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Nomenclature') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewEmailTemplate')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/nomenclature-manager/email-template/index']) ?>">
				<div class="visual">
					<i class="icon-envelope"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Email Templates') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
</div>

<div class="row">
	<?php if (Yii::$app->user->can('viewPage')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/nomenclature-manager/page/index']) ?>">
				<div class="visual">
					<i class="icon-docs"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Pages') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewMenu')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/nomenclature-manager/menu/index']) ?>">
				<div class="visual">
					<i class="icon-grid"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Menus') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewCarousel')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/nomenclature-manager/carousel/index']) ?>">
				<div class="visual">
					<i class="icon-layers"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Carousels') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewEventLog')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/eventlog-manager/event-log/index']) ?>">
				<div class="visual">
					<i class="icon-hourglass"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Event Logs') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
</div>

<div class="row">
	<?php if (Yii::$app->user->can('updateGeneralSetting')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager']) ?>">
				<div class="visual">
					<i class="icon-settings"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Settings') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('viewLanguageSetting')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager/language-manager']) ?>">
				<div class="visual">
					<i class="icon-globe"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Languages') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('updateSeoSetting')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager/setting/seo']) ?>">
				<div class="visual">
					<i class="icon-bulb"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Seo') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
	<?php if (Yii::$app->user->can('updateSocialNetworkSetting')): ?>
		<div class="col-lg-3 col-md-6">
			<a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager/setting/social-network']) ?>">
				<div class="visual">
					<i class="icon-social-facebook"></i>
				</div>
				<div class="details">
					<div class="number">
						<span data-counter="counterup"><?= Yii::t('common', 'Social Networks') ?></span>
					</div>
				</div>
			</a>
		</div>
	<?php endif; ?>
</div>
