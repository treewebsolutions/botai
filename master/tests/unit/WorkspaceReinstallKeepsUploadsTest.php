<?php

namespace tests\unit;

use common\models\Workspace;
use PHPUnit\Framework\TestCase;
use yii\helpers\FileHelper;

/**
 * A reinstall rebuilds the tenant directory, and used to do it by deleting the whole tree
 * first - taking every file the tenant had uploaded with it. The database was already
 * spared for the same reason; the filesystem was not.
 */
class WorkspaceReinstallKeepsUploadsTest extends TestCase
{
	/** @var string */
	private $dir;

	protected function setUp(): void
	{
		$this->dir = sys_get_temp_dir() . '/ws-' . uniqid('', true);
		FileHelper::createDirectory($this->dir . '/uploads/subdir', 0777, true);
		FileHelper::createDirectory($this->dir . '/frontend/web', 0777, true);
		FileHelper::createDirectory($this->dir . '/common/config', 0777, true);

		file_put_contents($this->dir . '/uploads/logo.png', 'logo');
		file_put_contents($this->dir . '/uploads/subdir/document.pdf', 'pdf');
		file_put_contents($this->dir . '/uploads/.htaccess', 'deny');
		file_put_contents($this->dir . '/frontend/web/index.php', '<?php');
		file_put_contents($this->dir . '/common/config/main.php', '<?php');
		file_put_contents($this->dir . '/yii', '#!/usr/bin/env php');
		file_put_contents($this->dir . '/.htaccess', 'rewrite');
	}

	protected function tearDown(): void
	{
		if (is_dir($this->dir)) {
			FileHelper::removeDirectory($this->dir);
		}
	}

	/**
	 * @param string[] $keep
	 */
	private function removeExcept(array $keep)
	{
		$method = new \ReflectionMethod(Workspace::class, 'removeDirectoryExcept');
		$method->setAccessible(true);
		$method->invoke(new Workspace(), $this->dir, $keep);
	}

	/**
	 * What the tenant uploaded survives, down to the nested files and the .htaccess that
	 * keeps the directory inert; everything the installer lays down is cleared for the
	 * rebuild, dotfiles included.
	 */
	public function testUploadsSurviveWhileTheRestIsCleared()
	{
		$this->removeExcept(Workspace::TENANT_OWNED_DIRECTORIES);

		$this->assertSame('logo', file_get_contents($this->dir . '/uploads/logo.png'));
		$this->assertSame('pdf', file_get_contents($this->dir . '/uploads/subdir/document.pdf'));
		$this->assertFileExists($this->dir . '/uploads/.htaccess');

		$this->assertDirectoryDoesNotExist($this->dir . '/frontend');
		$this->assertDirectoryDoesNotExist($this->dir . '/common');
		$this->assertFileDoesNotExist($this->dir . '/yii');
		$this->assertFileDoesNotExist($this->dir . '/.htaccess', 'a dotfile the installer owns');
	}

	/**
	 * Keeping nothing empties the directory: a permanent removal still means permanent.
	 */
	public function testKeepingNothingEmptiesTheDirectory()
	{
		$this->removeExcept([]);

		$this->assertDirectoryExists($this->dir);
		$this->assertSame(['.', '..'], scandir($this->dir));
	}
}
