<?php

namespace frontend\modules\embed\assets;

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
		'js/marked.min.js',
		'js/chat.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapPluginAsset',
		'frontend\modules\embed\assets\NpmAsset',
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
