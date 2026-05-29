<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%import_sheet}}".
 *
 * @property int $id
 * @property int $file_id
 * @property int $number
 * @property int $header
 * @property int $deleted
 *
 * @property ImportColumn[] $columns
 * @property ImportFile $file
 */
class ImportSheet extends CommonActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%import_sheet}}';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['file_id'], 'required'],
			[['file_id', 'number', 'header', 'deleted'], 'integer'],
			[['file_id'], 'exist', 'skipOnError' => true, 'targetClass' => ImportFile::class, 'targetAttribute' => ['file_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'file_id' => Yii::t('label', 'File ID'),
			'number' => Yii::t('label', 'Number'),
			'header' => Yii::t('label', 'Header'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getColumns()
	{
		return $this->hasMany(ImportColumn::class, ['sheet_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getFile()
	{
		return $this->hasOne(ImportFile::class, ['id' => 'file_id']);
	}
}
