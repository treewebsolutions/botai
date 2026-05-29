<?php
return [
	'aliases' => [
		'@nomenclature' => dirname(__DIR__),
	],
	'modules' => [
		'workspace-manager' => [
			'class' => 'backend\modules\nomenclature\modules\workspace\Module',
		],
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
					'pattern' => '<controller>/<id:(\d+|__\w+__|\w+)>/<action>',
					'route' => '<controller>/<action>',
				],
				[
					'pattern' => '<controller>/<action>',
					'route' => '<controller>/<action>',
				],
			],
		],
	],
];
