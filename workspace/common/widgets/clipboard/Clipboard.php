<?php

namespace common\widgets\clipboard;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;
use yii\widgets\InputWidget;

/**
 * Class Clipboard
 *
 * @package common\widgets\clipboard
 * @author Alin Hort <alinhort@gmail.com>
 * @link https://github.com/zenorocha/clipboard.js
 */
class Clipboard extends InputWidget
{
	const INPUT_ADDON_START = 'start';
	const INPUT_ADDON_END = 'end';

	/**
	 * @var bool|string The input addon
	 */
	public $inputAddon = self::INPUT_ADDON_START;

	/**
	 * @var string The input addon content
	 */
	public $inputAddonContent;

	/**
	 * @var string The linked DateTimePicker widget selector
	 */
	public $linkedTo;

	/**
	 * @var array The container options
	 */
	public $containerOptions = [];

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
		// Render input HTML tag
		$content[] = $this->renderInputHtml('text');
		// Close the widget HTML tag
		$content[] = Html::endTag('div');
		// Render the input addon at the proper position
		if ($this->inputAddon) {
			if ($this->inputAddon === self::INPUT_ADDON_START) {
				array_splice($content, 1, 0, $this->renderInputAddon());
			} else {
				array_splice($content, 2, 0, $this->renderInputAddon());
			}
		}
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
			$this->_clientSelector = '#' . $this->getId();
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
			$this->_hashVar = 'clipboard_' . hash('crc32', $this->buildClientOptions());
		}
		return $this->_hashVar;
	}

	/**
	 * Sets the widget properties.
	 */
	protected function setupProperties()
	{
		// Ensure that input id is null if does not have a model attached
		if (!$this->hasModel()) {
			$this->options['id'] = 'embed-input-' . $this->getId();
		}
		// Merge input options
		$this->options = ArrayHelper::merge([
			'class' => 'form-control',
			'autocomplete' => 'off',
			'data' => [
				'clipboard-options' => $this->getHashVar(),
			],
		], $this->options);
		$this->inputAddonContent = $this->inputAddonContent ?: Html::button('<span class="glyphicon glyphicon-copy"></span>', [
			'class' => 'btn btn-default',
			'data' => [
				'clipboard-target' => "#{$this->options['id']}",
			],
		]);
		// Ensure that containerOptions array contains an id key
		$this->containerOptions['id'] = $this->containerOptions['id'] ?: $this->getId();
		// Ensure default CSS class for the widget container
		Html::addCssClass($this->containerOptions, 'clipboard-container');
		// Ensure default CSS class for the input control
		Html::addCssClass($this->options, 'clipboard-input');
		// Check if the inputAddon is set
		if ($this->inputAddon) {
			// Add the proper CSS class
			Html::addCssClass($this->containerOptions, 'input-group');
		}
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
		ClipboardAsset::register($view);
		ClipboardHelperAsset::register($view);
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);
		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').yiiClipboard({$this->getHashVar()})";
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
	 * Renders a Bootstrap input group addon.
	 *
	 * @return string
	 */
	protected function renderInputAddon()
	{
		return Html::tag('div', $this->inputAddonContent, [
			'class' => 'input-group-btn',
		]);
	}
}