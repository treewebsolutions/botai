<?php

namespace common\widgets\fullcalendar;

use yii\web\AssetBundle;

class FullCalendarAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@npm/fullcalendar/dist';

	/**
	 * @inheritdoc
	 */
	public $css = [
		'fullcalendar.min.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'fullcalendar.min.js',
		'locale-all.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\common\widgets\fullcalendar\MomentAsset',
	];
}
