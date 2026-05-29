<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%file_translation}}".
 *
 * @property int $file_id
 * @property string $language_id
 * @property string $title
 * @property int $deleted
 *
 * @property File $file
 * @property Language $language
 */
class FileTranslation extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%file_translation}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['file_id', 'language_id', 'title'], 'required'],
			[['file_id', 'deleted'], 'integer'],
			[['language_id'], 'string', 'max' => 5],
			[['title'], 'string', 'max' => 255],
			[['file_id', 'language_id'], 'unique', 'targetAttribute' => ['file_id', 'language_id']],
			[['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
			[['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'file_id' => Yii::t('label', 'File ID'),
			'language_id' => Yii::t('label', 'Language ID'),
			'title' => Yii::t('label', 'Title'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getFile()
	{
		return $this->hasOne(File::class, ['id' => 'file_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getLanguage()
	{
		return $this->hasOne(Language::class, ['language_id' => 'language_id']);
	}
}
