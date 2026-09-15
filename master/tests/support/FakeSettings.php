<?php

namespace tests\support;

use yii\base\Component;

/**
 * Test double for the tws\settings\Settings component: same read API, but the values
 * come from a plain array instead of the `setting` table, and a missing key yields
 * null instead of an "undefined index" notice.
 */
class FakeSettings extends Component
{
	/**
	 * @var array category => [key => value]
	 */
	public $data = [];

	/**
	 * @return array every category's values, keyed by category
	 */
	public function getAll()
	{
		return $this->data;
	}

	/**
	 * @param string $category
	 * @return array
	 */
	public function getCategory($category)
	{
		return isset($this->data[$category]) ? $this->data[$category] : [];
	}

	/**
	 * @param string $item
	 * @return mixed the first match across all categories, null when absent
	 */
	public function getItem($item)
	{
		foreach ($this->data as $values) {
			if (array_key_exists($item, (array) $values)) {
				return $values[$item];
			}
		}
		return null;
	}

	/**
	 * @param string $item
	 * @param string $category
	 * @return mixed
	 */
	public function getItemFromCategory($item, $category)
	{
		if (isset($this->data[$category]) && array_key_exists($item, (array) $this->data[$category])) {
			return $this->data[$category][$item];
		}
		return null;
	}

	/**
	 * @param string $item
	 * @param string|null $category
	 * @return mixed
	 */
	public function get($item, $category = null)
	{
		if (empty($category) && array_key_exists($item, $this->data)) {
			return $this->getCategory($item);
		}
		if (empty($category)) {
			return $this->getItem($item);
		}
		return $this->getItemFromCategory($item, $category);
	}

	/**
	 * @param string $item
	 * @param mixed $value
	 * @param string $category
	 */
	public function set($item, $value, $category = 'general')
	{
		$this->data[$category][$item] = $value;
	}
}
