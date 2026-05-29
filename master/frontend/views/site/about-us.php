<?php
/* @var $this yii\web\View */

use common\helpers\FontIcon;
use tws\helpers\StringHelper;
use tws\helpers\Url;
use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<?= $this->context->currentPage->content ?>
	</div>
</div>

<?php if ($services = \common\models\Service::findFeaturedServices(3)): ?>
<section class="section section-md bg-light">
	<div class="container-fluid">
		<header class="section-header text-center">
			<h2 class="section-heading color-primary text-uppercase"><?= Yii::t('frontend', 'SECTION_SERVICES_TITLE') ?></h2>
			<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_SERVICES_DESCRIPTION') ?></h3>
		</header>
		<?php $servicesCount = count($services); ?>
		<div class="row row-spacing equal">
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
						<h3 class="card-heading">
							<a class="link-underline" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= $serviceTranslation->title ?></a>
						</h3>
						<?php if ($serviceTranslation->description): ?>
							<p class="card-excerpt gap-t-md"><?= StringHelper::truncate($serviceTranslation->description, 120) ?></p>
						<?php endif; ?>
						<footer class="card-footer">
							<a class="link-underline" href="<?= Url::to(['/service/default/view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= Yii::t('common', 'Read More') ?></a>
						</footer>
					</article>
				</div>
				<?php $i++; ?>
				<?php if ($i % 3 == 0 && $i != $servicesCount): ?>
					</div><div class="row row-spacing">
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<div class="text-center gap-t-xlg">
			<a class="btn btn-outline btn-default btn-slide-right" href="<?= Url::to(['/service/default/index']) ?>"><?= Yii::t('frontend', 'See More Details') ?></a>
		</div>
	</div>
</section>
<?php endif; ?>
