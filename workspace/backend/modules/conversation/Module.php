<?php

namespace backend\modules\conversation;

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
				'viewMessage',
				'viewThread',
				'viewParticipant',
			],
			'icon' => 'fa fa-comments-o',
			'label' => Yii::t('backend', 'Conversations'),
			'url' => '#',
			'items' => [
				[
					'roles' => ['viewMessage'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Messages'),
					'url' => ['/conversation-manager/message/index'],
				],
				[
					'roles' => ['viewThread'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Threads'),
					'url' => ['/conversation-manager/thread/index'],
				],
				[
					'roles' => ['viewParticipant'],
					'icon' => 'fa fa-circle-o',
					'label' => Yii::t('label', 'Participants'),
					'url' => ['/conversation-manager/participant/index'],
				],
			],
		];
	}
}
