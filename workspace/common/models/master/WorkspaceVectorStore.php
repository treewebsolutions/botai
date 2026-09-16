<?php

namespace common\models\master;

use Yii;
use yii\db\ActiveRecord;

/**
 * Tells the hub that this tenant has created a vector store on the platform's key.
 *
 * The other models here only read from the master database, because what they read belongs
 * to the hub. This one writes, for a reason worth stating: the store is created on the
 * hub's OpenAI account, so the hub is the only place where a record of it survives the
 * tenant. Uninstalling a workspace does not touch OpenAI, and once the tenant database is
 * gone so is `knowledge_base`.`vector_store_id` - the store would stay on the account,
 * billed, with nothing able to name it.
 *
 * Failing to write is never allowed to break indexing: the store exists either way, and a
 * tenant that cannot reach the master database has larger problems than bookkeeping.
 */
class WorkspaceVectorStore extends ActiveRecord
{
	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%workspace_vector_store}}';
	}

	/**
	 * {@inheritdoc}
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
	}

	/**
	 * Records a store the tenant has just provisioned.
	 *
	 * @param string $vectorStoreId
	 * @param string $name
	 * @return void
	 */
	public static function record($vectorStoreId, $name = '')
	{
		$vectorStoreId = trim((string) $vectorStoreId);
		if ($vectorStoreId === '') {
			return;
		}

		try {
			if (static::find()->where(['vector_store_id' => $vectorStoreId])->exists()) {
				return;
			}

			$workspaceId = static::currentWorkspaceId();

			$row = new static([
				'workspace_id' => $workspaceId,
				'workspace_code' => static::workspaceCode($workspaceId),
				'vector_store_id' => $vectorStoreId,
				'name' => mb_substr(trim((string) $name), 0, 255),
				'created_at' => date('Y-m-d H:i:s'),
			]);

			if (!$row->save()) {
				Yii::warning(['message' => 'Could not record the vector store with the hub.', 'errors' => $row->getErrors()], __METHOD__);
			}
		} catch (\Throwable $e) {
			// Bookkeeping must never cost the tenant its index.
			Yii::warning('Could not record the vector store with the hub: ' . $e->getMessage(), __METHOD__);
		}
	}

	/**
	 * The workspace's own code, which is also what its database is named after, so an
	 * operator reading this table later can find what the store belonged to. Falls back to
	 * the application name if the hub cannot be asked.
	 *
	 * @param int|null $workspaceId
	 * @return string
	 */
	protected static function workspaceCode($workspaceId)
	{
		if ($workspaceId !== null) {
			$code = Workspace::find()->select('code')->where(['id' => $workspaceId])->scalar();
			if (!empty($code)) {
				return (string) $code;
			}
		}

		return (string) Yii::$app->name;
	}

	/**
	 * The workspace id this application runs as, taken from the application id
	 * ("app-backend-4" / "workspace-4"), which is how the rest of the tenant code finds it.
	 *
	 * @return int|null
	 */
	protected static function currentWorkspaceId()
	{
		$parts = explode('-', (string) Yii::$app->id);
		$last = end($parts);

		return ctype_digit((string) $last) ? (int) $last : null;
	}
}
