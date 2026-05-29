<?php

namespace common\models\master;

use Yii;
use yii\caching\TagDependency;

/**
 * @inheritdoc
 */
class Setting extends \common\models\Setting
{
	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
	}

	/**
	 * Finds the master application settings.
	 *
	 * @return array
	 */
	public static function findMasterAppSettings()
	{
		try {
			return static::getDb()->cache(function ($db) {
				$result = [];
				$appSettings = static::find()
					->where([
						'type' => self::TYPE_APP,
						'status' => self::STATUS_ACTIVE,
						'deleted' => self::NO,
					])
					->asArray()
					->all();

				foreach ($appSettings as $appSetting) {
					$result[$appSetting['name']] = @unserialize($appSetting['setting']);
				}

				return $result;
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Exception $e) {
			return [];
		} catch (\Throwable $e) {
			return [];
		}
	}
}
