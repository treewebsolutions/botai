<?php

namespace backend\modules\import\widgets\spreadsheetimport;

use yii\web\AssetBundle;

class SpreadsheetImportAsset extends AssetBundle
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
		'js/spreadsheet-import.js',
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

		$this->sourcePath = __DIR__ . '/assets';

		if (YII_ENV_DEV) {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}
