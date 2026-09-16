<?php
namespace console\controllers;

use common\components\Scraper;
use common\models\Page;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Fetches one page per run, so a scheduled tick is short and a site is crawled over many
 * of them rather than in one long request.
 */
class ScraperController extends Controller
{
	/**
	 * How deep a newly discovered page is followed, so the rest of the site is found.
	 */
	const DISCOVERY_DEPTH = 3;

	/**
	 * Hours a page may go without a fetch before it is due for a refresh, unless
	 * `scraper.refreshAfterHours` says otherwise.
	 */
	const DEFAULT_REFRESH_AFTER_HOURS = 24;

	/**
	 * Fetches the next page that is due.
	 */
	public function actionRun()
	{
		list($page, $isNew) = static::findNextPage();
		if ($page === null) {
			$this->reportNothingDue();
			return ExitCode::OK;
		}

		$previousUpdate = $page->updated_at;

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			// A newly discovered page is followed, so its links join the queue. A refresh
			// fetches that page alone: the crawl already knows the site, and re-walking a
			// customer's whole website on every scheduled tick is not a refresh.
			$scraper = new Scraper($isNew ? static::DISCOVERY_DEPTH : 0);
			$scraper->scrape($page->url, $page->website, 0, Page::STATUS_ACTIVE);
			$dbTransaction->commit();
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			Yii::error(['message' => $e->getMessage(), 'page' => $page->id, 'url' => $page->url], __METHOD__);
			$this->stdout('[' . date('Y-m-d H:i:s') . '] Scraper failed on ' . $page->url . "\n");
			return ExitCode::UNSPECIFIED_ERROR;
		}

		// Scraper::scrape() swallows cURL errors and returns without saving, so an
		// unreachable URL would keep its old timestamp and be picked again on every tick
		// from now on. Move it to the back of the queue instead.
		$page->refresh();
		if ($page->updated_at === $previousUpdate) {
			$page->touch('updated_at');
			Yii::warning(['message' => 'Page could not be fetched.', 'page' => $page->id, 'url' => $page->url], __METHOD__);
			$this->stdout('[' . date('Y-m-d H:i:s') . '] Could not fetch ' . $page->url . "\n");
			return ExitCode::OK;
		}

		$this->stdout('[' . date('Y-m-d H:i:s') . '] ' . ($isNew ? 'Crawled ' : 'Refreshed ') . $page->url . "\n");
		return ExitCode::OK;
	}

	/**
	 * Says why there was nothing to do.
	 *
	 * Printing nothing at all is indistinguishable from the command being broken, and the
	 * two reasons want different things from the operator: a crawl that has not been
	 * started needs a first address, while a crawl that is simply up to date needs
	 * nothing.
	 *
	 * @return void
	 */
	protected function reportNothingDue()
	{
		$stamp = '[' . date('Y-m-d H:i:s') . '] ';
		$total = (int) Page::find()->where(['deleted' => Page::NO])->count();

		if ($total === 0) {
			$this->stdout($stamp . "No pages yet - nothing to crawl.\n");
			$this->stdout("Add the site's address under Pages to start the crawl; the links found there queue themselves.\n");
			return;
		}

		$hours = (int) (Yii::$app->params['scraper.refreshAfterHours'] ?? static::DEFAULT_REFRESH_AFTER_HOURS);
		if ($hours <= 0) {
			$this->stdout($stamp . "{$total} pages, all crawled. Refreshing is off (scraper.refreshAfterHours = 0).\n");
			return;
		}

		$oldest = Page::find()
			->where(['status' => Page::STATUS_ACTIVE, 'deleted' => Page::NO])
			->min('updated_at');

		$this->stdout($stamp . "Nothing due: {$total} pages, none older than {$hours}h.\n");
		if ($oldest) {
			$due = date('Y-m-d H:i:s', strtotime($oldest) + $hours * 3600);
			$this->stdout("Least recently fetched: {$oldest}, due {$due}.\n");
		}
	}

	/**
	 * The next page due for a fetch, and whether it has never been fetched.
	 *
	 * Newly discovered pages come first, oldest first; when there are none, the page that
	 * has gone longest without a refresh and is past the refresh age.
	 *
	 * Only the first half of that used to exist, so the crawler fell silent for good once
	 * every page it had found was ACTIVE - nothing ever puts a page back to INACTIVE, and
	 * savePage() cannot, because it only assigns a status that is truthy and INACTIVE is
	 * zero. The pages here were last fetched in March 2025.
	 *
	 * @return array{0: Page|null, 1: bool} the page (or null when nothing is due) and
	 *                                      whether it is newly discovered
	 */
	public static function findNextPage()
	{
		$page = Page::find()
			->where(['status' => Page::STATUS_INACTIVE, 'deleted' => Page::NO])
			->orderBy(['updated_at' => SORT_ASC, 'id' => SORT_ASC])
			->one();
		if ($page !== null) {
			return [$page, true];
		}

		$hours = (int) (Yii::$app->params['scraper.refreshAfterHours'] ?? static::DEFAULT_REFRESH_AFTER_HOURS);
		if ($hours <= 0) {
			return [null, false];
		}

		$page = Page::find()
			->where(['status' => Page::STATUS_ACTIVE, 'deleted' => Page::NO])
			->andWhere(['<', 'updated_at', date('Y-m-d H:i:s', strtotime("-{$hours} hours"))])
			->orderBy(['updated_at' => SORT_ASC, 'id' => SORT_ASC])
			->one();

		return [$page, false];
	}
}
