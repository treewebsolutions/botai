<?php

namespace tests\unit;

use common\models\Page;
use console\controllers\ScraperController;
use tests\DatabaseTestCase;
use Yii;

/**
 * Which page the scraper picks next.
 *
 * It only ever asked for an INACTIVE page, and nothing puts a page back to INACTIVE -
 * savePage() assigns a status only when it is truthy, and INACTIVE is zero. So once every
 * discovered page had been fetched the crawler went quiet for good and the content stayed
 * at whenever the site was first crawled.
 */
class ScraperQueueTest extends DatabaseTestCase
{
	/**
	 * @param array $attributes
	 * @return int
	 */
	private function page(array $attributes)
	{
		$row = array_merge([
			'url' => 'https://example.test/' . uniqid('', true),
			'website' => 'https://example.test/',
			'content' => 'content',
			'characters' => 7,
			'counter' => 1,
			'status' => Page::STATUS_ACTIVE,
			'deleted' => Page::NO,
			'created_at' => '2026-09-01 00:00:00',
			'updated_at' => '2026-09-01 00:00:00',
		], $attributes);

		Yii::$app->db->createCommand()->insert('{{%page}}', $row)->execute();

		return (int) Yii::$app->db->getLastInsertID();
	}

	/**
	 * @param string $hours
	 * @return string
	 */
	private function hoursAgo($hours)
	{
		return date('Y-m-d H:i:s', strtotime("-{$hours} hours"));
	}

	/**
	 * A page discovered but never fetched comes before any refresh, and is reported as new
	 * so the crawl follows its links.
	 */
	public function testNewPagesComeFirst()
	{
		$this->page(['status' => Page::STATUS_ACTIVE, 'updated_at' => $this->hoursAgo(500)]);
		$new = $this->page(['status' => Page::STATUS_INACTIVE, 'content' => '', 'characters' => 0]);

		list($page, $isNew) = ScraperController::findNextPage();

		$this->assertNotNull($page);
		$this->assertSame($new, (int) $page->id);
		$this->assertTrue($isNew);
	}

	/**
	 * With nothing new, the page that has gone longest without a fetch is taken, and it is
	 * not reported as new - a refresh fetches that page alone rather than re-crawling the
	 * site on every tick.
	 */
	public function testTheStalestPageIsRefreshedWhenNothingIsNew()
	{
		$this->page(['updated_at' => $this->hoursAgo(30)]);
		$stalest = $this->page(['updated_at' => $this->hoursAgo(400)]);
		$this->page(['updated_at' => $this->hoursAgo(26)]);

		list($page, $isNew) = ScraperController::findNextPage();

		$this->assertNotNull($page, 'the crawler used to stop here for good');
		$this->assertSame($stalest, (int) $page->id);
		$this->assertFalse($isNew);
	}

	/**
	 * A page fetched recently enough is not due, so a site that is entirely fresh costs
	 * nothing per tick.
	 */
	public function testNothingIsDueWhileEveryPageIsFresh()
	{
		$this->page(['updated_at' => $this->hoursAgo(1)]);
		$this->page(['updated_at' => $this->hoursAgo(5)]);

		list($page, $isNew) = ScraperController::findNextPage();

		$this->assertNull($page);
		$this->assertFalse($isNew);
	}

	/**
	 * Refreshing can be turned off without stopping discovery.
	 */
	public function testRefreshCanBeDisabled()
	{
		$this->page(['updated_at' => $this->hoursAgo(900)]);
		$previous = Yii::$app->params['scraper.refreshAfterHours'] ?? null;

		try {
			Yii::$app->params['scraper.refreshAfterHours'] = 0;
			list($page,) = ScraperController::findNextPage();
			$this->assertNull($page);

			$new = $this->page(['status' => Page::STATUS_INACTIVE, 'content' => '']);
			list($page, $isNew) = ScraperController::findNextPage();
			$this->assertSame($new, (int) $page->id, 'discovery keeps working');
			$this->assertTrue($isNew);
		} finally {
			Yii::$app->params['scraper.refreshAfterHours'] = $previous;
		}
	}

	/**
	 * Deleted pages are never picked.
	 */
	public function testDeletedPagesAreSkipped()
	{
		$this->page(['deleted' => Page::YES, 'updated_at' => $this->hoursAgo(900)]);
		$this->page(['status' => Page::STATUS_INACTIVE, 'deleted' => Page::YES, 'content' => '']);

		list($page,) = ScraperController::findNextPage();

		$this->assertNull($page);
	}
}
