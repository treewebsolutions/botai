<?php
/* @var $this \yii\web\View */
/* @var $model common\models\Service */
/* @var $modelTranslation common\models\ServiceTranslation */

use common\helpers\FontIcon;
use tws\widgets\socialshare\SocialShare;
use yii\helpers\Html;
use yii\helpers\Url;

$this->params['breadcrumbs'][] = [
	'label' => \common\models\Page::findPageByRoute(['/service/default/index'])->translation->title,
	'url' => ['index'],
];
$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<?php if ($model->image && is_file(Yii::getAlias("@uploads/service/{$model->id}/{$model->image}")) && !$model->video): ?>
	<div class="container-height-sm bg-placeholder parallax hatch-grey" style="background-image: url('<?= $model->getImageUrl() ?>');"></div>
<?php endif; ?>
<div class="section section-md section-blog">
	<div class="container-fluid">
		<div class="card card-normal">
			<?php if ($model->video): ?>
				<header class="card-header">
					<div class="embed-responsive embed-responsive-16by9">
						<iframe class="embed-responsive-item" src="<?= $model->getVideoEmbedUrl() ?>?hl=<?= Yii::$app->language ?>&autoplay=0" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
				</header>
			<?php elseif ($model->icon && !$model->image): ?>
				<header class="card-header text-center">
					<div class="card-figure p0">
						<?= FontIcon::render($model->icon, ['class' => 'card-figure-item card-figure-icon']) ?>
					</div>
				</header>
			<?php endif; ?>
			<?= $modelTranslation->content ?>
		</div>
		<div class="card card-bordered social-share-section text-center gap-t-lg">
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
	</div>
</div>

<?= $this->render('_sidebar-content', [
	'tags' => $tags,
]) ?>
