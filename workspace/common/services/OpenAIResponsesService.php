<?php

namespace common\services;

use common\models\Assistant;
use common\models\Conversation;
use common\models\Integration;
use common\models\KnowledgeBase;
use Yii;

/**
 * OpenAI Responses API + Vector Stores + Conversations API service.
 *
 * Uses the current (non-deprecated) OpenAI APIs:
 *   - POST /v1/responses          – generate a response with built-in tools
 *   - POST /v1/conversations       – create a persistent conversation
 *   - POST /v1/vector_stores       – create / manage vector stores
 *   - POST /v1/files               – upload files for file_search
 *   - POST /v1/vector_stores/{id}/files – attach file to vector store
 */
class OpenAIResponsesService
{
	private const BASE_URL = 'https://api.openai.com/v1';
	private const DEFAULT_SEARCH_MAX_RESULTS = 8;
	private const DEFAULT_SEARCH_SCORE_THRESHOLD = 0.25;
	private const VECTOR_SEARCH_MAX_PER_REQUEST = 50;
	private const VECTOR_SEARCH_MAX_UNIQUE_CAP = 250;

	public static function vectorSearchMaxPerRequest(): int
	{
		return self::VECTOR_SEARCH_MAX_PER_REQUEST;
	}

	public static function vectorSearchMaxUniqueCap(): int
	{
		return self::VECTOR_SEARCH_MAX_UNIQUE_CAP;
	}

	private const VECTOR_FILE_POLL_INTERVAL_US = 800000; // 0.8s
	private const VECTOR_FILE_MAX_WAIT_S = 180;          // 3 minutes

	// ─── Responses API ─────────────────────────────────────────

	/**
	 * Create a model response (single-turn, no conversation state).
	 *
	 * @param string $model              e.g. "gpt-5.2"
	 * @param array|string $input        Input messages or plain text
	 * @param string|null $instructions  Developer-level system instructions
	 * @param array $vectorStoreIds      Vector store IDs for file_search
	 * @param float|null $temperature
	 * @param float|null $topP
	 * @return string The assistant's text response
	 */
	public static function createResponse(
		string $model,
		$input,
		?string $instructions = null,
		array $vectorStoreIds = [],
		?float $temperature = null,
		?float $topP = null
	): string {
		$apiKey = self::getApiKey();

		$body = [
			'model' => $model,
			'input' => $input,
		];

		if ($instructions !== null && $instructions !== '') {
			$body['instructions'] = $instructions;
		}

		if (!empty($vectorStoreIds)) {
			$body['tools'] = [
				[
					'type' => 'file_search',
					'vector_store_ids' => array_values($vectorStoreIds),
				],
			];
		}

		if ($temperature !== null) {
			$body['temperature'] = $temperature;
		}
		if ($topP !== null) {
			$body['top_p'] = $topP;
		}

		$data = self::request('POST', '/responses', $body, $apiKey);

		return self::extractOutputText($data);
	}

	/**
	 * Create a model response and return raw payload (used when caller needs annotations/citations).
	 */
	public static function createResponseRaw(
		string $model,
		$input,
		?string $instructions = null,
		array $vectorStoreIds = [],
		?float $temperature = null,
		?float $topP = null,
		?int $fileSearchMaxResults = null,
		?float $fileSearchScoreThreshold = null
	): array {
		$apiKey = self::getApiKey();

		$body = [
			'model' => $model,
			'input' => $input,
		];

		if ($instructions !== null && $instructions !== '') {
			$body['instructions'] = $instructions;
		}

		if (!empty($vectorStoreIds)) {
			$tool = [
				'type' => 'file_search',
				'vector_store_ids' => array_values($vectorStoreIds),
			];
			if ($fileSearchMaxResults !== null) {
				// OpenAI accepts 1–50 for file_search.max_num_results (default 20). Clamp defensively.
				$tool['max_num_results'] = max(1, min(50, $fileSearchMaxResults));
			}
			if ($fileSearchScoreThreshold !== null) {
				// 0.0 = include every retrieved chunk (default), 1.0 = strict. Filtering low-score chunks here
				// keeps the model from grounding on weak matches that pollute downstream listing IDs.
				$tool['ranking_options'] = [
					'score_threshold' => max(0.0, min(1.0, $fileSearchScoreThreshold)),
				];
			}
			$body['tools'] = [$tool];
			$body['include'] = ['file_search_call.results'];
		}

		if ($temperature !== null) {
			$body['temperature'] = $temperature;
		}
		if ($topP !== null) {
			$body['top_p'] = $topP;
		}

		return self::request('POST', '/responses', $body, $apiKey);
	}

