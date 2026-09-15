<?php

namespace tests\unit;

use common\models\Country;
use tests\DatabaseTestCase;

/**
 * Database round-trip coverage for the `country` nomenclature: proves the test
 * database, the rolled-back transaction and insertRow()'s NOT NULL auto-fill work
 * end to end on an INT AUTO_INCREMENT table, and that the model finds and saves
 * what the raw insert wrote.
 */
class CountryPersistenceTest extends DatabaseTestCase
{
	/**
	 * insertRow() returns the auto-increment id and fills the NOT NULL columns the
	 * fixture left out (iso codes, names, continent, status), respecting CHAR(2)/CHAR(3)
	 * sizes under strict mode.
	 */
	public function testInsertRowReturnsAutoIncrementIdAndModelFindsIt()
	{
		$id = $this->insertRow('country', [
			'iso_alpha2' => 'RO',
			'iso_alpha3' => 'ROU',
			'name' => 'Romania',
			'status' => Country::STATUS_ACTIVE,
		]);

		$this->assertIsInt($id);
		$this->assertGreaterThan(0, $id);

		$country = Country::findOne($id);
		$this->assertNotNull($country);
		$this->assertSame('RO', $country->iso_alpha2);
		$this->assertSame('ROU', $country->iso_alpha3);
		$this->assertSame('Romania', $country->name);
		$this->assertSame(Country::STATUS_ACTIVE, (int) $country->status);
		$this->assertSame(Country::NO, (int) $country->deleted);
		// auto-filled NOT NULL columns without defaults
		$this->assertSame(3, strlen($country->iso_numeric));
		$this->assertSame(2, strlen($country->continent_code));
		$this->assertNotSame('', $country->full_name);
	}

	/**
	 * The active() scope and the model's own save() path work against the test
	 * schema: a row saved through validation is found by the scope, an inactive one
	 * is not.
	 */
	public function testModelSaveAndActiveScope()
	{
		$model = new Country([
			'iso_alpha2' => 'MD',
			'iso_alpha3' => 'MDA',
			'iso_numeric' => '498',
			'name' => 'Moldova',
			'full_name' => 'Republic of Moldova',
			'continent_code' => 'EU',
			'status' => Country::STATUS_ACTIVE,
		]);
		$this->assertTrue($model->save(), print_r($model->getErrors(), true));

		$inactiveId = $this->insertRow('country', ['iso_alpha2' => 'XX', 'status' => Country::STATUS_INACTIVE]);

		$activeIds = Country::find()->active()->select('id')->column();
		$this->assertContains((string) $model->id, array_map('strval', $activeIds));
		$this->assertNotContains((string) $inactiveId, array_map('strval', $activeIds));
	}
}
