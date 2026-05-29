<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Html::encode($message);
?>

<div class="section section-md text-center">
	<div class="container-fluid container-error">
		<div class="error-block">
			<img class="img-responsive inline-block" src="<?= Yii::getAlias('@web/img/error.png') ?>" alt="<?= Yii::$app->name ?>">
		</div>
		<a class="btn btn-lg btn-default btn-slide-right gap-t-lg" href="<?= Url::to(['/site/index']) ?>"><?= Yii::t('frontend', 'Go to Home Page') ?></a>
	</div>
</div>
