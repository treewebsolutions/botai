<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-frontend',
	'name' => 'StoreDo Documentation',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'frontend\controllers',
	'bootstrap' => [
		'log',
		'frontend\components\ApplicationBootstrap',
		'sitemap',
	],
	'modules' => [
		'sitemap' => [
			'class' => 'frontend\modules\sitemap\Module',
		],
	],
	'components' => [
//		'assetManager' => [
//			'assetMap' => [
//			],
//			'bundles' => [
//				'yii\web\JqueryAsset' => [
//					'sourcePath' => null,   // do not publish the bundle
//					'js' => [
//						'jquery.js',
//					]
//				],
//				'yii\bootstrap\BootstrapPluginAsset' => [
//					'js' => [
//
//					]
//				],
//				'yii\bootstrap\BootstrapAsset' => [
//					'css' => [
//
//					],
//				],
//			],
//		],
        'assetsAutoCompress' => [
            'class'                         => '\skeeks\yii2\assetsAuto\AssetsAutoCompressComponent',
            'enabled'                        => true,
            'jsCompressFlaggedComments'     => true,        //Cut comments during processing js
            'cssFileBottom'                 => false,       //Moving down the page css files
            'jsFileCompressFlaggedComments' => true,        //Cut comments during processing js
        ],
		'request' => [
			'baseUrl' => '/documentation',
			'csrfParam' => '_csrf',
			'enableCsrfValidation' => true,
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
			'baseUrl' => '/documentation',
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
					'pattern' => '<action:(logout|search|auth|ipn|accept-cookies)>',
					'route' => '/site/<action>',
				],
			],
		],
        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            'clients' => [
                'google' => [
                    'class' => 'yii\authclient\clients\Google',
                    'clientId' => '646795429922-g7h1luukdvruphtp6toscn2hqq1om40q.apps.googleusercontent.com',
                    'clientSecret' => 'Qlbd9-CQurWaH3BnPvxJhvei',
                ],
                'facebook' => [
                    'class' => 'yii\authclient\clients\Facebook',
                    'clientId' => '380227856795638',
                    'clientSecret' => 'ef1a70cf4fac81a7bce9be575122b7fd',
                    'attributeNames' => ['name', 'first_name', 'last_name', 'email', 'gender'],
                ],
            ],
        ]
	],
	'params' => $params,
];
