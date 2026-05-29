<?php
/**
 * @copyright Copyright (c) 2014 Serhiy Vinichuk
 * @license MIT
 * @author Serhiy Vinichuk <serhiyvinichuk@gmail.com>
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
namespace common\widgets\datatable;

use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\Response;

/**
 * Action for processing ajax requests from DataTables.
 * @see http://datatables.net/manual/server-side for more info
 * @package common\widgets\datatable
 */
class DataTableAction extends Action
{
	/**
	 * Types of request method
	 */
	const REQUEST_METHOD_GET = 'GET';
	const REQUEST_METHOD_POST = 'POST';

	/**
	 * @see \common\widgets\datatable\DataTableAction::getParam
	 * @var string
	 */
	public $requestMethod = self::REQUEST_METHOD_POST;

	/**
	 * @var array The request parameters
	 */
	public $requestParams = [];

	/**
	 * @var array External DataTable filters.
	 */
	public $externalFilters = [];

	/**
	 * @var ActiveQuery
	 */
	public $query;

	/**
	 * Applies ordering according to params from DataTable
	 * Signature is following:
	 * function ($query, $columns, $order)
	 * @var callable
	 */
	public $applyOrder;

	/**
	 * Applies filtering according to params from DataTable
	 * Signature is following:
	 * function ($query, $columns, $search)
	 * @var callable
	 */
	public $applyFilter;

	/**
	 * Format data
	 * Signature is following:
	 * function ($query, $columns)
	 * @var callable
	 */
	public $formatData;

	/**
	 * Format response
	 * Signature is following:
	 * function ($response)
	 * @var callable
	 */
	public $formatResponse;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		// Set the request parameters
		$this->requestParams = ($this->requestMethod == self::REQUEST_METHOD_GET) ?
			Yii::$app->request->get() :
			Yii::$app->request->post();

