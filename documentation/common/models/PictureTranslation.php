<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%picture_translation}}".
 *
 * @property int $picture_id
 * @property string $language_id
 * @property string $title
 * @property int $deleted
 *
 * @property Language $language
 * @property Picture $picture
 */
class PictureTranslation extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%picture_translation}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['picture_id', 'language_id', 'title'], 'required'],
			[['picture_id', 'deleted'], 'integer'],
			[['language_id'], 'string', 'max' => 5],
			[['title'], 'string', 'max' => 255],
			[['picture_id', 'language_id'], 'unique', 'targetAttribute' => ['picture_id', 'language_id']],
			[['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
			[['picture_id'], 'exist', 'skipOnError' => true, 'targetClass' => Picture::class, 'targetAttribute' => ['picture_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'picture_id' => Yii::t('label', 'Picture ID'),
			'language_id' => Yii::t('label', 'Language ID'),
			'title' => Yii::t('label', 'Title'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getLanguage()
	{
		return $this->hasOne(Language::class, ['language_id' => 'language_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getPicture()
	{
		return $this->hasOne(Picture::class, ['id' => 'picture_id']);
	}
}
