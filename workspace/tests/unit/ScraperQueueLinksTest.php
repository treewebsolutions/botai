<?php

namespace tests\unit;

use common\components\Scraper;
use common\models\Page;
use tests\DatabaseTestCase;
use Yii;

/**
 * What happens to a link the current run will not follow.
 *
 * It used to be dropped, which had two consequences: anything past the crawl depth was
 * never reached, and a refresh - which follows nothing - could not notice a page added to
 * the site since the first crawl. The crawl froze at whatever it found the first time.
 *
 * Such a link is now remembered as a row with no content, and a later run fetches it. The
 * queue is the discovery: one request per run, spreading through the site by itself.
 */
class ScraperQueueLinksTest extends DatabaseTestCase
{
	/**
	 * @param string $url
	 * @param string|null $website
	 * @return void
	 */
	private function queue($url, $website = 'https://example.ro/')
	{
		$scraper = new Scraper(0);
		$method = new \ReflectionMethod($scraper, 'queueLink');
		$method->setAccessible(true);
		$method->invoke($scraper, $url, $website);
	}

	/**
	 * @param string $url
	 * @return Page|null
	 */
	private function find($url)
	{
		return Page::find()->where(['url' => $url])->one();
	}

	/**
	 * A discovered address is stored, and stored as work still to do.
	 */
	public function testADiscoveredLinkIsQueuedWithoutContent()
	{
		$url = 'https://example.ro/ro/servicii';
		$this->queue($url);

		$page = $this->find($url);
		$this->assertNotNull($page);
		$this->assertSame(Page::STATUS_INACTIVE, (int) $page->status, 'queued, not crawled');
		$this->assertSame('', trim((string) $page->content), 'nothing was fetched');
		$this->assertSame(0, (int) $page->characters);
		$this->assertSame('https://example.ro/', $page->website);
	}

	/**
	 * An address already known is left as it is - the crawl must not reset a page it has
	 * already fetched back to the queue.
	 */
	public function testAKnownLinkIsNotTouched()
	{
		$url = 'https://example.ro/contact';
		Yii::$app->db->createCommand()->insert('{{%page}}', [
			'url' => $url,
			'website' => 'https://example.ro/',
			'content' => 'fetched',
			'characters' => 7,
			'counter' => 3,
			'status' => Page::STATUS_ACTIVE,
			'deleted' => Page::NO,
			'created_at' => '2026-09-01 00:00:00',
			'updated_at' => '2026-09-01 00:00:00',
		])->execute();

		$this->queue($url);

		$this->assertSame(1, (int) Page::find()->where(['url' => $url])->count(), 'no duplicate');
		$page = $this->find($url);
		$this->assertSame(Page::STATUS_ACTIVE, (int) $page->status);
		$this->assertSame(3, (int) $page->counter);
	}

	/**
	 * A page someone deleted stays deleted rather than reappearing on the next pass.
	 */
	public function testADeletedPageIsNotResurrected()
	{
		$url = 'https://example.ro/old';
		Yii::$app->db->createCommand()->insert('{{%page}}', [
			'url' => $url,
			'website' => 'https://example.ro/',
			'content' => 'gone',
			'characters' => 4,
			'counter' => 1,
			'status' => Page::STATUS_ACTIVE,
			'deleted' => Page::YES,
			'created_at' => '2026-09-01 00:00:00',
			'updated_at' => '2026-09-01 00:00:00',
		])->execute();

		$this->queue($url);

		$this->assertSame(1, (int) Page::find()->where(['url' => $url])->count());
		$this->assertSame(Page::YES, (int) $this->find($url)->deleted);
	}
}
