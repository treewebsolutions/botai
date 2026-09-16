<?php
return [
	// This parameter is used to remove a specific URL segment in order to get the right uploads URL
	'upload.ignoredBasePaths' => ['admin', 'dashboard'],

	// Allow-list for every image upload (avatars, carousels, company logos). Both keys are
	// read together by the `file` validators; `mimeTypes` makes the validator sniff the
	// real content instead of trusting the submitted file name.
	'image.extensions' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
	'image.mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],

	// Allow-list for non-image uploads (support-ticket attachments, documents).
	// Deliberately excludes anything the web server could be talked into executing.
	'file.extensions' => ['jpeg', 'jpg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'],
	'file.mimeTypes' => [
		'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
		'application/pdf', 'text/plain', 'text/csv',
		'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
		'application/zip', 'application/x-zip-compressed',
	],
	'defaultLanguage' => 'en-US',
	'languages' => [
		'en' => 'en-US',
		'ro' => 'ro-RO',
		'de' => 'de-DE',
	],

	'schedule.enabled' => false,

	// Whether each workspace is served from its own cPanel addon domain, or as a path
	// under the master host (botai.ro/<url>, routed by the root .htaccess).
	//
	// This is independent of whether the cPanel API is usable: an installation can let
	// cPanel create the tenant databases while still routing every tenant by path. It
	// drives both the addon domain creation and the tenant baseUrls, which keep the
	// "/<url>" prefix exactly when there is no addon domain to drop it for.
	'workspace.addonDomains' => false,

	'user.loginDuration' => 60 * 60 * 3600,
	'user.loginTokenExpiration' => 60 * 60 * 3600,
	'user.passwordResetTokenExpiration' => 60 * 60 * 3600,
	'user.signupTokenExpiration' => 60 * 60 * 3600,
	'user.accountActivation' => \common\models\User::ACCOUNT_ACTIVATION_CONFIRMATION,

	// Google Translate API key — set the real value in params-local.php (kept out of VCS)
	'google.translateKey' => '',
];
