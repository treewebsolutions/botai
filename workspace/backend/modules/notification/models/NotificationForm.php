<?php

namespace backend\modules\notification\models;

use common\models\Notification;
use common\models\User;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class NotificationForm extends Notification
{
	/**
	 * @var array The users that should get this notification.
	 */
	public $targeted_users = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->type = static::TYPE_USER;
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['title', 'message'], 'trim'],
			[['targeted_users'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['targeted_users' => 'id']]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'targeted_users' => Yii::t('label', 'Targeted Users'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->targeted_users = ArrayHelper::getColumn($this->users, 'id');
	}

	/**
	 * Links the User models.
	 *
	 * @return bool
	 */
	protected function saveUsers()
	{
		try {
			$this->unlinkAll('users', true);

			$users = User::findAll([
				'id' => $this->targeted_users,
				'status' => User::STATUS_ACTIVE,
				'deleted' => User::NO,
			]);

			foreach ($users as $user) {
				$this->link('users', $user);
			}

			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());

			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 * @throws \yii\db\Exception
	 */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveUsers()) {
				throw new \Exception();
			}

			$transaction->commit();
			return $this;
		} catch(\Exception $e) {
			$transaction->rollBack();
			return false;
		}
	}
}
