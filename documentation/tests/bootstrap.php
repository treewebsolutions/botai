<?php
/**
 * PHPUnit bootstrap for the documentation app: boots a minimal Yii console application
 * against the dedicated `botai_documentation_test` database (schema only). Provision it with:
 *
 *   tools/load-test-schema.sh documentation
 *
 * which drops/recreates the database from tests/_schema.sql (a `mysqldump --no-data`
 * of the live `botai_documentation` database — see the header of that file to regenerate it).
 *
 * Run the suite from the documentation directory: run-tests.sh / run-tests.bat, or from the
 * repo root: php tools/phpunit-9.phar --bootstrap documentation/tests/bootstrap.php documentation/tests
 *
 * Connection overrides: TEST_DB_DSN, TEST_DB_USER, TEST_DB_PASSWORD (inside the
 * botai_web container use TEST_DB_DSN=mysql:host=db;dbname=botai_documentation_test).
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';
require __DIR__ . '/../common/config/bootstrap.php';
require __DIR__ . '/../console/config/bootstrap.php';

Yii::setAlias('@tests', __DIR__);

$config = [
	'id' => 'documentation-tests',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'console\controllers',
	'language' => 'en-US',
	'components' => [
		// Empty settings store: CommonActiveRecord::delete() consults
		// Yii::$app->settings->get('enableSoftDelete') on every delete, and other code
		// reads app settings and falls back to its defaults when a key is absent.
		'settings' => [
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
		// The application always has one (FileCache in common/config/main.php), and the
		// page/menu finders cache through it with TagDependency — in-memory so nothing
		// leaks between test cases or onto disk.
		'cache' => [
			'class' => 'yii\caching\ArrayCache',
		],
		'db' => [
			'class' => 'yii\db\Connection',
			'dsn' => getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=botai_documentation_test',
			'username' => getenv('TEST_DB_USER') ?: 'root',
			'password' => getenv('TEST_DB_PASSWORD') !== false ? getenv('TEST_DB_PASSWORD') : 'mysql',
			'charset' => 'utf8mb4',
		],
	],
];

new yii\console\Application($config);
