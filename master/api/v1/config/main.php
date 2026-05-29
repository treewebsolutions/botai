<?php

return [
	'modules' => [
		'users' => [
			'class' => \api\v1\modules\user\Module::class,
		],
		'workspaces' => [
			'class' => \api\v1\modules\workspace\Module::class,
		],
		'contractors' => [
			'class' => \api\v1\modules\contractor\Module::class,
		],
	],
];
