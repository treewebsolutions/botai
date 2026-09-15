<?php

namespace console\controllers;

use common\models\Integration;
use common\models\KnowledgeBase;
use common\models\RecordVectorIndex;
use common\services\OpenAiRecordVectorStoreService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Builds / maintains the OpenAI semantic index for scraped pages.
 *
 * Requires:
 *  - an active Integration of type OpenAI (API key);
 *  - an active OpenAI KnowledgeBase (linked to the chat Assistant, or the
 *    `pageVectorKnowledgeBaseId` params override).
 *
 * Usage:
 *   php yii ai-index/setup sk-...    # store the OpenAI API key + create the default KnowledgeBase
 *   php yii ai-index/status          # index coverage stats
 *   php yii ai-index/reindex         # (re)index every displayable page
 *   php yii ai-index/reindex <id>    # (re)index a single page by id
 *   php yii ai-index/withdraw <id>   # remove a single page from the vector store
 *   php yii ai-index/cleanup         # withdraw pages no longer displayable
 *   php yii ai-index/reconcile       # index missing/updated + withdraw stale (scheduled)
 */
class AiIndexController extends Controller
{
	/**
	 * One-shot provisioning: stores the OpenAI API key as the default Integration (type OpenAI)
	 * and ensures an active OpenAI KnowledgeBase exists (the vector store itself is created lazily
	 * on the first sync). Run `ai-index/reindex` afterwards to backfill.
	 *
	 * @param string|null $apiKey OpenAI API key (sk-...). Omit to keep the existing key and only ensure the KB.
	 * @param string $kbName Name of the knowledge base row.
	 * @return int
	 */
	public function actionSetup($apiKey = null, $kbName = 'Website')
	{
		if ($apiKey !== null && trim($apiKey) !== '') {
			$integration = Integration::find()
				->where([
					'type' => Integration::TYPE_OPENAI,
					'deleted' => Integration::NO,
				])
				->orderBy(['default' => SORT_DESC, 'id' => SORT_ASC])
				->one();
			if ($integration === null) {
				$integration = new Integration();
				$integration->name = 'OpenAI';
				$integration->type = Integration::TYPE_OPENAI;
			}
			$integration->data = json_encode(['api_key' => trim($apiKey)], JSON_UNESCAPED_SLASHES);
			$integration->default = Integration::YES;
			$integration->status = Integration::STATUS_ACTIVE;
			$integration->deleted = Integration::NO;
			if (!$integration->save(false)) {
				$this->stderr("Failed to save the OpenAI integration.\n", Console::FG_RED);
				return ExitCode::UNSPECIFIED_ERROR;
			}
			$this->stdout("OpenAI API key stored (integration #{$integration->id}).\n", Console::FG_GREEN);
		}

		$kb = KnowledgeBase::find()
			->where([
				'provider' => KnowledgeBase::PROVIDER_OPENAI,
				'status' => KnowledgeBase::STATUS_ACTIVE,
				'deleted' => KnowledgeBase::NO,
			])
			->orderBy(['id' => SORT_ASC])
			->one();
		if ($kb === null) {
			$kb = new KnowledgeBase();
			$kb->name = $kbName;
			$kb->description = 'Scraped pages semantic index';
			$kb->provider = KnowledgeBase::PROVIDER_OPENAI;
			$kb->chunk_size = 2000;
			$kb->chunk_overlap = 250;
			$kb->status = KnowledgeBase::STATUS_ACTIVE;
			$kb->deleted = KnowledgeBase::NO;
			if (!$kb->save(false)) {
				$this->stderr("Failed to create the knowledge base.\n", Console::FG_RED);
				return ExitCode::UNSPECIFIED_ERROR;
			}
			$this->stdout("Knowledge base '{$kb->name}' created (#{$kb->id}).\n", Console::FG_GREEN);
		} else {
			$this->stdout("Knowledge base '{$kb->name}' already active (#{$kb->id}).\n", Console::FG_GREEN);
		}
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();

		$this->stdout("Setup complete. Run `php yii ai-index/reindex` to build the vector index.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Shows index coverage: how many displayable pages are actually present in the vector store.
	 *
	 * @return int
	 */
	public function actionStatus()
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured (create an active OpenAI KnowledgeBase — or set the pageVectorKnowledgeBaseId override — and configure the OpenAI Integration).\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		$displayableIds = $this->normalizeIds($this->buildDisplayableQuery()->column());
		$displayable = count($displayableIds);

		$indexedIds = $this->normalizeIds(
			RecordVectorIndex::find()
				->where(['deleted' => RecordVectorIndex::NO, 'status' => RecordVectorIndex::STATUS_ACTIVE])
				->select('record_id')
				->column()
		);
		$indexedDisplayable = count(array_intersect($displayableIds, $indexedIds));
		$errors = (int) RecordVectorIndex::find()
			->where(['status' => RecordVectorIndex::STATUS_ERROR])
			->count();
		$missing = $displayable - $indexedDisplayable;
		$stale = count($indexedIds) - $indexedDisplayable;

		$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();
		$this->stdout("Knowledge base:             #{$kb->id} {$kb->name} (" . ($kb->vector_store_id ?: 'vector store not provisioned yet') . ")\n");
		$this->stdout("Displayable pages:          {$displayable}\n");
		$this->stdout("Indexed (displayable):      {$indexedDisplayable}\n");
		$this->stdout("Missing from the index:     {$missing}\n", $missing > 0 ? Console::FG_RED : Console::FG_GREEN);
		$this->stdout("Stale rows (not listable):  {$stale}\n", $stale > 0 ? Console::FG_YELLOW : Console::FG_GREEN);
		$this->stdout("Index rows in error:        {$errors}\n", $errors > 0 ? Console::FG_YELLOW : Console::FG_GREEN);
		if ($missing > 0) {
			$this->stdout("Run `php yii ai-index/reindex` to index the missing pages.\n", Console::FG_YELLOW);
		}
		if ($stale > 0) {
			$this->stdout("Run `php yii ai-index/cleanup` to withdraw stale pages.\n", Console::FG_YELLOW);
		}
		return ExitCode::OK;
	}

	/**
	 * (Re)index displayable pages (active, not deleted, with content) into the OpenAI vector store.
	 *
	 * @param int|null $id Optional single page id.
	 * @return int
	 */
	public function actionReindex($id = null)
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured (create an active OpenAI KnowledgeBase — or set the pageVectorKnowledgeBaseId override — and configure the OpenAI Integration).\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		if ($id !== null) {
			$ids = $this->normalizeIds([$id]);
		} else {
			$ids = $this->normalizeIds($this->buildDisplayableQuery()->column());
		}

		$total = count($ids);
		if ($total === 0) {
			$this->stdout("No matching pages to index.\n", Console::FG_YELLOW);
			return ExitCode::OK;
		}

		$this->stdout("Indexing {$total} page(s)...\n", Console::FG_GREEN);
		$done = 0;
		$failed = 0;
		foreach ($ids as $pageId) {
			// syncPage never throws; success is reflected in the index row status.
			OpenAiRecordVectorStoreService::syncPage($pageId);
			$row = RecordVectorIndex::find()->where(['record_id' => $pageId])->one();
			if ($row !== null && (int) $row->status === RecordVectorIndex::STATUS_ACTIVE) {
				$done++;
				$this->stdout('.', Console::FG_GREEN);
			} else {
				$failed++;
				Yii::error('AiIndexController reindex page ' . $pageId . ': ' . ($row->error_message ?? 'unknown'), __METHOD__);
				$this->stdout('x', Console::FG_RED);
			}
		}

		$this->stdout("\nDone. Indexed: {$done}, failed: {$failed}" . ($failed > 0 ? ' — see record_vector_index.error_message' : '') . ".\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Remove a single page from the vector store.
	 *
	 * @param int $id Page id.
	 * @return int
	 */
	public function actionWithdraw($id)
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured.\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		OpenAiRecordVectorStoreService::withdrawFromVectorStore($id);
		$this->stdout("Withdrawn page {$id} from the vector store.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * Withdraw vectors for pages that are indexed but no longer displayable (inactive, deleted, empty).
	 *
	 * @return int
	 */
	public function actionCleanup()
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured.\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		$displayableIds = $this->normalizeIds($this->buildDisplayableQuery()->column());

		$staleQuery = RecordVectorIndex::find()
			->where(['status' => RecordVectorIndex::STATUS_ACTIVE, 'deleted' => RecordVectorIndex::NO]);
		if ($displayableIds !== []) {
			$staleQuery->andWhere(['not in', 'record_id', $displayableIds]);
		}
		$staleIds = $this->normalizeIds($staleQuery->select(['record_id'])->column());

		$total = count($staleIds);
		if ($total === 0) {
			$this->stdout("Nothing to clean up.\n", Console::FG_GREEN);
			return ExitCode::OK;
		}

		$this->stdout("Withdrawing {$total} stale page vector(s)...\n", Console::FG_GREEN);
		foreach ($staleIds as $pageId) {
			OpenAiRecordVectorStoreService::withdrawFromVectorStore($pageId);
			$this->stdout('.', Console::FG_GREEN);
		}

		$this->stdout("\nDone. Withdrawn: {$total}.\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * One-pass reconcile of the vector store against the DB: indexes displayable pages missing from
	 * the index, re-indexes those scraped again since their last indexing (`page.updated_at` newer than
	 * `indexed_at`), and withdraws vectors no longer displayable (a superset of cleanup).
	 *
	 * Scheduled every 30 minutes; it is the safety net for pages written by the scraper cron.
	 *
	 * @return int
	 */
	public function actionReconcile()
	{
		if (!OpenAiRecordVectorStoreService::isConfigured()) {
			$this->stderr("AI index is not configured.\n", Console::FG_RED);
			return ExitCode::CONFIG;
		}

		// id => page updated_at, for every page that belongs in the store.
		$displayable = [];
		foreach ($this->buildDisplayableQuery()->select(['p.id', 'p.updated_at'])->asArray()->all() as $row) {
			$id = OpenAiRecordVectorStoreService::normalizeRecordId($row['id']);
			if ($id !== 0) {
				$displayable[$id] = (string) ($row['updated_at'] ?? '');
			}
		}

		// id => indexed_at, for every vector currently live in the store.
		$indexed = [];
		foreach (RecordVectorIndex::find()
			->where(['deleted' => RecordVectorIndex::NO, 'status' => RecordVectorIndex::STATUS_ACTIVE])
			->select(['record_id', 'indexed_at'])
			->asArray()
			->all() as $row) {
			$id = OpenAiRecordVectorStoreService::normalizeRecordId($row['record_id']);
			if ($id !== 0) {
				$indexed[$id] = (string) ($row['indexed_at'] ?? '');
			}
		}

		$toSync = [];
		foreach ($displayable as $id => $updatedAt) {
			// MySQL DATETIME strings compare correctly as strings.
			if (!isset($indexed[$id]) || ($updatedAt !== '' && $indexed[$id] !== '' && strcmp($updatedAt, $indexed[$id]) > 0)) {
				$toSync[] = $id;
			}
		}
		$toWithdraw = array_keys(array_diff_key($indexed, $displayable));

		if ($toSync === [] && $toWithdraw === []) {
			$this->stdout("Nothing to reconcile.\n", Console::FG_GREEN);
			return ExitCode::OK;
		}

		$this->stdout('Reconciling: ' . count($toSync) . " to (re)index, " . count($toWithdraw) . " to withdraw...\n", Console::FG_GREEN);
		$done = 0;
		$failed = 0;
		foreach ($toSync as $pageId) {
			OpenAiRecordVectorStoreService::syncPage($pageId);
			$row = RecordVectorIndex::find()->where(['record_id' => $pageId])->one();
			if ($row !== null && (int) $row->status === RecordVectorIndex::STATUS_ACTIVE) {
				$done++;
				$this->stdout('.', Console::FG_GREEN);
			} else {
				$failed++;
				Yii::error('AiIndexController reconcile page ' . $pageId . ': ' . ($row->error_message ?? 'unknown'), __METHOD__);
				$this->stdout('x', Console::FG_RED);
			}
		}
		foreach ($toWithdraw as $pageId) {
			OpenAiRecordVectorStoreService::withdrawFromVectorStore($pageId);
			$this->stdout('-', Console::FG_YELLOW);
		}

		$this->stdout("\nDone. Indexed: {$done}, failed: {$failed}, withdrawn: " . count($toWithdraw) . ($failed > 0 ? ' — see record_vector_index.error_message' : '') . ".\n", Console::FG_GREEN);
		return ExitCode::OK;
	}

	/**
	 * IDs of pages that belong in the vector store — delegated to
	 * {@see OpenAiRecordVectorStoreService::buildDisplayableQuery()} so the console
	 * commands and the per-model sync gate share one rule.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	protected function buildDisplayableQuery()
	{
		return OpenAiRecordVectorStoreService::buildDisplayableQuery();
	}

	/**
	 * Positive unique ints, empties dropped.
	 *
	 * @param array $ids
	 * @return int[]
	 */
	protected function normalizeIds(array $ids)
	{
		$normalized = [];
		foreach ($ids as $id) {
			$id = OpenAiRecordVectorStoreService::normalizeRecordId($id);
			if ($id !== 0 && !in_array($id, $normalized, true)) {
				$normalized[] = $id;
			}
		}
		return $normalized;
	}
}
