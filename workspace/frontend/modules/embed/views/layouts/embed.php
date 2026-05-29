<?php
/* @var $this \yii\web\View */
/* @var $content string */

use kartik\growl\GrowlAsset;
use frontend\modules\embed\assets\EmbedAsset;

// Load variables.css from uploads directory FIRST (before EmbedAsset)
$workspace = \common\models\master\Workspace::findOne(end(explode('-', Yii::$app->id)));
if ($workspace) {
	$variablesCssPath = Yii::getAlias('@workspaces') . '/' . $workspace->id . '/uploads/variables.css';
	if (file_exists($variablesCssPath)) {
		// Construct URL: {hostInfo}/{workspace_url}/uploads/variables.css
		$workspaceUrl = rtrim(Yii::$app->request->hostInfo . '/' . $workspace->url, '/');
		$variablesCssUrl = $workspaceUrl . '/uploads/variables.css';
		// Register BEFORE EmbedAsset so variables are available when embed.css loads
		$this->registerCssFile($variablesCssUrl, ['position' => \yii\web\View::POS_HEAD]);
	}
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
