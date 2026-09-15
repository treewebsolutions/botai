<?php
return [
	'components' => [
		'db' => [
			'dsn' => 'mysql:host={{DB_HOST}};dbname={{DB_NAME}}',
			'username' => '{{DB_USERNAME}}',
			'password' => '{{DB_PASSWORD}}',
		],
	],
];
