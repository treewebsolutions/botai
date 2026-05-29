<?php
return [
	'aliases' => [
		'@setting' => dirname(__DIR__),
	],
	'modules' => [
		'language-manager' => [
			'class' => 'backend\modules\setting\modules\language\Module',
		],
		'currency-manager' => [
			'class' => 'backend\modules\setting\modules\currency\Module',
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
					'pattern' => '<controller>/<action>',
					'route' => '<controller>/<action>',
				],
			],
		],
	],
];
