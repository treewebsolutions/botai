<?php
return [
	'aliases' => [
		'@embed' => dirname(__DIR__),
	],
	'components' => [
		'urlManager' => [
			'class' => 'yii\web\UrlManager',
			'rules' => [
				[
					'pattern' => '/',
					'route' => 'default/index',
				],
				[
					'pattern' => 'api',
					'route' => 'default/api',
				],
				[
					'pattern' => 'chat',
					'route' => 'chat/index',
				],
			],
		],
	],
];
