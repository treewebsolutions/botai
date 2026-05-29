<?php
// ----------------------------------------------------------------------------
// phpMyAdmin user overrides (mounted at /etc/phpmyadmin/config.user.inc.php).
// Kept minimal on purpose: tweak session lifetime + skip the version-check
// network call that pops up the "Error code: 200 / OK (rejected)" dialog
// when the container can't reach phpmyadmin.net.
// ----------------------------------------------------------------------------

// Disable the "check for new phpMyAdmin versions" feature. The call is made
// from the browser against phpmyadmin.net; when it is blocked (offline dev,
// strict networks, etc.) jQuery surfaces it as "200 OK (rejected)".
$cfg['VersionCheck'] = false;

// Session cookie lifetime in seconds. Default is 1440 (24 min) which causes
// AJAX calls from a long-open tab to come back as HTML login pages.
// 8 hours is plenty for local development.
$cfg['LoginCookieValidity'] = 8 * 60 * 60;

// Make sure PHP's session GC matches the cookie lifetime above, otherwise
// the session on disk can be collected before the cookie expires.
ini_set('session.gc_maxlifetime', (string) (8 * 60 * 60));

// ----------------------------------------------------------------------------
// Import / export tuning
// ----------------------------------------------------------------------------

// No PMA-imposed time limit on imports (still bound by PHP max_execution_time
// which we also disable via docker/phpmyadmin/php.ini).
$cfg['ExecTimeLimit'] = 0;

// Let PMA use as much RAM as PHP's memory_limit allows.
$cfg['MemoryLimit'] = '1024M';

// Serve SQL files straight off the container filesystem. The host's
// database/ folder is mounted at /var/uploads (see docker-compose.yml),
// so in the Import tab you get a "Select from the web server upload
// directory" dropdown listing structure.sql / seed-*.sql. That bypasses
// the HTTP upload entirely — by far the biggest phpMyAdmin speed-up.
$cfg['UploadDir'] = '/var/uploads';
$cfg['SaveDir']   = '/var/uploads';

// Accept larger chunks per round-trip so phpMyAdmin doesn't slice a single
// multi-MB INSERT into many small ones.
$cfg['Import']['charset'] = 'utf8';

// ----------------------------------------------------------------------------
// Bulk DROP-friendly default
// ----------------------------------------------------------------------------
// Pre-tick the "Disable foreign key checks" option in PMA dialogs (Drop,
// Truncate, SQL tab, Import, ...). This lets you select-all and drop all
// tables from the Structure page in one shot without hitting "Cannot delete
// or update a parent row: a foreign key constraint fails".
$cfg['DefaultForeignKeyChecks'] = 'disable';

// phpMyAdmin hides "Drop database" from the Operations tab unless this is
// true (local dev only; keep false on shared hosting).
$cfg['AllowUserDropDatabase'] = true;

