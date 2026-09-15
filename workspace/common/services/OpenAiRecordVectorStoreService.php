<?php

namespace common\services;

use common\models\Assistant;
use common\models\Integration;
use common\models\KnowledgeBase;
use common\models\Page;
use common\models\RecordVectorIndex;
use Yii;
use yii\db\Expression;

/**
 * Indexes scraped pages into a dedicated OpenAI vector store (Knowledge Base) for semantic search and the chat.
 * Failures are logged only; they never throw out of {@see syncPage} so business operations are unaffected.
 *
 * For every displayable {@see Page} one plain-text file is generated ({@see buildIndexPlainText()}: website,
 * url, title, description and the visible text of the scraped HTML), uploaded to the OpenAI Files API and
 * attached to the Knowledge Base vector store; the mapping page ↔ OpenAI file lives in {@see RecordVectorIndex}.
 * Embeddings are computed by OpenAI when the file is ingested — nothing vector-like is stored locally.
 *
 * Configure {@see \Yii::$app->params}:
 * - `pageVectorKnowledgeBaseId` — optional PK override of an active OpenAI Knowledge Base. When 0/empty, the KB
 *   linked to the default chat Assistant is used, then any active OpenAI KB ({@see resolveKnowledgeBase()}). If none
 *   resolves, no indexing and no `record_vector_index` writes occur.
 * - `pageVectorSemanticSearch` — when true, callers may use vector retrieval ({@see searchPageIds()})
 * - `pageVectorSemanticSearchScoreThreshold` — optional 0..1 (higher = stricter)
 * - `pageVectorSemanticSearchRewriteQuery` — optional bool, default true (OpenAI may rewrite the query for retrieval)
 * - `pageVectorSemanticSearchRanker` — optional `auto` | `none` | `default-2024-11-15`
 * - `pageVectorSemanticSearchMaxResults`, `pageVectorSemanticSearchMaxRounds`, `pageVectorSemanticSearchCacheTtl`
 *
 * Temp index files are written to `<runtime>/record_vector` of the running application.
 */
class OpenAiRecordVectorStoreService
{
	/** @var bool */
	private static $knowledgeBaseLookupDone = false;

	/** @var KnowledgeBase|null Cached result of {@see resolveKnowledgeBase()} for this PHP process */
	private static $cachedKnowledgeBase;

	/** Max characters of page text written to the index file (keeps files reasonably sized for chunking). */
	const INDEX_TEXT_MAX_LENGTH = 200000;

	/**
	 * Normalizes a page id (int, numeric string) to a positive int. Returns 0 for empty / invalid input.
	 *
	 * @param mixed $id
	 */
	public static function normalizeRecordId($id): int
	{
		if ($id === null || $id === '' || $id === false || is_array($id)) {
			return 0;
		}
		if (is_object($id)) {
			$id = isset($id->id) ? $id->id : 0;
		}
		if (!is_numeric($id)) {
			return 0;
		}
		return max(0, (int) $id);
	}

	/**
	 * Resets the per-process Knowledge Base cache (tests and console loops that change the KB).
	 */
	public static function resetKnowledgeBaseCache(): void
	{
		self::$knowledgeBaseLookupDone = false;
		self::$cachedKnowledgeBase = null;
	}

