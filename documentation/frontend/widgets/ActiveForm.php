<?php

namespace frontend\widgets;

use Yii;

class ActiveForm extends \yii\bootstrap\ActiveForm
{
	/**
	 * @inheritdoc
	 */
	public $fieldClass = 'frontend\widgets\ActiveField';

}
