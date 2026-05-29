<?php

namespace frontend\modules\sitemap;

use Yii;
use yii\helpers\Inflector;

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
		Yii::setAlias("@{$this->id}", __DIR__);

		$app->i18n->translations[$this->id] = [
			'class' => 'yii\i18n\PhpMessageSource',
			'basePath' => "@{$this->id}/messages",
			'forceTranslation' => true,
			'fileMap' => [
				$this->id => 'i18n.php',
			],
		];

		if ($app instanceof \yii\web\Application) {
			if ($urlRules = $this->buildUrlRules()) {
				$app->getUrlManager()->addRules([$urlRules], false);
			}
		}
	}

	/**
	 * Builds the rules for all pages of this module.
	 *
	 * @return array
	 */
	protected function buildUrlRules()
	{
		$urlRules = [];
        $urlRules = array_merge($urlRules, $this->buildSitemapUrlRules());

		return [
			'class' => 'yii\web\GroupUrlRule',
			'routePrefix' => $this->id,
			'rules' => $urlRules,
		];
	}

	/**
	 * Builds URL Rules for ArticleController.
	 *
	 * @param \common\models\Page $page
	 * @param \common\models\PageTranslation $pageTranslation
	 * @return array
	 */
	protected function buildSitemapUrlRules()
	{

		$urlRules[] = [
			'pattern' => "/sitemap<index>",
			'route' => "default/view",
		];
		$urlRules[] = [
			'pattern' => '/sitemap',
			'route' => 'default/index',
		];
		return $urlRules;
	}
}
