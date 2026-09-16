<?php

namespace tests\unit;

use common\models\Workspace;
use PHPUnit\Framework\TestCase;
use Yii;

/**
 * The cPanel-side naming derived from a workspace row (no database, no cPanel): the
 * addon domain document root and subdomain label the installer sends to
 * AddonDomain::addaddondomain, and the install-environment switch when no cPanel
 * component exists (the test app has none, like a dev box with placeholders).
 */
class WorkspaceCpanelTest extends TestCase
{
	/**
	 * @param Workspace $workspace
	 * @param string $method
	 * @return mixed
	 */
	private function callProtected(Workspace $workspace, $method)
	{
		$reflection = new \ReflectionMethod($workspace, $method);
		$reflection->setAccessible(true);

		return $reflection->invoke($workspace);
	}

	/**
	 * The document root is the tenant directory under public_html, keyed the same way
	 * as the local install (bare host, no www., lowercase); the subdomain is the label
	 * before the first dot. A workspace without domain/url has no document root at all.
	 */
	public function testCpanelPathsFollowTheDirectoryName()
	{
		$workspace = new Workspace(['url' => 'primadentalclinic', 'domain' => 'https://www.PrimaDentalClinic.ro/']);
		$this->assertSame('public_html/workspaces/primadentalclinic.ro', $this->callProtected($workspace, 'getCpanelDirectoryPath'));
		$this->assertSame('primadentalclinic', $this->callProtected($workspace, 'getCpanelSubdomain'));

		$demo = new Workspace(['url' => 'demo']);
		$this->assertSame('public_html/workspaces/demo', $this->callProtected($demo, 'getCpanelDirectoryPath'));
		$this->assertSame('demo', $this->callProtected($demo, 'getCpanelSubdomain'));

		$empty = new Workspace();
		$this->assertSame('', $this->callProtected($empty, 'getCpanelDirectoryPath'));
	}

	/**
	 * Without a usable cPanel component the installer must stay on the local path
	 * (raw SQL, root .htaccess, no addon domain) even outside YII_ENV_DEV.
	 */
	public function testNoCpanelComponentMeansLocalInstall()
	{
		$workspace = new Workspace(['url' => 'demo']);
		$this->assertFalse($workspace->isCPanelConfigured());
		$this->assertTrue($workspace->isLocalInstallEnvironment());
		$this->assertTrue($this->callProtected($workspace, 'ensureCpanelAddonDomain'), 'a no-op locally');
	}

	/**
	 * Credentials decide, not the environment name.
	 *
	 * A production deployment left on the dev entry script used to take the local path
	 * before reading the cPanel settings at all, so it ran CREATE DATABASE as the tenant
	 * MySQL user and was refused by a host that never grants it.
	 */
	public function testConfiguredCredentialsMeanCpanelInstall()
	{
		$workspace = new Workspace(['url' => 'demo']);

		Yii::$app->set('cPanel', [
			'class' => \common\components\CPanel::class,
			'baseUrl' => 'https://example.test:2083',
			'username' => 'account',
			'apiToken' => 'token',
		]);

		try {
			$this->assertTrue($workspace->isCPanelConfigured());
			$this->assertFalse($workspace->isLocalInstallEnvironment(), 'the API path, whatever YII_ENV says');
		} finally {
			Yii::$app->clear('cPanel');
		}
	}

	/**
	 * The placeholders of an environment file that was never filled in are not
	 * credentials: the component refuses to initialise on them, and the installer has to
	 * read that as "install locally" rather than as an error.
	 */
	public function testPlaceholderCredentialsStayLocal()
	{
		$workspace = new Workspace(['url' => 'demo']);

		Yii::$app->set('cPanel', [
			'class' => \common\components\CPanel::class,
			'baseUrl' => 'CPANEL_BASE_URL',
			'username' => 'CPANEL_USERNAME',
			'password' => 'CPANEL_PASSWORD',
		]);

		try {
			$this->assertFalse($workspace->isCPanelConfigured());
			$this->assertTrue($workspace->isLocalInstallEnvironment());
		} finally {
			Yii::$app->clear('cPanel');
		}
	}
}
