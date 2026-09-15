<?php
$params = array_merge(
	require __DIR__ . '/../../../../workspace/common/config/params.php',
	require __DIR__ . '/../../../../workspace/common/config/params-local.php',
	require __DIR__ . '/../../../../workspace/console/config/params.php',
	require __DIR__ . '/../../../../workspace/console/config/params-local.php',
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/params.php'
);

return [
	'id' => 'app-console-{{ID}}',
	'name' => '{{NAME}}',
	'basePath' => dirname(__DIR__),
	'components' => [
		'urlManager' => [
			'baseUrl' => '/{{URL}}',
		],
	],
	'params' => $params,
];
