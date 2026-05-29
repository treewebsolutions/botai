<?php

namespace backend\modules\nomenclature\models;

use common\components\Scraper;
use common\models\Page;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PageForm extends Page
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->website = Yii::$app->user->identity->workspace->domain;
		$this->url = Yii::$app->user->identity->workspace->domain;
		$this->status = static::STATUS_INACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['website', 'url'], 'required'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
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
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$page = Page::findOne(['url' => $this->url, 'website' => $this->website]);

			if (empty($page) && !parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}

			$scraper = new Scraper(3);
			$scraper->scrape($this->url, $this->website, 0, static::STATUS_ACTIVE);

			$dbTransaction->commit();

			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
