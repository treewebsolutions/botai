<?php

namespace backend\models;

use common\models\User;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class SearchForm extends Model
{
	/**
	 * @var string $q The search criterion.
	 */
	public $q;

	/**
	 * @inheritdoc
	 */
	public function formName()
	{
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['q'], 'required'],
			[['q'], 'string', 'max' => 255],
			[['q'], 'trim'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'q' => Yii::t('common', 'Criterion'),
		];
	}

	/**
	 * Searches the User models.
	 *
	 * @return \yii\data\ActiveDataProvider
	 */
	public function search()
	{
		$query = User::find()
			->alias('u')
			->select(['u.*'])
			->where([
				'u.deleted' => User::NO,
			])
			->andFilterWhere([
				'OR',
				['LIKE', 'u.email', $this->q],
				['LIKE', new Expression('CONCAT(u.first_name, " ", u.last_name)'), $this->q],
				['LIKE', new Expression('CONCAT(u.last_name, " ", u.first_name)'), $this->q],
			])
			->groupBy(['u.id']);


		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'defaultPageSize' => Yii::$app->settings->get('itemsPerPage'),
			],
			'sort' => [
				'defaultOrder' => [
					'email' => SORT_ASC,
				],
			],
		]);
	}
}
