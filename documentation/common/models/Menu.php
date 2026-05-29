<?php

namespace common\models;

use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%menu}}".
 *
 * @property int $id
 * @property int $position
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property MenuItem[] $menuItems
 * @property MenuTranslation[] $menuTranslations
 * @property MenuTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class Menu extends CommonActiveRecord
{
	const POSITION_SIDEBAR = 1;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%menu}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'groupAttributes' => ['position'],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['position', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
			[['status'], 'required'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'position' => Yii::t('label', 'Position'),
			'default' => Yii::t('label', 'Default'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMenuItems()
	{
		return $this->hasMany(MenuItem::class, ['menu_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMenuTranslations()
	{
		return $this->hasMany(MenuTranslation::class, ['menu_id' => 'id']);
	}

	/**
	 * Gets the model translation.
	 *
	 * @param null|string $language
	 * @return null|MenuTranslation
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->menuTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%menu_translation}}', ['menu_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Gets the formattedPosition.
	 *
	 * @param string $glue
	 * @return string
	 */
	public function getFormattedPosition($glue = ', ')
	{
		return implode($glue, ArrayHelper::filter(self::getPositionLabels(), explode(',', $this->position)));
	}

	/**
	 * Gets the nested items.
	 *
	 * @param null|array $columns The columns for each items from the tree array.
	 * @param null|int $status The status of the record.
	 * @param null|int $deleted The deleted state of the record.
	 * @return array
	 */
	public function getNestedItems($columns = null, $status = null, $deleted = null)
	{
		$parentMenuItems = $this->getMenuItems()
			->where([
				'parent_id' => null,
				'deleted' => $deleted ?: MenuItem::NO,
			])
			->andFilterWhere([
				'status' => $status,
			])
			->orderBy(new Expression('[[sort_order]] IS NULL'))
			->addOrderBy(['sort_order' => SORT_ASC])
			->all();

		return self::getItemsWithChildren($parentMenuItems, $columns, $status, $deleted);
	}

	/**
	 * Gets the menu items with their children.
	 *
	 * @param MenuItem[] $menuItems
	 * @param null|string|array $columns The columns of the each item.
	 * @param null|int $status The status of the record.
	 * @param null|int $deleted The deleted state of the record.
	 * @return array
	 */
	public static function getItemsWithChildren($menuItems, $columns = null, $status = null, $deleted = null)
	{
		$items = [];

		foreach ($menuItems as $menuItem) {
			$item = [
				'id' => $menuItem->id,
				'content' => $menuItem->page_id ? $menuItem->page->translation->title : $menuItem->translation->title,
				'model' => $menuItem,
			];

			if (!empty($columns)) {
				$item = array_intersect_key($item, array_fill_keys((array) $columns, null));
			}

			$childMenuItems = $menuItem->getMenuItems()
				->andWhere([
					'deleted' => $deleted ?: MenuItem::NO,
				])
				->andFilterWhere([
					'status' => $status,
				])
				->orderBy(new Expression('[[sort_order]] IS NULL'))
				->addOrderBy(['sort_order' => SORT_ASC])
				->all();
			if ($childMenuItems) {
				$item['items'] = self::getItemsWithChildren($childMenuItems, $columns, $status);
			}

			$items[] = $item;
		}

		return $items;
	}

	/**
	 * Model position labels.
	 *
	 * @return array
	 */
	public static function getPositionLabels()
	{
		return [
			self::POSITION_SIDEBAR => Yii::t('label', 'Sidebar'),
		];
	}

	/**
	 * Finds the default record by position or fallback to the first record.
	 *
	 * @param int $position The menu position.
	 * @param bool $fallbackToFirst Indicates if the first record should be returned when a default one does not exist.
	 * @return array|\yii\db\ActiveRecord|self|null
	 */
	public static function findDefaultMenuByPosition($position, $fallbackToFirst = true)
	{
		$query = static::find()
			->andWhere([
				'position' => $position,
				'status' => self::STATUS_ACTIVE,
				'deleted' => self::NO,
			])
			->limit(1);

		if (!($model = $query->andWhere(['default' => self::YES])->one())) {
			if ($fallbackToFirst === true) {
				$model = $query->one();
			}
		}

		return $model;
	}

	/**
	 * Gets the default top bar menu.
	 *
	 * @return array|\yii\db\ActiveRecord|self|null
	 */
	public static function findDefaultSidebarMenu()
	{
		return self::findDefaultMenuByPosition(self::POSITION_SIDEBAR);
	}

	public static function invalidateMenuCache($menuItemId)
	{
		$tags = [
			'getItemsWithParents',
			'getNestedItems',
			'getItemsWithChildren',
			'menu_item_' . $menuItemId,
		];
		foreach (\common\models\Language::findAllLanguages() as $language) {
			$tags[] = [
				'menu_item_' . $menuItemId . '_' . $language->id,
			];
		}
		TagDependency::invalidate(Yii::$app->cache, $tags);
	}
}
