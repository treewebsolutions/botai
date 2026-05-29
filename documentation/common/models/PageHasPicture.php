<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%page_has_picture}}".
 *
 * @property int $page_id
 * @property int $picture_id
 *
 * @property Picture $picture
 * @property Page $page
 */
class PageHasPicture extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%page_has_picture}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['page_id', 'picture_id'], 'required'],
			[['page_id', 'picture_id'], 'integer'],
			[['page_id', 'picture_id'], 'unique', 'targetAttribute' => ['page_id', 'picture_id']],
			[['picture_id'], 'exist', 'skipOnError' => true, 'targetClass' => Picture::class, 'targetAttribute' => ['picture_id' => 'id']],
			[['page_id'], 'exist', 'skipOnError' => true, 'targetClass' => Page::class, 'targetAttribute' => ['page_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'page_id' => Yii::t('label', 'Page ID'),
			'picture_id' => Yii::t('label', 'Picture ID'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPicture()
	{
		return $this->hasOne(Picture::class, ['id' => 'picture_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPage()
	{
		return $this->hasOne(Page::class, ['id' => 'page_id']);
	}
}
