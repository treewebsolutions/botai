<?php

namespace frontend\modules\embed\assets;

use yii\web\AssetBundle;

class NpmAsset extends AssetBundle
{
	public $sourcePath = '@npm';

	public $css = [
		'font-awesome/css/font-awesome.min.css',
	];

	public $js = [
		'iframe-resizer/js/iframeResizer.contentWindow.min.js',
	];
}
