<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%template_translation}}".
 *
 * @property int $template_id
 * @property string $language_id
 * @property string $name
 * @property string $subject
 * @property string $header
 * @property string $footer
 * @property string $content
 * @property int $deleted
 *
 * @property Language $language
 * @property Template $template
 */
class TemplateTranslation extends CommonActiveRecord
{
	const NO = 0;
	const YES = 1;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%template_translation}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['template_id', 'language_id', 'name'], 'required'],
			[['template_id', 'deleted'], 'integer'],
			[['header', 'footer', 'content'], 'string'],
			[['language_id'], 'string', 'max' => 5],
			[['name', 'subject'], 'string', 'max' => 255],
			[['template_id', 'language_id'], 'unique', 'targetAttribute' => ['template_id', 'language_id']],
			[['language_id'], 'exist', 'skipOnError' => true, 'targetClass' => Language::class, 'targetAttribute' => ['language_id' => 'language_id']],
			[['template_id'], 'exist', 'skipOnError' => true, 'targetClass' => Template::class, 'targetAttribute' => ['template_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'template_id' => Yii::t('label', 'Template ID'),
			'language_id' => Yii::t('label', 'Language ID'),
			'name' => Yii::t('label', 'Name'),
			'subject' => Yii::t('label', 'Subject'),
			'header' => Yii::t('label', 'Header'),
			'footer' => Yii::t('label', 'Footer'),
			'content' => Yii::t('label', 'Content'),
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
	public function getTemplate()
	{
		return $this->hasOne(Template::class, ['id' => 'template_id']);
	}
}
