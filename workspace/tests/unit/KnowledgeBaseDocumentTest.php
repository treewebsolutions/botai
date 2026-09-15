<?php

namespace tests\unit;

use backend\modules\nomenclature\models\KnowledgeBaseDocumentForm;
use common\models\KnowledgeBase;
use common\models\KnowledgeBaseDocument;
use common\services\OpenAiDocumentVectorStoreService;
use common\services\OpenAiRecordVectorStoreService;
use tests\DatabaseTestCase;
use yii\web\UploadedFile;

/**
 * Uploaded documents that extend a knowledge base beyond the scraped pages: the upload
 * whitelist of the backend form, the on-disk file handling, and the sync service's
 * outcome when OpenAI cannot be reached (no API key): the row records the error and
 * nothing throws, exactly like the page index.
 */
class KnowledgeBaseDocumentTest extends DatabaseTestCase
{
	/**
	 * @var string[] temp files to remove
	 */
	private $tmpFiles = [];

	/**
	 * @var int[] document ids whose upload directories must be removed
	 */
	private $documentIds = [];

	protected function tearDown(): void
	{
		foreach ($this->tmpFiles as $file) {
			@unlink($file);
		}
		foreach ($this->documentIds as $id) {
			$dir = \Yii::getAlias("@uploads/knowledge-base-document/{$id}");
			if (is_dir($dir)) {
				\yii\helpers\FileHelper::removeDirectory($dir);
			}
		}
		OpenAiRecordVectorStoreService::resetKnowledgeBaseCache();
		parent::tearDown();
	}

	/**
	 * @param string $extension
	 * @return UploadedFile
	 */
	private function fakeUpload($extension, $content = 'Opening hours: Monday to Friday 9-17.')
	{
		$tmp = tempnam(sys_get_temp_dir(), 'kbd');
		file_put_contents($tmp, $content);
		$this->tmpFiles[] = $tmp;

		return new UploadedFile([
			'name' => 'Program clinica.' . $extension,
			'tempName' => $tmp,
			'type' => 'application/octet-stream',
			'size' => filesize($tmp),
			'error' => UPLOAD_ERR_OK,
		]);
	}

	/**
	 * @return int
	 */
	private function insertKnowledgeBase(array $attributes = [])
	{
		return $this->insertRow('knowledge_base', array_merge([
			'name' => 'Website',
			'provider' => KnowledgeBase::PROVIDER_OPENAI,
			'status' => KnowledgeBase::STATUS_ACTIVE,
			'deleted' => KnowledgeBase::NO,
		], $attributes));
	}

	/**
	 * Only the formats OpenAI file_search can read are accepted; the rest is rejected by
	 * validation before anything touches the disk.
	 */
	public function testFormRejectsUnsupportedExtensions()
	{
		$kbId = $this->insertKnowledgeBase();

		$form = new KnowledgeBaseDocumentForm();
		$form->knowledge_base_id = $kbId;
		$form->upload = $this->fakeUpload('exe');
		$form->file = $form->upload->name;

		$this->assertFalse($form->validate());
		$this->assertArrayHasKey('upload', $form->getErrors());

		foreach (KnowledgeBaseDocument::ALLOWED_EXTENSIONS as $extension) {
			$form = new KnowledgeBaseDocumentForm();
			$form->knowledge_base_id = $kbId;
			$form->upload = $this->fakeUpload($extension);
			$form->file = $form->upload->name;
			$form->name = 'Doc';
			$this->assertTrue($form->validate(), $extension . ': ' . print_r($form->getErrors(), true));
		}
	}

	/**
	 * A document must belong to a knowledge base: a missing one fails the `exist` rule.
	 */
	public function testDocumentRequiresAnExistingKnowledgeBase()
	{
		$document = new KnowledgeBaseDocument([
			'knowledge_base_id' => 999999,
			'name' => 'Orphan',
			'file' => 'orphan.pdf',
			'status' => KnowledgeBaseDocument::STATUS_ACTIVE,
		]);
		$this->assertFalse($document->save());
		$this->assertArrayHasKey('knowledge_base_id', $document->getErrors());
	}

	/**
	 * Without an OpenAI API key the sync cannot upload: the document ends in the ERROR index
	 * state with the reason on the row, no exception escapes, and an inactive document is
	 * simply withdrawn (index columns cleared).
	 */
	public function testSyncRecordsTheErrorWhenOpenAiIsNotConfigured()
	{
		$kbId = $this->insertKnowledgeBase();
		$documentId = $this->insertRow('knowledge_base_document', [
			'knowledge_base_id' => $kbId,
			'name' => 'Program',
			'file' => 'program.txt',
			'extension' => 'txt',
			'status' => KnowledgeBaseDocument::STATUS_ACTIVE,
			'deleted' => KnowledgeBaseDocument::NO,
		]);
		$this->documentIds[] = $documentId;
		$document = KnowledgeBaseDocument::findOne($documentId);
		\yii\helpers\FileHelper::createDirectory($document->getDirectoryPath());
		file_put_contents($document->getFilePath(), 'Monday to Friday 9-17.');
		$this->assertTrue($document->getIsIndexable());

		OpenAiDocumentVectorStoreService::syncDocument($documentId);

		$this->assertSame(KnowledgeBaseDocument::INDEX_STATUS_ERROR, (int) $this->fetchColumn('knowledge_base_document', $documentId, 'index_status'));
		$this->assertStringContainsString('API key', (string) $this->fetchColumn('knowledge_base_document', $documentId, 'error_message'));
		$this->assertFalse(OpenAiDocumentVectorStoreService::hasIndexedDocuments());

		// A Claude knowledge base has no vector store to upload to.
		$claudeKb = $this->insertKnowledgeBase(['provider' => KnowledgeBase::PROVIDER_CLAUDE]);
		$document->updateAttributes(['knowledge_base_id' => $claudeKb]);
		OpenAiDocumentVectorStoreService::syncDocument($documentId);
		$this->assertStringContainsString('not an OpenAI knowledge base', (string) $this->fetchColumn('knowledge_base_document', $documentId, 'error_message'));

		// Deactivated: withdrawn, no error left behind.
		$document->updateAttributes(['status' => KnowledgeBaseDocument::STATUS_INACTIVE]);
		OpenAiDocumentVectorStoreService::syncDocument($documentId);
		$this->assertSame(KnowledgeBaseDocument::INDEX_STATUS_NONE, (int) $this->fetchColumn('knowledge_base_document', $documentId, 'index_status'));
		$this->assertNull($this->fetchColumn('knowledge_base_document', $documentId, 'error_message'));
	}

	/**
	 * The chat availability check now counts indexed documents as searchable content, so a
	 * knowledge base built only from uploads still opens the chat.
	 */
	public function testIndexedDocumentsCountAsSearchableContent()
	{
		$kbId = $this->insertKnowledgeBase(['vector_store_id' => 'vs_docs']);
		$this->assertFalse(OpenAiDocumentVectorStoreService::hasIndexedDocuments());

		$this->insertRow('knowledge_base_document', [
			'knowledge_base_id' => $kbId,
			'name' => 'Brochure',
			'file' => 'brochure.pdf',
			'index_status' => KnowledgeBaseDocument::INDEX_STATUS_INDEXED,
			'status' => KnowledgeBaseDocument::STATUS_ACTIVE,
			'deleted' => KnowledgeBaseDocument::NO,
		]);
		$this->assertTrue(OpenAiDocumentVectorStoreService::hasIndexedDocuments());
	}
}
