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
					$normalizedLink = $this->normalizeUrl($link);
					if (!isset($this->visited[$normalizedLink])) {
						$this->scrape($normalizedLink, $website, $depth + 1);
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
			$href = $node->getAttribute('href');
			if ($this->isValidLink($href, $website)) {
				$links[] = $href;
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

		if ($website && strpos($href, $website) === false) {
			return false;
		}

		return true;
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



