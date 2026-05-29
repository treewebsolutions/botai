<?php

/* @var $this yii\web\View */

use common\helpers\StringHelper;
use tws\helpers\Url;

$this->params['bodyAttributes'] = [
	'class' => ['page-header-fixed page-sidebar-closed-hide-logo page-content-white'],
];
$content = $this->context->currentPage->content;
if (!empty(Yii::$app->request->cookies->getValue('search'))) {
	$content = StringHelper::highlightText($content, [Yii::$app->request->cookies->getValue('search')]);
}
?>

<div class="row">
	<div class="col-md-12 news-page blog-page">
		<h1><?= $this->title ?></h1>
		<?= $content ?>
	</div>
</div>
