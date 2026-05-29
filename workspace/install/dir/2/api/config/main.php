<?php
$params = array_merge(
	require __DIR__ . '/../../../../common/config/params.php',
	require __DIR__ . '/../../../../common/config/params-local.php',
	require __DIR__ . '/../../../../api/config/params.php',
	require __DIR__ . '/../../../../api/config/params-local.php',
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/params.php'
);

return [
	'id' => 'app-api-{{ID}}',
	'name' => '{{NAME}}',
	'basePath' => dirname(__DIR__),
	'components' => [
		'request' => [
			'baseUrl' => '/{{URL}}/api',
		],
	],
	'params' => $params,
];
