<?php

namespace tests\unit;

use common\filters\RateLimit;
use tests\WebTestCase;
use Yii;
use yii\base\Action;
use yii\web\Controller;
use yii\web\TooManyRequestsHttpException;

/**
 * The per-IP throttle on the credential endpoints.
 *
 * Yii's own RateLimiter keys on the identity, which is no help on login, signup and
 * password reset — nobody is authenticated yet, so an attacker is never the same user
 * twice. These cases cover the counting this replaces it with, and in particular the
 * fixed window: counting through the TTL alone would let each further attempt renew the
 * key and a user fumbling their password would extend their own lockout indefinitely.
 */
class RateLimitTest extends WebTestCase
{
	/**
	 * @param string $id
	 * @return Action
	 */
	private function action($id = 'login')
	{
		return new Action($id, new Controller('site', Yii::$app));
	}

	/**
	 * @param array $config
	 * @return RateLimit
	 */
	private function filter(array $config = [])
	{
		return new RateLimit(array_merge(['limit' => 3, 'window' => 900], $config));
	}

	protected function setUp(): void
	{
		parent::setUp();
		Yii::$app->cache->flush();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		Yii::$app->request->setBodyParams(['username' => 'someone']);
	}

	public function testRequestsUnderTheLimitPass()
	{
		$filter = $this->filter();
		$action = $this->action();

		for ($i = 0; $i < 3; $i++) {
			$this->assertTrue($filter->beforeAction($action), "attempt {$i} should pass");
		}
	}

	public function testTheRequestPastTheLimitIsRefused()
	{
		$filter = $this->filter();
		$action = $this->action();

		for ($i = 0; $i < 3; $i++) {
			$filter->beforeAction($action);
		}

		$this->expectException(TooManyRequestsHttpException::class);
		$filter->beforeAction($action);
	}

	public function testActionsAreCountedSeparately()
	{
		$filter = $this->filter();

		for ($i = 0; $i < 3; $i++) {
			$filter->beforeAction($this->action('login'));
		}

		$this->assertTrue(
			$filter->beforeAction($this->action('signup')),
			'exhausting one action must not lock out another'
		);
	}

	public function testGetRequestsAreNotCounted()
	{
		$filter = $this->filter();
		$action = $this->action();
		$_SERVER['REQUEST_METHOD'] = 'GET';
		Yii::$app->request->setBodyParams([]);

		for ($i = 0; $i < 10; $i++) {
			$this->assertTrue($filter->beforeAction($action), 'loading the form page is free');
		}
	}

	public function testAttemptsDoNotExtendTheWindow()
	{
		// The window is carried in the value, not the TTL, so the deadline set by the
		// first attempt is the one that stands.
		$filter = $this->filter(['limit' => 5]);
		$action = $this->action();

		$filter->beforeAction($action);
		$key = implode(':', ['ratelimit', $action->getUniqueId(), (string) Yii::$app->request->userIP]);
		$first = Yii::$app->cache->get($key);

		$filter->beforeAction($action);
		$second = Yii::$app->cache->get($key);

		$this->assertSame($first['start'], $second['start'], 'the window start must not move');
		$this->assertSame(2, $second['count']);
	}

	public function testTheWindowIsForgottenOnceItHasElapsed()
	{
		$filter = $this->filter(['window' => 1]);
		$action = $this->action();

		for ($i = 0; $i < 3; $i++) {
			$filter->beforeAction($action);
		}

		// Reach into the stored entry rather than sleeping: backdating the start is the
		// same thing the clock would do.
		$key = implode(':', ['ratelimit', $action->getUniqueId(), (string) Yii::$app->request->userIP]);
		$entry = Yii::$app->cache->get($key);
		$entry['start'] -= 3600;
		Yii::$app->cache->set($key, $entry, 3600);

		$this->assertTrue($filter->beforeAction($action), 'a lockout must not be permanent');
	}

	public function testAMissingCacheDoesNotLockTheSiteOut()
	{
		$filter = $this->filter();
		$cache = Yii::$app->cache;
		Yii::$app->set('cache', null);

		try {
			$this->assertTrue($filter->beforeAction($this->action()));
		} finally {
			Yii::$app->set('cache', $cache);
		}
	}
}
