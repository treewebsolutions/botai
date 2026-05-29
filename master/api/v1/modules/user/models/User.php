<?php

namespace api\v1\modules\user\models;

use common\helpers\UploadHelper;
use common\models\Workspace;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Link;
use yii\web\Linkable;

/**
 * {@inheritdoc}
 */
class User extends \common\models\User implements Linkable
{
	/**
	 * {@inheritdoc}
	 */
	public function fields()
	{
		return [
			'id',
			'parent_id',
			'auth_key',
			'email',
			'phone',
			'first_name',
			'middle_name',
			'last_name',
			'name' => function () {
				return $this->fullName;
			},
			'image' => function () {
				return $this->imageUrl;
			},
			'gender',
			'created_at' => function () {
				return Yii::$app->formatter->asDatetime($this->created_at, "php:" . DATE_ATOM);
			},
			'updated_at' => function () {
				return Yii::$app->formatter->asDatetime($this->updated_at, "php:" . DATE_ATOM);
			},
			'last_activity' => function () {
				return Yii::$app->formatter->asDatetime($this->last_activity, "php:" . DATE_ATOM);
			},
			'status',
			'workspaces' => function () {
				return $this->getCustomWorkspaces();
			},
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function extraFields()
	{
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLinks()
	{
		if (Yii::$app->user->id === $this->id) {
			return [
				Link::REL_SELF => Url::to(['view', 'id' => 'me'], true),
				'update' => Url::to(['view', 'id' => 'me'], true),
				'create' => Url::to(['index'], true),
				'index' => Url::to(['index'], true),
			];
		}

		return [
			Link::REL_SELF => Url::to(['view', 'id' => $this->id], true),
			'update' => Url::to(['view', 'id' => $this->id], true),
			'delete' => Url::to(['view', 'id' => $this->id], true),
			'create' => Url::to(['index'], true),
			'index' => Url::to(['index'], true),
		];
	}

	/**
	 * Gets the imageUrl with fallback to a blank image.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return UploadHelper::getFileUrl("user/{$this->id}/{$this->image}", $scheme);
	}

	/**
	 * Gets the custom Workspace models.
	 *
	 * @return array
	 */
	public function getCustomWorkspaces()
	{
		return ArrayHelper::toArray($this->workspaces, [
			Workspace::class => [
				'id',
				'code',
				'url' => function (Workspace $model) {
					return $model->getAbsoluteUrl();
				},
			],
		]);
	}

	/**
	 * Finds [[User]] profile.
	 *
	 * @param string|int $criteria
	 * @return \yii\db\ActiveRecord|static|null
	 */
	public static function findProfile($criteria)
	{
		return static::find()
			->alias('u')
			->select([
				'u.*',
			])
			->andWhere([
				'u.status' => self::STATUS_ACTIVE,
				'u.deleted' => self::NO,
			])
			->andWhere([
				'OR',
				['=', 'u.id', $criteria],
				['=', 'u.email', $criteria],
//				['=', 'u.phone', $criteria],
			])
			->one();
	}
}
