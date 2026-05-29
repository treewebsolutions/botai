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
				[
					'pattern' => 'chat/speak',
					'route' => 'chat/speak',
				],
				[
					'pattern' => 'chat/thread',
					'route' => 'chat/thread',
				],
				[
					'pattern' => 'chat/validate-thread',
					'route' => 'chat/validate-thread',
				],
				[
					'pattern' => 'chat/send-conversation',
					'route' => 'chat/send-conversation',
				],
			],
		],
	],
];
