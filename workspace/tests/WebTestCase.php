<?php

namespace tests;

use common\models\User;
use Yii;

/**
 * Base class for web-flow tests: each test runs against a real yii\web\Application
 * (the tenant frontend app with its `embed` module), inside the same rolled-back DB
 * transaction as DatabaseTestCase. Controllers are exercised through
 * [[runControllerAction()]], so routing filters (access control) and redirects
 * behave as in production; tests assert on redirects/JSON and database effects, not
 * on rendered HTML.
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
	 * Boots the frontend web application with test-friendly components.
	 */
	protected function createWebApplication()
	{
		// AssetManager refuses a missing basePath; widget-heavy views publish their
		// bundles here.
		$assetPath = sys_get_temp_dir() . '/botai-workspace-test-assets';
		if (!is_dir($assetPath)) {
			mkdir($assetPath, 0777, true);
		}

		new \yii\web\Application([
			'id' => 'workspace-web-tests',
			'basePath' => dirname(__DIR__) . '/frontend',
			// The frontend app's real vendor lives one level up (workspace/vendor).
			'vendorPath' => dirname(__DIR__) . '/vendor',
			'language' => 'en-US',
			'aliases' => [
				'@webroot' => dirname(__DIR__) . '/frontend/web',
				'@web' => '/',
				// same mapping as common/config/main.php — widget asset bundles resolve these
				'@bower' => '@vendor/bower-asset',
				'@npm' => '@vendor/npm-asset',
			],
			// Same ids as frontend/config/main.php.
			'modules' => [
				'embed' => [
					'class' => 'frontend\modules\embed\Module',
				],
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
				// Per-test in-memory cache: finders cache through Yii::$app->cache with
				// TagDependency. A fresh ArrayCache per test keeps them working without
				// cross-test leakage.
				'cache' => [
					'class' => 'yii\caching\ArrayCache',
				],
				'db' => [
					'class' => 'yii\db\Connection',
					'dsn' => getenv('TEST_DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=botai_workspace_test',
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
					'scriptFile' => dirname(__DIR__) . '/frontend/web/index.php',
					'scriptUrl' => '/index.php',
					'url' => '/test',
					'hostInfo' => 'http://test.local',
				],
				'user' => [
					'class' => 'yii\web\User',
					'identityClass' => User::class,
					'enableSession' => false,
					'loginUrl' => '/',
				],
				// Controllers call Yii::$app->session->setFlash(); the real Session would
				// session_start() and fail once PHPUnit has flushed output to the CLI.
				'session' => [
					'class' => \tests\support\FakeSession::class,
				],
				// Empty settings stores; tests that need a key set it on the fake:
				// Yii::$app->settings->set('key', 'value', 'category').
				'settings' => new \tests\support\FakeSettings(),
				'masterSettings' => new \tests\support\FakeSettings(),
				'instance' => [
					'class' => 'tws\instance\Instance',
				],
				// Mail goes to files under @runtime/mail instead of a transport.
				'mailer' => [
					'class' => 'yii\swiftmailer\Mailer',
					'viewPath' => '@common/mail',
					'useFileTransport' => true,
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
	 * @param string $route e.g. "embed/default/index"
	 * @param array $get
	 * @param array $post When non-empty the request reports itself as POST.
	 * @return mixed the action result (a Response for redirects/JSON, a string for renders)
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
	 * Creates an active user with a known password and returns it.
	 *
	 * @param string $password
	 * @param array $attributes
	 * @return User
	 */
	protected function createUser($password = 'secret123', array $attributes = [])
	{
		$suffix = substr(bin2hex(random_bytes(4)), 0, 6);
		$id = $this->insertRow('user', array_merge([
			'username' => 'tester' . $suffix,
			'email' => 'tester' . $suffix . '@test.local',
			'first_name' => 'Test',
			'last_name' => 'User',
			'password_hash' => Yii::$app->security->generatePasswordHash($password),
			'auth_key' => Yii::$app->security->generateRandomString(),
			'status' => User::STATUS_ACTIVE,
			'deleted' => 0,
		], $attributes));

		return User::findOne(['id' => $id]);
	}

	/**
	 * Authenticates the given user for the current request cycle.
	 *
	 * @param User $user
	 */
	protected function loginAs(User $user)
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
