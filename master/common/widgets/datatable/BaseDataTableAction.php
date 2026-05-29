<?php
namespace common\widgets\datatable;

use Yii;
use yii\base\Action;
use yii\helpers\ArrayHelper;

/**
 * BaseDataTableSqlAction serves as a base class that implements the basic functionality of DataTable actions.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
abstract class BaseDataTableAction extends Action
{
	const REQUEST_METHOD_GET = 'GET';
	const REQUEST_METHOD_POST = 'POST';

	/**
	 * @var string The request method used.
	 */
	public $requestMethod = self::REQUEST_METHOD_POST;

	/**
	 * @var array The request parameters.
	 */
	public $requestParams = [];

	/**
	 * @var array External DataTable filters.
	 */
	public $externalFilters = [];

	/**
	 * @var callable Applies filtering according to params from DataTable.
	 *
	 * Signature is following:
	 *
	 * ```php
	 * function ($dataset, $columns, $search)
	 * ```
	 */
	public $applyFilter;

	/**
	 * @var callable Applies ordering according to params from DataTable.
	 *
	 * Signature is following:
	 *
	 * ```php
	 * function ($dataset, $columns, $order)
	 * ```
	 */
	public $applyOrder;

	/**
	 * @var callable Format data.
	 *
	 * Signature is following:
	 *
	 * ```php
	 * function ($dataset, $columns)
	 * ```
	 */
	public $formatData;

	/**
	 * @var callable Format response.
	 *
	 * Signature is following:
	 *
	 * ```php
	 * function ($response)
	 * ```
	 */
	public $formatResponse;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		$this->requestParams = ($this->requestMethod == self::REQUEST_METHOD_GET) ?
			Yii::$app->request->get() :
			Yii::$app->request->post();

		if (is_array($this->requestParams['external_filters']) && !empty($this->requestParams['external_filters'])) {
			$this->externalFilters = ArrayHelper::map($this->requestParams['external_filters'], 'name', 'value');
		}
	}

	/**
	 * Extract parameters from request.
	 *
	 * @param string $name The name of the parameter.
	 * @param mixed $defaultValue The default value to be returned.
	 * @return mixed The parameter value.
	 */
	protected function getParam($name, $defaultValue = null)
	{
		return $this->requestMethod == self::REQUEST_METHOD_GET ?
			Yii::$app->request->getQueryParam($name, $defaultValue) :
			Yii::$app->request->getBodyParam($name, $defaultValue);
	}
}
