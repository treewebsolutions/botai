<?php

namespace backend\assets;

use yii\web\AssetBundle;
use Yii;

class AppAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $basePath = '@webroot';

	/**
	 * @inheritdoc
	 */
	public $baseUrl = '@web';

	/**
	 * Theme CSS file (default value set)
	 */
	public $theme;

	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/tpl/components.min.css',
		'css/tpl/layout.min.css',
		'css/main-app.css',
		'css/custom.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'js/tpl/app.min.js',
		'js/tpl/layout.min.js',
		'js/tpl/quick-sidebar.min.js',
		'js/main-app.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapAsset',
		'yii\bootstrap\BootstrapPluginAsset',
		'backend\assets\BowerAsset',
		'backend\assets\NpmAsset',
	];

	/**
	 * Initialize the asset bundle
	 */
	public function init()
	{
		parent::init();

		$theme = Yii::$app->settings->get('theme') ?: 'default.css';
		$this->theme = "css/tpl/skins/{$theme}.css";
		$index = array_search('css/tpl/layout.min.css', $this->css);
		if ($index !== false) {
			array_splice($this->css, $index + 1, 0, $this->theme);
		} else {
			$this->css[] = $this->theme;
		}
	}
}
