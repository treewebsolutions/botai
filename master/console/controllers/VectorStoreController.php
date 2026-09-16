<?php

namespace console\controllers;

use common\models\Integration;
use common\models\WorkspaceVectorStore;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * The vector stores standing on the platform's OpenAI key.
 *
 * Tenants provision them with the hub's key, so they are all billed to one account. A
 * workspace that is uninstalled leaves its store behind - nothing in the uninstall touches
 * OpenAI - and once the tenant database is gone, so is the only id that named it. The hub
 * keeps its own record for exactly this, and these commands read it.
 */
class VectorStoreController extends Controller
{
	/**
	 * Delete without asking. Off by default, because this removes indexes from OpenAI.
	 *
	 * @var bool
	 */
	public $force = false;

	/**
	 * {@inheritdoc}
	 */
	public function options($actionID)
	{
		return array_merge(parent::options($actionID), $actionID === 'purge' ? ['force'] : []);
	}

	/**
	 * Lists every store recorded, and whether its workspace is still there.
	 *
	 * @return int
	 */
	public function actionIndex()
	{
		$rows = WorkspaceVectorStore::find()->orderBy(['id' => SORT_ASC])->all();
		if ($rows === []) {
			$this->stdout("No vector stores recorded yet.\n");
			return ExitCode::OK;
		}

		$this->stdout(sprintf("%-26s %-10s %-22s %s\n", 'VECTOR STORE', 'WORKSPACE', 'CREATED', 'STATE'));
		foreach ($rows as $row) {
			$this->stdout(sprintf(
				"%-26s %-10s %-22s %s\n",
				$row->vector_store_id,
				$row->workspace_code ?: ($row->workspace_id ?: '-'),
				$row->created_at,
				$this->describe($row)
			));
		}

		$orphans = (int) WorkspaceVectorStore::findOrphans()->count();
		$this->stdout("\n{$orphans} orphaned. Run `yii vector-store/purge` to remove them from OpenAI.\n");

		return ExitCode::OK;
	}

	/**
	 * Removes the orphaned stores from OpenAI and marks them removed.
	 *
	 * @return int
	 */
	public function actionPurge()
	{
		$orphans = WorkspaceVectorStore::findOrphans()->all();
		if ($orphans === []) {
			$this->stdout("Nothing orphaned.\n", Console::FG_GREEN);
			return ExitCode::OK;
		}

		$apiKey = $this->apiKey();
		if ($apiKey === '') {
			$this->stderr("The hub has no OpenAI key, so nothing can be deleted with it.\n", Console::FG_RED);
			return ExitCode::UNSPECIFIED_ERROR;
		}

		$this->stdout(count($orphans) . " stores belong to workspaces that no longer exist:\n");
		foreach ($orphans as $row) {
			$this->stdout("  {$row->vector_store_id}  ({$row->workspace_code})\n");
		}
		if (!$this->force && !$this->confirm('Delete these from OpenAI? This cannot be undone.')) {
			$this->stdout("Nothing was deleted.\n");
			return ExitCode::OK;
		}

		$removed = 0;
		foreach ($orphans as $row) {
			if ($this->deleteRemote($row->vector_store_id, $apiKey)) {
				// Marked rather than deleted: what was on the account, and when it left, is
				// the history this table is for.
				$row->updateAttributes(['removed_at' => date('Y-m-d H:i:s')]);
				$removed++;
				$this->stdout("  removed {$row->vector_store_id}\n", Console::FG_GREEN);
			} else {
				$this->stdout("  failed  {$row->vector_store_id}\n", Console::FG_RED);
			}
		}

		$this->stdout("{$removed} of " . count($orphans) . " removed.\n");

		return $removed === count($orphans) ? ExitCode::OK : ExitCode::UNSPECIFIED_ERROR;
	}

	/**
	 * @param WorkspaceVectorStore $row
	 * @return string
	 */
	protected function describe(WorkspaceVectorStore $row)
	{
		if ($row->removed_at) {
			return 'removed ' . $row->removed_at;
		}
		$workspace = $row->workspace;
		if ($workspace === null) {
			return 'ORPHAN (workspace gone)';
		}
		if ((int) $workspace->deleted === $workspace::YES) {
			return 'ORPHAN (workspace deleted)';
		}

		return 'in use';
	}

	/**
	 * The platform's OpenAI key.
	 *
	 * @return string
	 */
	protected function apiKey()
	{
		$integration = Integration::findOpenAI();

		return $integration !== null ? (string) $integration->getApiKey() : '';
	}

	/**
	 * @param string $vectorStoreId
	 * @param string $apiKey
	 * @return bool
	 */
	protected function deleteRemote($vectorStoreId, $apiKey)
	{
		$ch = curl_init('https://api.openai.com/v1/vector_stores/' . rawurlencode($vectorStoreId));
		curl_setopt_array($ch, [
			CURLOPT_CUSTOMREQUEST => 'DELETE',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
		]);
		$body = curl_exec($ch);
		$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error = curl_error($ch);
		curl_close($ch);

		// 404 means it is already gone, which is the state we were after.
		if ($status === 200 || $status === 404) {
			return true;
		}

		Yii::error([
			'message' => 'Could not delete a vector store.',
			'vectorStore' => $vectorStoreId,
			'status' => $status,
			'error' => $error,
			'body' => $body,
		], __METHOD__);

		return false;
	}
}
