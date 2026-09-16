<?php

namespace common\components;

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
	 * The section a URL sits in: the first segment of its path.
	 *
	 * @param string|null $url
	 * @return string the segment as the site writes it, or `home`
	 */
	public static function classify($url): string
	{
		$segments = static::segments($url);

		return $segments === [] ? static::CATEGORY_HOME : $segments[0];
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
