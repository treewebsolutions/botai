<?php
$params = array_merge(
	require __DIR__ . '/../../../../common/config/params.php',
	require __DIR__ . '/../../../../common/config/params-local.php',
	require __DIR__ . '/../../../../frontend/config/params.php',
	require __DIR__ . '/../../../../frontend/config/params-local.php',
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/params.php'
);

return [
	'id' => 'app-frontend-{{ID}}',
	'name' => '{{NAME}}',
	'basePath' => dirname(__DIR__),
	'viewPath' => '@frontend/views',
	'components' => [
		'request' => [
			'baseUrl' => '/{{URL}}',
		],
	],
	'params' => $params,
];
