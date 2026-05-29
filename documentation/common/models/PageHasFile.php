<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%page_has_file}}".
 *
 * @property int $page_id
 * @property int $file_id
 *
 * @property File $file
 * @property Page $page
 */
class PageHasFile extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%page_has_file}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['page_id', 'file_id'], 'required'],
			[['page_id', 'file_id'], 'integer'],
			[['page_id', 'file_id'], 'unique', 'targetAttribute' => ['page_id', 'file_id']],
			[['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => File::class, 'targetAttribute' => ['file_id' => 'id']],
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
			'file_id' => Yii::t('label', 'File ID'),
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
	public function getPage()
	{
		return $this->hasOne(Page::class, ['id' => 'page_id']);
	}
}
