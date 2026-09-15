<?php

namespace tests\unit;

use common\models\ScheduledTask;
use tests\DatabaseTestCase;

/**
 * Database round-trip coverage for the tenant `scheduled_task` table: proves the
 * workspace test database (provisioned from install/db/_01_structure.sql), the
 * rolled-back transaction and insertRow() work end to end, and that the model's
 * enable()/disable() and due-date helpers behave on a persisted row.
 */
class ScheduledTaskPersistenceTest extends DatabaseTestCase
{
	/**
	 * insertRow() returns the auto-increment id, fills the NOT NULL `status`, and the
	 * model reads back exactly what was written.
	 */
	public function testInsertRowAndFind()
	{
		$id = $this->insertRow('scheduled_task', [
			'cron_expression' => '0 3 * * *',
			'app_command' => 'backup/run',
			'application' => 'console',
			'type' => ScheduledTask::TYPE_APP,
			'status' => ScheduledTask::STATUS_ACTIVE,
		]);

		$this->assertIsInt($id);
		$task = ScheduledTask::findOne($id);
		$this->assertNotNull($task);
		$this->assertSame('0 3 * * *', $task->cron_expression);
		$this->assertSame('backup/run', $task->app_command);
		$this->assertSame(ScheduledTask::TYPE_APP, (int) $task->type);
		$this->assertSame(ScheduledTask::STATUS_ACTIVE, (int) $task->status);
	}

	/**
	 * disable()/enable() persist the status through save(); the Blameable and
	 * Timestamp behaviors must cope with the console app having no `user`
	 * component (created_by stays null, updated_at is stamped).
	 */
	public function testDisableAndEnablePersistStatus()
	{
		$id = $this->insertRow('scheduled_task', [
			'cron_expression' => '*/5 * * * *',
			'status' => ScheduledTask::STATUS_ACTIVE,
		]);
		$task = ScheduledTask::findOne($id);

		$this->assertTrue($task->disable(), print_r($task->getErrors(), true));
		$this->assertSame(ScheduledTask::STATUS_INACTIVE, (int) $this->fetchColumn('scheduled_task', $id, 'status'));
		$this->assertNull($this->fetchColumn('scheduled_task', $id, 'updated_by'));
		$this->assertNotNull($this->fetchColumn('scheduled_task', $id, 'updated_at'));

		$this->assertTrue($task->enable());
		$this->assertSame(ScheduledTask::STATUS_ACTIVE, (int) $this->fetchColumn('scheduled_task', $id, 'status'));
	}

	/**
	 * The stored cron expression drives the next due date: a "03:00 daily" task
	 * evaluated from 10:00 is due at 03:00 the following day.
	 */
	public function testNextDueDateFollowsTheStoredExpression()
	{
		$id = $this->insertRow('scheduled_task', [
			'cron_expression' => '0 3 * * *',
			'status' => ScheduledTask::STATUS_ACTIVE,
		]);
		$task = ScheduledTask::findOne($id);

		$next = $task->getNextDueDate(1, '2026-01-10 10:00:00');
		$this->assertInstanceOf(\DateTimeInterface::class, $next);
		$this->assertSame('2026-01-11 03:00:00', $next->format('Y-m-d H:i:s'));

		$multiple = $task->getNextDueDate(2, '2026-01-10 10:00:00');
		$this->assertCount(2, $multiple);
		$this->assertSame('2026-01-12 03:00:00', $multiple[1]->format('Y-m-d H:i:s'));
	}
}
