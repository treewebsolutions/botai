<?php

namespace common\widgets\fullcalendar;

use yii\web\AssetBundle;

class FullCalendarGoogleAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $sourcePath = '@npm/fullcalendar/dist';

	/**
	 * @inheritdoc
	 */
	public $js = [
		'gcal.min.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'\common\widgets\fullcalendar\FullCalendarAsset',
	];
}
