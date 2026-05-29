<?php

namespace common\widgets\canvas;

use yii\web\AssetBundle;

class CanvasHelperAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@common/widgets/canvas/assets';

	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/yii.canvas.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'js/yii.canvas.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if (YII_ENV_DEV) {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}
