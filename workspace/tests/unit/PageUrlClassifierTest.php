<?php

namespace tests\unit;

use common\components\PageUrlClassifier;
use PHPUnit\Framework\TestCase;

/**
 * Filing a scraped page under the first segment of its URL path, so an answer can be
 * narrowed to the part of the site that would hold it. Every URL below is one of the
 * scraped pages of the tenants this was written against.
 */
class PageUrlClassifierTest extends TestCase
{
	/**
	 * The section is the segment, as the site writes it - nothing is translated or mapped
	 * onto a fixed list.
	 */
	public function testTheSectionIsTheFirstSegment()
	{
		$cases = [
			'https://www.primadentalclinic.ro/' => 'home',
			'https://www.primadentalclinic.ro/servicii/' => 'servicii',
			'https://www.primadentalclinic.ro/contact/' => 'contact',
			'https://www.primadentalclinic.ro/despre-noi/' => 'despre-noi',
			'https://www.primadentalclinic.ro/galerie/' => 'galerie',
			'https://www.primadentalclinic.ro/termeni-si-conditii/' => 'termeni-si-conditii',
			'https://www.primadentallaboratory.ro/lista-preturi/' => 'lista-preturi',
			'https://clinicadezambete.com/product/albire-dentara-laser/' => 'product',
			'https://agaauto.ro/autoturisme' => 'autoturisme',
			'https://example.test/services/implants/' => 'services',
		];

		foreach ($cases as $url => $expected) {
			$this->assertSame($expected, PageUrlClassifier::classify($url), $url);
		}
	}

	/**
	 * Only the first segment decides; what comes after it is the page inside the section.
	 */
	public function testDeeperSegmentsDoNotChangeTheSection()
	{
		$this->assertSame('servicii', PageUrlClassifier::classify('https://example.test/servicii/implant-dentar/'));
		$this->assertSame('servicii', PageUrlClassifier::classify('https://example.test/servicii/protetica/coroane/'));
		$this->assertSame(
			['servicii', 'protetica', 'coroane'],
			PageUrlClassifier::segments('https://example.test/servicii/protetica/coroane/')
		);
	}

	/**
	 * A page written at the root of the site is its own section, because that is all its
	 * URL says about it.
	 */
	public function testARootLevelPageIsItsOwnSection()
	{
		$this->assertSame(
			'atuurile-implantului-dentar',
			PageUrlClassifier::classify('https://www.primadentalclinic.ro/atuurile-implantului-dentar/')
		);
	}

	/**
	 * A query string, a fragment and the case of the path are not part of the section.
	 */
	public function testOnlyThePathCounts()
	{
		$this->assertSame('servicii', PageUrlClassifier::classify('https://example.test/Servicii/?utm_source=x#top'));
		$this->assertSame('despre noi', PageUrlClassifier::classify('https://example.test/despre%20noi/'));
	}

	/**
	 * A site's machinery is not one of its sections, so it does not become one.
	 */
	public function testMachinerySegmentsAreSkipped()
	{
		$this->assertSame(
			'2025',
			PageUrlClassifier::classify('https://example.test/wp-content/uploads/2025/08/cropped-logo.png'),
			'wp-content and uploads drop out'
		);
		$this->assertSame('blog', PageUrlClassifier::classify('https://example.test/blog/page/2/'));
	}

	/**
	 * A multilingual site puts the language first. Filing those pages under "ro" and "en"
	 * would say what language they are in and nothing about what they are.
	 */
	public function testALanguagePrefixIsSteppedOver()
	{
		$this->assertSame('servicii', PageUrlClassifier::classify('https://example.test/ro/servicii/'));
		$this->assertSame('services', PageUrlClassifier::classify('https://example.test/en/services/implants/'));
		$this->assertSame('services', PageUrlClassifier::classify('https://example.test/en-GB/services/'));
		$this->assertSame('dienstleistungen', PageUrlClassifier::classify('https://example.test/de-DE/dienstleistungen/'));

		// A language nobody configured here is still a language prefix by its shape.
		$this->assertSame('szolgaltatasok', PageUrlClassifier::classify('https://example.test/hu/szolgaltatasok/'));
	}

	/**
	 * The language home page is a home page.
	 */
	public function testALanguageOnItsOwnIsHome()
	{
		$this->assertSame('home', PageUrlClassifier::classify('https://example.test/ro/'));
		$this->assertSame('home', PageUrlClassifier::classify('https://example.test/en'));
	}

	/**
	 * Only the first segment can be a language, and only two-letter codes qualify: a
	 * three-letter slug is a section, not a language.
	 */
	public function testSectionsAreNotMistakenForLanguages()
	{
		$this->assertSame('cat', PageUrlClassifier::classify('https://example.test/cat/pisici/'), 'three letters is a section');
		$this->assertSame('art', PageUrlClassifier::classify('https://example.test/art/'));
		$this->assertSame(
			'servicii',
			PageUrlClassifier::classify('https://example.test/servicii/ro/'),
			'a language deeper in the path is not a prefix'
		);
	}

	/**
	 * A URL with no path, and no URL at all.
	 */
	public function testEmptyPaths()
	{
		$this->assertSame('home', PageUrlClassifier::classify('https://example.test'));
		$this->assertSame('home', PageUrlClassifier::classify(''));
		$this->assertSame('home', PageUrlClassifier::classify(null));
		$this->assertSame([], PageUrlClassifier::segments('https://example.test/'));
	}
}
