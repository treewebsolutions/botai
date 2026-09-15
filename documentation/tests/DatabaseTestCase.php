<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use Yii;

/**
 * Base class for database-backed unit tests.
 *
 * Every test runs inside a transaction that is rolled back in tearDown, so tests are
 * isolated and the `botai_documentation_test` schema stays empty. Foreign key checks are
 * disabled inside the transaction, so fixtures only need the rows relevant to the
 * scenario.
 */
abstract class DatabaseTestCase extends TestCase
{
	/**
	 * @var \yii\db\Transaction
	 */
	private $transaction;

	protected function setUp(): void
	{
		parent::setUp();
		$this->transaction = Yii::$app->db->beginTransaction();
		Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 0')->execute();
	}

	protected function tearDown(): void
	{
		if ($this->transaction) {
			$this->transaction->rollBack();
		}
		Yii::$app->db->createCommand('SET FOREIGN_KEY_CHECKS = 1')->execute();
		parent::tearDown();
	}

	/**
	 * Inserts a row filling every NOT NULL column without a default with a
	 * type-appropriate dummy value, so fixtures only specify what the scenario needs.
	 *
	 * @param string $table
	 * @param array $attributes
	 * @return int|string|null the new primary key: the auto-increment value for
	 * INT AUTO_INCREMENT tables, the supplied value for natural keys, null otherwise
	 */
	protected function insertRow($table, array $attributes = [])
	{
		$schema = Yii::$app->db->getTableSchema($table, true);
		if ($schema === null) {
			throw new \InvalidArgumentException("Unknown table '$table' in the test database.");
		}
		$row = $attributes;

		foreach ($schema->columns as $name => $column) {
			if (array_key_exists($name, $row) || $column->allowNull || $column->defaultValue !== null || $column->autoIncrement) {
				continue;
			}
			if (in_array($column->type, ['integer', 'smallint', 'bigint', 'tinyint', 'boolean'], true)) {
				$row[$name] = 0;
			} elseif (in_array($column->type, ['decimal', 'float', 'double', 'money'], true)) {
				$row[$name] = 0;
			} elseif (in_array($column->type, ['datetime', 'timestamp'], true)) {
				$row[$name] = date('Y-m-d H:i:s');
			} elseif ($column->type === 'date') {
				$row[$name] = date('Y-m-d');
			} elseif ($column->type === 'time') {
				$row[$name] = date('H:i:s');
			} elseif (!empty($column->enumValues)) {
				$row[$name] = $column->enumValues[0];
			} else {
				// CHAR(2)/CHAR(3) codes and the like are common in the nomenclature tables;
				// strict mode rejects values longer than the column, so respect the size.
				$value = 'test-' . substr(bin2hex(random_bytes(6)), 0, 8);
				$row[$name] = $column->size ? substr($value, 0, $column->size) : $value;
			}
		}

		Yii::$app->db->createCommand()->insert($table, $row)->execute();

		if (count($schema->primaryKey) === 1) {
			$pk = $schema->primaryKey[0];
			if (array_key_exists($pk, $row)) {
				return $row[$pk];
			}
			if ($schema->columns[$pk]->autoIncrement) {
				return (int) Yii::$app->db->getLastInsertID();
			}
		}

		return null;
	}

	/**
	 * Reads one column of a row by primary key.
	 *
	 * @param string $table
	 * @param int|string $id
	 * @param string $column
	 * @param string $pk primary key column name
	 * @return mixed
	 */
	protected function fetchColumn($table, $id, $column, $pk = 'id')
	{
		return (new \yii\db\Query())
			->select($column)
			->from($table)
			->where([$pk => $id])
			->scalar(Yii::$app->db);
	}
}
