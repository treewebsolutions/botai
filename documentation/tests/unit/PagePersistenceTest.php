<?php

namespace tests\unit;

use common\models\Page;
use common\models\User;
use tests\DatabaseTestCase;

/**
 * Database round-trip coverage for the documentation `page` tree and its authors:
 * proves the test database (provisioned from tests/_schema.sql), the rolled-back
 * transaction and insertRow() work end to end, and that the model reads the raw
 * rows back — including the self-referencing parent relation and the creator
 * relation onto `user`.
 */
class PagePersistenceTest extends DatabaseTestCase
{
	/**
	 * insertRow() returns the auto-increment id; a child inserted with parent_id
	 * resolves its parent through the model relation.
	 */
	public function testInsertRowAndParentRelation()
	{
		$parentId = $this->insertRow('page', [
			'controller' => 'site',
			'action' => 'index',
			'status' => Page::STATUS_ACTIVE,
		]);
		$childId = $this->insertRow('page', [
			'parent_id' => $parentId,
			'controller' => 'site',
			'action' => 'about',
			'status' => Page::STATUS_ACTIVE,
		]);

		$this->assertIsInt($parentId);
		$this->assertIsInt($childId);

		$child = Page::findOne($childId);
		$this->assertNotNull($child);
		$this->assertSame('about', $child->action);
		$this->assertSame($parentId, (int) $child->parent_id);
		$this->assertNotNull($child->parent);
		$this->assertSame('index', $child->parent->action);
		$this->assertSame(Page::NO, (int) $child->deleted);
	}

	/**
	 * The `user` fixture only needs what the scenario cares about: the NOT NULL
	 * first_name/last_name/status are auto-filled and the creator relation resolves.
	 */
	public function testCreatorRelationOntoUser()
	{
		$userId = $this->insertRow('user', [
			'username' => 'author',
			'email' => 'author@test.local',
			'status' => User::STATUS_ACTIVE,
		]);
		$pageId = $this->insertRow('page', [
			'controller' => 'site',
			'action' => 'index',
			'created_by' => $userId,
			'status' => Page::STATUS_ACTIVE,
		]);

		$page = Page::findOne($pageId);
		$this->assertNotNull($page->creator);
		$this->assertSame('author', $page->creator->username);
		$this->assertSame('author@test.local', $page->creator->email);
		$this->assertNotSame('', $page->creator->first_name);
	}

	/**
	 * A page saved through the model (validation + Timestamp/Blameable behaviors, no
	 * `user` component in the console app) lands in the table and is seen by the
	 * active() scope; the raw insert of an inactive page is not.
	 */
	public function testModelSaveAndActiveScope()
	{
		$page = new Page([
			'controller' => 'site',
			'action' => 'contact',
			'status' => Page::STATUS_ACTIVE,
		]);
		$this->assertTrue($page->save(), print_r($page->getErrors(), true));
		$this->assertNotNull($this->fetchColumn('page', $page->id, 'created_at'));
		$this->assertNull($this->fetchColumn('page', $page->id, 'created_by'));

		$inactiveId = $this->insertRow('page', ['controller' => 'site', 'action' => 'hidden', 'status' => Page::STATUS_INACTIVE]);

		$activeIds = array_map('intval', Page::find()->active()->select('id')->column());
		$this->assertContains((int) $page->id, $activeIds);
		$this->assertNotContains($inactiveId, $activeIds);
	}
}
