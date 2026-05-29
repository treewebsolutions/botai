<?php

namespace common\modules\embed\assets;

use yii\web\AssetBundle;

class EmbedAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@embed/web';

	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/embed.css',
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
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapPluginAsset',
		'common\modules\embed\assets\NpmAsset',
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
