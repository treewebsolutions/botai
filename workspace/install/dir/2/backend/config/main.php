<?php
$params = array_merge(
	require __DIR__ . '/../../../../common/config/params.php',
	require __DIR__ . '/../../../../common/config/params-local.php',
	require __DIR__ . '/../../../../backend/config/params.php',
	require __DIR__ . '/../../../../backend/config/params-local.php',
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/params.php'
);

return [
	'id' => 'app-backend-{{ID}}',
	'name' => '{{NAME}}',
	'basePath' => dirname(__DIR__),
	'viewPath' => '@backend/views',
	'components' => [
		'request' => [
			'baseUrl' => '/{{URL}}/admin',
		],
	],
	'params' => $params,
];
