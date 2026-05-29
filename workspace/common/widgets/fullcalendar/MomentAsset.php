<?php

namespace common\widgets\fullcalendar;

use yii\web\AssetBundle;

class MomentAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@npm/moment';

	/**
	 * @inheritdoc
	 */
	public $js = [
		'min/moment-with-locales.min.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\yii\web\JqueryAsset',
	];
}