	/**
	 * Create a response within a persistent OpenAI conversation.
	 * Used for multi-turn chat (conversation state managed by OpenAI).
	 *
	 * @param string $model
	 * @param array|string $input        New user message(s) for this turn
	 * @param string $conversationId     OpenAI conversation ID
	 * @param string|null $instructions
	 * @param array $vectorStoreIds
	 * @param float|null $temperature
	 * @param float|null $topP
	 * @return string The assistant's text response
	 */
	public static function createConversationResponse(
		string $model,
		$input,
		string $conversationId,
		?string $instructions = null,
		array $vectorStoreIds = [],
		?float $temperature = null,
		?float $topP = null
	): string {
		$apiKey = self::getApiKey();

		$body = [
			'model'        => $model,
			'input'        => $input,
			'conversation' => $conversationId,
			'store'        => true,
		];

		if ($instructions !== null && $instructions !== '') {
			$body['instructions'] = $instructions;
		}

		if (!empty($vectorStoreIds)) {
			$body['tools'] = [
				[
					'type' => 'file_search',
					'vector_store_ids' => array_values($vectorStoreIds),
				],
			];
		}

		if ($temperature !== null) {
			$body['temperature'] = $temperature;
		}
		if ($topP !== null) {
			$body['top_p'] = $topP;
		}

		$data = self::request('POST', '/responses', $body, $apiKey);

		return self::extractOutputText($data);
	}

	// ─── Conversations API ─────────────────────────────────────

	/**
	 * Create a new OpenAI conversation object.
	 */
	public static function createConversation(): string
	{
		$apiKey = self::getApiKey();
		$data = self::request('POST', '/conversations', new \stdClass(), $apiKey);

		$id = $data['id'] ?? null;
		if (empty($id)) {
			throw new \RuntimeException('OpenAI did not return a conversation ID');
		}
		return $id;
	}

	/**
	 * Ensure a local {@see Conversation} has an OpenAI conversation object. Creates it if missing.
	 */
	public static function ensureConversation(Conversation $conversation): string
	{
		if (!empty($conversation->openai_conversation_id)) {
			return $conversation->openai_conversation_id;
		}

		$openaiConvId = self::createConversation();
		$conversation->updateAttributes(['openai_conversation_id' => $openaiConvId]);
		return $openaiConvId;
	}

	// ─── Vector Stores ─────────────────────────────────────────

	public static function createVectorStore(string $name): string
	{
		$apiKey = self::getApiKey();
		$data = self::request('POST', '/vector_stores', ['name' => $name], $apiKey);

		$id = $data['id'] ?? null;
		if (empty($id)) {
			throw new \RuntimeException('OpenAI did not return a vector store ID');
		}
		return $id;
	}

	/**
	 * Ensure a KnowledgeBase has an OpenAI vector store. Creates if missing.
	 */
	public static function ensureVectorStore(KnowledgeBase $kb): string
	{
		if (!empty($kb->vector_store_id)) {
			return $kb->vector_store_id;
		}

		$vsId = self::createVectorStore((string)$kb->name);
		$kb->updateAttributes(['vector_store_id' => $vsId]);
		return $vsId;
	}

	public static function deleteVectorStore(string $vectorStoreId): void
	{
		$apiKey = self::getApiKey();
		self::request('DELETE', '/vector_stores/' . $vectorStoreId, null, $apiKey);
	}

	// ─── Files ─────────────────────────────────────────────────

	/**
	 * Upload a file and attach it to a vector store.
	 *
	 * @return string OpenAI file ID
	 */
	public static function uploadFileToVectorStore(string $vectorStoreId, string $filePath, string $fileName): string
	{
		$apiKey = self::getApiKey();

		$fileId = self::uploadFile($filePath, $fileName, $apiKey);

		self::attachFileToVectorStore($vectorStoreId, $fileId, [], $apiKey);

		self::waitForVectorStoreFileReady($vectorStoreId, $fileId, $apiKey);

		return $fileId;
	}

