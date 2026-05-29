<?php
namespace console\controllers;

use common\components\Scraper;
use common\models\Page;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

class ScraperController extends Controller
{
	/**
	 * Run backup.
	 */
	public function actionRun()
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$page = Page::findOne(['status' => Page::STATUS_INACTIVE]);
			if (!empty($page)) {
				$scraper = new Scraper(3);
				$scraper->scrape($page->url, $page->website, 0, Page::STATUS_ACTIVE);
				$dbTransaction->commit();
				$this->stdout('[' . date('Y-m-d H:i:s') . '] Scraper ran successfully.');
				return ExitCode::OK;
			} else {
				return ExitCode::OK;
			}
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			$this->stdout('[' . date('Y-m-d H:i:s') . '] Scraper failed.');
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}
}
