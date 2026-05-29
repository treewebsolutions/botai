<?php
namespace common\widgets\datatable;

use Yii;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Action class that process DataTable AJAX request with ActiveDataProvider.
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class DataTableAction extends BaseDataTableAction
{
	/**
	 * @var \yii\db\ActiveQuery The query used as dataset of the table.
	 */
	public $query;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * Runs this action.
	 *
	 * @return mixed
	 * @throws InvalidConfigException if query is not properly configured.
	 */
	public function run()
	{
		if (is_null($this->query)) {
			throw new InvalidConfigException(self::class . '::$query must be an instance of \yii\db\ActiveQuery.');
		}

		$originalQuery = $this->query;
		$filteredQuery = clone $originalQuery;
		$filteredQuery->where = null;

		$draw = $this->getParam('draw');
		$search = $this->getParam('search', ['value' => null, 'regex' => false]);
		$columns = $this->getParam('columns', []);
		$order = $this->getParam('order', []);

		$filteredQuery = $this->applyFilter($filteredQuery, $columns, $search);
		$filteredQuery = $this->applyOrder($filteredQuery, $columns, $order);
		if (!empty($originalQuery->where)) {
			$filteredQuery->andWhere($originalQuery->where);
		}

		$filteredQuery
			->offset($this->getParam('start', 0))
			->limit($this->getParam('length', -1));

		$dataProvider = new ActiveDataProvider([
			'query' => $filteredQuery,
			'pagination' => [
				'pageSize' => $this->getParam('length', 10),
			],
		]);

		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

		try {
			$response = [
				'draw' => (int) $draw,
				'recordsTotal' => (int) $originalQuery->count(),
				'recordsFiltered' => (int) $dataProvider->getTotalCount(),
				'data' => $this->formatData($filteredQuery, $columns),
			];
		} catch (\Exception $e) {
			return ['error' => $e->getMessage()];
		}

		return $this->formatResponse($response);
	}

	/**
	 * Applies filtering.
	 *
	 * @param \yii\db\ActiveQuery $query The query used as dataset of the table.
	 * @param array $columns The table columns.
	 * @param array $search The table searchable columns.
	 * @return \yii\db\ActiveQuery The filtered dataset.
	 * @throws InvalidConfigException
	 */
	public function applyFilter(ActiveQuery $query, $columns, $search)
	{
		if (is_callable($this->applyFilter)) {
			return call_user_func($this->applyFilter, $query, $columns, $search);
		}

		/** @var \yii\db\ActiveRecord $modelClass */
		$modelClass = $query->modelClass;
		$schema = $modelClass::getTableSchema()->columns;

		foreach ($columns as $column) {
			if ($column['searchable'] == 'true' && array_key_exists($column['data'], $schema) !== false) {
				if (!empty($search['value'])) {
					$value = $search['value'];
					$filterOperator = 'orFilterWhere';
				} else {
					$value = $column['search']['value'];
					$filterOperator = 'andFilterWhere';
				}
				$query->$filterOperator(['like', $column['data'], $value]);
			}
		}

		return $query;
	}

	/**
	 * Applies ordering.
	 *
	 * @param \yii\db\ActiveQuery $query The query used as dataset of the table.
	 * @param array $columns The table columns.
	 * @param array $order The table sortable columns.
	 * @return \yii\db\ActiveQuery The ordered dataset.
	 */
	public function applyOrder(ActiveQuery $query, $columns, $order)
	{
		if (is_callable($this->applyOrder)) {
			return call_user_func($this->applyOrder, $query, $columns, $order);
		}

		foreach ($order as $key => $item) {
			$column = $columns[$item['column']];
			if (array_key_exists('orderable', $column) && $column['orderable'] == 'false') {
				continue;
			}
			$sort = mb_strtolower($item['dir']) == 'desc' ? SORT_DESC : SORT_ASC;
			$query->addOrderBy([$column['data'] => $sort]);
		}

		return $query;
	}

	/**
	 * Formats the dataset.
	 *
	 * @param \yii\db\ActiveQuery $query The query used as dataset of the table.
	 * @param array $columns The table columns.
	 * @return array|ActiveRecord[] The formatted dataset.
	 */
	public function formatData(ActiveQuery $query, $columns)
	{
		if (is_callable($this->formatData)) {
			return call_user_func($this->formatData, $query, $columns);
		}

		return $query->all();
	}

	/**
	 * Formats the response.
	 *
	 * @param array $response
	 * @return array|ActiveRecord[] The formatted response.
	 */
	public function formatResponse($response)
	{
		if (is_callable($this->formatResponse)) {
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
	 * @return DataTableActiveAction
	 */
	public function applyColumnSmartFilter($config)
	{
		/** @var \yii\db\ActiveQuery $query */
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
