<?php

namespace common\widgets\clipboard;

use yii\web\AssetBundle;

class ClipboardAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@npm/clipboard/dist';

	/**
	 * @inheritdoc
	 */
	public $css = [

	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'clipboard.min.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
		'\yii\bootstrap\BootstrapAsset',
	];
}
