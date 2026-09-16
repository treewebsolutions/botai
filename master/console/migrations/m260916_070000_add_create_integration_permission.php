<?php

use yii\db\Migration;

/**
 * Grants superAdmin the permission behind the new "create integration" screen.
 *
 * The hub's integration module could list, view, update and delete, but never create -
 * there was no action and no permission for it. Adding an OpenAI key on the hub, which
 * every tenant without one of its own now falls back to, needs both.
 *
 * The seed dump in _database/ is not in version control (it can carry credentials), so
 * this is the only place a fresh installation picks the permission up.
 */
class m260916_070000_add_create_integration_permission extends Migration
{
	const PERMISSION = 'createIntegration';
	const ROLE = 'superAdmin';

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$now = time();

		// Idempotent: an installation whose dump already carries the row must not fail.
		$this->execute(
			'INSERT IGNORE INTO {{%auth_item}} ([[name]], [[type]], [[created_at]], [[updated_at]]) VALUES (:name, 2, :now, :now)',
			[':name' => self::PERMISSION, ':now' => $now]
		);

		// Only if the role exists: a workspace-flavoured database may not have it.
		$roleExists = (new \yii\db\Query())
			->from('{{%auth_item}}')
			->where(['name' => self::ROLE])
			->exists($this->db);

		if ($roleExists) {
			$this->execute(
				'INSERT IGNORE INTO {{%auth_item_child}} ([[parent]], [[child]]) VALUES (:parent, :child)',
				[':parent' => self::ROLE, ':child' => self::PERMISSION]
			);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		$this->delete('{{%auth_item_child}}', ['child' => self::PERMISSION]);
		$this->delete('{{%auth_assignment}}', ['item_name' => self::PERMISSION]);
		$this->delete('{{%auth_item}}', ['name' => self::PERMISSION]);
	}
}
