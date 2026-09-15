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
					'pattern' => 'chat/conversation',
					'route' => 'chat/conversation',
				],
				[
					'pattern' => 'chat/validate-conversation',
					'route' => 'chat/validate-conversation',
				],
				[
					'pattern' => 'chat/send-conversation',
					'route' => 'chat/send-conversation',
				],
			],
		],
	],
];
