<?php
/* @var $this \yii\web\View */

use common\helpers\FontIcon;
use common\models\Feature;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use kartik\rating\StarRating;
use tws\helpers\StringHelper;
use tws\widgets\carousel\Carousel;
use tws\helpers\Url;
use yii\helpers\Html;

Yii::$app->formatter->locale = Yii::$app->language;
?>

<h1 class="hidden"><?= $this->context->currentPage->translation->title . ' - ' . Yii::$app->name ?></h1>
<div class="page-content">
	<?php if ($homepageCarousel = \common\models\Carousel::findDefaultCarousel(\common\models\Carousel::TYPE_MAIN)): ?>
		<?php if ($homepageCarouselItems = $homepageCarousel->getOrderedCarouselItems()): ?>
			<div id="section-carousel" class="main-carousel-wrapper no-shrink">
				<?php
				$carouselItems = [];
				foreach ($homepageCarouselItems as $carouselItem) {
					$carouselItemTranslation = $carouselItem->getTranslation();
					$captions = [];

					if (!empty($carouselItemTranslation->title)) {
						$captions[] = [
							'type' => Carousel::CAPTION_TITLE,
							'animation' => 'fadeInDown 1s ease',
							'content' => $carouselItemTranslation->title,
						];
					}
					if (!empty($carouselItemTranslation->content)) {
						$captions[] = [
							'type' => Carousel::CAPTION_TEXT,
							'animation' => 'fadeInUp 1s ease 1s',
							'content' => $carouselItemTranslation->content,
						];
					}
					if (!empty($carouselItemTranslation->anchor) && !empty($carouselItemTranslation->url)) {
						$captions[] = [
							'type' => Carousel::CAPTION_CTA,
							'animation' => 'fadeInUp 1s ease 2s',
							'content' => Html::a($carouselItemTranslation->anchor, $carouselItemTranslation->url, [
								'class' => 'btn btn-default btn-slide-right',
								'target' => $carouselItem->target ?: null,
							]),
						];
					}

					$carouselItems[] = [
						'type' => Carousel::ITEM_BACKGROUND,
						'src' => $carouselItem->getImageUrl(),
						'captions' => $captions,
						'position' => Carousel::CAPTION_LEFT_CENTER,
					];
				}
				?>
				<?= Carousel::widget([
					'id' => "carousel-{$homepageCarousel->id}",
					'items' => $carouselItems,
					'pagination' => true,
					'navigation' => true,
					'scrollbar' => false,
					'clientOptions' => array_merge([
						'autoplay' => [
							'delay' => 10000,
						],
						'speed' => 1000,
						'effect' => 'slide',
						'fadeEffect' => [
							'crossFade' => false,
						],
						'pagination' => [
							'clickable' => true,
						],
					], $homepageCarousel->getConfigData()),
				]) ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ($services = \common\models\Service::findFeaturedServices(6)): ?>
		<section class="section section-md">
			<div class="container-fluid">
				<header class="section-header text-center">
					<h2 class="section-heading color-primary"><?= Yii::t('frontend', 'SECTION_SERVICES_TITLE') ?></h2>
					<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_SERVICES_DESCRIPTION') ?></h3>
				</header>
				<?php $servicesCount = count($services); ?>
				<div class="row row-spacing">
					<?php foreach ($services as $service) : ?>
						<?php $serviceTranslation = $service->getTranslation(); ?>
						<div class="col-sm-4 col-sm-spacing">
							<article class="card card-xs card-bordered card-shadow text-center">
								<header class="card-header card-header-bordered card-glued-top">
									<?php if ($service->video): ?>
										<div class="embed-responsive embed-responsive-16by9">
											<iframe class="embed-responsive-item" src="<?= $service->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
										</div>
									<?php elseif (($service->image && is_file(Yii::getAlias("@uploads/service/{$service->id}/{$service->image}"))) || !$service->icon): ?>
										<a class="img-ratio" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>">
											<img class="img-responsive img-ratio-object" src="<?= $service->getImageUrl() ?: Url::to('@web/img/logo-symbol.png') ?>" alt="<?= $serviceTranslation->title ?>">
										</a>
									<?php else: ?>
										<a class="card-figure" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>">
											<?= FontIcon::render($service->icon, ['class' => 'card-figure-item card-figure-icon']) ?>
										</a>
									<?php endif; ?>
								</header>
								<div class="equal-service">
									<h3 class="card-heading">
										<a class="link-underline" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= $serviceTranslation->title ?></a>
									</h3>
									<?php if ($serviceTranslation->description): ?>
										<p class="card-excerpt gap-t-md"><?= StringHelper::truncate($serviceTranslation->description, 255) ?></p>
									<?php endif; ?>
								</div>
								<footer class="card-footer">
									<a class="link-underline" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= Yii::t('common', 'Read More') ?></a>
								</footer>
							</article>
						</div>
						<?php $i++; ?>
						<?php if ($i % 3 == 0 && $i != $servicesCount): ?>
							</div><div class="row row-spacing equal">
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
				<div class="text-center gap-t-xlg">
					<ul class="list-spacing list-inline">
						<li>
							<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/service/default/index']) ?>"><?= Yii::t('frontend', 'Read More') ?></a>
						</li>
						<li>
							<?php if (Yii::$app->user->isGuest): ?>
								<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/site/login']) ?>"><?= Yii::t('frontend', 'Try Now') ?></a>
							<?php elseif (Yii::$app->user->identity->getIsSubscriber()): ?>
								<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/account/workspace/index']) ?>"><?= Yii::t('frontend', 'Try Now') ?></a>
							<?php else: ?>
								<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/site/subscribe']) ?>"><?= Yii::t('frontend', 'Try Now') ?></a>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section section-md section-how-it-works bg-01 border-top-light">
		<div class="container-fluid">
			<header class="section-header text-center">
				<h2 class="section-heading color-primary"><?= Yii::t('frontend', 'SECTION_HOWITWORKS_TITLE') ?></h2>
				<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_HOWITWORKS_DESCRIPTION') ?></h3>
			</header>
			<div class="row row-steps">
				<div class="col-md-4">
					<article class="step equal-step">
						<span class="step-icon fa fa-user-plus"></span>
						<h4 class="step-heading">1. <?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP1_TITLE') ?></h4>
						<p><?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP1_DESCRIPTION') ?></p>
					</article>
				</div>
				<div class="col-md-4">
					<article class="step equal-step">
						<span class="step-icon fa fa-briefcase"></span>
						<h4 class="step-heading">2. <?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP2_TITLE') ?></h4>
						<p><?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP2_DESCRIPTION') ?></p>
					</article>
				</div>
				<div class="col-md-4">
					<article class="step equal-step">
						<span class="step-icon fa fa-thumbs-up"></span>
						<h4 class="step-heading">3. <?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP3_TITLE') ?></h4>
						<p><?= Yii::t('frontend', 'SECTION_HOWITWORKS_STEP3_DESCRIPTION') ?></p>
					</article>
				</div>
			</div>
			<div class="text-center gap-t-xlg">
				<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/site/about-us']) ?>"><?= Yii::t('frontend', 'Read More') ?></a>
			</div>
		</div>
	</section>

	<?php if ($packages = \common\models\Package::findPaidPackages()): ?>
	<section id="section-pricing" class="section section-md">
		<div class="container-fluid">
			<header class="section-header text-center">
				<h2 class="section-heading color-primary"><?= Yii::t('frontend', 'SECTION_PRICING_TITLE') ?></h2>
				<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_PRICING_DESCRIPTION') ?></h3>
			</header>
			<?php Carousel::begin([
				'options' => [
					'class' => 'carousel-pricing',
				],
				'pagination' => false,
				'navigation' => false,
				'scrollbar' => false,
				'clientOptions' => [
					'autoplay' => [
						'delay' => 7000,
					],
					'speed' => 1000,
					'effect' => 'slide',
					'slidesPerView' => 1,
					'spaceBetween' => 15,
					'breakpointsInverse' => true,
					'breakpoints' => [
						650 => [
							'slidesPerView' => 2,
						],
						992 => [
							'slidesPerView' => 2,
						],
						1200 => [
							'slidesPerView' => 2,
						],
					],
				],
			]); ?>
			<?php
			$featureModuleLabels = FeatureModule::getModuleLabels();
			$featureLabels = Feature::getFeatureLabels();
			?>
			<?php foreach ($packages as $package): ?>
				<?php
				$packageTranslation = $package->getTranslation();
				/** @var PackageFeature[] $packageFeatures */
				$packageFeatures = $package->getPackageFeatures()->indexBy('name')->all();
				?>
				<div class="carousel-item swiper-slide">
					<?php if ($package->type == \common\models\Package::TYPE_STANDARD): ?>
						<div class="card card-pricing bg-white">
							<header class="card-header">
								<h4 class="card-heading"><?= $packageTranslation->name ?></h4>
								<div class="card-jumbotron color-default"><?= Yii::$app->formatter->asCurrency($package->price) ?></div>
							</header>
							<div class="list-icon list-spacing equal-package">
								<?php if (!empty($packageTranslation->content)): ?>
									<?= $packageTranslation->content ?>
								<?php endif; ?>
							</div>
							<footer class="card-footer">
								<?php if (Yii::$app->user->isGuest): ?>
									<a class="btn btn-block btn-default btn-outline btn-slide-right" href="<?= Url::to(['/site/signup', 'package_id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Sign Up') ?></a>
								<?php elseif (Yii::$app->user->identity->subscriber): ?>
									<a class="btn btn-block btn-default btn-outline btn-slide-right" href="<?= Url::to(['/account/payment/package', 'id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Buy Now') ?></a>
								<?php else: ?>
									<a class="btn btn-block btn-default btn-outline btn-slide-right" href="<?= Url::to(['/site/subscribe', 'package_id' => Yii::$app->security->maskToken((string) $package->id)]) ?>"><?= Yii::t('common', 'Subscribe') ?></a>
								<?php endif; ?>
							</footer>
						</div>
					<?php elseif ($package->type == \common\models\Package::TYPE_CUSTOM): ?>
						<div class="card card-pricing bg-white">
							<header class="card-header">
								<h4 class="card-heading"><?= $packageTranslation->name ?></h4>
								<div class="card-jumbotron color-default"><?= Yii::t('common', 'Contact Us') ?></div>
							</header>
							<ul class="list-icon list-spacing">
								<?php if (Yii::$app->user->isGuest || !Yii::$app->user->identity->subscriber): ?>
									<li class="fa-check-circle"><?= Yii::t('common', 'Trial Period') ?>: <?= $package->getFormattedTrialPeriod() ?></li>
								<?php endif; ?>
								<li class="fa-check-circle"><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></li>
								<li class="fa-check-circle"><?= $featureLabels[Feature::WORKSPACES] ?>: <?= Yii::t('common', 'Custom') ?></li>
								<li class="fa-check-circle"><?= $featureLabels[Feature::WORKING_POINTS] ?>: <?= Yii::t('common', 'Custom') ?></li>
								<li class="fa-check-circle"><?= $featureLabels[Feature::USERS] ?>: <?= Yii::t('common', 'Custom') ?></li>
							</ul>
							<footer class="card-footer">
								<a class="btn btn-block btn-default btn-outline btn-slide-right" href="<?= Url::to(['/site/contact', 'request' => 'enterprise']) ?>"><?= Yii::t('common', 'Contact Us') ?></a>
							</footer>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<?php Carousel::end(); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ($latestTestimonials = \common\models\Testimonial::findLatestTestimonials(3)): ?>
		<section class="section section-md bg-02">
			<div class="container-fluid">
				<header class="section-header text-center">
					<h2 class="section-heading color-primary"><?= Yii::t('frontend', 'SECTION_TESTIMONIALS_TITLE') ?></h2>
					<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_TESTIMONIALS_DESCRIPTION') ?></h3>
				</header>
				<?php Carousel::begin([
					'options' => [
						'class' => 'carousel-testimonials',
					],
					'pagination' => true,
					'navigation' => true,
					'scrollbar' => false,
					'clientOptions' => [
						'autoplay' => [
							'delay' => 10000,
						],
						'speed' => 1000,
						'effect' => 'slide',
						'fadeEffect' => [
							'crossFade' => false,
						],
						'slidesPerView' => 1,
						'autoHeight' => true,
						'pagination' => [
							'clickable' => true,
						],
					],
				]); ?>
					<?php foreach ($latestTestimonials as $testimonial): ?>
						<?php $testimonialTranslation = $testimonial->getTranslation(); ?>
						<div class="carousel-item swiper-slide">
							<div class="card card-normal text-center">
								<header class="card-header">
									<?= StarRating::widget([
										'id' => 'rating-' . rand(),
										'name' => '',
										'value' => $testimonial->rating ?: 0,
										'pluginOptions' => [
											'displayOnly' => true,
											'showCaption' => false,
											'showClear' => false,
											'size' => 'xxs',
											'min' => 0,
											'max' => 5,
											'stars' => 5,
											'step' => 1,
										],
									]) ?>
								</header>
								<div class="well">
									<?= $testimonial->translation->message ?>
								</div>
								<?php if (!empty($testimonial->name) || !empty($testimonialTranslation->role) || !empty($testimonial->organization)): ?>
									<footer class="card-footer">
										<div class="font-bold gap-t-sm">&mdash; <?= implode(', ', array_filter([$testimonial->name, $testimonialTranslation->role, $testimonial->organization])) ?></div>
									</footer>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php Carousel::end(); ?>
				<div class="text-center gap-t-lg">
					<a class="btn btn-default btn-slide-right" href="<?= Url::to(['/site/testimonial']) ?>"><?= Yii::t('frontend', 'Se All Testimonials') ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ($partners = \common\models\Partner::findAllPartners()): ?>
		<section class="section section-md bg-light">
			<div class="container-fluid">
				<header class="section-header text-center">
					<h2 class="section-heading color-primary"><?= Yii::t('frontend', 'SECTION_PARTNERS_TITLE') ?></h2>
					<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_PARTNERS_DESCRIPTION', [Yii::$app->name]) ?></h3>
				</header>
				<?php
				$carouselItems = [];
				foreach ($partners as $partner) {
					$img = Html::img($partner->imageUrl ?: Url::to('@web/img/logo-symbol.png'), ['class' => 'img-responsive', 'alt' => $partner->name]);
					$tag = 'div';
					$tagOptions = ['class' => 'carousel-item-inner'];
					if ($partner->url) {
						$tag = 'a';
						$tagOptions['href'] = $partner->url;
						$tagOptions['target'] = '_blank';
					}
					$carouselItems[] = [
						'content' => Html::tag($tag, $img, $tagOptions),
					];
				}
				?>
				<?= Carousel::widget([
					'options' => [
						'class' => 'carousel-partners',
					],
					'items' => $carouselItems,
					'pagination' => false,
					'navigation' => false,
					'scrollbar' => false,
					'clientOptions' => [
						'autoplay' => [
							'delay' => 5000,
						],
						'speed' => 1000,
						'effect' => 'slide',
						'slidesPerView' => 3,
						'spaceBetween' => 10,
						'mousewheel' => true,
						'loop' => true,
						'breakpointsInverse' => true,
						'breakpoints' => [
							400 => [
								'slidesPerView' => 3,
							],
							480 => [
								'slidesPerView' => 4,
							],
							650 => [
								'slidesPerView' => 5,
							],
							768 => [
								'slidesPerView' => 6,
							],
							992 => [
								'slidesPerView' => 8,
							],
							1200 => [
								'slidesPerView' => 12,
							],
						],
					],
				]) ?>
			</div>
		</section>
	<?php endif; ?>
</div>

<?php
$this->registerJs('
	var equalServiceMaxHeight = 0;
	$(".equal-service").each(function(){
		if ($(this).height() > equalServiceMaxHeight) { equalServiceMaxHeight = $(this).height(); }
	});
	$(".equal-service").height(equalServiceMaxHeight);
	
	var equalStepMaxHeight = 0;
	$(".equal-step").each(function(){
		if ($(this).height() > equalStepMaxHeight) { equalStepMaxHeight = $(this).height(); }
	});
	$(".equal-step").height(equalStepMaxHeight);
	
	var equalPackageMaxHeight = 0;
	$(".equal-package").each(function(){
		if ($(this).height() > equalPackageMaxHeight) { equalPackageMaxHeight = $(this).height(); }
	});
	$(".equal-package").height(equalPackageMaxHeight);
');
?>
