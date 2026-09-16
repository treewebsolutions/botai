<?php
/**
 * PHPUnit bootstrap for the workspace app: boots a minimal Yii console application
 * against the dedicated `botai_workspace_test` database, so ActiveRecord models and
 * console controllers can be exercised directly. Provision it with:
 *
 *   tools/load-test-schema.sh workspace
 *
 * which drops/recreates the database from the tenant schema
 * (workspace/install/db/_01_structure.sql, or install/db/1/_01_structure.sql while the
 * move of that file is in progress).
 *
 * Run the suite from the workspace directory: run-tests.sh / run-tests.bat, or from the
 * repo root: php tools/phpunit-9.phar --bootstrap workspace/tests/bootstrap.php workspace/tests
 *
 * Connection overrides: TEST_DB_DSN, TEST_DB_USER, TEST_DB_PASSWORD (inside the
 * botai_web container use TEST_DB_DSN=mysql:host=db;dbname=botai_workspace_test).
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../common/config/bootstrap.php';
require __DIR__ . '/../console/config/bootstrap.php';

Yii::setAlias('@tests', __DIR__);

$config = [
	'id' => 'workspace-tests',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'console\controllers',
	'language' => 'en-US',
	// The real application params, so the upload allow-lists and the rest of the
	// configuration the models read are the ones production uses rather than
	// whatever each test happens to set.
	'params' => require __DIR__ . '/../common/config/params.php',
	'components' => [
		// Empty settings stores (tenant + master): CommonActiveRecord::delete() consults
		// Yii::$app->settings->get('enableSoftDelete') on every delete, and code under
		// test falls back to its defaults when a key is absent.
		'settings' => [
			'class' => 'tws\settings\Settings',
		],
		'masterSettings' => [
			'class' => 'tws\settings\Settings',
		],
		// Catch-all message source so Yii::t() in validators/labels resolves without the
		// DbMessageSource of the full application config; with language == sourceLanguage
		// the source text is returned as-is and no message file is ever read.
		'i18n' => [
			'translations' => [
				'*' => [
					'class' => 'yii\i18n\PhpMessageSource',
					'basePath' => '@common/messages',
				],
			],
		],
		// The application always has one (FileCache in common/config/main.php), and
		// finders like Setting::findAppSettings() cache through it with TagDependency —
		// in-memory so nothing leaks between test cases or onto disk.
		'cache' => [
			'class' => 'yii\caching\ArrayCache',
		],
		'db' => [
			'class' => 'yii\db\Connection',
			'dsn' => getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=botai_workspace_test',
			'username' => getenv('TEST_DB_USER') ?: 'root',
			'password' => getenv('TEST_DB_PASSWORD') !== false ? getenv('TEST_DB_PASSWORD') : 'mysql',
			'charset' => 'utf8mb4',
		],
	],
];

new yii\console\Application($config);
