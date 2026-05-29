<?php

namespace api\v1\modules\user\services;

use api\v1\modules\user\models\User;
use yii\base\DynamicModel;
use yii\data\ActiveDataFilter;
use yii\data\ActiveDataProvider;

class UserService
{
   public function list($data)
   {
	   $filter = new ActiveDataFilter([
		   'searchModel' => function () {
			   return (new DynamicModel([
				   'id' => null,
				   'parent_id' => null,
				   'email' => null,
				   'phone' => null,
				   'first_name' => null,
				   'middle_name' => null,
				   'last_name' => null,
				   'gender' => null,
				   'status' => null,
			   ]))
				   ->addRule(['id', 'parent_id', 'gender', 'status'], 'integer')
				   ->addRule(['email', 'phone', 'first_name', 'middle_name', 'last_name'], 'string')
				   ->addRule(['email', 'phone', 'first_name', 'middle_name', 'last_name'], 'trim');
		   },
	   ]);
	   $filterCondition = null;

	   if ($filter->load($data)) {
		   $filterCondition = $filter->build();
		   if ($filterCondition === false) {
			   return $filter;
		   }
	   }

	   $query = User::find()->where(['deleted' => User::NO]);

	   if ($filterCondition !== null) {
		   $query->andFilterWhere($filterCondition);
	   }

	   return new ActiveDataProvider([
		   'query' => $query,
		   'sort' => [
			   'attributes' => [
				   'id',
				   'parent_id',
				   'email',
				   'phone',
				   'first_name',
				   'middle_name',
				   'last_name',
				   'gender',
				   'status',
			   ],
			   'defaultOrder' => [
				   'first_name' => SORT_ASC,
				   'middle_name' => SORT_ASC,
				   'last_name' => SORT_ASC,
			   ],
		   ],
		   'pagination' => [
			   'defaultPageSize' => 100,
			   'pageSizeLimit' => [0, INF],
		   ],
	   ]);
   }
}