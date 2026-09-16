<?php

namespace common\components;

use Yii;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\web\Application;
use yii\web\Response;

/**
 * Sends the response headers the application relies on for transport and
 * framing safety.
 *
 * These are set here rather than in .htaccess because the embed widget is
 * deliberately framable on the customer's own site while everything else must
 * not be, and only the application knows which route is being served.
 */
class SecurityHeaders extends Component implements BootstrapInterface
{
	/**
	 * @var int How long, in seconds, a browser should remember to reach this
	 * host over HTTPS only. Sent solely on requests that already arrived over
	 * TLS, so a plain-HTTP development host is never pinned. Zero disables it.
	 */
	public $hstsMaxAge = 31536000;

	/**
	 * @var bool Whether the HSTS policy also covers subdomains. Tenants are
	 * served from paths rather than subdomains here, so this stays off until
	 * every subdomain is known to be HTTPS-only.
	 */
	public $hstsIncludeSubDomains = false;

	/**
	 * @var string[] First route segments that are allowed to be framed by any
	 * origin. The chat widget is embedded in an iframe on customer sites, so
	 * denying framing would break the product.
	 */
	public $framableRoutes = ['embed'];

	/**
	 * @var string What the referrer is trimmed to when leaving the origin.
	 */
	public $referrerPolicy = 'strict-origin-when-cross-origin';

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		if (!$app instanceof Application) {
			return;
		}

		$app->getResponse()->on(Response::EVENT_BEFORE_SEND, function ($event) {
			$this->applyTo($event->sender);
		});
	}

	/**
	 * Adds the headers to a response, leaving any the application already set
	 * untouched.
	 *
	 * @param Response $response
	 */
	protected function applyTo(Response $response)
	{
		$headers = $response->getHeaders();

		// A stored file must never be re-interpreted as script by content
		// sniffing, whatever the declared type.
		if (!$headers->has('X-Content-Type-Options')) {
			$headers->set('X-Content-Type-Options', 'nosniff');
		}

		if ($this->referrerPolicy && !$headers->has('Referrer-Policy')) {
			$headers->set('Referrer-Policy', $this->referrerPolicy);
		}

		// Only meaningful over TLS, and pinning a host reached over plain HTTP
		// would lock out a development environment.
		if ($this->hstsMaxAge > 0 && !$headers->has('Strict-Transport-Security') && Yii::$app->request->getIsSecureConnection()) {
			$value = "max-age={$this->hstsMaxAge}";
			if ($this->hstsIncludeSubDomains) {
				$value .= '; includeSubDomains';
			}
			$headers->set('Strict-Transport-Security', $value);
		}

		if ($this->isFramable()) {
			return;
		}

		// frame-ancestors is the modern control; X-Frame-Options is kept for
		// browsers that still only honour the older header.
		if (!$headers->has('X-Frame-Options')) {
			$headers->set('X-Frame-Options', 'SAMEORIGIN');
		}
		if (!$headers->has('Content-Security-Policy')) {
			$headers->set('Content-Security-Policy', "frame-ancestors 'self'");
		}
	}

	/**
	 * Whether the route being served is one that any origin may frame.
	 *
	 * @return bool
	 */
	protected function isFramable()
	{
		$route = (string) Yii::$app->requestedRoute;
		if ($route === '') {
			return false;
		}

		$segment = explode('/', $route)[0];

		return in_array($segment, $this->framableRoutes, true);
	}
}
