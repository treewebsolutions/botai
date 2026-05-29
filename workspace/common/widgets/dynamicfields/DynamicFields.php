<?php

namespace common\widgets\dynamicfields;

use Symfony\Component\DomCrawler\Crawler;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Class DynamicFields
 *
 * @package common\widgets\dynamicfields
 * @author Alin Hort <alinhort@gmail.com>
 */
class DynamicFields extends Widget
{
	const INSERT_TYPE_PREPEND = 'prepend';
	const INSERT_TYPE_APPEND = 'append';
	const INSERT_TYPE_BEFORE = 'before';
	const INSERT_TYPE_AFTER = 'after';

	/**
	 * @var array The widget options
	 */
	public $options = [];

	/**
	 * @var array The client (JS) options
	 */
	public $clientOptions = [];

	/**
	 * @var array The client (JS) events
	 */
	public $clientEvents = [];

	/**
	 * @var string The client (JS) selector
	 */
	private $_clientSelector;

	/**
	 * @var string The global widget JS hash variable
	 */
	private $_hashVar;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		// Call the parent
		parent::init();
		// Set properties
		$this->setupProperties();
		// Register assets
		$this->registerAssets();
		// Begin widget content
		ob_start();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		// End widget content - Get the HTML as the widget content
		$content = ob_get_clean();
		// Render the widget content
		return Html::tag('div', $content, $this->options);
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
			$this->_hashVar = 'dynamicfields_' . hash('crc32', $this->buildClientOptions());
		}
		return $this->_hashVar;
	}

	/**
	 * Sets the widget properties.
	 */
	protected function setupProperties()
	{
		// Ensure that options array contains an id key
		$this->options['id'] = $this->options['id'] ?: $this->getId();
		// Ensure default CSS class for the widget container
		Html::addCssClass($this->options, 'dynamicfields-container');
	}

	/**
	 * Builds Client Options.
	 *
	 * @return string
	 */
	protected function buildClientOptions()
	{
		// Ensure default values
		$defaultClientOptions = [
			// Defaults can be added here
		];
		// Merge client options
		$clientOptions = ArrayHelper::merge($defaultClientOptions, $this->clientOptions);
		// Return options as JSON
		return Json::encode($clientOptions);
	}

	/**
	 * Registers widget assets.
	 */
	protected function registerAssets()
	{
		// Get the view
		$view = $this->getView();
		// Register assets
		DynamicFieldsAsset::register($view);
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);
		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').yiiDynamicFields({$this->getHashVar()})";
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
}