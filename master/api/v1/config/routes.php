<?php

return [
	[
		'class' => \yii\web\GroupUrlRule::class,
		'prefix' => 'v1/users',
		'rules' => [
			['verb' => 'GET', 'pattern' => '', 'route' => 'user/index'],
			['verb' => 'POST', 'pattern' => '', 'route' => 'user/create'],
			['verb' => 'DELETE', 'pattern' => '', 'route' => 'user/delete'],
			['verb' => 'POST', 'pattern' => 'delete', 'route' => 'user/delete'],
			['verb' => 'POST', 'pattern' => 'restore', 'route' => 'user/restore'],
			['verb' => 'GET', 'pattern' => '<id:(\d+)>', 'route' => 'user/view'],
			['verb' => ['PUT'], 'pattern' => '<id:(\d+)>', 'route' => 'user/update'],
			['verb' => ['PATCH'], 'pattern' => '<id:(\d+)>', 'route' => 'user/restore'],
			['verb' => 'DELETE', 'pattern' => '<id:(\d+)>', 'route' => 'user/delete'],
			['verb' => ['GET', 'PUT'], 'pattern' => 'me', 'route' => 'user/profile'],
			['verb' => 'POST', 'pattern' => 'login', 'route' => 'user/login'],
		],
	],
	[
		'class' => \yii\web\GroupUrlRule::class,
		'prefix' => 'v1/workspaces',
		'rules' => [
			['verb' => 'GET', 'pattern' => '', 'route' => 'workspace/index'],
			['verb' => 'POST', 'pattern' => '', 'route' => 'workspace/create'],
			['verb' => 'DELETE', 'pattern' => '', 'route' => 'workspace/delete'],
			['verb' => 'POST', 'pattern' => 'delete', 'route' => 'workspace/delete'],
			['verb' => 'POST', 'pattern' => 'restore', 'route' => 'workspace/restore'],
			['verb' => 'POST', 'pattern' => 'sync', 'route' => 'workspace/sync'],
			['verb' => 'GET', 'pattern' => '<id:(\d+)>', 'route' => 'workspace/view'],
			['verb' => ['PUT'], 'pattern' => '<id:(\d+)>', 'route' => 'workspace/update'],
			['verb' => ['PATCH'], 'pattern' => '<id:(\d+)>', 'route' => 'workspace/restore'],
			['verb' => 'DELETE', 'pattern' => '<id:(\d+)>', 'route' => 'workspace/delete'],
		],
	],
	[
		'class' => \yii\web\GroupUrlRule::class,
		'prefix' => 'v1/contractors',
		'rules' => [
			['verb' => 'POST', 'pattern' => 'clocking', 'route' => 'contractor/clocking'],
		],
	],
];
