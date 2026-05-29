<?php

namespace common\widgets\clipboard;

use yii\web\AssetBundle;

class ClipboardHelperAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $css = [

	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'js/yii.clipboard.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
		'\common\widgets\clipboard\ClipboardAsset',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->sourcePath = __DIR__ . '/assets';

		if (YII_ENV_DEV) {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}
