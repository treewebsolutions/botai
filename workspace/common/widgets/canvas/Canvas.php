<?php

namespace common\widgets\canvas;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * Class Canvas
 *
 * @package common\widgets\canvas
 * @author Alin Hort <alinhort@gmail.com>
 */
class Canvas extends InputWidget
{
	/**
	 * @var array The container options.
	 */
	public $containerOptions = [];

	/**
	 * @var array The canvas options.
	 */
	public $canvasOptions = [];

	/**
	 * @var array The widget options.
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
	 * @var string The global widget (JS) hash variable.
	 */
	private $_hashVar;

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		$this->setupProperties();
		$this->registerAssets();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		// Widget content
		$content = [];
		// Begin widget tag
		$content[] = Html::beginTag('div', $this->containerOptions);
		// Create the canvas
		$content[] = Html::tag('canvas', null, $this->canvasOptions);
		// Render input HTML tag
		$content[] = $this->renderInputHtml('hidden');
		// Close the widget HTML tag
		$content[] = Html::endTag('div');
		// Render the widget content
		return implode("\n", $content);
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
			$this->_hashVar = 'canvas_' . hash('crc32', $this->buildClientOptions());
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
			'class' => 'form-control',
			'autocomplete' => 'off',
			'data' => [
				'canvas-options' => $this->getHashVar(),
			],
		], $this->options);

		// Merge canvas options
		$this->containerOptions = ArrayHelper::merge([
			'id' => ($this->options['id'] ?: $this->getId()) . '-container',
			'class' => 'canvas-container',
		], $this->containerOptions);

		// Merge canvas options
		$this->canvasOptions = ArrayHelper::merge([
			'id' => ($this->options['id'] ?: $this->getId()) . '-element',
			'class' => 'canvas-element',
		], $this->canvasOptions);

		// Force default CSS classes
		Html::addCssClass($this->containerOptions, 'canvas-container');
		Html::addCssClass($this->canvasOptions, 'canvas-element');
		Html::addCssClass($this->options, 'canvas-input');
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
		CanvasAsset::register($view);
		CanvasHelperAsset::register($view);
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);
		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').yiiCanvas({$this->getHashVar()})";
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