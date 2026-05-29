<?php
/* @var $this yii\web\View */
/* @var $tags array */

use common\helpers\FontIcon;
use yii\helpers\Html;
use yii\helpers\Url;
?>

<?php if ($articleCategories = \common\models\ArticleCategory::findAllArticleCategories()): ?>
	<article class="card card-xs">
		<header class="card-header">
			<h2 class="card-heading"><?= Yii::t('common', 'Categories') ?></h2>
		</header>
		<ul class="list-group">
			<?php foreach ($articleCategories as $articleCategory) : ?>
				<?php $articleCategoryTranslation = $articleCategory->getTranslation(); ?>
				<li class="<?= Yii::$app->request->get('category') == $articleCategoryTranslation->slug ? 'active' : '' ?>">
					<a href="<?= Url::to(['index', 'category' => $articleCategoryTranslation->slug]) ?>"><?= $articleCategoryTranslation->title ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</article>
<?php endif; ?>

<?php if ($latestArticles = \common\models\Article::findLatestArticles(5)): ?>
	<article class="card card-xs">
		<header class="card-header">
			<h2 class="card-heading"><?= Yii::t('frontend', 'Latest Articles') ?></h2>
		</header>
		<?php foreach ($latestArticles as $article): ?>
			<?php $articleTranslation = $article->getTranslation(); ?>
			<div class="latest-article">
				<div class="row">
					<div class="col-sm-4 col-md-5">
						<?php if ($article->video): ?>
							<div class="article-header article-header-video">
								<div class="embed-responsive embed-responsive-4by3">
									<iframe class="embed-responsive-item" src="<?= $article->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
								</div>
							</div>
						<?php elseif ($article->image): ?>
							<a class="article-header article-header-image" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>">
								<img class="img-responsive" src="<?= $article->getImageUrl() ?>" alt="<?= $articleTranslation->title ?>">
							</a>
						<?php else: ?>
							<a class="article-header" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>">
								<?php if ($article->icon): ?>
									<?= FontIcon::render($article->icon, ['class' => 'post-icon']) ?>
								<?php else: ?>
									<img class="img-placeholder img-responsive" src="<?= Url::to('@web/img/logo-symbol.png') ?>" alt="<?= $articleTranslation->title ?>">
								<?php endif; ?>
							</a>
						<?php endif; ?>
					</div>
					<div class="col-sm-8 col-md-7">
						<h3 class="article-heading">
							<a class="link-underline" href="<?= Url::to(['view', 'slug' => $articleTranslation->slug]) ?>" class="recent-title"><?= $articleTranslation->title ?></a>
						</h3>
						<ul class="article-meta list-icon">
							<li class="fa-calendar-o"><?= Yii::$app->formatter->asDate($article->created_at, 'dd MMM yyyy') ?></li>
						</ul>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</article>
<?php endif; ?>

<?php if ($yearArticles = \common\models\Article::countArticlesByYear()): ?>
	<article class="card card-xs">
		<div class="card-header">
			<h2 class="card-heading"><?= Yii::t('frontend', 'Archives') ?></h2>
		</div>
		<ul class="list-group">
			<?php foreach ($yearArticles as $yearArticle): ?>
				<li class="<?= Yii::$app->request->get('year') == $yearArticle['year'] ? 'active' : '' ?>">
					<a href="<?= Url::to(['index', 'year' => $yearArticle['year']]) ?>">
						<?= $yearArticle['year'] ?> <span class="font-medium">(<?= $yearArticle['total'] ?>)</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</article>
<?php endif; ?>

<?php if (!empty($tags)): ?>
	<article class="card card-xs">
		<header class="card-header">
			<h2 class="card-heading"><?= Yii::t('frontend', 'Tags') ?></h2>
		</header>
		<ul class="list-tags">
			<?php foreach ($tags as $tag): ?>
				<li class="<?= Yii::$app->request->get('tag') == $tag ? 'active' : '' ?>">
					<a class="btn btn-xs btn-outline btn-default" href="<?= Url::to(['index', 'tag' => $tag]) ?>"><?= $tag ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</article>
<?php endif; ?>
