<?php
/* @var $this \yii\web\View */
/* @var $content string */

use kartik\growl\GrowlAsset;
use frontend\modules\embed\assets\EmbedAsset;

// The style saved in the interface settings, written to the tenant's own uploads
// directory by InterfaceSettingForm::saveVariablesCss(). Registered before EmbedAsset so
// the variables are defined by the time embed.css reads them.
//
// This looked for workspaces/<numeric id>/uploads/variables.css. Tenant directories are
// named after the domain (or the URL slug), not the id, and the writer already said so -
// so file_exists() was always false and the saved style never reached the widget.
// @uploads is the tenant's own directory, which is the same place the writer uses.
$variablesCssPath = Yii::getAlias('@uploads') . '/variables.css';
if (is_file($variablesCssPath)) {
	// baseUrl is the tenant's public prefix: "/<url>" served as a path under the master
	// host, "" on its own domain. The file name never changes, so its modification time
	// goes in the query string - otherwise a style saved in settings stays invisible
	// behind the browser cache.
	$variablesCssUrl = Yii::$app->request->baseUrl . '/uploads/variables.css?v=' . filemtime($variablesCssPath);
	$this->registerCssFile($variablesCssUrl, ['position' => \yii\web\View::POS_HEAD]);
}

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