		// Set the externalFilters
		if (is_array($this->requestParams['external_filters']) && !empty($this->requestParams['external_filters'])) {
			$this->externalFilters = ArrayHelper::map($this->requestParams['external_filters'], 'name', 'value');
		}
	}

	/**
	 * @return array|ActiveRecord[]
	 * @throws InvalidConfigException
	 */
	public function run()
	{
		// Ensure that query attribute is always set
		if (is_null($this->query)) {
			throw new InvalidConfigException(self::class . '::$query must be set.');
		}
		/** @var ActiveQuery $originalQuery */
		$originalQuery = $this->query;
		$filterQuery = clone $originalQuery;
		// Get DataTable draw id
		$draw = $this->getParam('draw');
		// Init filter conditions
		$filterQuery->where = null;
		// Get global search param
		$search = $this->getParam('search', ['value' => null, 'regex' => false]);
		// Get columns param
		$columns = $this->getParam('columns', []);
		// Get order param
		$order = $this->getParam('order', []);
		// Apply filter and order
		$filterQuery = $this->applyFilter($filterQuery, $columns, $search);
		$filterQuery = $this->applyOrder($filterQuery, $columns, $order);
		// Add extra condition
		if (!empty($originalQuery->where)) {
			$filterQuery->andWhere($originalQuery->where);
		}
		// Limit the records
		$filterQuery
			->offset($this->getParam('start', 0))
			->limit($this->getParam('length', -1));
		// Create data provider
		$dataProvider = new ActiveDataProvider([
			'query' => $filterQuery,
			'pagination' => [
				'pageSize' => $this->getParam('length', 10)
			]
		]);
		// Set the response format as JSON
		Yii::$app->response->format = Response::FORMAT_JSON;
		try {
			$response = [
				'draw' => (int)$draw,
				'recordsTotal' => (int)$originalQuery->count(),
				'recordsFiltered' => (int)$dataProvider->getTotalCount(),
				'data' => $this->formatData($filterQuery, $columns),
			];
		} catch (\Exception $e) {
			return ['error' => $e->getMessage()];
		}
		return $this->formatResponse($response);
	}

	/**
	 * Extract param from request.
	 *
	 * @param $name
	 * @param null $defaultValue
	 * @return mixed
	 */
	protected function getParam($name, $defaultValue = null)
	{
		return $this->requestMethod == self::REQUEST_METHOD_GET ?
			Yii::$app->request->getQueryParam($name, $defaultValue) :
			Yii::$app->request->getBodyParam($name, $defaultValue);
	}

	/**
	 * @param ActiveQuery $query
	 * @param array $columns
	 * @param array $search
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function applyFilter(ActiveQuery $query, $columns, $search)
	{
		// Use the custom closure if is set
		if ($this->applyFilter !== null) {
			return call_user_func($this->applyFilter, $query, $columns, $search);
		}
		/** @var \yii\db\ActiveRecord $modelClass */
		$modelClass = $query->modelClass;
		$schema = $modelClass::getTableSchema()->columns;
		// Loop through the DataTable columns
		foreach ($columns as $column) {
			if ($column['searchable'] == 'true' && array_key_exists($column['data'], $schema) !== false) {
				// Get the filter value
				if (!empty($search['value'])) {
					$value = $search['value'];
					$filterOperator = 'orFilterWhere';
				} else {
					$value = $column['search']['value'];
					$filterOperator = 'andFilterWhere';
				}
				// Apply filter
				$query->$filterOperator(['like', $column['data'], $value]);
			}
		}
		return $query;
	}

	/**
	 * @param ActiveQuery $query
	 * @param array $columns
	 * @param array $order
	 * @return ActiveQuery
	 */
	public function applyOrder(ActiveQuery $query, $columns, $order)
	{
		// Use the custom closure if is set
		if ($this->applyOrder !== null) {
			return call_user_func($this->applyOrder, $query, $columns, $order);
		}
		// Loop through the DataTable order items
		foreach ($order as $key => $item) {
			// Get the order targeted column
			$column = $columns[$item['column']];
			// Continue if the column is not orderable
			if (array_key_exists('orderable', $column) && $column['orderable'] == 'false') {
				continue;
			}
			// Get the order value
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;
			// Apply order
			$query->addOrderBy([$column['data'] => $sort]);
		}
		return $query;
	}

	/**
	 * @param ActiveQuery $query
	 * @param array $columns
	 * @return array|ActiveRecord[]
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		// Use the custom closure if is set
		if ($this->formatData !== null) {
			return call_user_func($this->formatData, $query, $columns);
		}
		return $query->all();
	}

	/**
	 * @param array $response
	 * @return array|ActiveRecord[]
	 */
	public function formatResponse($response)
	{
		// Use the custom closure if is set
		if ($this->formatResponse !== null) {
			return call_user_func($this->formatResponse, $response);
		}
		return $response;
	}

	/**
	 * Gets keywords from a string.
	 *
	 * @param string $value
	 * @param string|null $delimiter
	 * @return array
	 */
	public static function getKeywordsFromString($value, $delimiter = null)
	{
		if ($delimiter === null) {
			$delimiter = strpos($value, ',') !== false ? ',' : ' ';
		}
		$keywords = explode($delimiter, $value);
		$keywords = array_map('trim', $keywords);

		return array_filter($keywords);
	}

	/**
	 * Applies smart filter for a specific column.
	 *
	 * @param array $config
	 * @return DataTableAction
	 */
	public function applyColumnSmartFilter($config)
	{
		/** @var ActiveQuery $query */
		$query = ArrayHelper::remove($config, 'query', null);
		$column = ArrayHelper::remove($config, 'column', null);
		$value = ArrayHelper::remove($config, 'value', null);
		$keywords = ArrayHelper::remove($config, 'keywords', self::getKeywordsFromString($value));
		$filterOperator = ArrayHelper::remove($config, 'filterOperator', 'andFilterWhere');
		$operator = ArrayHelper::remove($config, 'operator', 'LIKE');

		if ($query instanceof ActiveQuery && !empty($column)) {
			$query->$filterOperator([$operator, $column, $value]);

			if (($keywordsCount = count($keywords)) > 1) {
				$conditions = ['AND'];
				for ($i = 1; $i < $keywordsCount; $i++) {
					$conditions[] = [$operator, $column, $keywords[$i]];
				}
				$query->orFilterWhere(['AND', [$operator, $column, $keywords[0]], $conditions]);
			}
		}

		return $this;
	}
}