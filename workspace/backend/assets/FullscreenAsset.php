<?php

namespace backend\assets;

use yii\web\AssetBundle;

class FullscreenAsset extends AssetBundle
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
	 * @inheritdoc
	 */
	public $css = [
		'css/pages/fullscreen.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'backend\assets\AppAsset',
	];
}
