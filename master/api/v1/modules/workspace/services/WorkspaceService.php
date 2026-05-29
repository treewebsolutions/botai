<?php

namespace api\v1\modules\workspace\services;

use api\v1\modules\workspace\models\Workspace;
use Yii;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;

class WorkspaceService
{
	public function list($data)
	{
		$filter = new ActiveDataFilter([
			'searchModel' => function () {
				return (new DynamicModel([
					'id' => null,
					'created_by' => null,
					'updated_by' => null,
					'created_at' => null,
					'updated_at' => null,
					'status' => null,
				]))
					->addRule(['created_by', 'updated_by', 'status'], 'integer')
					->addRule(['created_at', 'updated_at'], 'safe')
					->addRule(['created_at', 'updated_at'], 'default');
			},
			'attributeMap' => [
				'id' => 'w.id',
				'created_by' => 'w.created_by',
				'updated_by' => 'w.updated_by',
				'created_at' => 'w.created_at',
				'updated_at' => 'w.updated_at',
				'status' => 'w.status',
			],
		]);

		$filter->operatorTypes = array_merge($filter->operatorTypes, [
			'<' => '*',
			'>' => '*',
			'<=' => '*',
			'>=' => '*',
		]);

		$filterCondition = null;

		if ($filter->load($data)) {
			$filterCondition = $filter->build();
			if ($filterCondition === false) {
				return $filter;
			}
		}

		$query = Workspace::find()
			->alias('w')
			->select([
				'w.*',
			])
			->joinWith([
				'creator cr' => function (ActiveQuery $query) {
					$query->select([
						'cr.id',
						'cr.first_name',
						'cr.middle_name',
						'cr.last_name',
					]);
				},
			])
			->andWhere([
				'w.deleted' => Workspace::NO,
			]);

		if ($filterCondition !== null) {
			$query->andFilterWhere($filterCondition);
		}

		return new ActiveDataProvider([
			'query' => $query,
			'sort' => [
				'attributes' => [
					'id',
					'created_by',
					'updated_by',
					'created_at',
					'updated_at',
					'status',
				],
				'defaultOrder' => [
					'id' => SORT_ASC,
				],
			],
			'pagination' => [
				'defaultPageSize' => 100,
				'pageSizeLimit' => [0, INF],
			],
		]);
	}
}