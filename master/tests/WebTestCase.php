<?php

namespace tests;

use Yii;

/**
 * Base class for backend web-flow tests: each test runs against a real
 * yii\web\Application rooted at master/backend with its manager modules, inside the
 * same rolled-back DB transaction as DatabaseTestCase. Controllers are exercised
 * through [[runControllerAction()]], so routing filters (access control, verbs)
 * behave as in production; tests assert on the JSON responses and database effects.
 */
abstract class WebTestCase extends DatabaseTestCase
{
	/**
	 * @var \yii\console\Application The bootstrap console app, restored after each test.
	 */
	private static $consoleApp;

	protected function setUp(): void
	{
		self::$consoleApp = Yii::$app;
		$this->createWebApplication();
		parent::setUp();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		Yii::$app = self::$consoleApp;
	}

	/**
	 * Boots the backend web application with test-friendly components.
	 */
	protected function createWebApplication()
	{
		// AssetManager requires its basePath to exist before the first publish.
		$assetPath = sys_get_temp_dir() . '/botai-master-test-assets';
		if (!is_dir($assetPath)) {
			mkdir($assetPath, 0777, true);
		}

		new \yii\web\Application([
			'id' => 'master-web-tests',
			'basePath' => dirname(__DIR__) . '/backend',
			// The backend app's real vendor lives one level up (master/vendor); without
			// this, widgets publishing @vendor assets would look in backend/vendor.
			'vendorPath' => dirname(__DIR__) . '/vendor',
			'language' => 'en-US',
			'aliases' => [
				'@webroot' => dirname(__DIR__) . '/backend/web',
				'@web' => '/',
				// Composer installs the asset packages under bower-asset/npm-asset, not
				// Yii's default vendor/bower — same mapping as common/config/main.php.
				'@bower' => dirname(__DIR__) . '/vendor/bower-asset',
				'@npm' => dirname(__DIR__) . '/vendor/npm-asset',
			],
			// Same ids as backend/config/main.php; modules are instantiated lazily, so
			// listing them all costs nothing until a route touches one.
			'modules' => [
				'setting-manager' => ['class' => 'backend\modules\setting\Module'],
				'backup-manager' => ['class' => 'backend\modules\backup\Module'],
				'export-manager' => ['class' => 'backend\modules\export\Module'],
				'user-manager' => ['class' => 'backend\modules\user\Module'],
				'eventlog-manager' => ['class' => 'backend\modules\eventlog\Module'],
				'notification-manager' => ['class' => 'backend\modules\notification\Module'],
				'website-manager' => ['class' => 'backend\modules\website\Module'],
				'subscriber-manager' => ['class' => 'backend\modules\subscriber\Module'],
				'nomenclature-manager' => ['class' => 'backend\modules\nomenclature\Module'],
				'marketing-manager' => ['class' => 'backend\modules\marketing\Module'],
				'helpdesk-manager' => ['class' => 'backend\modules\helpdesk\Module'],
				'tutorial-manager' => ['class' => 'backend\modules\tutorial\Module'],
			],
			'components' => [
				// The save pipelines swallow exceptions into Yii::error; without a file
				// target those messages vanish, which makes red CI runs undiagnosable —
				// the workflow tails this log when the suite fails.
				'log' => [
					'targets' => [
						[
							'class' => 'yii\log\FileTarget',
							'logFile' => '@runtime/logs/app.log',
							'levels' => ['error', 'warning'],
							'logVars' => [],
							'exportInterval' => 1,
						],
					],
				],
				// The backend app always has one (FileCache), and write paths invalidate
				// cache tags — TagDependency::invalidate() fatals on a null cache. In-memory,
				// so a cached tree from one test cannot leak into the next.
				'cache' => [
					'class' => 'yii\caching\ArrayCache',
				],
				'db' => [
					'class' => 'yii\db\Connection',
					'dsn' => getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=botai_master_test',
					'username' => getenv('TEST_DB_USER') ?: 'root',
					'password' => getenv('TEST_DB_PASSWORD') !== false ? getenv('TEST_DB_PASSWORD') : 'mysql',
					'charset' => 'utf8mb4',
					// Tests mutate rows directly (no tag invalidation), so query results
					// must never be served stale from the cache component above.
					'enableQueryCache' => false,
				],
				'request' => [
					'class' => 'yii\web\Request',
					'enableCsrfValidation' => false,
					'enableCookieValidation' => false,
					'scriptFile' => dirname(__DIR__) . '/backend/web/index.php',
					'scriptUrl' => '/index.php',
					'url' => '/test',
					'hostInfo' => 'http://test.local',
				],
				// Real RBAC against the auth_* tables in tests/_schema.sql. Most tests never
				// touch it - the fake user component below answers can() without asking -
				// but the authorization tests need the actual tree, and anything that
				// assigns a role goes through here.
				'authManager' => [
					'class' => \yii\rbac\DbManager::class,
					// No cache: a role assigned mid-test has to be visible immediately, and
					// a cached tree must not survive into the next test.
					'cache' => null,
				],
				// The backend guards actions with RBAC roles; the fake passes them all,
				// so the tests exercise the action logic rather than the auth tree.
				'user' => [
					'class' => \tests\support\FakeAdminWebUser::class,
					'identityClass' => 'common\models\User',
					'enableSession' => false,
				],
				// Controllers call Yii::$app->session->setFlash(); the real Session would
				// session_start() and fail once PHPUnit has flushed output to the CLI.
				'session' => [
					'class' => \tests\support\FakeSession::class,
				],
				// CommonActiveRecord::delete() consults enableSoftDelete on every delete.
				'settings' => new \tests\support\FakeSettings(),
				'instance' => [
					'class' => 'tws\instance\Instance',
				],
				'i18n' => [
					'translations' => [
						'*' => [
							'class' => 'yii\i18n\PhpMessageSource',
							'basePath' => '@common/messages',
						],
					],
				],
				'assetManager' => [
					'basePath' => $assetPath,
					'baseUrl' => '/assets',
				],
			],
		]);
	}

	/**
	 * Runs a controller action through the application, with the given query and
	 * body parameters, and returns whatever the action produced.
	 *
	 * @param string $route e.g. "nomenclature-manager/country/update"
	 * @param array $get
	 * @param array $post When non-empty the request reports itself as POST.
	 * @return mixed the action result (a Response for JSON replies, a string for renders)
	 */
	protected function runControllerAction($route, array $get = [], array $post = [])
	{
		$request = Yii::$app->request;
		$request->setQueryParams($get);
		$request->setBodyParams($post);
		if (!empty($post)) {
			$_SERVER['REQUEST_METHOD'] = 'POST';
		} else {
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}

		return Yii::$app->runAction($route, $get);
	}

	/**
	 * Authenticates the given user for the current request cycle.
	 *
	 * @param \common\models\User $user
	 */
	protected function loginAs(\common\models\User $user)
	{
		Yii::$app->user->setIdentity($user);
	}

	/**
	 * The decoded JSON payload of an action result.
	 *
	 * @param mixed $result the value returned by runControllerAction()
	 * @return array
	 */
	protected function jsonPayload($result)
	{
		if ($result instanceof \yii\web\Response) {
			return (array) $result->data;
		}
		return (array) $result;
	}

	/**
	 * @param mixed $result the value returned by runControllerAction()
	 * @return string the redirect target URL ('' when the result is not a redirect)
	 */
	protected function redirectUrl($result)
	{
		if ($result instanceof \yii\web\Response) {
			return (string) $result->headers->get('Location');
		}
		return (string) Yii::$app->response->headers->get('Location');
	}
}
