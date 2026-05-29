<?php

namespace common\models;

use Yii;
use yii2tech\ar\position\PositionBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%import_column}}".
 *
 * @property int $id
 * @property int $sheet_id
 * @property string $target
 * @property string $source
 * @property int $source_index
 * @property string $field_type
 * @property int $sort_order
 * @property int $deleted
 *
 * @property ImportSheet $sheet
 */
class ImportColumn extends CommonActiveRecord
{
	const FIELD_TYPE_STRING = 'string';
	const FIELD_TYPE_NUMBER = 'number';
	const FIELD_TYPE_DATE = 'date';

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%import_column}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'PositionBehavior' => [
				'class' => PositionBehavior::class,
				'positionAttribute' => 'sort_order',
				'groupAttributes' => ['type']
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => self::YES,
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
			[['sheet_id'], 'required'],
			[['sheet_id', 'source_index', 'sort_order', 'deleted'], 'integer'],
			[['target', 'source', 'field_type'], 'string', 'max' => 255],
			[['sheet_id'], 'exist', 'skipOnError' => true, 'targetClass' => ImportSheet::class, 'targetAttribute' => ['sheet_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'sheet_id' => Yii::t('label', 'Sheet ID'),
			'target' => Yii::t('label', 'Target'),
			'source' => Yii::t('label', 'Source'),
			'source_index' => Yii::t('label', 'Source Index'),
			'field_type' => Yii::t('label', 'Field Type'),
			'sort_order' => Yii::t('label', 'Sort Order'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSheet()
	{
		return $this->hasOne(ImportSheet::class, ['id' => 'sheet_id']);
	}

	/**
	 * Model field type labels.
	 *
	 * @return array
	 */
	public static function getFieldTypeLabels()
	{
		return [
			self::FIELD_TYPE_STRING => Yii::t('label', 'String'),
			self::FIELD_TYPE_NUMBER => Yii::t('label', 'Number'),
			self::FIELD_TYPE_DATE => Yii::t('label', 'Calendar Date'),
		];
	}
}
