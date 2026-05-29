<?php
return [
	// This parameter is used to remove a specific URL segment in order to get the right uploads URL
	'upload.ignoredBasePaths' => ['admin'],
	'defaultLanguage' => 'en-US',
	'languages' => [
		'en' => 'en-US',
		'ro' => 'ro-RO',
		'de' => 'de-DE',
	],

	'schedule.enabled' => false,

	'user.loginDuration' => 60 * 60 * 3600,
	'user.loginTokenExpiration' => 60 * 60 * 3600,
	'user.passwordResetTokenExpiration' => 60 * 60 * 3600,
	'user.signupTokenExpiration' => 60 * 60 * 3600,
	'user.accountActivation' => \common\models\User::ACCOUNT_ACTIVATION_CONFIRMATION,
];
