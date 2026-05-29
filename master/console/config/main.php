<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-console',
	'name' => 'ManagerGSM Console',
	'basePath' => dirname(__DIR__),
	'aliases' => [
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'bootstrap' => [
		'log',
		'console\components\ApplicationBootstrap',
		'console\components\ScheduleBootstrap',
	],
	'controllerNamespace' => 'console\controllers',
	'controllerMap' => [
		'fixture' => [
			'class' => 'yii\console\controllers\FixtureController',
			'namespace' => 'common\fixtures',
		],
	],
	'components' => [
		'log' => [
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
				],
			],
		],
		'urlManager' => [
			'class' => 'yii\web\UrlManager',
			'baseUrl' => '',
			'enablePrettyUrl' => true,
			'showScriptName' => false,
		],
		'schedule' => 'omnilight\scheduling\Schedule',
	],
	'params' => $params,
];
