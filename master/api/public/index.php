<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');

(static function () {
	require __DIR__ . '/../../vendor/autoload.php';
	require __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
	require __DIR__ . '/../../common/config/bootstrap.php';

	$params = \yii\helpers\ArrayHelper::merge(
		require __DIR__ . '/../../common/config/params.php',
		require __DIR__ . '/../../common/config/params-local.php',
		require __DIR__ . '/../config/params.php'
	);

	$config = \yii\helpers\ArrayHelper::merge(
		require __DIR__ . '/../../common/config/main.php',
		require __DIR__ . '/../../common/config/main-local.php',
		require __DIR__ . '/../config/main.php'
	);

	(new \yii\web\Application($config))->run();
})();
