<?php

namespace common\widgets\canvas;

use yii\web\AssetBundle;

class CanvasAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@npm/fabric/dist';

	/**
	 * @inheritdoc
	 */
	public $js = [
		'fabric.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
	];
}
