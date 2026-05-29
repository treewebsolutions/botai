<?php
/* @var $this \yii\web\View */
Yii::$app->formatter->locale = Yii::$app->language;
$workspace = \common\models\master\Workspace::findOne(end(explode('-', Yii::$app->id)));
?>

<iframe id="externalPage" src="<?= $url ?>"></iframe>

<script id="chat-embed" src="<?= implode('/', [
	Yii::$app->request->hostInfo,
	$workspace->url,
	'embed',
	'api'
]) ?>" defer="defer" data-language="<?= Yii::$app->language ?>" data-color="<?= str_replace('#', '', Yii::$app->settings->get('chatColor', 'interface') ?: Yii::$app->settings->get('chatColor')) ?>" data-visible="<?= Yii::$app->settings->get('chatVisible', 'interface') ?: Yii::$app->settings->get('chatVisible') ?>" data-expanded="<?= Yii::$app->settings->get('chatExpanded', 'interface') ?: Yii::$app->settings->get('chatExpanded') ?>" data-remove="<?= Yii::$app->settings->get('chatRemove', 'interface') ?: Yii::$app->settings->get('chatRemove') ?>"></script>
