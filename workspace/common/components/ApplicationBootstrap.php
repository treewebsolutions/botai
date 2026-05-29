<?php

namespace common\components;

use common\models\Language;
use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class ApplicationBootstrap extends Component implements BootstrapInterface
{
	/**
	 * @var array The application active languages.
	 */
	public $languages = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->languages = ArrayHelper::map(Language::findAllLanguages(), 'language', 'language_id');
	}

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 * @throws \Throwable
	 */
	public function bootstrap($app)
	{
		$this->initSettings();
		$this->initMailer();
		$this->initFormatter();

		$app->on(Application::EVENT_BEFORE_ACTION, function ($event) {
			$this->initFormatter();
		});

//		if (!($app instanceof \yii\console\Application)) {
//			$app->on(\yii\web\Application::EVENT_BEFORE_REQUEST, function ($event) {
//				$app = $event->sender;
//				if ($app->request->isPost && ($bodyParams = $app->request->getBodyParams())) {
//					$app->request->setBodyParams($bodyParams);
//				}
//				if ($app->request->isGet && ($queryParams = Yii::$app->request->getQueryParams())) {
//					$app->request->setQueryParams($queryParams);
//				}
//			});
//		}

		$app->on('invalidate.cache', function ($event) use ($app) {
			$cache = $app->cache;
			$initialCachePath = $cache->cachePath;
			$baseCachePath = FileHelper::normalizePath(dirname($app->basePath) . '/{{APP}}/runtime/cache');

			if (isset($event->key)) {
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'backend']);
				TagDependency::invalidate($cache, $event->key);
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'frontend']);
				TagDependency::invalidate($cache, $event->key);
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'console']);
				TagDependency::invalidate($cache, $event->key);
			} else {
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'backend']);
				$cache->flush();
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'frontend']);
				$cache->flush();
				$cache->cachePath = strtr($baseCachePath, ['{{APP}}' => 'console']);
				$cache->flush();
			}

			$cache->cachePath = $initialCachePath;
		});
	}

	public static function sanitize($value)
	{
		$value = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $value);
		$value = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $value);
		$value = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $value);
		$value = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $value);
		$value = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $value);
		$value = preg_replace('/[\x{1F1E6}-\x{1F1FF}]/u', '', $value);
		$value = preg_replace('/[\x{1F910}-\x{1F95E}]/u', '', $value);
		$value = preg_replace('/[\x{1F980}-\x{1F991}]/u', '', $value);
		$value = preg_replace('/[\x{1F9C0}]/u', '', $value);
		$value = preg_replace('/[\x{1F9F9}]/u', '', $value);
		$value = preg_replace('#<meta(.*?)>(.*?)</meta>#is', '', $value);
		$value = preg_replace('/<meta[^>]+\>/i', '', $value);
		$value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);
		$value = preg_replace('/<script[^>]+\>/i', '', $value);
		$value = preg_replace('#<iframe(.*?)>(.*?)</iframe>#is', '', $value);
		$value = preg_replace('/<iframe[^>]+\>/i', '', $value);
		$value = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $value);
		$value = preg_replace('/<style[^>]+\>/i', '', $value);
		return $value;
	}

	/**
	 * Strips HTML tags from a given data.
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public static function recursiveStripTags($data)
	{
		$allowableTags = [
			// basic
			// '!DOCTYPE', 'html', 'title', 'body',
			'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'p', 'br', 'hr',
			// formatting
			'acronym', 'abbr', 'address', 'b', 'bdi', 'bdo', 'big',
			'blockquote', 'center', 'cite', 'code', 'del', 'dfn', 'em',
			'font', 'i', 'ins', 'kbd', 'mark', 'meter', 'pre', 'progress',
			'q', 'rp', 'rt', 'ruby', 's', 'samp', 'small', 'strike', 'strong',
			'sub', 'sup', 'time', 'tt', 'u', 'var', 'wbr',
			// forms and input
			'form', 'input', 'textarea', 'button', 'select', 'optgroup', 'option',
			'label', 'fieldset', 'legend', 'datalist', 'keygen', 'output',
			// frames
			// 'frame', 'frameset', 'noframes', 'iframe',
			// images
			'img', 'map', 'area', 'canvas', 'figcaption', 'figure',
			// audio and video
			'audio', 'source', 'track', 'video',
			// links
			'a', 'link', 'nav',
			// lists
			'ul', 'ol', 'li', 'dir', 'dl', 'dt', 'dd', 'menu', 'menuitem',
			// tables
			'table', 'caption', 'th', 'tr', 'td', 'thead', 'tbody', 'tfoot', 'col', 'colgroup',
			// styles and semantics
			'style', 'div', 'span', 'header', 'footer', 'main', 'section', 'article',
			'aside', 'details', 'dialog', 'summary',
			// meta info
			// 'head', 'meta', 'base', 'basefont',
			// programming
			// 'script', 'noscript', 'applet', 'embed', 'object', 'param',
		];
		$allowableTags = '<' . implode('><', $allowableTags) . '>';
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = self::recursiveStripTags($value);
				} else {
					$data[$key] = self::sanitize($value);
					$data[$key] = strip_tags($value, $allowableTags);
				}
			}
		} else {
			$data = self::sanitize($data);
			$data = strip_tags($data, $allowableTags);
		}
		return $data;
	}

	/**
	 * Gets the language from the URL with fallback to the current language.
	 *
	 * @return string
	 */
	public function getUrlLanguage()
	{
		// Check only the first two segments
		$segments = array_slice(explode('/', Yii::$app->request->pathInfo), 0, 2);

		// Ensure that the segments match the language pattern
		$segments = array_filter($segments, function ($segment) {
			return strlen($segment) === 2;
		});

		// Return the language from the URL path, or fallback to the current app language
		foreach ($segments as $segment) {
			if (array_key_exists($segment, $this->languages)) {
				return $this->languages[$segment];
			}
		}

		return Yii::$app->language;
	}

	/**
	 * Initializes Settings component.
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initSettings()
	{
		/** @var \tws\settings\Settings $settings */
		$settings = Yii::$app->has('settings') ? Yii::$app->get('settings') : new \tws\settings\Settings();
		$settings->data = (array) \common\models\Setting::findAppSettings();
		Yii::$app->set('settings', $settings);

		/** @var \tws\settings\Settings $masterSettings */
		$masterSettings = Yii::$app->has('masterSettings') ? Yii::$app->get('masterSettings') : new \tws\settings\Settings();
		$masterSettings->data = (array) \common\models\master\Setting::findMasterAppSettings();
		Yii::$app->set('masterSettings', $masterSettings);

		// Set custom app properties
		Yii::$app->name = Yii::$app->settings->get('appName');

		// Set the default application language
		if (Yii::$app->language != Yii::$app->settings->get('defaultLanguage')) {
			Yii::$app->language = Yii::$app->settings->get('defaultLanguage') ?: Yii::$app->sourceLanguage;
		}
	}

	/**
	 * Initializes Formatter component.
	 * @link http://www.yiiframework.com/doc-2.0/yii-i18n-formatter.html
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initFormatter()
	{
		if (Yii::$app->has('formatter')) {
			/** @var \yii\i18n\Formatter $formatter */
			$formatter = Yii::$app->get('formatter');
			$settings = Yii::$app->settings->getCategory('general');

			if (!empty($settings['timeZone'])) {
				Yii::$app->setTimeZone($settings['timeZone']);
			}
			$formatter->timeZone = Yii::$app->getTimeZone();
			$formatter->defaultTimeZone = Yii::$app->getTimeZone();
			$formatter->timeFormat = $settings['timeFormat'] ?: 'HH:mm:ss';
			$formatter->dateFormat = $settings['dateFormat'] ?: 'yyyy-MM-dd';
			$formatter->datetimeFormat = $settings['datetimeFormat'] ?: 'yyyy-MM-dd HH:mm:ss';
			$formatter->currencyCode = $settings['currencyCode'] ?: 'USD';
			$formatter->nullDisplay = '';

			Yii::$app->set('formatter', $formatter);
		}
	}

	/**
	 * Initializes Mailer component.
	 * @link http://www.yiiframework.com/doc-2.0/yii-swiftmailer-mailer.html
	 *
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function initMailer()
	{
		if (Yii::$app->has('mailer')) {
			/** @var \yii\swiftmailer\Mailer $mailer */
			$mailer = Yii::$app->get('mailer');
			$settings = Yii::$app->settings->getCategory('email');
			$masterSettings = Yii::$app->masterSettings->getCategory('email');

			$mailer->messageConfig = array_merge($mailer->messageConfig, array_filter([
				'from' => !empty($settings['from']) ? [$settings['from'] => Yii::$app->name] : [$masterSettings['from'] => Yii::$app->name],
				'replyTo' => !empty($settings['replyTo']) ? [$settings['replyTo'] => Yii::$app->name] : null,
			]));
			if ($settings['useCustomSMTP']) {
				$mailer->setTransport([
					'class' => 'Swift_SmtpTransport',
					'host' => $settings['host'],
					'port' => $settings['port'],
					'encryption' => $settings['encryption'],
					'username' => $settings['username'],
					'password' => $settings['password'],
				]);
			} else {
				$mailer->setTransport([
					'class' => 'Swift_SmtpTransport',
					'host' => $masterSettings['host'],
					'port' => $masterSettings['port'],
					'encryption' => $masterSettings['encryption'],
					'username' => $masterSettings['username'],
					'password' => $masterSettings['password'],
				]);
			}

			Yii::$app->set('mailer', $mailer);
		}
	}
}