	/**
	 * Upload a local file to OpenAI Files API (purpose assistants). Returns file-… id.
	 */
	public static function uploadAssistantFile(string $filePath, string $fileName, ?string $apiKey = null): string
	{
		$apiKey = $apiKey ?: self::getApiKey();
		return self::uploadFile($filePath, $fileName, $apiKey);
	}

	/**
	 * Attach an uploaded OpenAI file to a vector store with optional attributes (metadata for filtering / search).
	 *
	 * @param string $vectorStoreId
	 * @param string $openaiFileId  file-… id from Files API
	 * @param array<string, int|string|bool> $attributes key-value pairs (OpenAI allows string, number, boolean)
	 * @param string|null $apiKey
	 * @return array{vector_store_file_id: string, openai_file_id: string, raw: array}
	 */
	public static function attachFileToVectorStore(
		string $vectorStoreId,
		string $openaiFileId,
		array $attributes = [],
		?string $apiKey = null
	): array {
		$apiKey = $apiKey ?: self::getApiKey();
		$body = [
			'file_id' => $openaiFileId,
		];
		if (!empty($attributes)) {
			$body['attributes'] = $attributes;
		}

		$data = self::request('POST', '/vector_stores/' . $vectorStoreId . '/files', $body, $apiKey);
		$vsFileId = (string)($data['id'] ?? '');
		$fileIdOut = (string)($data['file_id'] ?? $openaiFileId);

		return [
			'vector_store_file_id' => $vsFileId,
			'openai_file_id' => $fileIdOut,
			'raw' => $data,
		];
	}

	public static function removeFileFromVectorStore(string $vectorStoreId, string $fileId): void
	{
		$apiKey = self::getApiKey();
		self::request('DELETE', '/vector_stores/' . $vectorStoreId . '/files/' . $fileId, null, $apiKey);
	}

	public static function deleteFile(string $fileId): void
	{
		$apiKey = self::getApiKey();
		self::request('DELETE', '/files/' . $fileId, null, $apiKey);
	}

	/**
	 * Poll vector_store file status until it becomes completed.
	 */
	public static function waitForVectorStoreFileReady(
		string $vectorStoreId,
		string $fileId,
		?string $apiKey = null
	): void {
		$apiKey = $apiKey ?: self::getApiKey();
		$deadline = microtime(true) + self::VECTOR_FILE_MAX_WAIT_S;
		$lastStatus = 'unknown';

		while (microtime(true) < $deadline) {
			$data = self::request('GET', '/vector_stores/' . $vectorStoreId . '/files/' . $fileId, null, $apiKey);
			$status = (string)($data['status'] ?? '');
			$lastStatus = $status !== '' ? $status : $lastStatus;

			if ($status === 'completed') {
				return;
			}

			if ($status === 'failed' || $status === 'cancelled' || $status === 'expired') {
				$error = (string)($data['last_error']['message'] ?? '');
				$msg = $error !== '' ? $error : ('vector file status: ' . $status);
				throw new \RuntimeException('Vector store indexing failed: ' . $msg);
			}

			usleep(self::VECTOR_FILE_POLL_INTERVAL_US);
		}

		throw new \RuntimeException(
			'Vector store indexing timeout for file ' . $fileId . ' (last status: ' . $lastStatus . ')'
		);
	}

	// ─── Helpers for callers ───────────────────────────────────

	/**
	 * Collect vector_store_ids from all KBs linked to an assistant.
	 */
	public static function collectVectorStoreIds(Assistant $assistant): array
	{
		$ids = [];
		foreach ($assistant->knowledgeBases as $kb) {
			if (!empty($kb->vector_store_id)) {
				$ids[] = $kb->vector_store_id;
			}
		}
		return array_unique($ids);
	}

	/**
	 * Check if an assistant has any vector stores provisioned.
	 */
	public static function hasVectorStores(Assistant $assistant): bool
	{
		return !empty(self::collectVectorStoreIds($assistant));
	}

	// ─── Internal ──────────────────────────────────────────────

	/**
	 * API key of the OpenAI integration (default first, then any active one).
	 *
	 * @throws \RuntimeException when no OpenAI integration with a key is configured
	 */
	public static function getApiKey(): string
	{
		$integration = Integration::findOpenAI();

		if ($integration === null || (string) $integration->getApiKey() === '') {
			throw new \RuntimeException('OpenAI API key not configured');
		}

		return (string) $integration->getApiKey();
	}

