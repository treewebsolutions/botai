<?php

namespace common\widgets\dynamicfields;

use yii\web\AssetBundle;

class DynamicFieldsAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@common/widgets/dynamicfields/assets';

	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/yii.dynamicFields.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'js/yii.dynamicFields.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
		'\yii\widgets\ActiveFormAsset',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		// Call the parent
		parent::init();
		// Handle DEV environment
		if (YII_ENV_DEV) {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}
