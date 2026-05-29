<?php

namespace common\helpers;

use DateInterval;
use DateTime;
use DateTimeZone;
use Yii;

class DateHelper
{
	/**
	 * Formats a string to a custom date string.
	 *
	 * @param string|\DateTime $value
	 * @param string $format
	 * @param string|null $modify
	 * @return null|string
	 */
	public static function format($value, $format, $modify = null)
	{
		// Return null if the value is empty
		if (empty($value)) {
			return null;
		}
		try {
			// Create the DateTime from the value
			if (!($value instanceof DateTime)) {
				$value = new DateTime($value);
			}
			// Apply date modification
			if ($modify) {
				$value->modify($modify);
			}
			return $value->format($format);
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Formats a string to a date string.
	 *
	 * @param string|\DateTime $value
	 * @param string|null $modify
	 * @return null|string
	 */
	public static function formatAsDate($value, $modify = null)
	{
		return self::format($value, 'Y-m-d', $modify);
	}

	/**
	 * Formats a string to a datetime string.
	 *
	 * @param string|\DateTime $value
	 * @param string|null $modify
	 * @return null|string
	 */
	public static function formatAsDateTime($value, $modify = null)
	{
		return self::format($value, 'Y-m-d H:i:s', $modify);
	}

	/**
	 * Formats a value to a relative time string.
	 * @deprecated use Yii::$app->formatter->asRelativeTime() instead
	 *
	 * @param DateTime|string $value
	 * @param DateTime|string $referenceTime
	 * @return string
	 */
	public static function formatAsRelativeTime($value, $referenceTime = 'now')
	{
		$interval = $value instanceof DateInterval ? $value : self::getDateInterval($value, $referenceTime);

		// Future
		if ($interval->invert) {
			if ($interval->y >= 1) {
				return $interval->y === 1 ?
					Yii::t('common', 'in a year') :
					Yii::t('common', 'in {delta} years', ['delta' => $interval->y]);
			}
			if ($interval->m >= 1) {
				return $interval->m === 1 ?
					Yii::t('common', 'in a month') :
					Yii::t('common', 'in {delta} months', ['delta' => $interval->m]);
			}
			if ($interval->d >= 1) {
				return $interval->d === 1 ?
					Yii::t('common', 'in a day') :
					Yii::t('common', 'in {delta} days', ['delta' => $interval->d]);
			}
			if ($interval->h >= 1) {
				return $interval->h === 1 ?
					Yii::t('common', 'in an hour') :
					Yii::t('common', 'in {delta} hours', ['delta' => $interval->h]);
			}
			if ($interval->i >= 1) {
				return $interval->i === 1 ?
					Yii::t('common', 'in a minute') :
					Yii::t('common', 'in {delta} minutes', ['delta' => $interval->i]);
			}
			if ($interval->s == 0) {
				return Yii::t('common', 'just now');
			}

			return $interval->s === 1 ?
				Yii::t('common', 'in a second') :
				Yii::t('common', 'in {delta} seconds', ['delta' => $interval->s]);
		}

		// Past
		if ($interval->y >= 1) {
			return $interval->y === 1 ?
				Yii::t('common', '{delta} year ago', ['delta' => $interval->y]) :
				Yii::t('common', '{delta} years ago', ['delta' => $interval->y]);
		}
		if ($interval->m >= 1) {
			return $interval->m === 1 ?
				Yii::t('common', '{delta} month ago', ['delta' => $interval->m]) :
				Yii::t('common', '{delta} months ago', ['delta' => $interval->m]);
		}
		if ($interval->d >= 1) {
			return $interval->d === 1 ?
				Yii::t('common', '{delta} day ago', ['delta' => $interval->d]) :
				Yii::t('common', '{delta} days ago', ['delta' => $interval->d]);
		}
		if ($interval->h >= 1) {
			return $interval->h === 1 ?
				Yii::t('common', '{delta} hour ago', ['delta' => $interval->h]) :
				Yii::t('common', '{delta} hours ago', ['delta' => $interval->h]);
		}
		if ($interval->i >= 1) {
			return $interval->i === 1 ?
				Yii::t('common', '{delta} minute ago', ['delta' => $interval->i]) :
				Yii::t('common', '{delta} minutes ago', ['delta' => $interval->i]);
		}
		if ($interval->s == 0) {
			return Yii::t('common', 'just now');
		}

		return $interval->s === 1 ?
			Yii::t('common', '{delta} second ago', ['delta' => $interval->s]) :
			Yii::t('common', '{delta} seconds ago', ['delta' => $interval->s]);
	}

	/**
	 * Gets the interval between two dates.
	 *
	 * @param DateTime|string $startDate
	 * @param DateTime|string $endDate
	 * @return DateInterval|string|null
	 */
	public static function getDateInterval($startDate, $endDate = 'now')
	{
		if (empty($startDate)) {
			return Yii::$app->formatter->nullDisplay;
		}

		try {
			$timeZone = new DateTimeZone(Yii::$app->timeZone);

			if (!($startDate instanceof DateTime)) {
				$startDate = new DateTime($startDate, $timeZone);
			}

			if (!($endDate instanceof DateTime)) {
				$endDate = new DateTime($endDate, $timeZone);
			}
		} catch (\Exception $e) {
			return Yii::$app->formatter->nullDisplay;
		}

		return $startDate->diff($endDate);
	}

	/**
	 * Gets the i18n days of the week.
	 *
	 * @param string $firstDay
	 * @param string $format
	 * @return array
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDaysOfWeek($firstDay = 'Monday', $format = 'EEEE')
	{
		$dow = [];

		for ($i = 0; $i < 7; $i++) {
			$dow[] = Yii::$app->formatter->asDate("{$firstDay} +{$i} days", $format);
		}

		return $dow;
	}

	/**
	 * Gets count weeks of year.
	 *
	 * @param $year
	 * @return int
	 * @throws \Exception
	 */
	public static function getCountWeeksOfYear($year = null)
	{
		// Get the year or fallback to current year
		$year = $year ?: date('Y');
		// Date
		$date = new DateTime();
		// Set ISO Date
		$date->setISODate($year, 53);
		return ($date->format('W') === '53' ? 53 : 52);
	}

	/**
	 * Gets weeks of year.
	 *
	 * @param $year
	 * @return array
	 * @throws \Exception
	 */
	public static function getWeeksOfYear($year = null)
	{
		// Get weeks of year count
		$countWeeks = self::getCountWeeksOfYear($year);
		// Weeks of year array
		$weeksOfYear = [];
		// Compose the result
		for ($i = 1; $i <= $countWeeks; $i++) {
			$weeksOfYear[] = $i;
		}
		return array_combine($weeksOfYear, $weeksOfYear);
	}

	/**
	 * Gets years with negative and positive offsets.
	 *
	 * @param $leftOffset
	 * @param $rightOffset
	 * @return array
	 * @throws \Exception
	 */
	public static function getOffsetYears($leftOffset = null, $rightOffset = null)
	{
		// Date
		$date = new DateTime();
		// Current year
		$currentYear = $date->format('Y');
		// Years
		$years = [$currentYear];
		// Left offset
		if (!is_null($leftOffset)) {
			for ($i = 1; $i <= $leftOffset; $i++) {
				array_unshift($years, (new DateTime("-{$i} years"))->format('Y'));
			}
		}
		// Right offset
		if (!is_null($rightOffset)) {
			for ($i = 1; $i <= $rightOffset; $i++) {
				array_push($years, (new DateTime("+{$i} years"))->format('Y'));
			}
		}
		return array_combine($years, $years);
	}
}
