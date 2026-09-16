<?php

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * What the platform's OpenAI key has had created on it, and by which workspace.
 *
 * Since tenants fall back to the hub's key, every vector store they provision lives in one
 * OpenAI account - the platform's. Uninstalling a workspace does not touch OpenAI, and if
 * the tenant database is later dropped, `knowledge_base`.`vector_store_id` goes with it:
 * the store stays on the account, billed, with nothing left in the system able to name it.
 *
 * This is that name, kept where the bill is. The tenant writes a row when it provisions a
 * store; the hub reads to see what it is paying for and what it can clean up.
 *
 * There is deliberately no foreign key to `workspace`, and `workspace_code` is copied as
 * text: a cascade would delete precisely the evidence this table exists to keep, and after
 * the workspace is gone its code is the only thing left that says whose store this was.
 *
 * @property int $id
 * @property int|null $workspace_id
 * @property string|null $workspace_code
 * @property string $vector_store_id
 * @property string|null $name
 * @property string $created_at
 * @property string|null $removed_at
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
	 */
	public function rules()
	{
		return [
			[['vector_store_id', 'created_at'], 'required'],
			[['workspace_id'], 'integer'],
			[['created_at', 'removed_at'], 'safe'],
			[['vector_store_id'], 'string', 'max' => 128],
			[['workspace_code', 'name'], 'string', 'max' => 255],
			[['vector_store_id'], 'unique'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'workspace_id' => Yii::t('label', 'Workspace'),
			'workspace_code' => Yii::t('label', 'Code'),
			'vector_store_id' => Yii::t('label', 'Vector Store ID'),
			'name' => Yii::t('label', 'Name'),
			'created_at' => Yii::t('label', 'Created At'),
			'removed_at' => Yii::t('label', 'Removed At'),
		];
	}

	/**
	 * The workspace this store was created for, or null once it has been deleted - which is
	 * the case this table is for.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public function getWorkspace()
	{
		return $this->hasOne(Workspace::class, ['id' => 'workspace_id']);
	}

	/**
	 * Stores still on the account whose workspace no longer exists, or exists only as a
	 * deleted row. Nothing in the system will ever use these again.
	 *
	 * @return \yii\db\ActiveQuery
	 */
	public static function findOrphans()
	{
		return static::find()
			->alias('vs')
			->leftJoin(Workspace::tableName() . ' w', 'w.id = vs.workspace_id')
			->where(['vs.removed_at' => null])
			->andWhere(['or', ['w.id' => null], ['w.deleted' => Workspace::YES]]);
	}
}
