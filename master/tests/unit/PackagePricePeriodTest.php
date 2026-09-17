<?php

namespace tests\unit;

use common\models\Package;
use common\models\ScheduledTask;
use tests\DatabaseTestCase;

/**
 * The billing period shown after the price on the public package cards.
 */
class PackagePricePeriodTest extends DatabaseTestCase
{
	/**
	 * @param int|null $period
	 * @param string|null $cycle
	 * @return string
	 */
	private function suffixFor($period, $cycle)
	{
		$package = new Package();
		$package->billing_period = $period;
		$package->billing_cycle = $cycle;

		return $package->getFormattedPricePeriod();
	}

	/**
	 * One cycle reads as the bare noun.
	 */
	public function testSingleCycleIsTheNounAlone()
	{
		$this->assertSame('/ year', $this->suffixFor(1, ScheduledTask::CYCLE_YEAR));
		$this->assertSame('/ month', $this->suffixFor(1, ScheduledTask::CYCLE_MONTH));
	}

	/**
	 * Several cycles carry the count and the plural.
	 */
	public function testSeveralCyclesCarryTheCount()
	{
		$this->assertSame('/ 3 months', $this->suffixFor(3, ScheduledTask::CYCLE_MONTH));
		$this->assertSame('/ 2 years', $this->suffixFor(2, ScheduledTask::CYCLE_YEAR));
	}

	/**
	 * A package with no billing of its own - a free one - shows its price alone, and an
	 * unknown cycle is left out rather than printed raw.
	 */
	public function testNothingToShowWithoutABillingCycle()
	{
		$this->assertSame('', $this->suffixFor(null, null));
		$this->assertSame('', $this->suffixFor(1, null));
		$this->assertSame('', $this->suffixFor(1, ScheduledTask::CYCLE_MINUTE));
	}
}
