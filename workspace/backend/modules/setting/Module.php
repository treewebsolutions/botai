<?php

namespace backend\modules\setting;

use Yii;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		Yii::configure($this, require __DIR__ . '/config/main.php');
	}

	/**
	 * @inheritdoc
	 */
	public function bootstrap($app)
	{
		if ($app instanceof \yii\web\Application) {
			$app->getUrlManager()->addRules([$this->getUrlManagerRules()], false);
		}
	}

	/**
	 * Gets the UrlManager component rules.
	 * Handles the children and submodules UrlManager component rules.
	 *
	 * @return array
	 */
	public function getUrlManagerRules()
	{
		// Children rules
		$rules = array_map(function ($rule) {
			if (isset($rule['class']) && $rule['class'] === 'yii\web\GroupUrlRule') {
				$rule['prefix'] = str_replace('<module>', $this->id, $rule['prefix']);
				$rule['routePrefix'] = str_replace('<module>', $this->id, $rule['routePrefix']);
			}
			return $rule;
		}, $this->urlManager->rules);

		// Submodules rules
		foreach ($this->modules as $moduleId => $module) {
			$module = $this->getModule($moduleId);
			if (method_exists($module, 'getUrlManagerRules')) {
				array_unshift($rules, $module->getUrlManagerRules());
			}
		}

		return [
			'class' => 'yii\web\GroupUrlRule',
			'prefix' => $this->module instanceof \yii\base\Application ? $this->id : "{$this->module->id}/{$this->id}",
			'rules' => $rules,
		];
	}

	/**
	 * Gets the nav menu items of this module.
	 *
	 * @return array
	 */
	public function getNavMenuItems()
	{
		return [
			'roles' => ['updateGeneralSetting', 'updateEmailSetting', 'updateInterfaceSetting', 'updateSmsSetting', 'updatePrintSetting', 'viewLanguage', 'clearCacheSetting'],
			'icon' => 'icon-settings',
			'label' => Yii::t('backend', 'Settings'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['updateGeneralSetting'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'General'),
					'url' => ['/setting-manager/setting/index'],
				],
				[
					'roles' => ['updateEmailSetting'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'Email'),
					'url' => ['/setting-manager/setting/email'],
				],
				[
					'roles' => ['updateInterfaceSetting'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'Interface'),
					'url' => ['/setting-manager/setting/interface'],
				],
				[
					'roles' => ['viewLanguage'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'Languages'),
					'url' => ['/setting-manager/language-manager/language/index'],
				],
				[
					'roles' => ['viewCurrency'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'Currencies'),
					'url' => ['/setting-manager/currency-manager/currency/index'],
				],
				[
					'roles' => ['clearCacheSetting'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('backend', 'Clear Cache'),
					'url' => ['/setting-manager/setting/clear-cache'],
					'linkOptions' => [
						'data' => [
							'method' => 'POST',
							'confirm' => Yii::t('backend', 'Are you sure you want to perform this operation?'),
						],
					]
				],
			],
		];
	}
}
