<?php

namespace api\v1;

use Yii;

class Module extends \yii\base\Module
{
	/**
	 * {@inheritdoc}
	 */
	public $controllerNamespace = 'api\v1\controllers';


	/**
	 * {@inheritdoc}
	 */
	public function __construct($id, $parent = null, $config = [])
	{
		parent::__construct($id, $parent, $config);

		Yii::configure($this, require __DIR__ . '/config/main.php');
	}
}
