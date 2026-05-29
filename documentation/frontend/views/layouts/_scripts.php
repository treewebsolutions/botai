<?php

/* @var $this yii\web\View */

use yii\web\View;

$settings = Yii::$app->settings->getAll();
?>

<?= Yii::$app->settings->get('footerContent', 'script') ?>
