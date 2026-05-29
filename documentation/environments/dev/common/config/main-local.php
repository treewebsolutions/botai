<?php
return [
	'components' => [
		'db' => [
			'class' => 'yii\db\Connection',
			'dsn' => 'mysql:host=localhost;dbname=DATABASE_NAME',
			'username' => 'DATABASE_USERNAME',
			'password' => 'DATABASE_PASSWORD',
			'charset' => 'utf8',
			'enableLogging' => true,
			'enableProfiling' => true,
			'enableSchemaCache' => true,
			'schemaCache' => 'cache',
			'schemaCacheDuration' => 3600,
			'enableQueryCache' => true,
			'queryCache' => 'cache',
			'queryCacheDuration' => 3600,
		],
		'mailer' => [
			'class' => 'yii\swiftmailer\Mailer',
			'viewPath' => '@common/mail',
			'useFileTransport' => false,
			'messageConfig' => [
				'charset' => 'UTF-8',
			],
		],
		'company' => [
			'class' => 'tws\commercial\components\Company',
			'enableCaching' => true,
			'cache' => 'cache',
			'cacheKey' => 'company',
			'cacheDuration' => 24 * 60 * 60,
		],
	],
];
