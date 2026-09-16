<?php

namespace tests\unit;

use common\models\WorkspaceVectorStore;
use tests\DatabaseTestCase;
use Yii;

/**
 * The hub's record of what stands on its OpenAI key.
 *
 * Tenants provision vector stores with the platform's key, so they are all billed to one
 * account. Uninstalling a workspace does not touch OpenAI, and once the tenant database is
 * gone so is the id that named the store - it would stay on the account, billed, with
 * nothing in the system able to find it.
 */
class WorkspaceVectorStoreTest extends DatabaseTestCase
{
	/**
	 * @param array $attributes
	 * @return int
	 */
	private function store(array $attributes = [])
	{
		$row = new WorkspaceVectorStore(array_merge([
			'workspace_id' => null,
			'workspace_code' => 'code',
			'vector_store_id' => 'vs_' . uniqid('', true),
			'name' => 'Website',
			'created_at' => date('Y-m-d H:i:s'),
		], $attributes));

		$this->assertTrue($row->save(), json_encode($row->getErrors()));

		return (int) $row->id;
	}

	/**
	 * @param array $attributes
	 * @return int
	 */
	private function workspace(array $attributes = [])
	{
		Yii::$app->db->createCommand()->insert('{{%workspace}}', array_merge([
			'code' => substr(md5(uniqid('', true)), 0, 8),
			'url' => 'w' . substr(md5(uniqid('', true)), 0, 6),
			'type' => 1,
			'status' => 1,
			'deleted' => 0,
			'created_at' => '2026-09-01 00:00:00',
			'updated_at' => '2026-09-01 00:00:00',
		], $attributes))->execute();

		return (int) Yii::$app->db->getLastInsertID();
	}

	/**
	 * A store whose workspace is still there is in use, not waste.
	 */
	public function testAStoreOfALiveWorkspaceIsNotOrphaned()
	{
		$this->store(['workspace_id' => $this->workspace()]);

		$this->assertSame(0, (int) WorkspaceVectorStore::findOrphans()->count());
	}

	/**
	 * A workspace that was deleted, and one whose row is gone entirely, both leave a store
	 * nothing will ever use again.
	 */
	public function testStoresWithoutALiveWorkspaceAreOrphaned()
	{
		$softDeleted = $this->store(['workspace_id' => $this->workspace(['deleted' => 1])]);
		$vanished = $this->store(['workspace_id' => 999999]);
		$this->store(['workspace_id' => $this->workspace()]);

		$orphans = WorkspaceVectorStore::findOrphans()->all();
		$ids = array_map(static function ($row) {
			return (int) $row->id;
		}, $orphans);

		sort($ids);
		$expected = [$softDeleted, $vanished];
		sort($expected);
		$this->assertSame($expected, $ids);
	}

	/**
	 * Once removed from OpenAI the row stays, marked: what was on the account and when it
	 * left is the history this table keeps.
	 */
	public function testARemovedStoreIsNoLongerOffered()
	{
		$this->store(['workspace_id' => 999999, 'removed_at' => date('Y-m-d H:i:s')]);

		$this->assertSame(0, (int) WorkspaceVectorStore::findOrphans()->count());
		$this->assertSame(1, (int) WorkspaceVectorStore::find()->count(), 'the record is kept');
	}

	/**
	 * One store, one row - a tenant that provisions repeatedly must not multiply it.
	 */
	public function testTheStoreIdIsUnique()
	{
		$id = 'vs_' . uniqid('', true);
		$this->store(['vector_store_id' => $id]);

		$duplicate = new WorkspaceVectorStore([
			'vector_store_id' => $id,
			'created_at' => date('Y-m-d H:i:s'),
		]);

		$this->assertFalse($duplicate->save());
		$this->assertArrayHasKey('vector_store_id', $duplicate->getErrors());
	}
}
