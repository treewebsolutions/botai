<?php

namespace tests\unit;

use backend\modules\subscriber\models\WorkspaceForm;
use common\models\Workspace;
use PHPUnit\Framework\TestCase;

/**
 * The domain a workspace is reachable at, now that it can be typed in the master form:
 * how it is reduced to the tenant directory key, how a rename resolves the directory it
 * is moving away from, and what the form refuses to store.
 */
class WorkspaceDomainTest extends TestCase
{
	/**
	 * The column may hold a bare host or a full URL. Both reduce to the same lowercase
	 * host without the `www.` prefix, which is what names the directory.
	 */
	public function testNormalizeHostReducesAnythingToABareHost()
	{
		$this->assertSame('example.ro', Workspace::normalizeHost('example.ro'));
		$this->assertSame('example.ro', Workspace::normalizeHost('https://www.Example.ro/'));
		$this->assertSame('example.ro', Workspace::normalizeHost('http://example.ro/a/b?c=d'));
		$this->assertSame('example.ro', Workspace::normalizeHost('  WWW.EXAMPLE.RO  '));
		$this->assertSame('sub.example.ro', Workspace::normalizeHost('sub.example.ro'), 'only a leading www. is dropped');
		$this->assertSame('', Workspace::normalizeHost(''));
		$this->assertSame('', Workspace::normalizeHost(null));
	}

	/**
	 * The domain names the directory; the URL slug is only the fallback for a workspace
	 * that has none.
	 */
	public function testDomainOutranksUrlForTheDirectoryName()
	{
		$withDomain = new Workspace(['url' => 'primadentalclinic', 'domain' => 'https://www.primadentalclinic.ro/']);
		$this->assertSame('primadentalclinic.ro', $withDomain->getDirectoryName());

		$withoutDomain = new Workspace(['url' => 'Demo']);
		$this->assertSame('demo', $withoutDomain->getDirectoryName());

		$this->assertNull((new Workspace())->getDirectoryName());
	}

	/**
	 * A rename has to name the directory it is moving away from, and that directory was
	 * keyed by the values as they were before the edit.
	 *
	 * The empty string matters: a workspace that had no domain must resolve its old
	 * directory from the old slug. Passing null there would mean "unchanged" and read the
	 * new domain back, so the move would be skipped and the old directory orphaned.
	 */
	public function testRenameResolvesThePreviousDirectoryFromThePreviousValues()
	{
		// A domain was just added to a workspace that had none.
		$gainedDomain = new Workspace(['url' => 'demo', 'domain' => 'example.ro']);
		$this->assertSame('example.ro', $gainedDomain->getDirectoryName());
		$this->assertSame('demo', $gainedDomain->getDirectoryName('', 'demo'), 'the directory it moves away from');

		// The domain changed between two real names.
		$changedDomain = new Workspace(['url' => 'demo', 'domain' => 'new.ro']);
		$this->assertSame('new.ro', $changedDomain->getDirectoryName());
		$this->assertSame('old.ro', $changedDomain->getDirectoryName('old.ro', 'demo'));

		// Only the slug changed, and the domain still wins: the directory stays put.
		$slugOnly = new Workspace(['url' => 'new-slug', 'domain' => 'example.ro']);
		$this->assertSame('example.ro', $slugOnly->getDirectoryName());
		$this->assertSame('example.ro', $slugOnly->getDirectoryName(null, 'old-slug'), 'nothing to move');
	}

	/**
	 * The directory is named after the domain and on cPanel it becomes the addon domain,
	 * which rejects a name without a TLD label - so the form rejects it first. An empty
	 * domain stays valid: locally a workspace is served as a path under the master host.
	 */
	public function testDomainMustBeAFullDomainNameOrNothing()
	{
		foreach (['example.ro', 'https://www.example.ro/', 'sub.example.co.uk', '', null] as $accepted) {
			$form = new WorkspaceForm(['domain' => $accepted]);
			$form->validate(['domain']);
			$this->assertFalse($form->hasErrors('domain'), var_export($accepted, true) . ' should be accepted');
		}

		foreach (['localhost', 'example', 'http://localhost:8080/', 'nu are spatii.ro'] as $rejected) {
			$form = new WorkspaceForm(['domain' => $rejected]);
			$form->validate(['domain']);
			$this->assertTrue($form->hasErrors('domain'), var_export($rejected, true) . ' should be rejected');
		}
	}
}
