<?php

namespace backend\modules\nomenclature\models;

use common\helpers\StringHelper;
use common\models\Language;
use common\models\MenuItem;
use common\models\MenuItemTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class MenuItemForm extends MenuItem
{
	/**
	 * @var array The multilingual title of the menu item.
	 */
	public $title = [];

	/**
	 * @var array The multilingual url of the menu item.
	 */
	public $url = [];


	public $options = [];

	/**
	 * @var array Enable google translator.
	 */
	public $translator = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->options = [];
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['title'], 'required', 'when' => function ($model) {
				return empty($model->page_id) && empty($model->title[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[page_id]\"]").val() == "" && attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['excluded'], 'boolean'],
			[['excluded'], 'default', 'value' => 0],
			[['title', 'url'], 'each', 'rule' => ['trim']],
			[['icon', 'target'], 'default', 'value' => null],
			[['translator'], 'safe'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'page_id' => Yii::t('label', 'Page'),
			'title' => Yii::t('label', 'Title'),
			'url' => Yii::t('label', 'URL'),
			'translator' => Yii::t('label', 'Translator'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->title = ArrayHelper::map($this->menuItemTranslations, 'language_id', 'title');
		$this->url = ArrayHelper::map($this->menuItemTranslations, 'language_id', 'url');
		$this->options = $this->getUnserializedValue('options', []);
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveMenuItemTranslations()
	{
		try {
			if (!empty($this->translator)) {
				$languages = ArrayHelper::getColumn(Yii::$app->translate->discover()['data']['languages'], 'language');
				$defaultLanguage = Language::findOne(['language_id' => Yii::$app->language]);
			}

			foreach (Language::findAllLanguages() as $language) {
				$menuItemTranslation = MenuItemTranslation::findOne([
					'menu_item_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$menuItemTranslation) {
					$menuItemTranslation = new MenuItemTranslation();
					$menuItemTranslation->menu_item_id = $this->id;
					$menuItemTranslation->language_id = $language->language_id;
				}

				if (!empty($this->translator)) {
					if ($language->language_id != Yii::$app->language && in_array($defaultLanguage->language, $languages) & in_array($language->language, $languages)) {
						$source = $defaultLanguage->language;
						$target = $language->language;
					}
					$menuItemTranslation->title = StringHelper::truncate($this->title[$language->language_id] && ($language->language_id == Yii::$app->language || !in_array(2, (array)$this->translator)) ? $this->title[$language->language_id] : ($source && $target ? Yii::$app->translate->translate($source, $target, $this->title[Yii::$app->language])['data']['translations'][0]['translatedText'] : null), 255, '');
					$menuItemTranslation->url = StringHelper::truncate($this->url[$language->language_id] && ($language->language_id == Yii::$app->language || !in_array(2, (array)$this->translator)) ? $this->url[$language->language_id] : ($source && $target ? Yii::$app->translate->translate($source, $target, $this->url[Yii::$app->language])['data']['translations'][0]['translatedText'] : null), 255, '');
					$menuItemTranslation->slug = $menuItemTranslation->menuItem->getNestedSlug($language->language_id);
				} else {
					$menuItemTranslation->title = $this->title[$language->language_id];
					$menuItemTranslation->url = $this->url[$language->language_id];
					$menuItemTranslation->slug = $menuItemTranslation->menuItem->getNestedSlug($language->language_id);
				}

				$this->link('menuItemTranslations', $menuItemTranslation);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$this->setSerializedValue('options', $this->options);
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveMenuItemTranslations()) {
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
