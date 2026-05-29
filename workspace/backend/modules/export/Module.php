<?php

namespace backend\modules\export;

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
			$app->getUrlManager()->addRules($this->getUrlManagerRules(), false);
		}
	}

	/**
	 * Prepares the UrlManager component rules.
	 *
	 * @param $rules
	 * @param $moduleId
	 * @return array
	 */
	protected static function prepareUrlManagerRules($rules, $moduleId)
	{
		if (!is_array($rules) || empty($rules)) {
			return [];
		}

		return array_map(function ($rule) use ($moduleId) {
			if (isset($rule['class']) && $rule['class'] === 'yii\web\GroupUrlRule') {
				$rule['prefix'] = str_replace('<module>', $moduleId, $rule['prefix']);
				$rule['routePrefix'] = str_replace('<module>', $moduleId, $rule['routePrefix']);
			}

			return $rule;
		}, $rules);
	}

	/**
	 * Gets the UrlManager component rules.
	 * Handles the children and submodules UrlManager component rules.
	 *
	 * @return array
	 */
	protected function getUrlManagerRules()
	{
		// Children rules
		$moduleUrlRules = self::prepareUrlManagerRules($this->urlManager->rules, $this->id);

		// Submodules rules
		foreach ($this->modules as $moduleId => $module) {
			$moduleName = reset(explode('-', $moduleId));
			$moduleConfig = require __DIR__ . "/modules/{$moduleName}/config/main.php";

			if (isset($moduleConfig['components']['urlManager']['rules'])) {
				array_unshift($moduleUrlRules, [
					'class' => 'yii\web\GroupUrlRule',
					'prefix' => "{$this->id}/{$moduleId}",
					'rules' => self::prepareUrlManagerRules($moduleConfig['components']['urlManager']['rules'], "{$this->id}/{$moduleId}"),
				]);
			}
		}

		return [
			[
				'class' => 'yii\web\GroupUrlRule',
				'prefix' => $this->id,
				'rules' => $moduleUrlRules,
			],
		];
	}
}
