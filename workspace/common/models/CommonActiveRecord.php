<?php

namespace common\models;

use Yii;

/**
 * This is the base model class for the common ActiveRecord models.
 */
class CommonActiveRecord extends \yii\db\ActiveRecord
{
	const NO = 0;
	const YES = 1;

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;

	const GENDER_MALE = 1;
	const GENDER_FEMALE = 2;

	/**
	 * @inheritdoc
	 */
	public static function find()
	{
		return new CommonActiveQuery(get_called_class());
	}

	/**
	 * @inheritdoc
	 * @param bool $isPermanent Flag that indicates if the deletion is forced to be permanent.
	 */
	public function delete($isPermanent = false)
	{
		if (!$isPermanent && Yii::$app->settings->get('enableSoftDelete') && $this->getBehavior('SoftDeleteBehavior')) {
			// Detach PositionBehavior because it breaks the sort_order on soft delete
			if ($this->getBehavior('PositionBehavior')) {
				$this->detachBehavior('PositionBehavior');
			}
			return parent::softDelete();
		}
		return parent::delete();
	}

	/**
	 * Restores a deleted record.
	 * No database query will be performed if the record is not marked as deleted.
	 *
	 * @return bool
	 */
	public function restore()
	{
		if ($this->hasAttribute('deleted')) {
			if ($this->getAttribute('deleted') == static::NO) {
				return true;
			}
			return (bool) $this->updateAttributes(['deleted' => static::NO]);
		}
		return false;
	}

	/**
	 * Gets the unserialized value of an attribute.
	 *
	 * @param string $attribute
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getUnserializedValue($attribute, $defaultValue = null)
	{
		if ($this->hasAttribute($attribute)) {
			return @unserialize($this->$attribute) ?: $defaultValue;
		}
		return $defaultValue;
	}

	/**
	 * Sets the serialized value for an attribute.
	 *
	 * @param string $attribute
	 * @param mixed $value
	 * @return $this
	 */
	public function setSerializedValue($attribute, $value)
	{
		if ($this->hasAttribute($attribute)) {
			$unserializedValue = $this->getUnserializedValue($attribute);
			if (is_array($unserializedValue) && is_array($value)) {
				$this->$attribute = @serialize(array_merge($unserializedValue, $value));
			} else {
				$this->$attribute = @serialize($value);
			}
		}
		return $this;
	}

	/**
	 * Model status labels.
	 *
	 * @return array
	 */
	public static function getStatusLabels()
	{
		return [
			self::STATUS_INACTIVE => [
				'label' => Yii::t('label', 'Inactive'),
				'color' => 'danger',
			],
			self::STATUS_ACTIVE => [
				'label' => Yii::t('label', 'Active'),
				'color' => 'success',
			],
		];
	}

	/**
	 * Model boolean labels.
	 *
	 * @return array
	 */
	public static function getBooleanLabels()
	{
		return [
			self::NO => Yii::t('yii', 'No'),
			self::YES => Yii::t('yii', 'Yes'),
		];
	}

	/**
	 * Model gender labels.
	 *
	 * @return array
	 */
	public static function getGenderLabels()
	{
		return [
			self::GENDER_MALE => Yii::t('label', 'Male'),
			self::GENDER_FEMALE => Yii::t('label', 'Female'),
		];
	}

	/**
	 * Ensures a translation value for a given set of data.
	 *
	 * @param array $data Translation source data.
	 * @param string $language Requested language of the translation.
	 * @param string $defaultLanguage Default language to fallback if the requested language translation does not exist.
	 * @param string $prefix A string prefix for missing translation.
	 * @return null|string
	 */
	public static function ensureTranslationValue($data, $language, $defaultLanguage = 'en-US', $prefix = '***MISSING_TRANSLATION***')
	{
		if (!is_array($data)) {
			return null;
		}

		// Return translation for requested language, if exist
		if (!empty($data[$language])) {
			return trim($data[$language]);
		}

		// Return translation for default language, if exist
		if (!empty($data[$defaultLanguage])) {
			return $prefix . ' ' . trim($data[$defaultLanguage]);
		}

		// Get the first non-empty translation from the data
		foreach ($data as $language_id => $translation) {
			if (!empty($translation)) {
				return $prefix . ' ' . trim($translation);
			}
		}

		return $prefix;
	}

	protected function getCustomErrorMessage(\Exception $e)
	{
		if (YII_ENV === 'dev' && YII_DEBUG === true) {
			$message = $e->getMessage();
		} else {
			switch (true) {
				// HTTP Exceptions:
				case $e instanceof yii\web\HttpException:
					$message = Yii::t("common", "An error occurred while processing your request.");
					break;
				case $e instanceof yii\web\NotFoundHttpException:
					$message = Yii::t("common", "Sorry, we couldn't find the page you're looking for.");
					break;
				case $e instanceof yii\web\UnauthorizedHttpException:
					$message = Yii::t("common", "You are not authorized to access this page.");
					break;
				case $e instanceof yii\web\ForbiddenHttpException:
					$message = Yii::t("common", "Access to this page is forbidden.");
					break;
				case $e instanceof yii\web\BadRequestHttpException:
					$message = Yii::t("common", "The requested method is not allowed for this page.");
					break;
				case $e instanceof yii\web\MethodNotAllowedHttpException:
					$message = Yii::t("common", "Something went wrong. We are working on the issue. Please try again later.");
					break;
				// Database Exceptions:
				case $e instanceof yii\db\Exception:
					$message = Yii::t("common", "We're experiencing a temporary database issue. Please try again later.");
					break;
				case $e instanceof yii\db\IntegrityException:
					$message = Yii::t("common", "We're unable to process your request due to data integrity constraints.");
					break;
				case $e instanceof yii\db\StaleObjectException:
					$message = Yii::t("common", "The information you're viewing may be outdated. Please refresh the page.");
					break;
				// Validation Exceptions:
				case $e instanceof yii\base\InvalidValueException:
					$message = Yii::t("common", "Please check your input and try again.");
					break;
				// Core Exception Classes:
				case $e instanceof yii\base\Exception:
					$message = Yii::t("common", "Something went wrong. We are working on the issue. Please try again later.");
					break;
				case $e instanceof yii\base\UserException:
					$message = Yii::t("common", "Oops! Please check your input and try again.");
					break;
				case $e instanceof yii\base\InvalidConfigException:
					$message = Yii::t("common", "There seems to be an error in the configuration. Please contact support.");
					break;
				case $e instanceof yii\base\NotSupportedException:
					$message = Yii::t("common", "The requested action is not currently supported.");
					break;
				case $e instanceof yii\base\UnknownMethodException:
					$message = Yii::t("common", "The requested function is unavailable.");
					break;
				// Other types of exceptions:
				default:
					$message = Yii::t("common", "An error occurred while processing your request.");
					break;

			}
		}
		return $message;
	}
}
