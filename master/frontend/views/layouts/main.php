<?php
/* @var $this \yii\web\View */
/* @var $content string */

use common\widgets\Alert;
use yii\helpers\Html;
use yii\widgets\Breadcrumbs;

\frontend\assets\AppAsset::register($this);
\common\widgets\gallery\GalleryAsset::register($this);

$bodyAttributes = array_merge_recursive($this->context->bodyAttributes, (array) $this->params['bodyAttributes']);
?>

<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta charset="<?= Yii::$app->charset ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $this->registerCsrfMetaTags() ?>
	<title><?= implode(' - ', array_filter([Html::encode($this->title), Html::encode(Yii::$app->name)])) ?></title>
	<?php $this->head() ?>
</head>
<body <?= Html::renderTagAttributes($bodyAttributes) ?>>
<?php $this->beginBody() ?>

<?= $this->render('_widgets') ?>
<?= $this->render('_header') ?>

<div class="page-content">
	<?php if ($this->context->currentPage->module != 'product' || ($this->context->currentPage->module == 'product' && Yii::$app->request->get('category'))): ?>
		<div class="page-content-header text-center bg-light">
			<div class="section section-xs">
				<div class="container-fluid">
					<h1 class="page-title"><?= Html::encode($this->title) ?></h1>
					<?= Breadcrumbs::widget([
						'options' => [
							'class' => 'breadcrumb',
						],
						'encodeLabels' => false,
						'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
					]) ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
	<?= $content ?>
</div>

<?= $this->render('_footer') ?>
<?= $this->render('_scripts') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
