<?php
/* @var $this \yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

use common\helpers\FontIcon;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\LinkPager;

if (Yii::$app->request->get('category')) {
	$this->params['breadcrumbs'][] = [
		'label' => \common\models\Page::findPageByRoute(['/service/default/index'])->translation->title,
		'url' => ['index'],
	];
}
$this->params['breadcrumbs'][] = Html::encode($this->title);
$tags = [];
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>
		<?php $i = 0;  if ($services = $dataProvider->getModels()): ?>
			<?php $servicesCount = count($services); ?>
			<div class="row row-spacing equal">
				<?php foreach ($services as $service) : ?>
					<?php
					/** @var common\models\Service $service */
					$serviceTranslation = $service->getTranslation();
					$tags = array_merge($tags, explode(',', $serviceTranslation->keywords));
					?>
					<div class="col-sm-4 col-sm-spacing">
						<article class="card card-xs card-bordered card-shadow text-center">
							<header class="card-header card-header-bordered card-glued-top">
								<?php if ($service->video): ?>
									<div class="embed-responsive embed-responsive-16by9">
										<iframe class="embed-responsive-item" src="<?= $service->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
									</div>
								<?php elseif (($service->image && is_file(Yii::getAlias("@uploads/service/{$service->id}/{$service->image}"))) || !$service->icon): ?>
									<a class="img-ratio" href="<?= Url::to(['view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>">
										<img class="img-responsive img-ratio-object" src="<?= $service->getImageUrl() ?: Url::to('@web/img/logo-symbol.png') ?>" alt="<?= $serviceTranslation->title ?>">
									</a>
								<?php else: ?>
									<a class="card-figure" href="<?= Url::to(['view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>">
										<?= FontIcon::render($service->icon, ['class' => 'card-figure-item card-figure-icon']) ?>
									</a>
								<?php endif; ?>
							</header>
							<h3 class="card-heading">
								<a class="link-underline" href="<?= Url::to(['view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= $serviceTranslation->title ?></a>
							</h3>
							<?php if ($serviceTranslation->description): ?>
								<p class="card-excerpt gap-t-md equalize"><?= StringHelper::truncate($serviceTranslation->description, 120) ?></p>
							<?php endif; ?>
							<footer class="card-footer">
								<a class="link-underline" href="<?= Url::to(['view', 'slug' => $serviceTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($serviceTranslation->title)]) ?>"><?= Yii::t('common', 'Read More') ?></a>
							</footer>
						</article>
					</div>
					<?php $i++; ?>
					<?php if ($i % 3 == 0 && $i != $servicesCount): ?>
						</div><div class="row row-spacing">
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
			<div class="text-center">
				<?= LinkPager::widget([
					'pagination' => $dataProvider->pagination,
					'maxButtonCount' => 5,
					'registerLinkTags' => true,
					'prevPageLabel' => '&lsaquo;',
					'nextPageLabel' => '&rsaquo;',
					'firstPageLabel' => '&laquo;',
					'lastPageLabel' => '&raquo;',
				]) ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?= $this->render('_sidebar-content', [
	'tags' => array_filter($tags),
]) ?>

<?php
$this->registerJs('
	var maxHeight = 0;
	$(".equalize").each(function(){
		if ($(this).height() > maxHeight) { maxHeight = $(this).height(); }
	});
	$(".equalize").height(maxHeight);
');
?>
