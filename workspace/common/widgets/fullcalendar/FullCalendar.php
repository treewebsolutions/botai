<?php

namespace common\widgets\fullcalendar;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Class FullCalendar
 *
 * @package common\widgets\fullcalendar
 * @author AlinHort <alinhort@gmail.com>
 * @link https://fullcalendar.io/docs
 */
class FullCalendar extends Widget
{
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
	 * @var string The global widget JS hash variable.
	 */
	private $_hashVar;

	/**
	 * @inheritdoc
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
			$this->_hashVar = 'fullcalendar_' . hash('crc32', $this->buildClientOptions());
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
			'id' => $this->getId(),
			'data' => [
				'fullcalendar-options' => $this->getHashVar(),
			],
		], $this->options);

		// Force default CSS classes
		Html::addCssClass($this->options, 'fullcalendar');
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
			'locale' => substr(Yii::$app->language, 0, 2),
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
		MomentAsset::register($view);
		FullCalendarAsset::register($view);
		if (isset($this->clientOptions['googleCalendarApiKey'])) {
			FullCalendarGoogleAsset::register($view);
		}
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);
		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').fullCalendar({$this->getHashVar()})";
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