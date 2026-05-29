<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-backend',
	'name' => 'BotAi',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'backend\controllers',
	'bootstrap' => [
		'log',
		'backend\components\ApplicationBootstrap',
		'setting-manager',
		'eventlog-manager',
		'notification-manager',
		'user-manager',
		'import-manager',
		'export-manager',
		'nomenclature-manager',
        'backup-manager',
        'conversation-manager',
	],
	'modules' => [
		'setting-manager' => [
			'class' => 'backend\modules\setting\Module',
		],
		'eventlog-manager' => [
			'class' => 'backend\modules\eventlog\Module',
		],
		'notification-manager' => [
			'class' => 'backend\modules\notification\Module',
		],
		'user-manager' => [
			'class' => 'backend\modules\user\Module',
		],
		'import-manager' => [
			'class' => 'backend\modules\import\Module',
		],
		'export-manager' => [
			'class' => 'backend\modules\export\Module',
		],
		'nomenclature-manager' => [
			'class' => 'backend\modules\nomenclature\Module',
		],
        'backup-manager' => [
            'class' => 'backend\modules\backup\Module',
        ],
		'conversation-manager' => [
			'class' => 'backend\modules\conversation\Module',
		],
	],
	'components' => [
		'request' => [
			'baseUrl' => '/admin',
			'csrfParam' => '_csrf',
			'enableCsrfValidation' => false,
			'enableCookieValidation' => true,
			'enableCsrfCookie' => false,
			'csrfCookie' => [
				'path' => '/',
				'httpOnly' => true,
			],
		],
		'user' => [
			'identityClass' => 'common\models\User',
			'enableAutoLogin' => true,
			'identityCookie' => [
				'path' => '/',
				'name' => '_identity',
				'httpOnly' => true,
			],
			'loginUrl' => '/',
		],
		'session' => [
			'name' => 'BOTAISESSID',
			'useCookies' => true,
			'cookieParams' => [
				'path' => '/',
				'httpOnly' => true,
			],
		],
		'log' => [
			'traceLevel' => YII_DEBUG ? 3 : 0,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
				],
			],
		],
		'errorHandler' => [
			'errorAction' => 'site/error',
		],
		'urlManager' => [
			'class' => 'codemix\localeurls\UrlManager',
			'languages' => ['en' => 'en-US'], 				// This is overwritten by ApplicationComponent in bootstrap phase
			'enableLanguageDetection' => false,				// Detects and sets the language of the browser
			'enableDefaultLanguageUrlCode' => false,	// Removes the language from URL if is default language
			'enableLanguagePersistence' => false,			// Keep the last language in a cookie
			'enableStrictParsing' => true, 						// This does not allow other rules than defined ones
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'normalizer' => [
				'class' => 'yii\web\UrlNormalizer',
				'action' => yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT,
			],
			'rules' => [
				// Site
				[
					'pattern' => '/',
					'route' => '/site/index',
				],
				[
					'pattern' => '/info',
					'route' => '/site/info',
				],
				// User
				// TODO: remove the check action after is not needed anymore
				[
					'pattern' => '/<action:(login|logout|request-password-reset|reset-password|profile|search|check)>',
					'route' => '/site/<action>',
				],
				// Application
				[
					'pattern' => '/<controller>/<id:(\d+|__\w+__)>/<action>',
					'route' => '/<controller>/<action>',
				],
				[
					'pattern' => '/<controller>/<action>',
					'route' => '/<controller>/<action>',
				],
			],
		],
		'notification' => [
			'class' => 'backend\modules\notification\components\Notification',
			'model' => 'common\models\Notification',
		],
		'eventLog' => [
			'class' => 'backend\modules\eventlog\components\EventLog',
			'model' => 'common\models\EventLog',
		],
        'translate' => [
            'class' => 'richweber\google\translate\Translation',
            'key' => '',
        ],
	],
	'params' => $params,
];
