<?php
/* @var $this \yii\web\View */

use yii\helpers\Html;
use tws\helpers\Url;
use yii\widgets\ActiveForm;
?>

<section class="page-cta section section-xs bg-primary">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-8">
				<h2 class="font-sm"><?= Yii::t('frontend', 'SECTION_CALL_TO_ACTION_TITLE', [Yii::$app->name]) ?></h2>
				<p class="gap-t-xs"><?= Yii::t('frontend', 'SECTION_CALL_TO_ACTION_DESCRIPTION') ?></p>
			</div>
			<div class="col-md-4">
				<a class="btn btn-white btn-outline btn-slide-right btn-block gap-t-sm" href="https://botai.ro/demo" target="_blank"><?= Yii::t('frontend', 'Try Demo') ?></a>
			</div>
		</div>
	</div>
</section>


<footer id="page-footer" class="page-footer">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-4">
				<a class="app-logo" href="<?= Url::to(['/site/index']) ?>">
					<img class="img-responsive" src="<?= Url::to(Yii::$app->settings->get('appLogoAlt')) ?: Url::to('@web/img/logo-alt.png') ?>" alt="<?= Yii::$app->name ?>">
				</a>
				<?php if ($appDescription = Yii::$app->settings->get('appDescription', 'general')): ?>
					<p class="app-description"><?= $appDescription[Yii::$app->language] ?></p>
				<?php endif; ?>
			</div>
			<div class="col-md-4">
				<div class="card card-normal">
					<div class="card-header">
						<h2 class="card-heading"><?= Yii::t('frontend', 'Contact Us') ?></h2>
					</div>
					<?php
					$contact = Yii::$app->settings->getCategory('contact');
					$address = implode(', ', array_filter([
						$contact['streetName'],
						$contact['streetNumber'],
						$contact['locality'],
						$contact['zipCode'],
						$contact['county'],
						$contact['country'] ? \common\models\Country::findAllCountries()[$contact['country']]->translation->name : null,
					]));
					?>
					<div class="clearfix"></div>
					<ul class="list-icon">
						<?php if ($contact['company']): ?>
							<li class="fa-briefcase"><?= $contact['company'] ?></li>
						<?php endif; ?>
						<?php if ($contact['schedule'][Yii::$app->language]): ?>
							<li class="fa-clock-o"><?= nl2br($contact['schedule'][Yii::$app->language]) ?></li>
						<?php endif; ?>
						<?php if ($contact['mobilePhone'] || $contact['fixedPhone']): ?>
							<li class="fa-phone">
								<?= implode(', ', array_filter([
									$contact['mobilePhone'] ? Html::a($contact['mobilePhone'], "tel:{$contact['mobilePhone']}", ['class' => 'link-underline']) : null,
									$contact['fixedPhone'] ? Html::a($contact['fixedPhone'], "tel:{$contact['fixedPhone']}", ['class' => 'link-underline']) : null,
								])) ?>
							</li>
						<?php endif; ?>
						<?php if ($contact['email']): ?>
							<li class="fa-envelope-o">
								<a class="link-underline" href="mailto:<?= $contact['email'] ?>"><?= $contact['email'] ?></a>
							</li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
			<div class="col-md-4">
				<?php if ($navMenu = \common\models\Menu::findDefaultFooterMenu()): ?>
					<div class="card card-normal">
						<div class="card-header">
							<h2 class="card-heading"><?= Yii::t('frontend', 'Quick Links') ?></h2>
						</div>
						<?= \common\widgets\sitemenu\SiteNav::widget([
							'options' => [
								'class' => 'list-icon list-circle list-link-underline',
							],
							'activateParents' => true,
							'encodeLabels' => false,
							'items' => $navMenu->getNestedItems(),
						]) ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="page-footer-bottom-bar text-center">
		<div class="container-fluid">
            <p>
				<?php if (Yii::$app->settings->get('appleStoreUrl')): ?>
                    <a href="<?= Yii::$app->settings->get('appleStoreUrl') ?>" target="_blank" class="btn btn-store">
                        <span class="fa fa-apple fa-3x pull-left"></span>
                        <span class="btn-label">Download on the</span>
                        <span class="btn-caption">App Store</span>
                    </a>
				<?php endif; ?>
				<?php if (Yii::$app->settings->get('googlePlayUrl')): ?>
                    <a href="<?= Yii::$app->settings->get('googlePlayUrl') ?>" target="_blank" class="btn btn-store">
                        <span class="fa fa-android fa-3x pull-left"></span>
                        <span class="btn-label">Download on the</span>
                        <span class="btn-caption">Google Play</span>
                    </a>
				<?php endif; ?>
            </p>
			<ul class="list-square">
				<?php $socialNetwork = Yii::$app->settings->getCategory('socialNetwork'); ?>
				<?php if ($socialNetwork['facebookPage']): ?>
					<li>
						<a class="link-social-facebook" href="<?= $socialNetwork['facebookPage'] ?>" title="Facebook" target="_blank">
							<span class="fa fa-facebook"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($socialNetwork['instagramPage']): ?>
					<li>
						<a class="link-social-instagram" href="<?= $socialNetwork['instagramPage'] ?>" title="Instagram" target="_blank">
							<span class="fa fa-instagram"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($socialNetwork['twitterPage']): ?>
					<li>
						<a class="link-social-twitter" href="<?= $socialNetwork['twitterPage'] ?>" title="Twitter" target="_blank">
							<span class="fa fa-twitter"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($socialNetwork['linkedinPage']): ?>
					<li>
						<a class="link-social-linkedin" href="<?= $socialNetwork['linkedinPage'] ?>" title="Linked In" target="_blank">
							<span class="fa fa-linkedin"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($socialNetwork['pinterestPage']): ?>
					<li>
						<a class="link-social-pinterest" href="<?= $socialNetwork['pinterestPage'] ?>" title="Pinterest" target="_blank">
							<span class="fa fa-pinterest"></span>
						</a>
					</li>
				<?php endif; ?>
				<?php if ($socialNetwork['tiktokPage']): ?>
					<li>
						<a class="link-social-tiktok" href="<?= $socialNetwork['tiktokPage'] ?>" title="TikTok" target="_blank">
							<svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><style>svg{fill:#72a4f7}</style><path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/></svg>
						</a>
					</li>
				<?php endif; ?>
			</ul>
			<small class="copyright">
				<?= Yii::t('common', '&copy; {0} {1}. All rights reserved.', [Yii::$app->name, date('Y')]) ?>
				<a class="dev-by-tws link-underline" href="//www.treewebsolutions.com" target="_blank" title="Tree Web Solutions"><?= Yii::t('common', 'Developed by {0}.', 'TWS') ?></a>
			</small>
		</div>
	</div>
</footer>

<a class="btn btn-default btn-jump-top skip-link" href="#page-header" title="<?= Yii::t('common', 'Go to Top') ?>">
	<span class="sr-only"><?= Yii::t('common', 'Go to Top') ?></span>
	<span class="fa fa-angle-up"></span>
</a>

<?php if (!Yii::$app->getRequest()->getCookies()->has('acceptCookies')): ?>
    <div id="cookiebar">
        <div class="row">
            <div class="col-md-1"></div>
            <div class="col-md-7" style="padding: 0 15px;">
                <h3 style="margin: 0 15px;"><?= Yii::t('frontend', '{0} uses COOKIES', [Yii::$app->name]) ?></h3>
                <p style="margin: 0 15px;"><?= Yii::t('frontend', 'By clicking on "Yes, I accept" you agree to the use of cookies installed on the site. These are used to give you the best browsing experience possible.') ?></p>
            </div>
            <div class="col-md-3" style="margin-top: 5px;">
				<?php $form = ActiveForm::begin([
					'action' =>['/accept-cookies'],
					'id' => 'accept-cookies-form',
				]); ?>
				<?= Html::hiddenInput('acceptCookies', 1); ?>
				<?= Html::hiddenInput('backUrl', Url::canonical()); ?>
				<?= Html::submitButton('&nbsp;&nbsp;&nbsp;' . Yii::t('frontend', 'Yes, I Accept'), ['id' => 'okcookies']) ?>
				<?= Html::a(Yii::t('common', 'Details'), ['/site/cookie-policy']); ?>
				<?php ActiveForm::end() ?>
            </div>
            <div class="col-md-2"></div>
        </div>
    </div>
<?php endif; ?>
