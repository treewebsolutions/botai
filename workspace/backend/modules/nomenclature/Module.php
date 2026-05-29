<?php

namespace backend\modules\nomenclature;

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
			'roles' => [
				'viewIntegration',
				'viewVectorStore',
				'viewVectorStoreFile',
				'viewAssistant',
			],
			'icon' => 'fa fa-list',
			'label' => Yii::t('backend', 'Nomenclature'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['viewPage'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Pages'),
					'url' => ['/nomenclature-manager/page/index'],
				],
//				[
//					'roles' => ['viewIntegration'],
//					'icon' => 'fa fa-circle-o',
//					'label' => Yii::t('label', 'Integrations'),
//					'url' => ['/nomenclature-manager/integration/index'],
//				],
				[
					'roles' => ['viewVectorStore'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Vector Stores'),
					'url' => ['/nomenclature-manager/vector-store/index'],
				],
				[
					'roles' => ['viewVectorStoreFile'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Vector Store Files'),
					'url' => ['/nomenclature-manager/vector-store-file/index'],
				],
				[
					'roles' => ['viewAssistant'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Assistants'),
					'url' => ['/nomenclature-manager/assistant/index'],
				],
			],
		];
	}
}
