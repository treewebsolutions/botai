<?php

namespace tests\unit;

use common\models\Workspace;
use PHPUnit\Framework\TestCase;
use Yii;

/**
 * Whether a (re)install is allowed to create and seed the tenant database.
 *
 * Reinstall must never import the install structure over a live tenant - that is what it
 * was stopped from doing - but a workspace whose database was never created has to be able
 * to get one, otherwise the reinstall button cannot repair it.
 *
 * The suite runs against `botai_master_test`, so a workspace whose code is `test` names
 * exactly that database (getWorkspaceDbName() swaps the last segment for the code) and
 * stands in for a tenant that already has tables.
 */
class WorkspaceDatabaseInstallTest extends TestCase
{
	/**
	 * @param Workspace $workspace
	 * @return bool
	 */
	private function needsInstall(Workspace $workspace)
	{
		$reflection = new \ReflectionMethod($workspace, 'workspaceDatabaseNeedsInstall');
		$reflection->setAccessible(true);

		return $reflection->invoke($workspace);
	}

	/**
	 * @param string $code
	 * @return Workspace
	 */
	private function installedWorkspace($code)
	{
		$workspace = new Workspace(['url' => 'demo', 'code' => $code]);
		// getWorkspaceDbName() answers null for a new record; the question only arises
		// for a row that has been saved.
		$workspace->setIsNewRecord(false);

		return $workspace;
	}

	/**
	 * A database that is not there is the whole point: say so, so install() creates it.
	 */
	public function testAMissingDatabaseNeedsInstalling()
	{
		$workspace = $this->installedWorkspace('nosuchdatabase');
		$this->assertStringEndsWith('nosuchdatabase', (string) $workspace->getWorkspaceDbName());
		$this->assertTrue($this->needsInstall($workspace));
	}

	/**
	 * A tenant that already has tables is left alone, so a reinstall cannot write the
	 * install structure over its conversations and messages.
	 */
	public function testADatabaseWithTablesIsLeftAlone()
	{
		$dbName = Yii::$app->db->createCommand('SELECT DATABASE()')->queryScalar();
		$tables = (int) Yii::$app->db->createCommand(
			'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :name',
			[':name' => $dbName]
		)->queryScalar();
		$this->assertGreaterThan(0, $tables, 'the test database must be provisioned: tools/load-test-schema.sh master');

		// The last segment of botai_master_test is swapped for the code, naming it again.
		$workspace = $this->installedWorkspace('test');
		$this->assertSame($dbName, $workspace->getWorkspaceDbName());
		$this->assertFalse($this->needsInstall($workspace));
	}

	/**
	 * An empty database counts as missing: it is as unusable as no database at all and
	 * holds nothing that an import could destroy.
	 */
	public function testAnEmptyDatabaseNeedsInstalling()
	{
		$db = Yii::$app->db;
		$workspace = $this->installedWorkspace('emptyprobe');
		$dbName = $workspace->getWorkspaceDbName();

		$db->createCommand("CREATE DATABASE IF NOT EXISTS `{$dbName}`")->execute();
		try {
			$this->assertTrue($this->needsInstall($workspace), 'no tables means nothing to lose');
		} finally {
			$db->createCommand("DROP DATABASE IF EXISTS `{$dbName}`")->execute();
		}
	}
}
