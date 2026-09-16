<?php

namespace common\models\master;

use Yii;

/**
 * The hub's integration records, read from the master database.
 *
 * A tenant that has not configured an OpenAI key of its own falls back to the platform's,
 * so the chat works before anybody sets anything up. Only reading is ever done here - the
 * record belongs to the hub and is managed from its settings screens.
 *
 * @inheritdoc
 */
class Integration extends \common\models\Integration
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
	 * The platform's OpenAI integration, or null when the hub has none either.
	 *
	 * Wrapped because the master database is a separate connection: a hub that is
	 * unreachable must leave the tenant without a key, not take the request down.
	 *
	 * @return static|null
	 */
	public static function findPlatformOpenAI()
	{
		try {
			return static::findOpenAI();
		} catch (\Throwable $e) {
			Yii::warning('Could not read the platform OpenAI integration: ' . $e->getMessage(), __METHOD__);

			return null;
		}
	}
}
