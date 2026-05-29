<?php

namespace api\v1\modules\contractor;

use Yii;

class  Module extends \api\v1\Module
{
	/**
	 * {@inheritdoc}
	 */
	public $controllerNamespace = 'api\v1\modules\contractor\controllers';


	/**
	 * {@inheritdoc}
	 */
	public function __construct($id, $parent = null, $config = [])
	{
		parent::__construct($id, $parent, $config);
	}
}
