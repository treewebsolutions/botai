<?php

namespace api\v1\modules\workspace\models;

use common\helpers\DateHelper;
use common\models\User;
use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Link;
use yii\web\Linkable;

/**
 * {@inheritdoc}
 */
class Workspace extends \common\models\Workspace implements Linkable
{
	/**
	 * {@inheritdoc}
	 */
	public function fields()
	{
		return [
			'id',
			'name' => function () {
				return $this->translation->name;
			},
			'budget' => function () {
				return $this->budget;
			},
			'currency' => function () {
				return $this->currency;
			},
			'period' => function () {
				return $this->period;
			},
			'cycle' => function () {
				$label = static::getCycleLabels()[$this->cycle];
				return ['label' => $label, 'value' => $this->cycle];
			},
			'threshold' => function () {
				return $this->threshold;
			},
			'threshold_type' => function () {
				$label = static::getThresholdTypeLabels()[$this->threshold_type];
				return ['label' => $label, 'value' => $this->threshold_type];
			},
			'created_by' => function () {
				return ['label' => $this->creator->fullName, 'value' => $this->created_by];
			},
			'updated_by' => function () {
				return ['label' => $this->updater->fullName, 'value' => $this->updated_by];
			},
			'created_at' => function () {
				return Yii::$app->formatter->asDatetime($this->created_at, "php:" . DATE_ATOM);
			},
			'updated_at' => function () {
				return Yii::$app->formatter->asDatetime($this->updated_at, "php:" . DATE_ATOM);
			},
			'status' => function () {
				$label = static::getStatusLabels()[$this->status]['label'];
				return ['label' => $label, 'value' => $this->status];
			},
			'translations' => function () {
				return $this->workspaceTranslations;
			},
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function extraFields()
	{
		return [
			'stakeholders' => [$this, 'retrieveStakeholders'],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLinks()
	{
		return [
			Link::REL_SELF => Url::to(['view', 'id' => $this->id], true),
			'update' => Url::to(['view', 'id' => $this->id], true),
			'delete' => Url::to(['view', 'id' => $this->id], true),
			'create' => Url::to(['index'], true),
			'index' => Url::to(['index'], true),
			'list' => Url::to(['list'], true),
		];
	}

	/**
	 * Get associated models
	 * @return array
	 */
	public function retrieveStakeholders()
	{
		$query = User::find()
			->alias('u')
			->select([
				'u.*',
			])
			->joinWith([
				'stakeholderWorkspaces d',
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
				'd.id' => $this->id,
				'u.deleted' => User::NO,
			]);
		return ArrayHelper::toArray($query->each(), [
			User::class => [
				'id',
				'image' => function (User $model) {
					if ($model->image) {
						return $model->getImageUrl(true);
					}
					return '';
				},
				'role' => function (User $model) {
					return $model->authAssignment->item_name ? $model->authAssignment->itemName->description : '';
				},
				'name' => function (User $model) {
					return $model->fullName ?: '';
				},
				'email' => function (User $model) {
					return $model->email ?: '';
				},
				'phone' => function (User $model) {
					return $model->phone ?: '';
				},
				'created_at' => function (User $model) {
					return $model->created_at ? DateHelper::format($model->created_at, DATE_ATOM) : '';
				},
				'last_activity' => function (User $model) {
					return $model->last_activity ? DateHelper::format($model->created_at, DATE_ATOM) : '';
				},
				'status' => function (User $model) {
					$status = User::getStatusLabels()[$model->status];
					return ['label' => $status['label'], 'value' => $model->status];
				},
			],
		]);
	}
}
