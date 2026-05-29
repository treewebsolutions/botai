<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%menu_item}}".
 *
 * @property int $id
 * @property int $menu_id
 * @property int $parent_id
 * @property int $page_id
 * @property string $icon
 * @property string $target
 * @property int $sort_order
 * @property int $excluded
 * @property string $options
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Menu $menu
 * @property MenuItem $parent
 * @property MenuItem[] $menuItems
 * @property Page $page
 * @property MenuItemTranslation[] $menuItemTranslations
 * @property MenuItemTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class MenuItem extends CommonActiveRecord
{
	private $items = [];
	
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%menu_item}}';
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
			'PositionBehavior' => [
				'class' => PositionBehavior::class,
				'positionAttribute' => 'sort_order',
				'groupAttributes' => ['menu_id'],
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
			[['menu_id', 'status'], 'required'],
			[['menu_id', 'parent_id', 'page_id', 'sort_order', 'excluded', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['options'], 'string'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
			[['icon', 'target'], 'string', 'max' => 255],
			[['menu_id'], 'exist', 'skipOnError' => true, 'targetClass' => Menu::class, 'targetAttribute' => ['menu_id' => 'id']],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => MenuItem::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::class, 'targetAttribute' => ['page_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'menu_id' => Yii::t('label', 'Menu ID'),
			'parent_id' => Yii::t('label', 'Parent ID'),
			'page_id' => Yii::t('label', 'Page ID'),
			'icon' => Yii::t('label', 'Icon'),
			'target' => Yii::t('label', 'Target'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'excluded' => Yii::t('label', 'Excluded'),
			'options' => Yii::t('label', 'Options'),
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
	public function getMenu()
	{
		return $this->hasOne(Menu::class, ['id' => 'menu_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(MenuItem::class, ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMenuItems()
	{
		return $this->hasMany(MenuItem::class, ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPage()
	{
		return $this->hasOne(Page::class, ['id' => 'page_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getMenuItemTranslations()
	{
		return $this->hasMany(MenuItemTranslation::class, ['menu_item_id' => 'id']);
	}

	/**
	 * Gets the model translation.
	 *
	 * @param null|string $language
	 * @return null|MenuItemTranslation
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->menuItemTranslations, 'language_id')[$language];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%menu_item_translation}}', ['menu_item_id' => 'id']);
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
	 * Gets the nested slug.
	 *
	 * @param null|string $language The language of the slug.
	 * @param string $glue The string used for concatenation.
	 * @return string
	 */
	public function getNestedSlug($language = null, $glue = '/')
	{
		$menuItems = self::getItemsWithParents($this->id, $language);
		$slug = [];

		foreach ($menuItems as $menuItem) {
			$menuItemTranslation = $menuItem->getTranslation($language);
			if ($menuItemTranslation->slug) {
				$slug[] = $menuItemTranslation->slug;
			}
		}

		return ltrim(implode($glue, $slug), $glue);
	}

	/**
	 * Gets the HTML options for this menu item.
	 *
	 * @param string $target Options target. It could be 'item' or 'link'.
	 * @return array
	 */
	public function getHtmlOptions($target = 'item')
	{
		$options = $this->getUnserializedValue('options', []);
		$options = $options[$target];

		if (!is_array($options)) {
			return [];
		}

		return [
			'style' => [
				'color' => $options['color'],
			],
		];
	}

	/**
	 * Gets the related nested models.
	 *
	 * @param int $id The model ID.
	 * @param null|string $language The language of record.
	 * @return self[]
	 */
	public static function getItemsWithParents($id, $language = null)
	{
		$tree = [];

		if (!$id) {
			return $tree;
		}

		/** @var self $model */
		$model = static::find()
			->alias('mi')
			->joinWith([
				'menuItemTranslations mit' => function (ActiveQuery $query) use ($language) {
					return $query->andOnCondition(['mit.language_id' => $language ?: Yii::$app->language]);
				},
			])
			->where([
				'mi.id' => $id,
				'mi.status' => self::STATUS_ACTIVE,
				'mi.deleted' => self::NO,
			])
			->one();

		$tree[$model->id] = $model->page ?: $model;

		if ($model->parent_id) {
			$tree = array_merge($tree, array_reverse(self::getItemsWithParents($model->parent_id, $language)));
		}

		return array_reverse(array_filter($tree));
	}


	/**
	 * @param $parent_id
	 * @param int $level
	 * @param null $status
	 * @param array $params
	 * @return array
	 */
	public static function getItemsByParent($parent_id, $status = null, $menu_id = null, $params = [])
	{
		$children = [];
		/** @var static[] $items */
		$query = static::find()
			->alias('i')
			->joinWith([
				'menuItemTranslations it' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'it.language_id' => Yii::$app->language,
						'it.deleted' => self::NO,
					]);
				},
				'page.pageTranslations pt' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'pt.language_id' => Yii::$app->language,
						'pt.deleted' => self::NO,
					]);
				},
			])
			->where([
				'i.menu_id' => $menu_id,
				'i.parent_id' => $parent_id,
				'i.deleted' => self::NO,
			]);
		if ($status) {
			$query->andWhere([
				'i.status' => $status
			]);
		}

		if (isset($params['search']) && !empty($params['search'])) {
			$searchWord = $params['search'];
			$searchPattern = '[[:<:]]' . preg_quote($searchWord) . '[[:>:]]';
			$titleRegexp = new Expression("it.title COLLATE utf8_general_ci REGEXP :pattern", [':pattern' => $searchPattern]);
			$contentRegexp = new Expression("pt.content COLLATE utf8_general_ci REGEXP :pattern", [':pattern' => $searchPattern]);
			$query->andWhere(['or', $titleRegexp, $contentRegexp]);
		}

		$query->orderBy(new Expression('[[i.sort_order]] IS NULL'));
		$query->addOrderBy(['i.sort_order' => SORT_ASC]);
		$items = $query->all();

		foreach ($items as $item) {
			$children[] = $item;
		}

		return $children;
	}


	/**
	 * @param $item_id
	 * @param null $status
	 * @return MenuItem[]
	 */
	public static function getParents($item_id, $status = null, $menu_id = null)
	{
		$query = static::find()
			->alias('i')
			->joinWith([
				'menuItemTranslations it' => function (ActiveQuery $query) {
					$query->andOnCondition([
						'it.language_id' => Yii::$app->language,
						'it.deleted' => self::NO,
					]);
				},
			])
			->where([
				'i.id' => $item_id,
				'i.menu_id' => $menu_id,
				'i.deleted' => self::NO,
			]);
		if ($status) {
			$query->andWhere([
				'i.status' => $status
			]);
		}
		$query->orderBy(new Expression('[[i.sort_order]] IS NULL'));
		$query->addOrderBy(['i.sort_order' => SORT_ASC]);
		$parent = $query->one();
		if ($parent->parent_id) {
			array_push($this->items, $parent);
			static::getParents($parent->parent_id, $status, $menu_id);
		}
		return $this->items;
	}

	/**
	 * Treeview
	 */
	public static function tree($parent_id, $current = null, $status = null, $menu_id = null, $params = [])
	{
		$items = static::getItemsByParent($parent_id, $status, $menu_id, $params);
		$output = [];
		if (!empty($items)) {
			foreach ($items as $item) {
				$children = self::tree($item->id, $current, $status, $menu_id, $params);
				if (empty($children)) {
					$url = implode('', array_filter([
						\tws\helpers\Url::base(true),
						(Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' : '/' . (in_array((mb_substr(Yii::$app->language, 0, 2)), ['en']) && !in_array(Yii::$app->language, ['en-US']) ? Yii::$app->language : (mb_substr(Yii::$app->language, 0, 2)))),
						'/' . (
						(strpos($item->translation->url, '#') === 0)
							? $item->page->translation->slug . $item->translation->url
							: ($item->translation->url ?: $item->page->translation->slug)
						),
					]));
					$output[] = [
						'icon' => $item->icon,
						'label' => $item->translation->title ?: $item->page->translation->title,
						'active' => \tws\helpers\Url::current([], true) == $url,
						'url' => $url,
					];
				} else {
					$output[] = [
						'icon' => $item->icon,
						'label' => $item->translation->title ?: $item->page->translation->title,
						'url' => '#',
						'items' => $children,
					];
				}
			}
		}

		return $output;
	}
}
