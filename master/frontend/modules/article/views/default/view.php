<?php
/* @var $this \yii\web\View */
/* @var $model common\models\Article */
/* @var $modelTranslation common\models\ArticleTranslation */
/* @var $tags array */

use common\helpers\FontIcon;
use tws\widgets\socialshare\SocialShare;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = [
	'label' => \common\models\Page::findPageByRoute(['/article/default/index'])->translation->title,
	'url' => ['index'],
];
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="section section-md section-blog">
	<div class="container-fluid">
		<div class="row">
			<section class="col-md-8 content">
				<div>
					<?php if ($model->video): ?>
						<header class="card-header card-header-video">
							<div class="embed-responsive embed-responsive-16by9">
								<iframe class="embed-responsive-item" src="<?= $model->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
							</div>
							<div class="card-date">
								<div class="date"><?= Yii::$app->formatter->asDate($model->created_at, 'dd') ?></div>
								<div class="month"><?= Yii::$app->formatter->asDatetime($model->created_at, 'MMM yyyy') ?></div>
							</div>
						</header>
					<?php elseif ($model->image && is_file(Yii::getAlias("@uploads/article/{$model->id}/{$model->image}"))): ?>
						<header class="card-header card-header-image">
							<img class="img-responsive" src="<?= $model->getImageUrl() ?>" alt="<?= $modelTranslation->title ?>">
							<div class="card-date">
								<div class="date"><?= Yii::$app->formatter->asDate($model->created_at, 'dd') ?></div>
								<div class="month"><?= Yii::$app->formatter->asDatetime($model->created_at, 'MMM yyyy') ?></div>
							</div>
						</header>
					<?php else: ?>
						<header class="card-header">
							<?php if ($model->icon): ?>
								<?= FontIcon::render($model->icon, ['class' => 'post-icon']) ?>
							<?php else: ?>
								<img class="img-placeholder img-responsive" src="<?= Url::to('@web/img/logo-symbol.png') ?>" alt="<?= $modelTranslation->title ?>">
							<?php endif; ?>
							<div class="card-date">
								<div class="date"><?= Yii::$app->formatter->asDate($model->created_at, 'dd') ?></div>
								<div class="month"><?= Yii::$app->formatter->asDatetime($model->created_at, 'MMM yyyy') ?></div>
							</div>
						</header>
					<?php endif; ?>
					<ul class="card-meta list-icon">
						<li class="fa-eye" data-toggle="tooltip" title="<?= Yii::t('label', 'Views') ?>"><?= $model->views ?></li>
						<?php if ($model->creator): ?>
							<li class="fa-user"><?= Yii::t('label', 'Created By') ?>&nbsp;<?= $model->creator->shortName ?></li>
						<?php endif; ?>
					</ul>
					<h3 class="card-heading"><?= $modelTranslation->title ?></h3>
					<div class="gap-t-md">
						<?= $modelTranslation->content ?>
					</div>
				</div>
				<nav class="section section-xs" aria-label="<?= Yii::t('common', 'Articles') ?>">
					<ul class="pager">
						<?php if ($prevModel = $model->getPrevArticle()): ?>
							<?php $prevModelTranslation = $prevModel->getTranslation(); ?>
							<li class="previous">
								<a href="<?= Url::to(['view', 'slug' => $prevModelTranslation->slug]) ?>" title="<?= Html::encode($prevModelTranslation->title) ?>">
									<span aria-hidden="true">&larr;</span> <?= Yii::t('frontend', 'Previous') ?>
								</a>
							</li>
						<?php endif; ?>
						<?php if ($nextModel = $model->getNextArticle()): ?>
							<?php $nextModelTranslation = $nextModel->getTranslation(); ?>
							<li class="next">
								<a href="<?= Url::to(['view', 'slug' => $nextModelTranslation->slug]) ?>" title="<?= Html::encode($nextModelTranslation->title) ?>">
									<?= Yii::t('frontend', 'Next') ?> <span aria-hidden="true">&rarr;</span>
								</a>
							</li>
						<?php endif; ?>
					</ul>
				</nav>
				<div class="card card-bordered social-share-section text-center">
					<div class="fb-like"
							 data-href="<?= Url::canonical() ?>"
							 data-layout="button_count"
							 data-action="like"
							 data-size="large"
							 data-show-faces="true"
							 data-share="false">
					</div>
					<div class="section-header">
						<h3 class="section-heading">
							<span class="fa fa-share-alt"></span>&nbsp;<?= Yii::t('common', 'Share') ?>
						</h3>
					</div>
					<?= SocialShare::widget([
						'options' => [
							'itemOptions' => [
								'buttonOptions' => [
									'class' => 'btn btn-sm btn-default',
								],
							],
						],
					]) ?>
				</div>
			</section>
			<aside class="col-md-4 sidebar">
				<?= $this->render('_sidebar-content', [
					'tags' => $tags,
				]) ?>
			</aside>
		</div>
	</div>
</div>
