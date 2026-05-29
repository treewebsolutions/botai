<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-frontend',
	'name' => 'StoreDo',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'frontend\controllers',
	'bootstrap' => [
		'log',
		'frontend\components\ApplicationBootstrap',
		'account',
		'article',
		'service',
	],
	'modules' => [
		'account' => [
			'class' => 'frontend\modules\account\Module',
		],
		'article' => [
			'class' => 'frontend\modules\article\Module',
		],
		'service' => [
			'class' => 'frontend\modules\service\Module',
		],
	],
	'components' => [
		'request' => [
			'baseUrl' => '',
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
			'loginUrl' => ['/site/login'],
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
			'baseUrl' => '',
			'languages' => ['en' => 'en-US'],
			'enableLanguageDetection' => false,
			'enableDefaultLanguageUrlCode' => false,
			'enableLanguagePersistence' => false,
			'enableStrictParsing' => true,
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'normalizer' => [
				'class' => 'yii\web\UrlNormalizer',
				'action' => yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT,
			],
			'rules' => [
				[
					'pattern' => '',
					'route' => '/site/index',
				],
				[
					'pattern' => '/rss',
					'route' => '/site/rss',
				],
				[
					'pattern' => '/sitemap',
					'route' => '/site/sitemap',
				],
				[
					'pattern' => '<action:(invitation|logout|search|ipn|accept-cookies|assume-identity)>',
					'route' => '/site/<action>',
				],
				[
					'pattern' => 'ab2<auth_key>',
					'route' => '/site/ab2',
				],
				[
					'pattern' => 'qr-code/<code>',
					'route' => '/site/qr-code',
				],
			],
		],
	],
	'params' => $params,
];
