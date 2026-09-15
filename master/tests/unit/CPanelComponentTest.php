<?php

namespace tests\unit;

use common\components\CPanel;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;

/**
 * The cPanel API client the workspace installer talks through (ported from
 * masteranunturi): credential validation, placeholder detection that keeps a dev box on
 * the raw-SQL install path, and the Authorization header (API token vs Basic auth).
 * No request is made.
 */
class CPanelComponentTest extends TestCase
{
	/**
	 * The dev environment file ships CPANEL_* placeholders; instantiating the component
	 * with them must throw, which is what makes Workspace::isCPanelConfigured() false.
	 */
	public function testPlaceholderBaseUrlIsRejected()
	{
		$this->expectException(InvalidConfigException::class);
		new CPanel([
			'baseUrl' => 'CPANEL_BASE_URL',
			'username' => 'CPANEL_USERNAME',
			'password' => 'CPANEL_PASSWORD',
		]);
	}

	/**
	 * An account with two-factor authentication has no usable password: a token alone
	 * must be enough, and a component with neither is a configuration error.
	 */
	public function testTokenOrPasswordIsRequired()
	{
		$cpanel = new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'acct', 'apiToken' => 'tok']);
		$this->assertSame('cpanel acct:tok', $cpanel->getAuthorizationHeader());

		$cpanel = new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'acct', 'password' => 'secret']);
		$this->assertSame('Basic ' . base64_encode('acct:secret'), $cpanel->getAuthorizationHeader());

		// The token wins over the password when both are set (keeps the password off the wire).
		$cpanel = new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'acct', 'password' => 'secret', 'apiToken' => 'tok']);
		$this->assertSame('cpanel acct:tok', $cpanel->getAuthorizationHeader());

		$this->expectException(InvalidConfigException::class);
		new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'acct']);
	}

	/**
	 * isConfigured() is what the installer consults before touching cPanel: a valid URL
	 * with a CPANEL_* placeholder anywhere in the credentials still counts as unconfigured.
	 */
	public function testIsConfiguredDetectsPlaceholders()
	{
		$real = new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'acct', 'apiToken' => 'tok']);
		$this->assertTrue($real->isConfigured());

		$placeholder = new CPanel(['baseUrl' => 'https://host.example:2083', 'username' => 'CPANEL_USERNAME', 'password' => 'CPANEL_PASSWORD']);
		$this->assertFalse($placeholder->isConfigured());
	}
}
