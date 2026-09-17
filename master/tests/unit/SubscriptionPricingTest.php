<?php

namespace tests\unit;

use backend\modules\subscriber\models\SubscriptionForm;
use common\models\Package;
use common\models\ScheduledTask;
use tests\DatabaseTestCase;

/**
 * The price a subscription created in the backend inherits from its package.
 *
 * A package price covers one of the package's own billing cycles, so the subscription
 * price is that price scaled by how many of those cycles the chosen billing period
 * covers - not the package price taken as a monthly rate.
 */
class SubscriptionPricingTest extends DatabaseTestCase
{
	/**
	 * The price SubscriptionForm would save for a package billed at $packagePrice every
	 * $packagePeriod $packageCycle, when the subscription is billed every $period $cycle.
	 *
	 * @param float $packagePrice
	 * @param int $packagePeriod
	 * @param string $packageCycle
	 * @param int $period
	 * @param string $cycle
	 * @return float
	 */
	private function priceFor($packagePrice, $packagePeriod, $packageCycle, $period, $cycle)
	{
		$package = new Package();
		$package->price = $packagePrice;
		$package->billing_period = $packagePeriod;
		$package->billing_cycle = $packageCycle;

		$subscription = new SubscriptionForm();
		$subscription->populateRelation('package', $package);
		$subscription->billing_period = $period;
		$subscription->billing_cycle = $cycle;

		$method = new \ReflectionMethod($subscription, 'getScaledPackagePrice');
		$method->setAccessible(true);

		return $method->invoke($subscription);
	}

	/**
	 * The regression: a package that already costs 900 a year stays at 900 on a yearly
	 * subscription. It used to be multiplied by twelve on top of its own cycle.
	 */
	public function testYearlyPackageKeepsItsPriceOnAYearlySubscription()
	{
		$this->assertSame(900.0, $this->priceFor(900, 1, ScheduledTask::CYCLE_YEAR, 1, ScheduledTask::CYCLE_YEAR));
	}

	/**
	 * A monthly package billed for a year costs twelve months.
	 */
	public function testMonthlyPackageBilledYearlyCostsTwelveMonths()
	{
		$this->assertSame(5999.88, round($this->priceFor(499.99, 1, ScheduledTask::CYCLE_MONTH, 1, ScheduledTask::CYCLE_YEAR), 2));
	}

	/**
	 * The other direction: a yearly package billed monthly costs a twelfth.
	 */
	public function testYearlyPackageBilledMonthlyCostsATwelfth()
	{
		$this->assertSame(75.0, $this->priceFor(900, 1, ScheduledTask::CYCLE_YEAR, 1, ScheduledTask::CYCLE_MONTH));
	}

	/**
	 * Billing several of the package's own cycles at once multiplies by the period.
	 */
	public function testSeveralPeriodsMultiplyThePrice()
	{
		$this->assertSame(300.0, $this->priceFor(100, 1, ScheduledTask::CYCLE_MONTH, 3, ScheduledTask::CYCLE_MONTH));
		$this->assertSame(1800.0, $this->priceFor(900, 1, ScheduledTask::CYCLE_YEAR, 2, ScheduledTask::CYCLE_YEAR));
	}

	/**
	 * A package whose own period is more than one is still divided by it.
	 */
	public function testPackagePeriodDividesThePrice()
	{
		$this->assertSame(100.0, $this->priceFor(300, 3, ScheduledTask::CYCLE_MONTH, 1, ScheduledTask::CYCLE_MONTH));
	}
}
