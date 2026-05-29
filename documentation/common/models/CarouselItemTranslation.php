<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%carousel_item_translation}}".
 *
 * @property int $carousel_item_id
 * @property string $language_id
 * @property string $title
 * @property string $content
 * @property string $anchor
 * @property string $url
 * @property int $deleted
 *
 * @property CarouselItem $carouselItem
 * @property Language $language
 */
class CarouselItemTranslation extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%carousel_item_translation}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['carousel_item_id', 'language_id'], 'required'],
			[['carousel_item_id', 'deleted'], 'integer'],
			[['content'], 'string'],
			[['language_id'], 'string', 'max' => 5],
			[['title', 'anchor', 'url'], 'string', 'max' => 255],
			[['carousel_item_id', 'language_id'], 'unique', 'targetAttribute' => ['carousel_item_id', 'language_id']],
			[['carousel_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => CarouselItem::class, 'targetAttribute' => ['carousel_item_id' => 'id']],
			[['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'carousel_item_id' => Yii::t('label', 'Carousel Item ID'),
			'language_id' => Yii::t('label', 'Language ID'),
			'title' => Yii::t('label', 'Title'),
			'content' => Yii::t('label', 'Content'),
			'anchor' => Yii::t('label', 'Anchor'),
			'url' => Yii::t('label', 'URL'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCarouselItem()
	{
		return $this->hasOne(CarouselItem::class, ['id' => 'carousel_item_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getLanguage()
	{
		return $this->hasOne(Language::class, ['language_id' => 'language_id']);
	}
}
