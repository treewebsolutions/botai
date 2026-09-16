<?php

namespace common\filters;

use Yii;
use yii\base\ActionFilter;
use yii\web\TooManyRequestsHttpException;

/**
 * Per-IP throttle for the credential endpoints.
 *
 * Yii's own RateLimiter keys on the identity, which is no use on exactly the actions
 * that matter here — login, signup and password reset are reached before anyone is
 * authenticated, so an attacker is never the same "user" twice. This keys on the client
 * address and the action instead, which is what stops credential stuffing and the
 * mail/SMS flooding the assessment called out.
 *
 * Counting lives in the cache, so it is best-effort: a cache flush forgets the window,
 * and a shared address (office NAT, mobile carrier) is counted as one client. That is
 * the accepted trade for a control that needs no schema and no request of its own.
 *
 * Usage, in a controller's behaviors():
 *
 * ```php
 * 'rateLimit' => [
 *     'class' => RateLimit::class,
 *     'only' => ['login', 'signup'],
 *     'limit' => 10,
 *     'window' => 900,
 * ],
 * ```
 */
class RateLimit extends ActionFilter
{
	/**
	 * @var int How many requests one address may make within [[window]].
	 */
	public $limit = 10;

	/**
	 * @var int The length of the counting window, in seconds.
	 */
	public $window = 900;

	/**
	 * @var string Cache key prefix, so unrelated filters cannot share a counter.
	 */
	public $keyPrefix = 'ratelimit';

	/**
	 * @var bool Whether GET requests count. They are reads — the form page itself is a
	 * GET — so by default only the submissions are counted.
	 */
	public $countSafeRequests = false;

	/**
	 * {@inheritdoc}
	 */
	public function beforeAction($action)
	{
		if (!$this->countSafeRequests && Yii::$app->request->isGet) {
			return parent::beforeAction($action);
		}

		$cache = Yii::$app->has('cache') ? Yii::$app->cache : null;
		if ($cache === null) {
			// No cache configured: let the request through rather than lock the site out.
			return parent::beforeAction($action);
		}

		$key = $this->buildKey($action);
		$now = time();
		$entry = $cache->get($key);

		// A fixed window, carried in the value rather than left to the TTL: re-setting a
		// key renews its expiry, so counting that way would let each further attempt push
		// the window out and a user fumbling their password would extend their own
		// lockout indefinitely.
		if (!is_array($entry) || ($now - $entry['start']) >= $this->window) {
			$entry = ['count' => 0, 'start' => $now];
		}

		if ($entry['count'] >= $this->limit) {
			Yii::warning(
				"Rate limit hit on {$action->getUniqueId()} from " . Yii::$app->request->userIP,
				__METHOD__
			);
			throw new TooManyRequestsHttpException(
				Yii::t('common', 'Too many attempts. Please try again later.')
			);
		}

		$entry['count']++;
		$cache->set($key, $entry, $this->window - ($now - $entry['start']));

		return parent::beforeAction($action);
	}

	/**
	 * @param \yii\base\Action $action
	 * @return string
	 */
	protected function buildKey($action)
	{
		return implode(':', [
			$this->keyPrefix,
			$action->getUniqueId(),
			(string) Yii::$app->request->userIP,
		]);
	}
}
