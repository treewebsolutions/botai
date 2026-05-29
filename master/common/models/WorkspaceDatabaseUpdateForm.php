<?php

namespace common\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * Workspace Database Update form
 */
class WorkspaceDatabaseUpdateForm extends Workspace
{
	public $query;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['query'], 'required'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'query' => Yii::t('label', 'Query'),
		]);
	}

    /**
     * Saves the model.
     *
     * @return bool|\yii\db\ActiveRecord
     */
    public function saveModel()
    {
        $models = static::find()
            ->where([
                'status' => static::STATUS_ACTIVE,
                'deleted' => static::NO,
            ])
            ->all();
        $this->query = html_entity_decode($this->query);
        if (!empty($models)) {
            foreach ($models as $model) {
                try {
                    $connection = $model->getWorkspaceDb();
                    $command = $connection->createCommand(
                        $this->query
                    );
                    $command->queryAll();
                } catch(\Exception $e) {
                    $this->addError('', $e->getMessage());
                    continue;
                }
            }
        }
        return true;
    }
}
