<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
$this->params['bodyAttributes']['class'][] = 'page-error';
?>
<div class="site-error">
	<div class="content-container">
		<div class="row">
			<div class="col-md-12">
				<h1><?= $this->title ?></h1>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="page-message"><?= Html::encode($exception->getMessage()) ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<?= Html::a(Yii::t('common', 'Go To Main Page'), ['/site/index'], ['class' => 'btn btn-lg red mt-15']) ?>
			</div>
		</div>
	</div>
</div>