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
	<?= $content ?>
</div>

<?= $this->render('_footer') ?>
<?= $this->render('_scripts') ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