	/**
	 * Whether an OpenAI integration with an API key exists (no API call is made).
	 */
	public static function isApiKeyConfigured(): bool
	{
		try {
			$integration = Integration::findOpenAI();
			return $integration !== null && (string) $integration->getApiKey() !== '';
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Search a vector store and extract text snippets with file_id from results.
	 *
	 * @param array<string, mixed>|null $filters Optional OpenAI `filters` (e.g. attribute `nin` for cascading search).
	 * @param bool $rewriteQuery When true, OpenAI may rewrite the query for retrieval (often improves match quality).
	 * @param string|null $ranker OpenAI `ranking_options.ranker`: `auto`, `none`, or `default-2024-11-15`; null uses `auto`.
	 * @return array<int, array{file_id:string, filename:string, text:string}>
	 */
	public static function searchVectorStore(
		string $vectorStoreId,
		string $query,
		int $maxNumResults = self::DEFAULT_SEARCH_MAX_RESULTS,
		float $scoreThreshold = self::DEFAULT_SEARCH_SCORE_THRESHOLD,
		?array $filters = null,
		bool $rewriteQuery = true,
		?string $ranker = null
	): array {
		$apiKey = self::getApiKey();
		$allowedRankers = ['auto', 'none', 'default-2024-11-15'];
		$ranker = $ranker ?? 'auto';
		if (!in_array($ranker, $allowedRankers, true)) {
			$ranker = 'auto';
		}
		$body = [
			'query' => $query,
			'max_num_results' => max(1, min(self::VECTOR_SEARCH_MAX_PER_REQUEST, $maxNumResults)),
			'ranking_options' => [
				'ranker' => $ranker,
				'score_threshold' => self::normalizeVectorStoreScoreThreshold($scoreThreshold),
			],
			'rewrite_query' => $rewriteQuery,
		];
		if (!empty($filters)) {
			$body['filters'] = $filters;
		}

		$data = self::request('POST', '/vector_stores/' . $vectorStoreId . '/search', $body, $apiKey);
		$rows = $data['data'] ?? [];
		if (!is_array($rows)) {
			return [];
		}

		return self::vectorStoreSearchRowsToSnippets($rows);
	}

	/**
	 * OpenAI rejects score_threshold values with more than 16 fractional digits (PHP float JSON noise).
	 */
	protected static function normalizeVectorStoreScoreThreshold(float $scoreThreshold): float
	{
		$clamped = max(0.0, min(1.0, $scoreThreshold));
		return round($clamped, 6);
	}

	/**
	 * @param array<int, mixed> $rows `data` rows from vector store search JSON.
	 * @return array<int, array{file_id:string, filename:string, text:string}>
	 */
	protected static function vectorStoreSearchRowsToSnippets(array $rows): array
	{
		$snippets = [];
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$fileId = (string)($row['file_id'] ?? '');
			$filename = (string)($row['filename'] ?? '');
			$content = $row['content'] ?? [];
			if (!is_array($content)) {
				continue;
			}
			foreach ($content as $chunk) {
				if (!is_array($chunk)) {
					continue;
				}
				if (($chunk['type'] ?? '') !== 'text') {
					continue;
				}
				$text = trim((string)($chunk['text'] ?? ''));
				if ($text !== '') {
					$snippets[] = [
						'file_id' => $fileId,
						'filename' => $filename,
						'text' => $text,
					];
				}
			}
		}

		return $snippets;
	}

	protected static function uploadFile(string $filePath, string $fileName, string $apiKey): string
	{
		if (!is_file($filePath)) {
			throw new \RuntimeException("File not found: {$filePath}");
		}

		$ch = curl_init(self::BASE_URL . '/files');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST           => true,
			CURLOPT_HTTPHEADER     => [
				'Authorization: Bearer ' . $apiKey,
			],
			CURLOPT_POSTFIELDS => [
				'purpose' => 'assistants',
				'file'    => new \CURLFile($filePath, '', $fileName),
			],
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_TIMEOUT        => 300,
		]);

