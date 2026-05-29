<?php
/* @var $this \yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $tags array */

use common\helpers\FontIcon;
use yii\helpers\Html;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\widgets\LinkPager;

if (Yii::$app->request->get('category') || Yii::$app->request->get('tag')) {
	$this->params['breadcrumbs'][] = [
		'label' => \common\models\Page::findPageByRoute(['/article/default/index'])->translation->title,
		'url' => ['index'],
	];
}
$this->params['breadcrumbs'][] = Html::encode($this->title);
$tags = [];
?>

<div class="section section-md section-blog">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>
		<div class="row">
			<section class="col-md-8 content">
				<?php if ($articles = $dataProvider->getModels()): ?>
					<div>
						<?php foreach ($articles as $article) : ?>
							<?php
							/** @var common\models\Article $article */
							$articleTranslation = $article->getTranslation();
							$tags = array_merge($tags, explode(',', $articleTranslation->keywords));
							?>
							<article class="card card-normal">
								<?php if ($article->video): ?>
									<header class="card-header card-header-video">
										<div class="embed-responsive embed-responsive-16by9">
											<iframe class="embed-responsive-item" src="<?= $article->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
										</div>
										<div class="card-date">
											<div class="date"><?= Yii::$app->formatter->asDate($article->created_at, 'dd') ?></div>
											<div class="month"><?= Yii::$app->formatter->asDatetime($article->created_at, 'MMM yyyy') ?></div>
										</div>
									</header>
								<?php elseif ($article->image && is_file(Yii::getAlias("@uploads/article/{$article->id}/{$article->image}"))): ?>
									<header class="card-header card-header-image">
										<a href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($articleTranslation->title)]) ?>">
											<img class="img-responsive" src="<?= $article->getImageUrl() ?>" alt="<?= $articleTranslation->title ?>">
										</a>
										<div class="card-date">
											<div class="date"><?= Yii::$app->formatter->asDate($article->created_at, 'dd') ?></div>
											<div class="month"><?= Yii::$app->formatter->asDatetime($article->created_at, 'MMM yyyy') ?></div>
										</div>
									</header>
								<?php else: ?>
									<a class="card-header" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($articleTranslation->title)]) ?>">
										<?php if ($article->icon): ?>
											<?= FontIcon::render($article->icon, ['class' => 'card-icon']) ?>
										<?php else: ?>
											<img class="img-placeholder img-responsive" src="<?= Url::to('@web/img/logo-symbol.png') ?>" alt="<?= $articleTranslation->title ?>">
										<?php endif; ?>
										<div class="card-date">
											<div class="date"><?= Yii::$app->formatter->asDate($article->created_at, 'dd') ?></div>
											<div class="month"><?= Yii::$app->formatter->asDatetime($article->created_at, 'MMM yyyy') ?></div>
										</div>
									</a>
								<?php endif; ?>
								<ul class="card-meta list-icon">
									<li class="fa-eye" data-toggle="tooltip" title="<?= Yii::t('label', 'Views') ?>"><?= $article->views ?></li>
									<?php if ($article->creator): ?>
										<li class="fa-user"><?= Yii::t('label', 'Created By') ?>&nbsp;<?= $article->creator->shortName ?></li>
									<?php endif; ?>
								</ul>
								<h3 class="card-heading">
									<a class="link-underline" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($articleTranslation->title)]) ?>"><?= $articleTranslation->title ?></a>
								</h3>
								<?php if ($articleTranslation->description): ?>
									<p class="card-excerpt"><?= StringHelper::truncate($articleTranslation->description, 180) ?></p>
								<?php endif; ?>
								<footer class="card-footer">
									<a class="btn btn-slide-right btn-default" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>" title="<?= Yii::t('common', 'Read More About {0}', [Html::encode($articleTranslation->title)]) ?>"><?= Yii::t('common', 'Read More') ?></a>
								</footer>
							</article>
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
				<?php else: ?>
					<div class="alert alert-info gap-t-md" role="alert"><?= Yii::t('common', 'No records found.') ?></div>
				<?php endif; ?>
			</section>
			<aside class="col-md-4 sidebar">
				<?= $this->render('_sidebar-content', [
					'tags' => array_filter($tags),
				]) ?>
			</aside>
		</div>
	</div>
</div>
