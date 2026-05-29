<?php

namespace common\components;

use common\models\Page;
use yii\base\Component;
use yii\httpclient\Client;
use Yii;

class Scraper extends Component
{
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

			// Close cURL session
			curl_close($ch);

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

		// Save the page record in the database
		$page->save();
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

		if (preg_match('/\.(jpg|jpeg|png|gif|css|js|pdf|mp4|mp3|avi|mov|wmv)(\?|$)/i', $href)) {
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



