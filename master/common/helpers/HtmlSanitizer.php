<?php

namespace common\helpers;

use yii\helpers\HtmlPurifier;

/**
 * Allow-list sanitiser for rich text that came from outside the admin.
 *
 * The support-ticket editors are open to every registered customer, and what they write
 * is printed as HTML in the helpdesk screens an administrator reads. Nothing sanitised
 * it, in either direction, which made a ticket the shortest path from an ordinary
 * account into an administrator's session.
 *
 * Same shape as the workspace app's Announcement::sanitizeContentHtml(): keep exactly
 * the formatting the editor offers and drop everything else, rather than hunting for
 * dangerous constructs. A blocklist of `<script>` and friends — which is what the
 * workspace's older ApplicationBootstrap::sanitize() does — loses to the next encoding
 * trick; an allow-list only ever lets through what it was told to.
 *
 * Purifying on the way in would leave whatever is already stored, so callers apply this
 * at save time and the helpdesk views apply it again on the way out.
 */
class HtmlSanitizer
{
	/**
	 * The formatting the support-ticket editor offers, and nothing else: no images, no
	 * iframes, no embeds, no event-handler attributes, no inline styles beyond the
	 * editor's own alignment buttons.
	 */
	const RICH_TEXT_CONFIG = [
		'HTML.Allowed' => 'p[style],br,hr,strong,b,em,i,u,s,blockquote,pre,code,'
			. 'ul[style],ol[style],li[style],a[href|target|rel],'
			. 'table,thead,tbody,tr,th,td,h1,h2,h3,h4,h5,h6,span',
		// Only the layout styles the editor's align / indent buttons emit; any other
		// inline CSS (colours, fonts, positioning) is stripped, so nothing can be
		// overlaid on the surrounding page.
		'CSS.AllowedProperties' => ['text-align', 'padding-left', 'margin-left'],
		// Purifier drops target= unless the frame target is allowed.
		'Attr.AllowedFrameTargets' => ['_blank'],
		'HTML.Nofollow' => true,
	];

	/**
	 * Reduces rich text to the allowed subset.
	 *
	 * @param string|null $value
	 * @return string|null null and '' pass through untouched
	 */
	public static function richText($value)
	{
		if ($value === null || $value === '') {
			return $value;
		}

		return HtmlPurifier::process($value, static::RICH_TEXT_CONFIG);
	}

	/**
	 * A stored URL that is safe to put in an href.
	 *
	 * Encoding does nothing here: `javascript:alert(1)` contains no markup and survives
	 * Html::encode() intact, so a link built from a stored URL runs it on click. Only
	 * the schemes that actually navigate are allowed through.
	 *
	 * @param string|null $url
	 * @return string|null null when the URL is not one we are willing to link to
	 */
	public static function href($url)
	{
		if ($url === null || trim($url) === '') {
			return null;
		}

		$url = trim($url);
		// Relative and protocol-relative URLs stay on our own origin.
		if (preg_match('#^(/|\#|\?)#', $url)) {
			return $url;
		}

		$scheme = parse_url($url, PHP_URL_SCHEME);
		if ($scheme === null) {
			// No scheme at all ("example.com/x") -- treat it as http, which is what a
			// browser does with a bare host anyway.
			return 'http://' . $url;
		}

		return in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true) ? $url : null;
	}
}
