<?php

namespace tests\unit;

use common\helpers\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

/**
 * The allow-list sanitiser behind the helpdesk and the admin content previews.
 *
 * A support ticket is written by any registered customer and printed as HTML in the
 * screens an administrator reads, which made it the shortest path from an ordinary
 * account into an administrator's session. Same shape as the workspace app's
 * Announcement::sanitizeContentHtml(): keep what the editor offers, drop the rest.
 */
class HtmlSanitizerTest extends TestCase
{
	public function testTheEditorsFormattingSurvives()
	{
		$html = '<p>A <strong>ticket</strong> with <em>detail</em></p><ul><li>one</li><li>two</li></ul>';

		$this->assertSame($html, HtmlSanitizer::richText($html));
	}

	/**
	 * @dataProvider payloadProvider
	 * @param string $html
	 * @param string $mustNotSurvive
	 */
	public function testExecutableContentIsStripped($html, $mustNotSurvive)
	{
		$out = HtmlSanitizer::richText($html);

		$this->assertStringNotContainsString($mustNotSurvive, strtolower($out));
	}

	/**
	 * @return array
	 */
	public function payloadProvider()
	{
		return [
			'script tag' => ['<p>hi</p><script>alert(1)</script>', '<script'],
			'event handler' => ['<p onclick="alert(1)">hi</p>', 'onclick'],
			'image error handler' => ['<img src=x onerror="alert(1)">', 'onerror'],
			'iframe' => ['<iframe src="https://attacker.example"></iframe>', '<iframe'],
			'javascript href' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
			'svg payload' => ['<svg onload="alert(1)"></svg>', 'onload'],
			'style block' => ['<style>body{display:none}</style>', '<style'],
			'form' => ['<form action="https://attacker.example"><input name="p"></form>', '<form'],
			'object' => ['<object data="x"></object>', '<object'],
			// The blocklist this replaces matched "<script" literally and missed the rest.
			// What matters is that no tag survives -- leftover text reading "alert(1)" is
			// just text, which is the point of an allow-list.
			'uppercase script' => ['<SCRIPT>alert(1)</SCRIPT>', '<script'],
			'broken-up tag' => ['<scr<script>ipt>alert(1)</script>', '<script'],
		];
	}

	public function testPositioningStylesAreStrippedButAlignmentSurvives()
	{
		$out = HtmlSanitizer::richText('<p style="text-align:right;position:fixed;font-size:90px;">x</p>');

		$this->assertStringContainsString('text-align', $out);
		$this->assertStringNotContainsString('position', $out, 'nothing may be overlaid on the page around it');
		$this->assertStringNotContainsString('font-size', $out);
	}

	public function testLinksSurviveAndAreMarkedNofollow()
	{
		$out = HtmlSanitizer::richText('<p><a href="https://example.test/x" target="_blank">x</a></p>');

		$this->assertStringContainsString('href="https://example.test/x"', $out);
		$this->assertStringContainsString('nofollow', $out);
	}

	public function testEmptyValuesPassThrough()
	{
		$this->assertNull(HtmlSanitizer::richText(null));
		$this->assertSame('', HtmlSanitizer::richText(''));
	}

	/**
	 * @dataProvider unsafeHrefProvider
	 * @param string $url
	 */
	public function testUnsafeSchemesAreRefusedForHrefs($url)
	{
		// Encoding is no help here: `javascript:alert(1)` contains no markup and comes
		// through Html::encode() unchanged, so only the scheme check stops it.
		$this->assertNull(HtmlSanitizer::href($url));
	}

	/**
	 * @return array
	 */
	public function unsafeHrefProvider()
	{
		return [
			'javascript' => ['javascript:alert(1)'],
			'uppercase javascript' => ['JavaScript:alert(1)'],
			'data uri' => ['data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg=='],
			'vbscript' => ['vbscript:msgbox(1)'],
			'file' => ['file:///etc/passwd'],
		];
	}

	/**
	 * @dataProvider safeHrefProvider
	 * @param string $url
	 * @param string $expected
	 */
	public function testNavigableUrlsAreKept($url, $expected)
	{
		$this->assertSame($expected, HtmlSanitizer::href($url));
	}

	/**
	 * @return array
	 */
	public function safeHrefProvider()
	{
		return [
			'https' => ['https://example.test/x', 'https://example.test/x'],
			'http' => ['http://example.test/x', 'http://example.test/x'],
			'mailto' => ['mailto:a@example.test', 'mailto:a@example.test'],
			'relative' => ['/about', '/about'],
			'a bare host gets a scheme' => ['example.test/x', 'http://example.test/x'],
		];
	}

	public function testAnEmptyUrlIsNotALink()
	{
		$this->assertNull(HtmlSanitizer::href(null));
		$this->assertNull(HtmlSanitizer::href('   '));
	}
}
