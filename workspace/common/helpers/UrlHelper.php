<?php

namespace common\helpers;

use Yii;

class UrlHelper
{
	/**
	 * Gets the host url.
	 *
	 * @return string
	 */
	public static function getHostUrl()
	{
		return Yii::$app->getRequest()->getHostInfo();
	}

	/**
	 * Gets the host url without the protocol.
	 *
	 * @return string
	 */
	public static function getRelativeHostUrl()
	{
		return preg_replace('#^https?:#', '', self::getHostUrl());
	}

	/**
	 * Gets the upload directory url.
	 *
	 * @return string
	 */
	public static function getUploadsUrl()
	{
		return self::getHostUrl() . '/uploads';
	}


	/**
	 *  Check file url.
	 *
	 * @param $url
	 * @return bool
	 */
	public static function checkFileUrl($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return ($code == 200);
	}
}