<?php
return [
	'language' => 'en-US',
	'sourceLanguage' => 'en-US',
	'timeZone' => 'Europe/Bucharest',
	'vendorPath' => dirname(__DIR__, 2) . '/vendor',
	'aliases' => [
		'@bower' => '@vendor/bower-asset',
		'@npm' => '@vendor/npm-asset',
	],
	'bootstrap' => [
		'common\components\ApplicationBootstrap',
		'common\components\WorkspaceBootstrap',
		'common\components\Scraper',
	],
	'modules' => [
		'translatemanager' => [
			'class' => 'lajax\translatemanager\Module',
			'root' => [
				'@common',
				'@backend',
				'@frontend',
			],
			'ignoredCategories' => ['yii', 'tws'],
			'ignoredItems' => ['config'],
			'patterns' => ['*.php'],
			'scanTimeLimit' => null,
			'scanners' => [
				'lajax\translatemanager\services\scanners\ScannerPhpFunction',
				'lajax\translatemanager\services\scanners\ScannerPhpArray',
				'lajax\translatemanager\services\scanners\ScannerDatabase',
			],
		],
	],
	'controllerMap' => [
		'translate' => 'lajax\translatemanager\commands\TranslatemanagerController',
	],
	'components' => [
		'cache' => [
			'class' => 'yii\caching\FileCache',
		],
		'authManager' => [
			'class' => 'yii\rbac\DbManager',
			'cache' => 'cache',
			'cacheKey' => 'rbac',
//			'defaultRoles' => ['operator'],
		],
		'assetManager' => [
			'linkAssets' => true,
			'appendTimestamp' => true,
		],
		'i18n' => [
			'translations' => [
				'*' => [
					'class' => 'yii\i18n\DbMessageSource',
					'db' => 'db',
					'sourceLanguage' => 'en-US', // Developer language
					'sourceMessageTable' => '{{%language_source}}',
					'messageTable' => '{{%language_translate}}',
					'cachingDuration' => 86400, // 24 hours
					'enableCaching' => true,
					'forceTranslation' => true,
				],
			],
		],
		'settings' => [
			'class' => 'tws\settings\Settings',
		],
		'masterSettings' => [
			'class' => 'tws\settings\Settings',
		],
		'instance' => [
			'class' => 'tws\instance\Instance',
		],
	],
];
