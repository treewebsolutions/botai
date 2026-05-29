<?php
/* @var $this yii\web\View */

use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<p><?= Yii::t('label', 'Last Updated On') ?>: <?= Yii::$app->formatter->asDate($this->context->currentPage->updated_at, 'MMMM, yyyy') ?></p>
		<?= $this->context->currentPage->content ?>
	</div>
</div>
