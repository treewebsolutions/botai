<?php

namespace common\services;

use common\models\KnowledgeBase;
use common\models\KnowledgeBaseDocument;
use Yii;

/**
 * Keeps the uploaded {@see KnowledgeBaseDocument} files in sync with the OpenAI vector store of
 * their {@see KnowledgeBase}, the way {@see OpenAiRecordVectorStoreService} does for scraped pages.
 *
 * The file is uploaded as-is (PDF, Word, text...) to the Files API and attached to the knowledge
 * base's vector store; OpenAI extracts and chunks the text. A document that is inactive, deleted
 * or whose file is gone is withdrawn. Every method is non-throwing: the outcome is written on the
 * row (`index_status`, `error_message`).
 */
class OpenAiDocumentVectorStoreService
{
	/**
	 * Defers a sync until after the HTTP response, so uploads never slow the backend down.
	 *
	 * @param int $documentId
	 */
	public static function scheduleSync($documentId): void
	{
		$documentId = (int) $documentId;
		if ($documentId <= 0) {
			return;
		}
		register_shutdown_function(function () use ($documentId) {
			try {
				self::syncDocument($documentId);
			} catch (\Throwable $e) {
				Yii::error('OpenAiDocumentVectorStoreService::syncDocument failed: ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * Defers a withdrawal until after the HTTP response.
	 *
	 * @param int $documentId
	 */
	public static function scheduleWithdraw($documentId): void
	{
		$documentId = (int) $documentId;
		if ($documentId <= 0) {
			return;
		}
		register_shutdown_function(function () use ($documentId) {
			try {
				self::withdrawDocument($documentId);
			} catch (\Throwable $e) {
				Yii::error('OpenAiDocumentVectorStoreService::withdrawDocument failed: ' . $e->getMessage(), __METHOD__);
			}
		});
	}

	/**
	 * Uploads the document to the vector store of its knowledge base (replacing a previous upload),
	 * or withdraws it when it is no longer indexable. Never throws.
	 *
	 * @param int $documentId
	 */
	public static function syncDocument($documentId): void
	{
		$document = KnowledgeBaseDocument::findOne((int) $documentId);
		if ($document === null) {
			return;
		}
		if (!$document->getIsIndexable()) {
			self::withdrawDocument($document->id);
			return;
		}

		$openaiFileId = null;
		try {
			$kb = $document->knowledgeBase;
			if ($kb === null || (int) $kb->deleted !== KnowledgeBase::NO) {
				throw new \RuntimeException('The knowledge base no longer exists.');
			}
			if ((int) $kb->provider !== KnowledgeBase::PROVIDER_OPENAI) {
				throw new \RuntimeException(Yii::t('common', 'The knowledge base is not an OpenAI knowledge base.'));
			}

			OpenAIResponsesService::ensureVectorStore($kb);
			$vsId = (string) $kb->vector_store_id;

			// Replace the previous upload (re-index / file changed / moved to another store).
			self::tryRemoveRemote(
				(string) $document->vector_store_id,
				(string) $document->openai_file_id,
				$document->vector_store_file_id
			);

			$openaiFileId = OpenAIResponsesService::uploadAssistantFile($document->getFilePath(), $document->file);
			$attach = OpenAIResponsesService::attachFileToVectorStore($vsId, $openaiFileId, [
				'document_id' => (int) $document->id,
				'source' => 'document',
				'name' => mb_substr((string) $document->name, 0, 200),
			], null);
			OpenAIResponsesService::waitForVectorStoreFileReady($vsId, $openaiFileId);

			$document->updateAttributes([
				'vector_store_id' => $vsId,
				'openai_file_id' => $openaiFileId,
				'vector_store_file_id' => $attach['vector_store_file_id'] !== '' ? $attach['vector_store_file_id'] : null,
				'index_status' => KnowledgeBaseDocument::INDEX_STATUS_INDEXED,
				'indexed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
				'error_message' => null,
			]);
			OpenAiRecordVectorStoreService::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			if (OpenAiRecordVectorStoreService::isOpenAiFileId($openaiFileId)) {
				try {
					OpenAIResponsesService::deleteFile($openaiFileId);
				} catch (\Throwable $ignored) {
				}
			}
			$document->updateAttributes([
				'index_status' => KnowledgeBaseDocument::INDEX_STATUS_ERROR,
				'error_message' => mb_substr($e->getMessage(), 0, 65000),
			]);
			Yii::warning('OpenAiDocumentVectorStoreService::syncDocument (non-fatal) document=' . $document->id . ': ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Removes the document from its vector store and clears the index columns. Never throws.
	 *
	 * @param int $documentId
	 */
	public static function withdrawDocument($documentId): void
	{
		$document = KnowledgeBaseDocument::findOne((int) $documentId);
		if ($document === null) {
			return;
		}
		self::tryRemoveRemote(
			(string) $document->vector_store_id,
			(string) $document->openai_file_id,
			$document->vector_store_file_id
		);
		try {
			$document->updateAttributes([
				'openai_file_id' => null,
				'vector_store_file_id' => null,
				'index_status' => KnowledgeBaseDocument::INDEX_STATUS_NONE,
				'indexed_at' => null,
				'error_message' => null,
			]);
			OpenAiRecordVectorStoreService::bumpSearchCacheGeneration();
		} catch (\Throwable $e) {
			Yii::warning('withdrawDocument save: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Before a hard delete: captures what has to be purged on the OpenAI side, so the caller can
	 * run {@see scheduleRemotePurge()} after the row (and its file) are gone.
	 *
	 * @param KnowledgeBaseDocument $document
	 * @return array{0:string,1:string,2:?string}|null vector_store_id, openai_file_id, vector_store_file_id
	 */
	public static function describeRemote(KnowledgeBaseDocument $document): ?array
	{
		if (!OpenAiRecordVectorStoreService::isOpenAiFileId($document->openai_file_id)) {
			return null;
		}
		return [
			(string) $document->vector_store_id,
			(string) $document->openai_file_id,
			$document->vector_store_file_id !== null && $document->vector_store_file_id !== '' ? (string) $document->vector_store_file_id : null,
		];
	}

	/**
	 * @param array|null $meta as returned by {@see describeRemote()}
	 */
	public static function scheduleRemotePurge(?array $meta): void
	{
		if ($meta === null) {
			return;
		}
		register_shutdown_function(function () use ($meta) {
			self::tryRemoveRemote($meta[0], $meta[1], $meta[2]);
		});
	}

	/**
	 * Whether at least one document is live in a vector store (feeds the chat availability check).
	 *
	 * @return bool
	 */
	public static function hasIndexedDocuments(): bool
	{
		try {
			return KnowledgeBaseDocument::find()
				->where([
					'index_status' => KnowledgeBaseDocument::INDEX_STATUS_INDEXED,
					'status' => KnowledgeBaseDocument::STATUS_ACTIVE,
					'deleted' => KnowledgeBaseDocument::NO,
				])
				->exists();
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Best-effort removal of a file from a vector store and from the Files API.
	 *
	 * @param string $vectorStoreId
	 * @param string $openaiFileId
	 * @param string|null $vectorStoreFileId
	 */
	protected static function tryRemoveRemote(string $vectorStoreId, string $openaiFileId, ?string $vectorStoreFileId): void
	{
		if (!OpenAiRecordVectorStoreService::isOpenAiFileId($openaiFileId)) {
			return;
		}
		if ($vectorStoreId !== '') {
			try {
				OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $openaiFileId);
			} catch (\Throwable $e) {
				if ($vectorStoreFileId) {
					try {
						OpenAIResponsesService::removeFileFromVectorStore($vectorStoreId, $vectorStoreFileId);
					} catch (\Throwable $e2) {
						Yii::warning('removeFileFromVectorStore (document): ' . $e->getMessage() . ' / ' . $e2->getMessage(), __METHOD__);
					}
				} else {
					Yii::warning('removeFileFromVectorStore (document): ' . $e->getMessage(), __METHOD__);
				}
			}
		}
		try {
			OpenAIResponsesService::deleteFile($openaiFileId);
		} catch (\Throwable $e) {
			Yii::warning('deleteFile (document): ' . $e->getMessage(), __METHOD__);
		}
	}
}
