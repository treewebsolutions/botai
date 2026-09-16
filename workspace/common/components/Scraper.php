<?php

namespace common\components;

use common\models\Page;
use common\services\OpenAiRecordVectorStoreService;
use yii\base\Component;
use yii\httpclient\Client;
use Yii;

class Scraper extends Component
{
	/**
	 * Response types worth storing. Anything else is a file the site serves, not a page it
	 * wrote, and its bytes would go into the knowledge base as if they were text.
	 */
	const ALLOWED_CONTENT_TYPES = ['text/html', 'application/xhtml+xml', 'text/plain'];

	/**
	 * Extensions never worth fetching. This spares the request, but it is not the guard -
	 * the list is always a step behind what a site serves, which is how a .webp reached
	 * the page table past a filter that named .png. {@see isStorableContentType()} is what
	 * actually decides, on the response itself.
	 */
	const SKIPPED_EXTENSIONS = 'jpg|jpeg|png|gif|webp|avif|svg|ico|bmp|tiff?|css|js|mjs|json|xml|rss|pdf|docx?|xlsx?|pptx?|odt|ods|csv|zip|rar|7z|gz|tar|mp4|m4v|mp3|m4a|wav|ogg|webm|avi|mov|wmv|flv|woff2?|ttf|otf|eot|apk|exe|dmg';

	private $visited = [];
	private $baseUrl;
	private $depthLimit; // Recursion limit

	public function __construct($depthLimit = 3)
	{
		$this->depthLimit = $depthLimit; // Max depth level
	}

	public function scrape($url, $website = null, $depth = 0, $status = Page::STATUS_INACTIVE)
	{
		// ✅ Stop recursion if depth exceeds limit or URL is already visited
		if ($depth > $this->depthLimit || isset($this->visited[$url])) {
			return;
		}

		$this->visited[$url] = true; // ✅ Mark URL as visited
		$this->baseUrl = $this->getBaseUrl($url);

		try {
			// ✅ Rate limiting to prevent getting blocked
			usleep(500000); // 0.5 seconds delay

			// Initialize cURL session
			$ch = curl_init($url);

			// Set cURL options
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			// Execute cURL request
			$response = curl_exec($ch);

			// Check for cURL errors
			if (curl_errno($ch)) {
				curl_close($ch);
				return;
			}

			// Read the type before closing the handle: it is what decides whether this is a
			// page or a file the site happens to serve at a URL.
			$contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

			// Close cURL session
			curl_close($ch);

			if (!static::isStorableContentType($contentType)) {
				// A link the extension list did not recognise, or a URL with no extension
				// at all. Storing it would put an image's bytes in the page table, and
				// from there into the vector store as text.
				Yii::info(['message' => 'Skipped a response that is not a page.', 'url' => $url, 'contentType' => $contentType], __METHOD__);
				return;
			}

			// If the response is not empty, proceed
			if ($response !== false) {
				$content = $response; // Store the content of the page
				$this->savePage($url, $content, $website, $status);  // Save the page content with the status

				// ✅ Extract links and recursively scrape them
				$links = $this->extractLinks($content, $website);
				if (empty($links)) {
					return;
				}

				foreach ($links as $link) {
					if (isset($this->visited[$link])) {
						continue;
					}

					if ($depth + 1 <= $this->depthLimit) {
						$this->scrape($link, $website, $depth + 1);
					} else {
						// Deeper than this run follows. Remember the address without
						// fetching it, so the queue picks it up on a later run instead of
						// it being lost - which is what used to happen at the depth limit,
						// and what made a refresh unable to notice a page added to a site
						// since the first crawl.
						$this->queueLink($link, $website);
					}
				}
			} else {
				return;
			}
		} catch (\Exception $e) {
			return;
		}
	}

	private function savePage($url, $content, $website = null, $status = Page::STATUS_INACTIVE)
	{
		$page = Page::findOne(['url' => $url]);

		if ($page) {
			// If the page is already in the database, update it
			$page->counter += 1;  // Increment counter
			$page->content = $content;
			if ($status) {
				$page->status = $status;
			}
		} else {
			// If it's a new page, create a new record
			$page = new Page([
				'url' => $url,
				'content' => $content,
				'website' => $website,
				'counter' => 1,
				'status' => Page::STATUS_INACTIVE, // Initial status should be INACTIVE
			]);
		}

		// The visible text of the page, extracted once here rather than on every sync. It
		// is what goes into the vector store, so storing it makes what will be indexed
		// something you can look at instead of something recomputed out of sight.
		$page->text = OpenAiRecordVectorStoreService::htmlToPlainText((string) $page->content);
		$page->characters = mb_strlen((string) $page->content);

		// Save the page record in the database
		if ($page->save()) {
			// Keep the OpenAI vector store in sync: indexes the page when it is displayable (ACTIVE with
			// content), withdraws it otherwise. No-op when no Knowledge Base is configured. Runs after
			// the response (shutdown function) so scraping is never slowed down by the upload.
			OpenAiRecordVectorStoreService::scheduleSync($page->id);
		}
	}

