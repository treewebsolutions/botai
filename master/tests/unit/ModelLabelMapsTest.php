<?php

namespace tests\unit;

use common\models\CommonActiveRecord;
use common\models\Country;
use common\models\ScheduledTask;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit coverage (no database) for the static label maps every grid, form and
 * badge in the backend is built from. The keys are the persisted status/cycle
 * values, so a stray key or a missing map entry silently breaks filters and
 * dropdowns — cheap to pin down, and it proves the bootstrap (Yii::t() through the
 * catch-all message source) works without a DbMessageSource.
 */
class ModelLabelMapsTest extends TestCase
{
	/**
	 * The status map is keyed by the persisted STATUS_* values and carries a badge
	 * colour per state; inherited by every CommonActiveRecord model.
	 */
	public function testStatusLabelsAreKeyedByStatusConstants()
	{
		$labels = Country::getStatusLabels();

		$this->assertSame([CommonActiveRecord::STATUS_INACTIVE, CommonActiveRecord::STATUS_ACTIVE], array_keys($labels));
		$this->assertSame('Inactive', $labels[CommonActiveRecord::STATUS_INACTIVE]['label']);
		$this->assertSame('danger', $labels[CommonActiveRecord::STATUS_INACTIVE]['color']);
		$this->assertSame('Active', $labels[CommonActiveRecord::STATUS_ACTIVE]['label']);
		$this->assertSame('success', $labels[CommonActiveRecord::STATUS_ACTIVE]['color']);
	}

	/**
	 * The boolean map follows the NO/YES constants used by the `deleted`,
	 * `default`, `requires_postcode`... flags.
	 */
	public function testBooleanLabels()
	{
		$this->assertSame([
			CommonActiveRecord::NO => 'No',
			CommonActiveRecord::YES => 'Yes',
		], Country::getBooleanLabels());
	}

	/**
	 * All three wordings of the scheduler cycle map cover exactly the six cycles, in
	 * the same order, so the cycle dropdown and the "Every ..." summary stay in sync.
	 */
	public function testCycleLabelsCoverEveryCycleInEachMode()
	{
		$cycles = [
			ScheduledTask::CYCLE_MINUTE,
			ScheduledTask::CYCLE_HOUR,
			ScheduledTask::CYCLE_DAY,
			ScheduledTask::CYCLE_WEEK,
			ScheduledTask::CYCLE_MONTH,
			ScheduledTask::CYCLE_YEAR,
		];

		foreach ([null, 'plural', 'adjective'] as $mode) {
			$this->assertSame($cycles, array_keys(ScheduledTask::getCycleLabels($mode)), "mode: " . var_export($mode, true));
		}
		$this->assertSame('Day', ScheduledTask::getCycleLabels()[ScheduledTask::CYCLE_DAY]);
		$this->assertSame('Days', ScheduledTask::getCycleLabels('plural')[ScheduledTask::CYCLE_DAY]);
		$this->assertSame('Every Day', ScheduledTask::getCycleLabels('adjective')[ScheduledTask::CYCLE_DAY]);
	}
}
