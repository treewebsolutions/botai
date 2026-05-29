<?php

/* @var $this yii\web\View */

use tws\helpers\Url;
use yii\widgets\Breadcrumbs;

$this->params['bodyAttributes'] = [
	'class' => ['page-header-fixed page-sidebar-closed-hide-logo page-content-white'],
];
$this->params['breadcrumbs'][] = $this->title;

$content = $this->context->currentPage->content;
if (!empty(Yii::$app->request->cookies->getValue('search'))) {
	$content = \common\helpers\StringHelper::highlightText($content, [Yii::$app->request->cookies->getValue('search')]);
}
?>

<?php if ($breadcrumbs = $this->params['breadcrumbs']): ?>
	<div class="page-bar">
		<?= Breadcrumbs::widget([
			'homeLink' => [
				'label' => '<i class="fa fa-home"></i>',
				'url' => implode('', array_filter([
					\tws\helpers\Url::base(true),
					(Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' : '/' . mb_substr(Yii::$app->language, 0, 2)),
				])),
				'title' => \common\models\Page::findPageByRoute(['site/index'])->translation->title,
				'template' => '<li>{link}</li>',
			],
			'options' => [
				'class' => 'page-breadcrumb',
			],
			'encodeLabels' => false,
			'links' => !empty($breadcrumbs) ? $breadcrumbs : [],
		]) ?>
	</div>
<?php endif; ?>

<div class="row">
	<div class="col-md-12 news-page blog-page">
		<h1><?= $this->title ?></h1>
		<?= $content ?>
	</div>
</div>