	/**
	 * Whether a response is a page rather than a file the site serves.
	 *
	 * An empty type is taken as storable: some servers send none, and the extension list
	 * has already had its say by this point.
	 *
	 * @param string $contentType the raw Content-Type header, parameters and all
	 * @return bool
	 */
	public static function isStorableContentType($contentType): bool
	{
		$type = strtolower(trim((string) $contentType));
		if ($type === '') {
			return true;
		}

		// "text/html; charset=UTF-8" - only the type itself matters.
		$type = trim(explode(';', $type)[0]);

		return in_array($type, static::ALLOWED_CONTENT_TYPES, true);
	}

	private function extractLinks($html, $website = null)
	{
		libxml_use_internal_errors(true); // ✅ Prevents HTML parsing errors

		$dom = new \DOMDocument();
		$dom->loadHTML($html);
		$links = [];

		foreach ($dom->getElementsByTagName('a') as $node) {
			$href = trim($node->getAttribute('href'));
			// Judged on the raw href, before anything is resolved: "#top" means this page,
			// and resolving it would point at the site root instead - a different page
			// entirely when the crawl is somewhere deeper.
			if ($href === '' || preg_match('/^(#|javascript:|mailto:|tel:|data:)/i', $href)) {
				continue;
			}

			// Resolve before judging. A site that writes its links relative - "/ro/servicii"
			// rather than "https://example.ro/ro/servicii" - had every one of them thrown
			// away, because the check asked whether the href contained the site address and
			// a relative href never does.
			$absolute = $this->stripFragment($this->normalizeUrl($href));

			if ($this->isValidLink($absolute, $website)) {
				$links[] = $absolute;
			}
		}

		return array_unique($links); // ✅ Prevents duplicate links
	}

	private function isValidLink($href, $website = null)
	{
		if (empty($href) || isset($this->visited[$href])) {
			return false;
		}

		if (preg_match('/^(#|javascript:|mailto:|tel:)/', $href)) {
			return false;
		}

		if (preg_match('/\.(' . static::SKIPPED_EXTENSIONS . ')(\?|#|$)/i', $href)) {
			return false;
		}

		// Same site, compared by host rather than by substring: a site reached as
		// "example.ro" writes some of its links as "www.example.ro", and one address being
		// a substring of the other is not what makes two pages the same site.
		if ($website) {
			$siteHost = $this->hostOf($website);
			if ($siteHost !== '' && $this->hostOf($href) !== $siteHost) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Remembers an address to fetch later, without fetching it now.
	 *
	 * The row carries no content, so it is not indexable and shows in the page list as
	 * having no text until the crawl reaches it. A URL already known is left alone,
	 * whatever its state: a page someone deleted stays deleted rather than reappearing on
	 * the next pass.
	 *
	 * @param string $url absolute
	 * @param string|null $website
	 * @return void
	 */
	private function queueLink($url, $website = null)
	{
		$url = trim((string) $url);
		if ($url === '' || Page::find()->where(['url' => $url])->exists()) {
			return;
		}

		$page = new Page([
			'url' => $url,
			'website' => $website,
			'content' => '',
			'text' => '',
			'characters' => 0,
			'counter' => 0,
			'status' => Page::STATUS_INACTIVE,
		]);

		if (!$page->save()) {
			Yii::warning(['message' => 'Could not queue a discovered link.', 'url' => $url, 'errors' => $page->getErrors()], __METHOD__);
		}
	}

	/**
	 * The bare host of a URL, lowercased and without the `www.` prefix, so that
	 * "example.ro" and "https://www.Example.ro/a" are recognised as one site.
	 *
	 * @param string $url
	 * @return string
	 */
	private function hostOf($url)
	{
		$url = trim((string) $url);
		if ($url === '') {
			return '';
		}

		$host = parse_url(strpos($url, '://') === false ? "http://{$url}" : $url, PHP_URL_HOST);

		return strtolower(preg_replace('/^www\./i', '', (string) $host));
	}

	/**
	 * Drops the fragment: /servicii#contact and /servicii are one page, and keeping both
	 * would store the same content twice and crawl it twice.
	 *
	 * @param string $url
	 * @return string
	 */
	private function stripFragment($url)
	{
		$position = strpos((string) $url, '#');

		return $position === false ? (string) $url : substr((string) $url, 0, $position);
	}

	private function normalizeUrl($url)
	{
		if (parse_url($url, PHP_URL_SCHEME) != '') {
			return $url;
		}

		return $this->urlToAbsolute($this->baseUrl, $url);
	}

	private function getBaseUrl($url)
	{
		$parsedUrl = parse_url($url);
		return $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
	}

	private function urlToAbsolute($base, $relative)
	{
		if (parse_url($relative, PHP_URL_SCHEME) != '') {
			return $relative;
		}

		return rtrim($base, '/') . '/' . ltrim($relative, '/');
	}
}



