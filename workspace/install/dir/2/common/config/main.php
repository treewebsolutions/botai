<?php
return [
	'components' => [
		'db' => [
			'dsn' => 'mysql:host=localhost;dbname={{DB_NAME}}',
			'username' => '{{DB_USERNAME}}',
			'password' => '{{DB_PASSWORD}}',
		],
	],
];
