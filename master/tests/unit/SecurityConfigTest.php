<?php

namespace tests\unit;

use PHPUnit\Framework\TestCase;

/**
 * Configuration-level guarantees that have no natural home in a behavioural test.
 *
 * Ported from the masteranunturi security branch. Each of these was found switched off
 * during the assessment, and each is a single line away from being switched off again by
 * accident. The config files are read as text rather than required: they pull in
 * params-local.php, which is generated per environment and is not in the repository, so
 * requiring them would fatal. Asserting against a booted Yii::$app would be worse - the
 * test harness builds its own component set, so it would only confirm what the harness
 * configured.
 */
class SecurityConfigTest extends TestCase
{
	/**
	 * @param string $relative path from the repository root
	 * @return string
	 */
	private function source($relative)
	{
		// tests/unit -> tests -> master -> repository root
		$path = dirname(__DIR__, 3) . '/' . $relative;
		$this->assertFileExists($path);

		return file_get_contents($path);
	}

	/**
	 * The session-backed apps. The REST API is stateless and opts out deliberately.
	 *
	 * @return array
	 */
	public function webAppConfigProvider()
	{
		return [
			'master frontend' => ['master/frontend/config/main.php'],
			'master backend' => ['master/backend/config/main.php'],
			'workspace frontend' => ['workspace/frontend/config/main.php'],
			'workspace backend' => ['workspace/backend/config/main.php'],
			'documentation frontend' => ['documentation/frontend/config/main.php'],
			'documentation backend' => ['documentation/backend/config/main.php'],
		];
	}

	/**
	 * @dataProvider webAppConfigProvider
	 * @param string $path
	 */
	public function testCsrfValidationIsEnabled($path)
	{
		$source = $this->source($path);

		$this->assertStringContainsString(
			"'enableCsrfValidation' => true",
			$source,
			"{$path} must validate CSRF tokens; the embed widget opts out at the controller"
		);
		$this->assertStringNotContainsString(
			"'enableCsrfValidation' => false",
			$source,
			"{$path} still turns CSRF validation off somewhere"
		);
	}

	/**
	 * @dataProvider webAppConfigProvider
	 * @param string $path
	 */
	public function testTheBrowserCookiesAreHttpOnlyAndSameSite($path)
	{
		$source = $this->source($path);

		// One for the session, one for the identity cookie, one for the CSRF cookie.
		$this->assertSame(3, substr_count($source, "'httpOnly' => true"),
			"{$path} must mark all three browser cookies httpOnly");
		$this->assertSame(3, substr_count($source, "'sameSite' => 'Lax'"),
			"{$path} must set sameSite on all three browser cookies");
		$this->assertSame(3, substr_count($source, "'secure' => YII_ENV_PROD"),
			"{$path} must mark all three browser cookies secure wherever TLS is in front");
	}

	/**
	 * @dataProvider webAppConfigProvider
	 * @param string $path
	 */
	public function testTheCookiesAreNotHardWiredSecure($path)
	{
		// Dev runs without a trusted certificate; a flat `'secure' => true` would have
		// the browser drop the session cookie and log everyone out on every request.
		$this->assertStringNotContainsString("'secure' => true", $this->source($path));
	}

	/**
	 * The headers live in the application rather than in .htaccess because the embed
	 * widget must stay framable while every other route must not, and only the
	 * application knows which route it is serving.
	 */
	public function testTheSecurityHeadersComponentIsWired()
	{
		foreach (['master', 'workspace', 'documentation'] as $app) {
			$this->assertStringContainsString(
				"'common\\components\\SecurityHeaders'",
				$this->source("{$app}/common/config/main.php"),
				"{$app} must bootstrap the security headers"
			);
		}

		$component = $this->source('master/common/components/SecurityHeaders.php');
		foreach ([
			'Strict-Transport-Security',
			'X-Frame-Options',
			'X-Content-Type-Options',
			'Referrer-Policy',
			'frame-ancestors',
		] as $header) {
			$this->assertStringContainsString($header, $component, "{$header} must be sent");
		}

		// The widget is the product; denying framing would break every customer site.
		$this->assertStringContainsString('framableRoutes', $component);
	}

	/**
	 * The frontend and the admin share BOTAISESSID at path /, so whichever answers last
	 * writes the cookie. Split attributes would make the flags depend on the route.
	 */
	public function testBothMasterAppsNameTheSameSessionCookie()
	{
		$this->assertStringContainsString(
			"'name' => 'BOTAISESSID'",
			$this->source('master/frontend/config/main.php')
		);
		$this->assertStringContainsString(
			"'name' => 'BOTAISESSID'",
			$this->source('master/backend/config/main.php'),
			'the cookie attribute assertions above only mean anything while both apps share it'
		);
	}

	/**
	 * @return array
	 */
	public function uploadsHtaccessProvider()
	{
		return [
			'master' => ['master/uploads/.htaccess'],
			'workspace' => ['workspace/uploads/.htaccess'],
			'documentation' => ['documentation/uploads/.htaccess'],
			'tenant template' => ['workspace/install/dir/uploads/.htaccess'],
		];
	}

	/**
	 * @dataProvider uploadsHtaccessProvider
	 * @param string $path
	 */
	public function testTheUploadsDirectoryRefusesToExecuteAnything($path)
	{
		$htaccess = $this->source($path);

		$this->assertStringContainsString('php_flag engine off', $htaccess);
		$this->assertStringContainsString('SetHandler default-handler', $htaccess);
		$this->assertMatchesRegularExpression(
			'/RemoveHandler[^\n]*\.php\b/',
			$htaccess,
			'the PHP handlers must be unbound in the uploads tree'
		);
		// Serving is default-deny, so a type that is not on the allow-list cannot be
		// fetched at all even if some future handler would run it.
		$this->assertMatchesRegularExpression(
			'/Require all denied|Order allow,deny/',
			$htaccess,
			'the uploads tree must deny by default and allow only known media types'
		);
	}

	/**
	 * The uploads guard is worthless if it never reaches a deployment.
	 */
	public function testTheUploadsGuardIsTracked()
	{
		foreach (['master', 'workspace', 'documentation'] as $app) {
			$gitignore = $this->source("{$app}/.gitignore");

			$this->assertStringContainsString('!/uploads/.htaccess', $gitignore,
				"{$app}/.gitignore must keep the uploads guard tracked");
			$this->assertStringNotContainsString("\n/uploads\n", $gitignore,
				"{$app}/.gitignore must not exclude the uploads directory wholesale");
		}
	}
}
