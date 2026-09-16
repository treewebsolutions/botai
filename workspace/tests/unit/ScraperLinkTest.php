<?php

namespace tests\unit;

use common\components\Scraper;
use PHPUnit\Framework\TestCase;

/**
 * Which links a crawled page hands on to the crawler.
 *
 * A site that writes its links relative - "/ro/servicii" rather than the full address -
 * had every one of them discarded, because the check asked whether the href contained the
 * site address and a relative href never does. On such a site the crawl found the page it
 * was given and stopped there.
 */
class ScraperLinkTest extends TestCase
{
	/**
	 * @param string $html
	 * @param string $website
	 * @param string $baseUrl
	 * @return string[]
	 */
	private function extract($html, $website, $baseUrl = 'https://example.ro')
	{
		$scraper = new Scraper(0);

		$reflection = new \ReflectionClass($scraper);
		$base = $reflection->getProperty('baseUrl');
		$base->setAccessible(true);
		$base->setValue($scraper, $baseUrl);

		$method = $reflection->getMethod('extractLinks');
		$method->setAccessible(true);

		return array_values($method->invoke($scraper, $html, $website));
	}

	/**
	 * Relative links are resolved against the page and kept.
	 */
	public function testRelativeLinksAreFollowed()
	{
		$html = '<a href="/ro/servicii">s</a><a href="/ro/despre-noi">d</a><a href="contact">c</a>';

		$this->assertSame([
			'https://example.ro/ro/servicii',
			'https://example.ro/ro/despre-noi',
			'https://example.ro/contact',
		], $this->extract($html, 'https://example.ro/'));
	}

	/**
	 * The site is recognised by host, so www and the scheme do not decide it.
	 */
	public function testTheSameSiteIsRecognisedHoweverItIsWritten()
	{
		$html = '<a href="https://www.example.ro/a">a</a><a href="http://example.ro/b">b</a>';

		$this->assertSame([
			'https://www.example.ro/a',
			'http://example.ro/b',
		], $this->extract($html, 'https://example.ro/'));
	}

	/**
	 * Another site is another site.
	 */
	public function testExternalLinksAreLeftAlone()
	{
		$html = '<a href="https://facebook.com/page">f</a><a href="/ro/servicii">s</a>';

		$this->assertSame(['https://example.ro/ro/servicii'], $this->extract($html, 'https://example.ro/'));
	}

	/**
	 * A fragment points inside a page, not at another one: keeping both would store the
	 * same content twice and crawl it twice.
	 */
	public function testFragmentsDoNotMakeANewPage()
	{
		$html = '<a href="/servicii">s</a><a href="/servicii#preturi">p</a><a href="#top">t</a>';

		$this->assertSame(['https://example.ro/servicii'], $this->extract($html, 'https://example.ro/'));
	}

	/**
	 * Files the site serves are not pages, and neither are the non-http schemes.
	 */
	public function testAssetsAndNonHttpSchemesAreSkipped()
	{
		$html = '<a href="/logo.webp">w</a><a href="/brochure.pdf">p</a>'
			. '<a href="mailto:a@example.ro">m</a><a href="tel:+40">t</a>'
			. '<a href="javascript:void(0)">j</a><a href="/ro/servicii">s</a>';

		$this->assertSame(['https://example.ro/ro/servicii'], $this->extract($html, 'https://example.ro/'));
	}
}
