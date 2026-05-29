<?php
/* @var $this \yii\web\View */
/* @var $content string */

use kartik\growl\GrowlAsset;
use common\modules\embed\assets\EmbedAsset;

EmbedAsset::register($this);
GrowlAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
	<meta charset="<?= Yii::$app->charset ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php $this->registerCsrfMetaTags() ?>
	<title><?= $this->title . ' ::: ' . Yii::$app->name ?></title>
	<?php $this->head() ?>
</head>
<body class="page-embed">
<?php $this->beginBody() ?>
	<div class="page-container">
		<main class="page-body">
			<?= $content ?>
		</main>
	</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
