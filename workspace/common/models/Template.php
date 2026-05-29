<?php

namespace common\models;

use tws\behaviors\DefaultBehavior;
use DateTime;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%template}}".
 *
 * @property int $id
 * @property int $type
 * @property int $variant
 * @property int $section
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property TemplateTranslation[] $templateTranslations
 * @property TemplateTranslation $translation
 * @property Language[] $languages
 * @property User $creator
 * @property User $updater
 */
class Template extends CommonActiveRecord
{
	const TYPE_EMAIL = 10;
	const EMAIL_VARIANT_ACCOUNT_ACTIVATION = 101;
	const EMAIL_VARIANT_PASSWORD_RESET = 102;
	const EMAIL_VARIANT_WELCOME = 103;

	const TYPE_DOCUMENT = 20;
	const DOCUMENT_TEMPORARY_EMPLOYMENT_CONTRACT = 201;


	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%template}}';
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
				'value' => (new DateTime)->format('Y-m-d H:i:s'),
			],
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'groupAttributes' => ['type', 'variant'],
				'ensureDefaultValue' => true,
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
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['type', 'status'], 'required'],
			[['type', 'variant', 'section', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'type' => Yii::t('label', 'Type'),
			'variant' => Yii::t('label', 'Variant'),
			'section' => Yii::t('label', 'Section'),
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
	public function getTemplateTranslations()
	{
		return $this->hasMany(TemplateTranslation::class, ['template_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getLanguages()
	{
		return $this->hasMany(Language::class, ['language_id' => 'language_id'])->viaTable('{{%template_translation}}', ['template_id' => 'id']);
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
	 * Gets the model translation.
	 *
	 * @param null|string $language
	 * @return null|TemplateTranslation
	 */
	public function getTranslation($language = null)
	{
		if ($language === null) {
			$language = Yii::$app->language;
		}
		return ArrayHelper::index($this->templateTranslations, 'language_id')[$language];
	}

	/**
	 * Model variant labels.
	 *
	 * @return array
	 */
	public static function getVariantLabels()
	{
		return [
//			static::TYPE_EMAIL => [
//				static::EMAIL_VARIANT_ACCOUNT_ACTIVATION => Yii::t('common', 'Account Activation'),
//				static::EMAIL_VARIANT_PASSWORD_RESET => Yii::t('common', 'Password Reset'),
//				static::EMAIL_VARIANT_WELCOME => Yii::t('common', 'Welcome'),
//			],
			static::TYPE_DOCUMENT => [
				static::DOCUMENT_TEMPORARY_EMPLOYMENT_CONTRACT => Yii::t('common', 'Temporary Employment Contract'),
			],
		];
	}

	/**
	 * Finds default record by type.
	 * A fallback to a non-default record can be returned.
	 *
	 * @param int $type
	 * @param bool $fallback Flag that indicates if a non-default record should be returned instead.
	 * @return array|\yii\db\ActiveRecord|null|static
	 */
	public static function findDefaultByType($type, $fallback = true)
	{
		$query = static::find()
			->where([
				'type' => $type,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->limit(1);

		if ((!$model = $query->andWhere(['default' => static::YES])->one())) {
			if ($fallback === true) {
				$model = $query->one();
			}
		}

		return $model;
	}

	/**
	 * Finds default record by type and variant.
	 * A fallback to a non-default record can be returned.
	 *
	 * @param int $type
	 * @param int $variant
	 * @param bool $fallback Flag that indicates if a non-default record should be returned instead.
	 * @return array|\yii\db\ActiveRecord|null|static
	 */
	public static function findDefaultByTypeAndVariant($type, $variant, $fallback = true)
	{
		$query = static::find()
			->where([
				'type' => $type,
				'variant' => $variant,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->limit(1);

		if ((!$model = $query->andWhere(['default' => static::YES])->one())) {
			if ($fallback === true) {
				$model = $query->one();
			}
		}

		return $model;
	}

	/**
	 * Gets the mapped model type with the owner model type.
	 *
	 * @param null|string $group The group name.
	 * @param null|int $ownerModelType The owner model type value.
	 * @return string
	 */
	public static function getType($group, $ownerModelType)
	{
		switch ($group) {
			case 'document':
				$typeMap = [
					self::DOCUMENT_TEMPORARY_EMPLOYMENT_CONTRACT => self::TYPE_DOCUMENT,
				];
				break;
			default:
				$typeMap = [];
				break;
		}

		return $typeMap[$ownerModelType];
	}

	/**
	 * Finds all active records.
	 *
	 * @return mixed
	 * @throws \Throwable
	 */
	public static function findAllTemplates()
	{
		return static::getDb()->cache(function ($db) {
			$templates = static::find()
				->with('templateTranslations')
				->andWhere([
					'status' => self::STATUS_ACTIVE,
					'deleted' => self::NO,
				])
				->all();

			return ArrayHelper::index($templates, null, 'type');
		}, 0, new TagDependency(['tags' =>  __FUNCTION__]));
	}

	public static function queryTemplatesByType($type)
	{
		try {
			return static::find()
				->alias('t')
				->select([
					"t.id",
					"tt.name",
				])
				->joinWith([
					'templateTranslations tt',
				])
				->andWhere([
					't.status' => self::STATUS_ACTIVE,
					't.deleted' => self::NO,
					't.variant' => $type,
				])
				->createCommand()
				->queryAll();
		} catch (\Exception $e) {
			return [];
		}
	}
}
