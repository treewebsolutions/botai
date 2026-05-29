<?php

namespace common\modules\embed;

use Yii;
use yii\base\BootstrapInterface;

/**
 * This is the main class for the Commercial module.
 *
 * To use Commercial, include it as a module in the application configuration like the following:
 *
 * ~~~
 * return [
 *     'bootstrap' => ['commercial'],
 *     'modules' => [
 *         'commercial' => [
 *             'class' => 'common\modules\embed\Module',
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * @author Tree Web Solutions <treewebsolutions.com@gmail.com>
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public $controllerNamespace = 'common\modules\embed\controllers';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		Yii::configure($this, require __DIR__ . '/config/main.php');

		if (Yii::$app instanceof \yii\console\Application) {
			$this->controllerNamespace = 'common\modules\embed\commands';
		}
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		$app->getUrlManager()->addRules([
			[
				'class' => 'yii\web\GroupUrlRule',
				'routePrefix' => $this->id,
				'prefix' => $this->id,
				'rules' => [
					[
						'class' => 'yii\web\UrlRule',
						'route' => '<controller>/<action>',
						'pattern' => '<controller:[\w\-]+>/<action:[\w\-]+>',
						'suffix' => false,
					],
				],
			],
		], false);
	}
}