	/**
	 * Defer work until after the HTTP response (non-blocking for the user).
	 *
	 * @param int $pageId
	 */
	public static function scheduleSync($pageId): void
	{
		$pageId = self::normalizeRecordId($pageId);
		if ($pageId === 0 || !self::isConfigured()) {
			return;
		}
		register_shutdown_function(static function () use ($pageId): void {
			try {
				self::syncPage($pageId);
			} catch (\Throwable $e) {
				// syncPage should not throw; kept as a safety net so shutdown never breaks the app.
				Yii::error('OpenAiRecordVectorStoreService::syncPage failed: ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * Remove vectors for a page after it is deactivated / deleted.
	 *
	 * @param int $pageId
	 */
	public static function scheduleWithdraw($pageId): void
	{
		$pageId = self::normalizeRecordId($pageId);
		if ($pageId === 0 || !self::isConfigured()) {
			return;
		}
		register_shutdown_function(static function () use ($pageId): void {
			try {
				self::withdrawFromVectorStore($pageId);
			} catch (\Throwable $e) {
				Yii::error('OpenAiRecordVectorStoreService::withdrawFromVectorStore failed: ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * Before hard-deleting a page row, drop its index row; after the response, purge the OpenAI objects
	 * with {@see scheduleRemotePurge()}.
	 *
	 * @param int $pageId
	 * @return array{0:string,1:string,2:?string}|null vector_store_id, openai_file_id, vector_store_file_id
	 */
	public static function detachPageIndexRow($pageId): ?array
	{
		$pageId = self::normalizeRecordId($pageId);
		if ($pageId === 0) {
			return null;
		}
		$row = RecordVectorIndex::find()->where(['record_id' => $pageId])->one();
		if ($row === null) {
			return null;
		}
		$meta = [
			(string) $row->vector_store_id,
			(string) $row->openai_file_id,
			$row->vector_store_file_id !== null && $row->vector_store_file_id !== '' ? (string) $row->vector_store_file_id : null,
		];
		RecordVectorIndex::deleteAll(['record_id' => $pageId]);
		self::bumpSearchCacheGeneration();
		return $meta;
	}

	/**
	 * @param array|null $meta as returned by {@see detachPageIndexRow()}
	 */
	public static function scheduleRemotePurge(?array $meta): void
	{
		if ($meta === null || $meta[0] === '' || !self::isOpenAiFileId($meta[1])) {
			return;
		}
		register_shutdown_function(static function () use ($meta): void {
			try {
				self::tryRemoveRemote($meta[0], $meta[1], $meta[2]);
			} catch (\Throwable $e) {
				Yii::warning('OpenAI vector purge (shutdown): ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * True only when an active OpenAI {@see KnowledgeBase} resolves in DB.
	 * No vector/OpenAI work runs when this is false — including writes to `record_vector_index`.
	 */
	public static function isConfigured(): bool
	{
		return self::resolveKnowledgeBase() !== null;
	}

	public static function semanticSearchEnabled(): bool
	{
		return self::isConfigured() && !empty(Yii::$app->params['pageVectorSemanticSearch']);
	}

	/**
	 * True only when the AI chat is fully operational: an active OpenAI {@see Integration} with an API key,
	 * an active OpenAI {@see KnowledgeBase} with a provisioned vector store, and at least one indexed page.
	 *
	 * Used to decide whether the embed chat should answer from the knowledge base.
	 */
	public static function isChatAvailable(): bool
	{
		try {
			$integration = Integration::findOpenAI();
			if ($integration === null || (string) $integration->getApiKey() === '') {
				return false;
			}

			$kb = self::resolveKnowledgeBase();
			if ($kb === null || empty($kb->vector_store_id)) {
				return false;
			}

			return RecordVectorIndex::find()
				->where([
					'status' => RecordVectorIndex::STATUS_ACTIVE,
					'deleted' => RecordVectorIndex::NO,
				])
				->exists();
		} catch (\Throwable $e) {
			Yii::warning('isChatAvailable check failed: ' . $e->getMessage(), __METHOD__);
			return false;
		}
	}

	/**
	 * Bumped on every sync / withdraw / hard-detach so cached `query → page_ids` entries from before
	 * the change become unreachable (the generation is part of the cache key).
	 */
	public static function bumpSearchCacheGeneration(): void
	{
		if (!Yii::$app->has('cache')) {
			return;
		}
		try {
			Yii::$app->cache->set('pvs:gen', microtime(true) . '_' . bin2hex(random_bytes(4)), 0);
		} catch (\Throwable $e) {
			Yii::warning('bumpSearchCacheGeneration: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Stable per-process generation token. Lazy-creates on first read so a cold cache still produces a key.
	 */
	public static function currentSearchCacheGeneration(): string
	{
		if (!Yii::$app->has('cache')) {
			return '0';
		}
		$gen = Yii::$app->cache->get('pvs:gen');
		if (is_string($gen) && $gen !== '') {
			return $gen;
		}
		try {
			$seed = microtime(true) . '_' . bin2hex(random_bytes(4));
			Yii::$app->cache->set('pvs:gen', $seed, 0);
			return $seed;
		} catch (\Throwable $e) {
			return '0';
		}
	}

	/**
	 * Ordered page IDs from vector search (best-effort), mapped via {@see RecordVectorIndex}.
	 * Uses several OpenAI search calls (max 50 hits each) with attribute filter `record_id nin …`
	 * to collect up to {@see OpenAIResponsesService::vectorSearchMaxUniqueCap()} distinct files.
	 *
	 * @param int|null $maxResults Cap 1..250; null uses `pageVectorSemanticSearchMaxResults` or 250.
	 * @param array<string,mixed>|null $audit When a variable is passed by reference, it is replaced with diagnostics.
	 * @return int[] Page ids, ordered by relevance.
	 */
	public static function searchPageIds(string $query, ?int $maxResults = null, &$audit = null, ?float $scoreThresholdOverride = null): array
	{
		$auditActive = func_num_args() > 2;
		if ($auditActive) {
			$audit = [
				'query' => trim($query),
				'configured' => self::isConfigured(),
				'skipped_reason' => null,
				'vector_store_id' => null,
				'max_results_target' => null,
				'per_request' => null,
				'rounds' => [],
				'file_ids_ordered' => [],
				'mapped_record_ids' => [],
				'unmapped_file_ids' => [],
			];
		}

		$query = trim($query);
		if ($query === '') {
			if ($auditActive) {
				$audit['skipped_reason'] = 'empty_query';
			}
			return [];
		}
		if (!self::isConfigured()) {
			if ($auditActive) {
				$audit['skipped_reason'] = 'not_configured';
			}
			return [];
		}
		$kb = self::resolveKnowledgeBase();
		if ($kb === null || empty($kb->vector_store_id)) {
			if ($auditActive) {
				$audit['skipped_reason'] = $kb === null ? 'knowledge_base_missing' : 'vector_store_id_empty';
			}
			return [];
		}
		$vsId = (string) $kb->vector_store_id;
		if ($auditActive) {
			$audit['vector_store_id'] = $vsId;
		}
		$scoreThreshold = max(
			0.0,
			min(1.0, $scoreThresholdOverride ?? (float) (Yii::$app->params['pageVectorSemanticSearchScoreThreshold'] ?? 0.7))
		);
		$rewriteQuery = !array_key_exists('pageVectorSemanticSearchRewriteQuery', Yii::$app->params)
			|| !empty(Yii::$app->params['pageVectorSemanticSearchRewriteQuery']);
		$allowedRankers = ['auto', 'none', 'default-2024-11-15'];
		$ranker = isset(Yii::$app->params['pageVectorSemanticSearchRanker'])
			? (string) Yii::$app->params['pageVectorSemanticSearchRanker']
			: 'default-2024-11-15';
		if (!in_array($ranker, $allowedRankers, true)) {
			$ranker = 'auto';
		}
		if ($auditActive) {
			$audit['score_threshold'] = sprintf('%.4f', $scoreThreshold);
			$audit['rewrite_query'] = $rewriteQuery;
			$audit['ranker'] = $ranker;
		}
		$target = $maxResults ?? (int) (Yii::$app->params['pageVectorSemanticSearchMaxResults'] ?? 250);
		$target = max(1, min(OpenAIResponsesService::vectorSearchMaxUniqueCap(), $target));
		$perCall = OpenAIResponsesService::vectorSearchMaxPerRequest();
		if ($auditActive) {
			$audit['max_results_target'] = $target;
			$audit['per_request'] = $perCall;
		}

		// Cache lookup — same query / parameters reuse the previous result for a short TTL.
		$cacheTtl = (int) (Yii::$app->params['pageVectorSemanticSearchCacheTtl'] ?? 0);
		$cache = null;
		$cacheKey = null;
		if (!$auditActive && $cacheTtl > 0 && Yii::$app->has('cache')) {
			$cache = Yii::$app->cache;
			$cacheKey = 'pvs:' . sha1(implode('|', [
				self::currentSearchCacheGeneration(),
				$vsId,
				$query,
				$target,
				sprintf('%.4f', $scoreThreshold),
				$ranker,
				$rewriteQuery ? '1' : '0',
			]));
			$cached = $cache->get($cacheKey);
			if (is_array($cached)) {
				return $cached;
			}
		}

		$excludeRecordIds = [];
		$fileOrder = [];
		$seenFile = [];
		$maxRoundsParam = (int) (Yii::$app->params['pageVectorSemanticSearchMaxRounds'] ?? 2);
		$maxRoundsParam = max(1, min(25, $maxRoundsParam));
		$maxRounds = min($maxRoundsParam, (int) ceil($target / $perCall) + 1);

		for ($round = 0; $round < $maxRounds && count($fileOrder) < $target; $round++) {
			$filters = null;
			if ($excludeRecordIds !== []) {
				$filters = [
					'type' => 'and',
					'filters' => [
						[
							'key' => 'record_id',
							'type' => 'nin',
							// record_id attributes are numeric page ids (see vectorStoreFileAttributes()).
							'value' => array_values(array_unique(array_map('intval', $excludeRecordIds))),
						],
					],
				];
			}
			$roundInfo = [
				'round' => $round,
				'filters' => $filters,
				'raw_snippet_count' => 0,
				'new_unique_file_ids_this_round' => [],
				'snippets' => [],
				'api_error' => null,
			];
			try {
				$snippets = OpenAIResponsesService::searchVectorStore(
					$vsId,
					$query,
					$perCall,
					$scoreThreshold,
					$filters,
					$rewriteQuery,
					$ranker
				);
			} catch (\Throwable $e) {
				Yii::warning('Vector search failed (round ' . $round . '): ' . $e->getMessage(), __METHOD__);
				$roundInfo['api_error'] = $e->getMessage();
				if ($auditActive) {
					$audit['rounds'][] = $roundInfo;
				}
				break;
			}
			$roundInfo['raw_snippet_count'] = count($snippets);
			if ($snippets === []) {
				if ($auditActive) {
					$audit['rounds'][] = $roundInfo;
				}
				break;
			}
			$newFileIds = [];
			$snippetLogCap = 30;
			$snippetLogged = 0;
			foreach ($snippets as $s) {
				if (!is_array($s)) {
					continue;
				}
				$fid = (string) ($s['file_id'] ?? '');
				if ($auditActive && $snippetLogged < $snippetLogCap) {
					$roundInfo['snippets'][] = [
						'file_id' => $fid,
						'filename' => (string) ($s['filename'] ?? ''),
						'text_preview' => mb_substr((string) ($s['text'] ?? ''), 0, 500),
					];
					$snippetLogged++;
				}
				if ($fid === '' || isset($seenFile[$fid])) {
					continue;
				}
				$seenFile[$fid] = true;
				$fileOrder[] = $fid;
				$newFileIds[] = $fid;
				if (count($fileOrder) >= $target) {
					if ($auditActive) {
						if (count($snippets) > $snippetLogged) {
							$roundInfo['snippets_omitted'] = count($snippets) - $snippetLogged;
						}
						$roundInfo['new_unique_file_ids_this_round'] = $newFileIds;
						$audit['rounds'][] = $roundInfo;
					}
					break 2;
				}
			}
			if ($auditActive && count($snippets) > $snippetLogged) {
				$roundInfo['snippets_omitted'] = count($snippets) - $snippetLogged;
			}
			$roundInfo['new_unique_file_ids_this_round'] = $newFileIds;
			if ($auditActive) {
				$audit['rounds'][] = $roundInfo;
			}
			if ($newFileIds === []) {
				break;
			}
			$rows = RecordVectorIndex::find()
				->where([
					'openai_file_id' => $newFileIds,
					'deleted' => RecordVectorIndex::NO,
					'status' => RecordVectorIndex::STATUS_ACTIVE,
				])
				->all();
			foreach ($rows as $row) {
				$excludeRecordIds[] = (int) $row->record_id;
			}
		}

		if ($fileOrder === []) {
			if ($auditActive) {
				$audit['skipped_reason'] = $audit['skipped_reason'] ?? 'no_file_ids_from_api';
			}
			if ($cache !== null && $cacheKey !== null) {
				$cache->set($cacheKey, [], $cacheTtl);
			}
			return [];
		}
		$rows = RecordVectorIndex::find()
			->where([
				'openai_file_id' => $fileOrder,
				'deleted' => RecordVectorIndex::NO,
				'status' => RecordVectorIndex::STATUS_ACTIVE,
			])
			->indexBy('openai_file_id')
			->all();
		$ids = [];
		foreach ($fileOrder as $fid) {
			if (!isset($rows[$fid])) {
				continue;
			}
			$ids[] = (int) $rows[$fid]->record_id;
		}
		if ($auditActive) {
			$audit['file_ids_ordered'] = $fileOrder;
			$audit['mapped_record_ids'] = $ids;
			$unmapped = [];
			foreach ($fileOrder as $fid) {
				if (!isset($rows[$fid])) {
					$unmapped[] = $fid;
				}
			}
			$audit['unmapped_file_ids'] = $unmapped;
			$audit['skipped_reason'] = $ids === [] ? 'no_db_rows_for_openai_files' : null;
		}
		if ($cache !== null && $cacheKey !== null) {
			$cache->set($cacheKey, $ids, $cacheTtl);
		}
		return $ids;
	}

	/**
	 * Maps page ids from OpenAI file ids (e.g. citations of a chat answer) through {@see RecordVectorIndex}.
	 *
	 * @param string[] $fileIds
	 * @return int[] Page ids in the order of the given file ids (unknown files skipped).
	 */
	public static function mapFileIdsToPageIds(array $fileIds): array
	{
		$fileIds = array_values(array_unique(array_filter(array_map('strval', $fileIds))));
		if ($fileIds === []) {
			return [];
		}
		$rows = RecordVectorIndex::find()
			->where([
				'openai_file_id' => $fileIds,
				'deleted' => RecordVectorIndex::NO,
				'status' => RecordVectorIndex::STATUS_ACTIVE,
			])
			->indexBy('openai_file_id')
			->all();
		$ids = [];
		foreach ($fileIds as $fid) {
			if (isset($rows[$fid])) {
				$ids[] = (int) $rows[$fid]->record_id;
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Positive, unique int literals safe to inline in SQL.
	 *
	 * @param array $ids
	 * @return int[]
	 */
	protected static function intLiterals(array $ids): array
	{
		$literals = [];
		foreach ($ids as $id) {
			$id = self::normalizeRecordId($id);
			if ($id === 0 || in_array($id, $literals, true)) {
				continue;
			}
			$literals[] = $id;
		}
		return $literals;
	}

	/**
	 * Apply relevance ordering for page IDs (MySQL FIELD / generic CASE).
	 *
	 * @param string $columnSql Unquoted column reference, e.g. p.id.
	 * @param int[] $ids
	 */
	public static function orderByPageIdsExpression(string $columnSql, array $ids): Expression
	{
		$literals = self::intLiterals($ids);
		if ($literals === []) {
			return new Expression('1');
		}
		$driver = Yii::$app->db->driverName;
		if ($driver === 'mysql' || $driver === 'mysqli') {
			return new Expression('FIELD(' . $columnSql . ', ' . implode(',', $literals) . ')');
		}
		$parts = [];
		foreach ($literals as $i => $literal) {
			$parts[] = 'WHEN ' . $literal . ' THEN ' . $i;
		}
		return new Expression('CASE ' . $columnSql . ' ' . implode(' ', $parts) . ' ELSE 999999 END');
	}

	/**
	 * Order rows so semantic hits (IDs in $semanticOrderedIds) come first in that order, then others by created_at.
	 *
	 * @param string $idColumnSql Unquoted column reference, e.g. p.id.
	 * @param int[] $semanticOrderedIds
	 * @return array<string, int|\yii\db\Expression> Yii {@see \yii\db\ActiveQuery::orderBy()} payload
	 */
	public static function orderSemanticFirstThenDate(string $idColumnSql, array $semanticOrderedIds): array
	{
		$literals = self::intLiterals($semanticOrderedIds);
		$alias = strpos($idColumnSql, '.') !== false ? substr($idColumnSql, 0, strpos($idColumnSql, '.')) . '.' : '';
		if ($literals === []) {
			return [
				$alias . 'created_at' => SORT_DESC,
				$idColumnSql => SORT_DESC,
			];
		}
		$field = 'FIELD(' . $idColumnSql . ', ' . implode(',', $literals) . ')';
		return [
			new Expression("({$field} > 0) DESC"),
			new Expression("{$field} ASC"),
			$alias . 'created_at' => SORT_DESC,
			$idColumnSql => SORT_DESC,
		];
	}

	/**
	 * Generates the index file for a page and (re)uploads it to the vector store.
	 * Never throws; the outcome is recorded in the {@see RecordVectorIndex} row (status + error_message).
	 *
	 * @param int $pageId
	 */
	public static function syncPage($pageId): void
	{
		$pageId = self::normalizeRecordId($pageId);
		if ($pageId === 0 || !self::isConfigured()) {
			return;
		}
		$kb = self::resolveKnowledgeBase();
		$page = Page::find()
			->where(['id' => $pageId, 'deleted' => Page::NO])
			->one();
		// Self-correcting: a sync scheduled after ANY lifecycle change either indexes or withdraws,
		// depending on where the page landed. Not displayable (deleted / not ACTIVE / empty content)
		// means its vectors must go, so an edit or status flip can never leave stale vectors.
		if ($page === null || !self::isDisplayable($page)) {
			self::withdrawFromVectorStore($pageId);
			return;
		}

		$row = RecordVectorIndex::find()->where(['record_id' => $pageId])->one();
		if ($row === null) {
			$row = new RecordVectorIndex();
			$row->record_id = $pageId;
		}

		$tmpFile = null;
		$openaiFileId = null;
		try {
			OpenAIResponsesService::ensureVectorStore($kb);
			$vsId = (string) $kb->vector_store_id;
			$row->vector_store_id = $vsId;

			if (!empty($row->vector_store_id) && self::isOpenAiFileId($row->openai_file_id)) {
				self::tryRemoveRemote($row->vector_store_id, $row->openai_file_id, $row->vector_store_file_id);
			}

			$text = self::buildIndexPlainText($page);
			$tmpFile = self::writeIndexFile($pageId, $text);

			$openaiFileId = OpenAIResponsesService::uploadAssistantFile($tmpFile, self::indexFileName($pageId));
			$attach = OpenAIResponsesService::attachFileToVectorStore(
				$vsId,
				$openaiFileId,
				self::vectorStoreFileAttributes($page),
				null
			);
			OpenAIResponsesService::waitForVectorStoreFileReady($vsId, $openaiFileId);

			@unlink($tmpFile);
			$tmpFile = null;

			$row->openai_file_id = $openaiFileId;
			$row->vector_store_file_id = $attach['vector_store_file_id'] !== '' ? $attach['vector_store_file_id'] : null;
			$row->status = RecordVectorIndex::STATUS_ACTIVE;
			$row->deleted = RecordVectorIndex::NO;
			$row->indexed_at = (new \DateTime())->format('Y-m-d H:i:s');
			$row->error_message = null;
			if (!$row->save(false)) {
				throw new \RuntimeException('Failed to save RecordVectorIndex');
			}
			self::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			if ($tmpFile !== null && is_file($tmpFile)) {
				@unlink($tmpFile);
			}
			if (self::isOpenAiFileId($openaiFileId)) {
				try {
					OpenAIResponsesService::deleteFile($openaiFileId);
				} catch (\Throwable $ignored) {
				}
			}
			if (self::isConfigured()) {
				if (!self::isOpenAiFileId($row->openai_file_id ?? null)) {
					$row->openai_file_id = RecordVectorIndex::FILE_ID_WITHDRAWN;
				}
				if ($row->vector_store_id === null || $row->vector_store_id === '') {
					$row->vector_store_id = RecordVectorIndex::VECTOR_STORE_ID_UNKNOWN;
				}
				$row->status = RecordVectorIndex::STATUS_ERROR;
				$row->error_message = mb_substr($e->getMessage(), 0, 65000);
				try {
					$row->save(false);
				} catch (\Throwable $saveEx) {
					Yii::error(
						'OpenAiRecordVectorStoreService: could not persist error state for page ' . $pageId . ': ' . $saveEx->getMessage(),
						__METHOD__
					);
				}
			}
			Yii::warning(
				'OpenAiRecordVectorStoreService::syncPage (non-fatal) page=' . $pageId . ': ' . $e->getMessage(),
				__METHOD__
			);
		}
	}

	/**
	 * @param int $pageId
	 */
	public static function withdrawFromVectorStore($pageId): void
	{
		$pageId = self::normalizeRecordId($pageId);
		if ($pageId === 0 || !self::isConfigured()) {
			return;
		}
		$row = RecordVectorIndex::find()->where(['record_id' => $pageId])->one();
		if ($row === null) {
			return;
		}
		$kb = self::resolveKnowledgeBase();
		try {
			if (!empty($row->vector_store_id) && self::isOpenAiFileId($row->openai_file_id)) {
				self::tryRemoveRemote($row->vector_store_id, $row->openai_file_id, $row->vector_store_file_id);
			}
		} catch (\Throwable $e) {
			Yii::warning('withdrawFromVectorStore remote: ' . $e->getMessage(), __METHOD__);
		}
		$row->status = RecordVectorIndex::STATUS_INACTIVE;
		$row->deleted = RecordVectorIndex::YES;
		$row->openai_file_id = RecordVectorIndex::FILE_ID_WITHDRAWN;
		$row->vector_store_file_id = null;
		if ($kb !== null && !empty($kb->vector_store_id)) {
			$row->vector_store_id = (string) $kb->vector_store_id;
		} elseif ($row->vector_store_id === null || $row->vector_store_id === '') {
			$row->vector_store_id = RecordVectorIndex::VECTOR_STORE_ID_UNKNOWN;
		}
		$row->indexed_at = null;
		$row->error_message = null;
		try {
			$row->save(false);
			self::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			Yii::warning('withdrawFromVectorStore save: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Whether the page belongs in the vector store: ACTIVE, not deleted and with scraped content.
	 * Must agree with {@see buildDisplayableQuery()} (its SQL twin).
	 */
	public static function isDisplayable(Page $page): bool
	{
		if ((int) $page->status !== Page::STATUS_ACTIVE || (int) $page->deleted !== Page::NO) {
			return false;
		}
		return trim((string) $page->content) !== '';
	}

	/**
	 * IDs of pages that belong in the vector store — the SQL twin of {@see isDisplayable()}.
	 * Used by ai-index/status, reindex, cleanup and reconcile.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public static function buildDisplayableQuery()
	{
		return Page::find()
			->alias('p')
			->where([
				'p.status' => Page::STATUS_ACTIVE,
				'p.deleted' => Page::NO,
			])
			->andWhere(['not', ['p.content' => null]])
			->andWhere(['<>', new Expression('TRIM(p.content)'), ''])
			->select(['p.id'])
			->distinct();
	}

	/**
	 * Attributes on the vector store file (OpenAI) — same keys/values are written first in {@see buildIndexPlainText()}.
	 * `record_id` is the numeric page id; it is the key used by the `nin` filter of {@see searchPageIds()}.
	 *
	 * @return array<string, int|string|bool>
	 */
	public static function vectorStoreFileAttributes(Page $page): array
	{
		return [
			'record_id' => (int) $page->id,
			'status' => (int) $page->status,
			'website' => mb_substr(trim((string) $page->website), 0, 500),
			'url' => mb_substr(trim((string) $page->url), 0, 500),
		];
	}

	/**
	 * Plain-text payload for the vector index file: one line per field, always `property: value`.
	 * Values are single-line (newlines collapsed to spaces) for stable chunking, except the page text
	 * which keeps paragraph breaks so OpenAI chunks it along the document structure.
	 * Starts with the same attributes as the vector store file attachment.
	 */
	public static function buildIndexPlainText(Page $page): string
	{
		$lines = [];
		foreach (self::vectorStoreFileAttributes($page) as $key => $value) {
			if (is_bool($value)) {
				$value = $value ? '1' : '0';
			}
			$lines[] = self::indexPropertyLine((string) $key, (string) $value);
		}
		$html = (string) $page->content;
		$lines[] = self::indexPropertyLine('title', self::extractTitle($html));
		$lines[] = self::indexPropertyLine('description', self::extractMetaDescription($html));
		$lines[] = self::indexPropertyLine('language', self::extractLanguage($html));
		$lines[] = self::indexPropertyLine('scraped_at', (string) ($page->updated_at ?: $page->created_at));
		$text = self::htmlToPlainText($html);
		if (mb_strlen($text) > self::INDEX_TEXT_MAX_LENGTH) {
			$text = mb_substr($text, 0, self::INDEX_TEXT_MAX_LENGTH);
		}
		$lines[] = 'content:';
		$lines[] = $text;
		return implode("\n", $lines) . "\n";
	}

	/**
	 * Single line `property: value` for index .txt files (vector store ingestion).
	 */
	public static function indexPropertyLine(string $property, string $value): string
	{
		$property = trim($property);
		$value = trim((string) preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\r", "\n"], ' ', $value)));
		return $property . ': ' . $value;
	}

	/**
	 * `<title>` of a scraped HTML document ('' when absent).
	 */
	public static function extractTitle(string $html): string
	{
		if (preg_match('~<title[^>]*>(.*?)</title>~isu', $html, $m)) {
			return self::decodeText($m[1]);
		}
		return '';
	}

	/**
	 * `<meta name="description">` (or og:description) of a scraped HTML document ('' when absent).
	 */
	public static function extractMetaDescription(string $html): string
	{
		$patterns = [
			'~<meta\s+[^>]*name\s*=\s*["\']description["\'][^>]*content\s*=\s*["\']([^"\']*)["\']~isu',
			'~<meta\s+[^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*name\s*=\s*["\']description["\']~isu',
			'~<meta\s+[^>]*property\s*=\s*["\']og:description["\'][^>]*content\s*=\s*["\']([^"\']*)["\']~isu',
		];
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $html, $m)) {
				return self::decodeText($m[1]);
			}
		}
		return '';
	}

	/**
	 * `<html lang="…">` of a scraped HTML document ('' when absent).
	 */
	public static function extractLanguage(string $html): string
	{
		if (preg_match('~<html[^>]*\slang\s*=\s*["\']([a-zA-Z-]+)["\']~isu', $html, $m)) {
			return strtolower($m[1]);
		}
		return '';
	}

	/**
	 * Visible text of a scraped HTML document: scripts/styles/head removed, block tags turned into line
	 * breaks, entities decoded, whitespace collapsed. Plain (non-HTML) content is returned normalized.
	 */
	public static function htmlToPlainText(string $html): string
	{
		$text = $html;
		// Drop non-content sections entirely.
		$text = (string) preg_replace('~<(script|style|noscript|template|svg|iframe|head)\b[^>]*>.*?</\1\s*>~isu', ' ', $text);
		$text = (string) preg_replace('~<!--.*?-->~su', ' ', $text);
		// Block-level boundaries become paragraph breaks so chunking follows the document structure.
		$text = (string) preg_replace('~<\s*br\s*/?\s*>~iu', "\n", $text);
		$text = (string) preg_replace('~</\s*(p|div|section|article|header|footer|nav|aside|main|h[1-6]|li|ul|ol|table|tr|blockquote|pre|dd|dt|dl|figure|figcaption|form|fieldset|address)\s*>~iu', "\n", $text);
		$text = (string) preg_replace('~<\s*(p|div|section|article|header|footer|nav|aside|main|h[1-6]|li|ul|ol|table|tr|blockquote|pre|dd|dt|dl|figure|figcaption|form|fieldset|address|hr)\b[^>]*>~iu', "\n", $text);
		$text = (string) preg_replace('~</?\s*(td|th)\b[^>]*>~iu', ' ', $text);
		$text = strip_tags($text);
		$text = self::decodeText($text);
		// Normalize whitespace: collapse runs of spaces, keep at most one blank line between paragraphs.
		$text = str_replace(["\r\n", "\r"], "\n", $text);
		$text = (string) preg_replace('/[ \t\x{00A0}\x{200B}]+/u', ' ', $text);
		$text = (string) preg_replace('/ *\n */u', "\n", $text);
		$text = (string) preg_replace('/\n{3,}/u', "\n\n", $text);
		return trim($text);
	}

	/**
	 * Decodes HTML entities and normalizes the string to valid UTF-8.
	 */
	protected static function decodeText(string $value): string
	{
		$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		if (!mb_check_encoding($value, 'UTF-8')) {
			$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
		}
		return trim((string) preg_replace('/\s+/u', ' ', $value));
	}

	/**
	 * Resolves the KnowledgeBase that hosts the page vector index.
	 *
	 * Order: `pageVectorKnowledgeBaseId` param (optional PK override) → first OpenAI KB linked to the chat
	 * Assistant ({@see Assistant::findChatAssistant()}) → oldest active OpenAI KB in DB. Cached per PHP process.
	 */
	public static function resolveKnowledgeBase(): ?KnowledgeBase
	{
		if (self::$knowledgeBaseLookupDone) {
			return self::$cachedKnowledgeBase;
		}
		self::$knowledgeBaseLookupDone = true;
		self::$cachedKnowledgeBase = null;

		try {
			$id = (int) (Yii::$app->params['pageVectorKnowledgeBaseId'] ?? 0);
			if ($id > 0) {
				$kb = KnowledgeBase::find()
					->where([
						'id' => $id,
						'status' => KnowledgeBase::STATUS_ACTIVE,
						'deleted' => KnowledgeBase::NO,
					])
					->one();
				if ($kb !== null && (int) $kb->provider === KnowledgeBase::PROVIDER_OPENAI) {
					self::$cachedKnowledgeBase = $kb;
					return $kb;
				}
			}

			// Prefer the KB(s) linked to the chat assistant configured in the backend.
			$assistant = Assistant::findChatAssistant();
			if ($assistant !== null) {
				foreach ($assistant->knowledgeBases as $kb) {
					if ((int) $kb->provider === KnowledgeBase::PROVIDER_OPENAI
						&& (int) $kb->status === KnowledgeBase::STATUS_ACTIVE
						&& (int) $kb->deleted === KnowledgeBase::NO
					) {
						self::$cachedKnowledgeBase = $kb;
						return $kb;
					}
				}
			}

			// Last resort: any active OpenAI KB (oldest first, deterministic).
			$kb = KnowledgeBase::find()
				->where([
					'provider' => KnowledgeBase::PROVIDER_OPENAI,
					'status' => KnowledgeBase::STATUS_ACTIVE,
					'deleted' => KnowledgeBase::NO,
				])
				->orderBy(['id' => SORT_ASC])
				->one();
			self::$cachedKnowledgeBase = $kb;
			return $kb;
		} catch (\Throwable $e) {
			// Missing tables (fresh install) or DB errors: behave as "not configured".
			Yii::warning('resolveKnowledgeBase: ' . $e->getMessage(), __METHOD__);
			self::$cachedKnowledgeBase = null;
			return null;
		}
	}

	/**
	 * @param string|null $id
	 */
	public static function isOpenAiFileId($id): bool
	{
		return is_string($id) && $id !== '' && strpos($id, 'file-') === 0;
	}

	/**
	 * Name of the per-page index file as uploaded to OpenAI.
	 */
	public static function indexFileName(int $pageId): string
	{
		return 'page_' . $pageId . '.txt';
	}

	/**
	 * Local scratch directory for page .txt payloads before the OpenAI upload: `<runtime>/record_vector`
	 * of the running application (per tenant, always writable).
	 */
	public static function getRecordVectorTempDirectory(): string
	{
		return rtrim(Yii::$app->getRuntimePath(), '/\\') . DIRECTORY_SEPARATOR . 'record_vector';
	}

	/**
	 * Writes the index payload to a temp file and returns its path.
	 *
	 * @throws \RuntimeException
	 */
	protected static function writeIndexFile(int $pageId, string $text): string
	{
		$tmpDir = self::getRecordVectorTempDirectory();
		if (!is_dir($tmpDir) && !@mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
			throw new \RuntimeException('Cannot create temp directory: ' . $tmpDir);
		}
		$tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'page_' . $pageId . '_' . bin2hex(random_bytes(4)) . '.txt';
		if (file_put_contents($tmpFile, $text) === false) {
			throw new \RuntimeException('Failed to write temp index file');
		}
		return $tmpFile;
	}

	protected static function tryRemoveRemote(string $vectorStoreId, string $openaiFileId, ?string $vectorStoreFileId): void
	{
		if ($vectorStoreId === '' || !self::isOpenAiFileId($openaiFileId)) {
			return;
		}
		try {
			OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $openaiFileId);
		} catch (\Throwable $e) {
			if ($vectorStoreFileId) {
				try {
					OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $vectorStoreFileId);
				} catch (\Throwable $e2) {
					Yii::warning('removeFileFromVectorStore: ' . $e->getMessage() . ' / ' . $e2->getMessage(), __METHOD__);
				}
			} else {
				Yii::warning('removeFileFromVectorStore: ' . $e->getMessage(), __METHOD__);
			}
		}
		try {
			OpenAIResponsesService::deleteFile($openaiFileId);
		} catch (\Throwable $e) {
			Yii::warning('deleteFile: ' . $e->getMessage(), __METHOD__);
		}
	}
}
