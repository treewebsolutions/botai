<?php

$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php'
);

return [
	'id' => 'api',
	'name' => 'REST API Application',
	'basePath' => dirname(__DIR__),
	'aliases' => [
		'@api' => dirname(__DIR__),
	],
	'modules' => [
		'v1' => [
			'class' => \api\v1\Module::class,
		],
	],
	'bootstrap' => [
		\api\components\ApplicationBootstrap::class,
	],
	'components' => [
		'request' => [
			'class' => \yii\web\Request::class,
			'baseUrl' => '/api',
			'enableCookieValidation' => false,
			'enableCsrfValidation' => false,
			'parsers' => [
				'application/json' => \yii\web\JsonParser::class,
				'multipart/form-data' => \yii\web\MultipartFormDataParser::class,
			],
		],
		'response' => [
			'class' => \yii\web\Response::class,
			'format' => \yii\web\Response::FORMAT_JSON,
			'on beforeSend' => function ($event) {
				/** @var \yii\web\Response $response */
				$response = $event->sender;

				$data = [
					'success' => $response->data['success'] ?? $response->getIsSuccessful(),
					'message' => $response->data['message'] ?? null,
					'data' => $response->data,
					'errors' => $response->data['errors'] ?? [],
				];

				if (is_array($response->data)) {
					if (array_key_exists('data', $response->data)) {
						$data['data'] = $response->data['data'];
					}
				}

				if (!$data['success']) {
					$data['data'] = null;
				}

				if ($response->getIsServerError() && YII_DEBUG) {
					$data['errors']['debug'] = $response->data;
				}

				$response->data = $data;
			},
		],
		'authManager' => [
			'class' => \yii\rbac\DbManager::class,
			'cache' => 'cache',
			'cacheKey' => 'rbac',
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
		'user' => [
			'class' => \yii\web\User::class,
			'identityClass' => \common\models\User::class,
			'enableSession' => false,
			'loginUrl' => null,
		],
		'urlManager' => [
			'class' => \yii\web\UrlManager::class,
			'enablePrettyUrl' => true,
			'enableStrictParsing' => true,
			'showScriptName' => false,
			'rules' => [
				'v1' => [
					'class' => \yii\web\GroupUrlRule::class,
					'prefix' => 'v1',
					'rules' => require dirname(__DIR__) . '/v1/config/routes.php',
				],
			],
		],
		'settings' => [
			'class' => 'tws\settings\Settings',
		],
	],
	'params' => $params,
];
