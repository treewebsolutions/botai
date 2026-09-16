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

	// Google Translate API key — set the real value in params-local.php (kept out of VCS)
	'google.translateKey' => '',
];
