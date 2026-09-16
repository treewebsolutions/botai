<?php

namespace common\components;

use Yii;

/**
 * Files a scraped page under the first segment of its URL path.
 *
 * A scraped page carries no category of its own - unlike a listing, which is filed under
 * one when it is written - but the path already says where it sits: /servicii/implant/ is
 * under "servicii", /despre-noi/ is under "despre-noi". The segment travels with the page
 * into the vector store as a file attribute, so an answer can be narrowed to the part of
 * the site that would hold it.
 *
 * The segment is taken as it is written, in whatever language the site uses. Nothing is
 * translated or mapped onto a fixed list: a section is whatever the site calls it.
 *
 * A language prefix is the one thing that is stepped over. A multilingual site puts the
 * language first - /ro/servicii/, /en/services/ - and filing those pages under "ro" and
 * "en" would say what language they are in and nothing about what they are.
 */
class PageUrlClassifier
{
	/**
	 * The home page: a URL with no path of its own.
	 */
	const CATEGORY_HOME = 'home';

	/**
	 * Path segments that belong to the machinery of a site rather than to its content, and
	 * would otherwise be read as sections.
	 */
	const IGNORED_SEGMENTS = ['wp-content', 'wp-admin', 'wp-includes', 'wp-json', 'uploads', 'feed', 'amp', 'page', 'index.php'];

	/**
	 * The shape of a language prefix for a language the application has not been told
	 * about: two letters, optionally followed by a region ("ro", "en-gb", "pt_BR").
	 *
	 * Deliberately not three letters: "cat" and "art" are sections, not languages.
	 */
	const LANGUAGE_SEGMENT_PATTERN = '/^[a-z]{2}([-_][a-z]{2,4})?$/';

	/**
	 * The section a URL sits in: the first segment of its path.
	 *
	 * @param string|null $url
	 * @return string the segment as the site writes it, or `home`
	 */
	public static function classify($url): string
	{
		$segments = static::segments($url);
		if ($segments !== [] && static::isLanguageSegment($segments[0])) {
			// /ro/servicii/ is the services section in Romanian, not the "ro" section.
			// /ro/ on its own is the Romanian home page, and drops to home below.
			array_shift($segments);
		}

		return $segments === [] ? static::CATEGORY_HOME : $segments[0];
	}

	/**
	 * Whether a segment is a language prefix rather than a section.
	 *
	 * The languages the application is configured for answer first, since those are the
	 * ones it knows by name. A site may well be published in a language nobody configured
	 * here, so a segment shaped like a language code counts too.
	 *
	 * @param string $segment already lowercased by {@see segments()}
	 * @return bool
	 */
	public static function isLanguageSegment($segment): bool
	{
		$segment = trim((string) $segment);
		if ($segment === '') {
			return false;
		}

		foreach (static::configuredLanguages() as $language) {
			if ($segment === $language) {
				return true;
			}
		}

		return (bool) preg_match(static::LANGUAGE_SEGMENT_PATTERN, $segment);
	}

	/**
	 * The language codes the application is configured for, both short and full
	 * ("ro" and "ro-ro"), lowercased.
	 *
	 * @return string[]
	 */
	public static function configuredLanguages(): array
	{
		$configured = Yii::$app->params['languages'] ?? [];
		if (!is_array($configured)) {
			return [];
		}

		$codes = [];
		foreach ($configured as $short => $locale) {
			$codes[] = strtolower(trim((string) $short));
			$codes[] = strtolower(trim((string) $locale));
		}

		return array_values(array_unique(array_filter($codes)));
	}

	/**
	 * The path segments of a URL, lowercased, with the machinery dropped.
	 *
	 * @param string|null $url
	 * @return string[]
	 */
	public static function segments($url): array
	{
		$path = (string) parse_url(trim((string) $url), PHP_URL_PATH);
		$path = strtolower(trim($path, "/ \t"));
		if ($path === '') {
			return [];
		}

		$segments = [];
		foreach (explode('/', $path) as $segment) {
			$segment = trim(urldecode($segment));
			if ($segment === '' || in_array($segment, static::IGNORED_SEGMENTS, true)) {
				continue;
			}
			$segments[] = $segment;
		}

		return $segments;
	}
}