		$response = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			throw new \RuntimeException('File upload cURL error: ' . $error);
		}

		$data = json_decode((string)$response, true);
		if ($httpCode < 200 || $httpCode >= 300) {
			$msg = $data['error']['message'] ?? 'HTTP ' . $httpCode;
			throw new \RuntimeException('File upload failed: ' . $msg);
		}

		$id = $data['id'] ?? null;
		if (empty($id)) {
			throw new \RuntimeException('File upload returned no ID');
		}
		return $id;
	}

	/**
	 * Extract the text from a Responses API response.
	 * Handles output_text shortcut and full output array traversal.
	 */
	public static function extractOutputText(array $data): string
	{
		// Quick path: output_text is a top-level convenience field
		if (!empty($data['output_text']) && is_string($data['output_text'])) {
			return trim($data['output_text']);
		}

		// Full traversal: output → message items → content → output_text blocks
		$output = $data['output'] ?? [];
		if (!is_array($output)) {
			throw new \RuntimeException('No output in OpenAI response');
		}

		$parts = [];
		foreach ($output as $item) {
			if (!is_array($item)) continue;

			// Message output items
			if (($item['type'] ?? '') === 'message' && ($item['role'] ?? '') === 'assistant') {
				$content = $item['content'] ?? [];
				if (!is_array($content)) continue;
				foreach ($content as $block) {
					if (!is_array($block)) continue;
					if (($block['type'] ?? '') === 'output_text' && isset($block['text'])) {
						$parts[] = (string)$block['text'];
					}
				}
			}
		}

		$text = trim(implode("\n", $parts));
		if ($text === '') {
			throw new \RuntimeException('No text content in OpenAI response');
		}
		return $text;
	}

	/**
	 * Extract unique cited OpenAI file IDs from response annotations.
	 *
	 * @return string[]
	 */
	public static function extractCitedFileIds(array $data): array
	{
		$refs = self::extractCitedReferences($data);
		$fileIds = [];
		foreach ($refs as $ref) {
			if (!empty($ref['file_id'])) {
				$fileIds[] = (string)$ref['file_id'];
			}
		}
		return array_values(array_unique($fileIds));
	}

	/**
	 * Extract unique file_ids from file_search_call results (requires `include: [file_search_call.results]`).
	 *
	 * @return array{file_ids: string[], results: array[], queries: string[]} file_ids is unique, results is the raw list
	 */
	public static function extractFileSearchResults(array $data): array
	{
		$output = $data['output'] ?? [];
		if (!is_array($output)) {
			return ['file_ids' => [], 'results' => [], 'queries' => []];
		}

		$allResults = [];
		$queries = [];
		foreach ($output as $item) {
			if (!is_array($item) || ($item['type'] ?? '') !== 'file_search_call') {
				continue;
			}
			$itemQueries = $item['queries'] ?? [];
			if (is_array($itemQueries)) {
				foreach ($itemQueries as $q) {
					$q = trim((string) $q);
					if ($q !== '') {
						$queries[] = $q;
					}
				}
			}
			$results = $item['results'] ?? [];
			if (!is_array($results)) {
				continue;
			}
			foreach ($results as $result) {
				if (!is_array($result)) {
					continue;
				}
				$allResults[] = $result;
			}
		}

		$fileIds = [];
		$seen = [];
		foreach ($allResults as $r) {
			$fid = (string)($r['file_id'] ?? '');
			if ($fid !== '' && !isset($seen[$fid])) {
				$seen[$fid] = true;
				$fileIds[] = $fid;
			}
		}

		return [
			'file_ids' => $fileIds,
			'results' => $allResults,
			'queries' => array_values(array_unique($queries)),
		];
	}

	/**
	 * Extract ordered citation references with optional page metadata.
	 *
	 * @return array<int, array{file_id:string, page:int|null}>
	 */
	public static function extractCitedReferences(array $data): array
	{
		$refs = [];
		$seen = [];
		foreach (self::extractRawAnnotations($data) as $ann) {
			$key = $ann['file_id'] . '|' . ($ann['page'] !== null ? $ann['page'] : 'null');
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;
			$refs[] = ['file_id' => $ann['file_id'], 'page' => $ann['page']];
		}
		return $refs;
	}

	/**
	 * Extract all annotations with position info for inline marker replacement.
	 *
	 * @return array<int, array{file_id:string, page:int|null, start_index:int|null, end_index:int|null}>
	 */
	public static function extractAnnotationsWithPositions(array $data): array
	{
		return self::extractRawAnnotations($data);
	}

	/**
	 * Core annotation extractor shared by both public methods.
	 */
	private static function extractRawAnnotations(array $data): array
	{
		$output = $data['output'] ?? [];
		if (!is_array($output)) {
			return [];
		}

		$results = [];
		foreach ($output as $item) {
			if (!is_array($item)) {
				continue;
			}
			$content = $item['content'] ?? [];
			if (!is_array($content)) {
				continue;
			}
			foreach ($content as $block) {
				if (!is_array($block)) {
					continue;
				}
				$annotations = $block['annotations'] ?? [];
				if (!is_array($annotations)) {
					continue;
				}
				foreach ($annotations as $annotation) {
					if (!is_array($annotation)) {
						continue;
					}

					$fileId = (string)($annotation['file_id'] ?? '');
					if ($fileId === '' && isset($annotation['file_citation']) && is_array($annotation['file_citation'])) {
						$fileId = (string)($annotation['file_citation']['file_id'] ?? '');
					}
					if ($fileId === '') {
						continue;
					}

					$page = null;
					$candidates = [
						$annotation['page'] ?? null,
						$annotation['page_number'] ?? null,
						$annotation['start_page'] ?? null,
					];
					if (isset($annotation['file_citation']) && is_array($annotation['file_citation'])) {
						$candidates[] = $annotation['file_citation']['page'] ?? null;
						$candidates[] = $annotation['file_citation']['page_number'] ?? null;
						$candidates[] = $annotation['file_citation']['start_page'] ?? null;
					}
					foreach ($candidates as $candidate) {
						if (is_numeric($candidate) && (int)$candidate > 0) {
							$page = (int)$candidate;
							break;
						}
					}

					$startIndex = isset($annotation['start_index']) && is_numeric($annotation['start_index'])
						? (int)$annotation['start_index'] : null;
					$endIndex = isset($annotation['end_index']) && is_numeric($annotation['end_index'])
						? (int)$annotation['end_index'] : null;

					$results[] = [
						'file_id' => $fileId,
						'page' => $page,
						'start_index' => $startIndex,
						'end_index' => $endIndex,
					];
				}
			}
		}

		return $results;
	}

	protected static function request(string $method, string $path, $body, string $apiKey): array
	{
		$url = self::BASE_URL . $path;

		$headers = [
			'Authorization: Bearer ' . $apiKey,
		];

		if (strpos($path, '/vector_stores/') !== false) {
			$headers[] = 'OpenAI-Beta: assistants=v2';
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);

		if ($method === 'GET') {
			curl_setopt($ch, CURLOPT_URL, $url);
		} elseif ($method === 'DELETE') {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
		} else {
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			$headers[] = 'Content-Type: application/json';
			$payload = json_encode($body, JSON_UNESCAPED_UNICODE);
			if ($payload === false) {
				throw new \RuntimeException('OpenAI JSON encode failed');
			}
			// Some PHP builds emit IEEE noise for floats in JSON; OpenAI allows at most 16 fractional digits.
			if (is_array($body) && isset($body['ranking_options']) && is_array($body['ranking_options'])
				&& array_key_exists('score_threshold', $body['ranking_options'])) {
				$thr = max(0.0, min(1.0, (float) $body['ranking_options']['score_threshold']));
				$literal = rtrim(rtrim(sprintf('%.10f', $thr), '0'), '.');
				if ($literal === '' || $literal === '-') {
					$literal = '0';
				}
				$payload = (string) preg_replace(
					'/"score_threshold"\s*:\s*-?[0-9.]+(?:[eE][+-]?\d+)?/',
					'"score_threshold":' . $literal,
					$payload,
					1
				);
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response = curl_exec($ch);
		$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		if ($error) {
			Yii::error("OpenAI API cURL error ({$method} {$path}): {$error}", __METHOD__);
			throw new \RuntimeException('OpenAI API cURL error: ' . $error);
		}

		$data = json_decode((string)$response, true);

		if ($httpCode < 200 || $httpCode >= 300) {
			$msg = $data['error']['message'] ?? 'HTTP ' . $httpCode . ': ' . $response;
			Yii::error("OpenAI API error ({$method} {$path}): {$msg}", __METHOD__);
			throw new \RuntimeException('OpenAI API error: ' . $msg);
		}

		return is_array($data) ? $data : [];
	}
}
