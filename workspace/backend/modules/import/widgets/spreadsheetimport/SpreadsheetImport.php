<?php

namespace backend\modules\import\widgets\spreadsheetimport;

use backend\modules\transaction\models\TransactionForm;
use Yii;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use tws\helpers\Url;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Class SpreadsheetImport
 *
 * @author Alin Hort <alinhort@gmail.com>
 * @link http://opensource.box.com/spout/docs/
 */
class SpreadsheetImport extends Widget
{
	/**
	 * @var \yii\base\Model|\yii\db\ActiveRecord The model used to import the data.
	 */
	public $model;

	/**
	 * @var array The model custom columns which will be ignored if is empty.
	 */
	public $columns = [];

	/**
	 * @var array The excluded columns from the model.
	 */
	public $excludedColumns = [];

	/**
	 * @var array The included columns in the model.
	 */
	public $includedColumns = [];

	/**
	 * @var string The return URL to go after the last step.
	 */
	public $returnUrl;

	/**
	 * @var array The widget container options.
	 */
	public $options = [];

	/**
	 * @var array The client (JS) options.
	 */
	public $clientOptions = [];

	/**
	 * @var array The client (JS) events.
	 */
	public $clientEvents = [];

	/**
	 * @var string The client (JS) selector.
	 */
	private $_clientSelector;

	/**
	 * @var string The global widget JS hash variable.
	 */
	private $_hashVar;

	/**
	 * @inheritdoc
	 * @throws InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		if (!class_exists($this->model)) {
			throw new InvalidConfigException('The "model" property must be an existing class.');
		}

		$this->initColumns();
		$this->setupProperties();
		$this->registerAssets();

		// Save the configuration to the current session, the module will use this parameters
		Yii::$app->session->set('SpreadsheetImport', [
			'model' => $this->model,
			'columns' => $this->columns,
			'excludedColumns' => $this->excludedColumns,
			'includedColumns' => $this->includedColumns,
			'returnUrl' => $this->returnUrl,
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		return Html::tag('div', null, $this->options);
	}

	/**
	 * Gets the client selector.
	 *
	 * @return string
	 */
	public function getClientSelector()
	{
		if (!$this->_clientSelector) {
			$this->_clientSelector = '#' . $this->options['id'] ?: $this->getId();
		}
		return $this->_clientSelector;
	}

	/**
	 * Gets the hash variable.
	 *
	 * @return string
	 */
	public function getHashVar()
	{
		if (!$this->_hashVar) {
			$this->_hashVar = 'spreadsheet_' . hash('crc32', $this->buildClientOptions());
		}
		return $this->_hashVar;
	}

	/**
	 * Sets the widget properties.
	 */
	protected function setupProperties()
	{
		// Merge input options
		$this->options = ArrayHelper::merge([
			'id' => $this->options['id'] ?: $this->getId(),
			'data' => [
				'spreadsheet-options' => $this->getHashVar(),
			],
		], $this->options);

		Html::addCssClass($this->options, 'spreadsheet');
	}

	/**
	 * Builds the client options.
	 *
	 * @return string
	 */
	protected function buildClientOptions()
	{
		// Ensure default values
		$defaultClientOptions = [
			'url' => Url::to(['/import-manager/spreadsheet']),
		];
		// Merge client options
		$clientOptions = ArrayHelper::merge($defaultClientOptions, $this->clientOptions);
		// Return options as JSON
		return Json::encode($clientOptions);
	}

	/**
	 * Registers the widget assets.
	 */
	protected function registerAssets()
	{
		// Get the view
		$view = $this->getView();
		// Register assets
		SpreadsheetImportAsset::register($view);
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);
		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').spreadsheetImport({$this->getHashVar()})";
		// Build client events
		if (!empty($this->clientEvents)) {
			foreach ($this->clientEvents as $clientEvent => $eventHandler) {
				if (!($eventHandler instanceof JsExpression)) {
					$eventHandler = new JsExpression($eventHandler);
				}
				$js .= ".on('{$clientEvent}', {$eventHandler})";
			}
		}
		// Register widget JavaScript
		$view->registerJs("{$js};");
	}

	/**
	 * Gets the model columns.
	 */
	protected function initColumns()
	{
		// Use the provided columns or get the model attributes
		if (!is_array($this->columns) || empty($this->columns)) {
			$this->columns = array_keys((new $this->model)->attributes);
		}
		// Filter out excluded columns
		$this->columns = array_filter($this->columns, function ($column) {
			return !in_array($column, $this->excludedColumns);
		});
		//Add included columns
		$this->columns = array_merge($this->columns, $this->includedColumns);
	}
}
