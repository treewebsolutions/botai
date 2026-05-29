<?php

namespace backend\modules\notification\components;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Notification component automatically adds notification records to the database.
 *
 * @author Alin Hort <alinhort@gmail.com>
 */
class Notification extends Component
{
	/**
	 * @var \yii\db\ActiveRecord The component model used to save notifications.
	 */
	public $model = '\common\models\Notification';

	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		if (!isset($this->model) || !is_subclass_of($this->model, '\yii\db\ActiveRecord')) {
			throw new InvalidConfigException('The "model" attribute must be a subclass of the ActiveRecord class.');
		}
	}

	/**
	 * Saves a notification record.
	 *
	 * @param array $attributes The model attributes.
	 * @param array|null|bool $targetedUsers The users which this notification targets. False means that no user is targeted.
	 * @return bool The successful or the failure of the model save operation.
	 */
	public function create($attributes, $targetedUsers = null)
	{
		/** @var \yii\db\ActiveRecord|\common\models\Notification $model */
		$model = new $this->model;

		// Merge defaults with the provided attributes
		$attributes = ArrayHelper::merge([
			'type' => $model::TYPE_APP,
			'icon' => 'fa fa-info',
			'status' => $model::STATUS_ACTIVE,
		], $attributes);

		// Ensure that model key is always a string if is set
		if (isset($attributes['model_key'])) {
			$attributes['model_key'] = (string) $attributes['model_key'];
		}

		// Prevent creating a duplicate notification
		if ($this->notificationExists($attributes)) {
			return true;
		}

		$model->setAttributes($attributes);
		if (!$model->save()) {
			return false;
		}

		// Assign this notification to the targeted users
		if ($targetedUsers !== false) {
			if ($targetedUsers === null) {
				// Target all the users
				$targetedUsers = \common\models\User::findAllUsers();
			} elseif (is_array($targetedUsers)) {
				// Filter targeted users by ID
				$targetedUsers = array_filter(\common\models\User::findAllUsers(), function ($user) use ($targetedUsers) {
					return in_array($user->id, $targetedUsers);
				});
			}

			// Exclude the current user from getting notified
//			$targetedUsers = array_filter($targetedUsers, function ($user) {
//				return $user->id != Yii::$app->user->id;
//			});

			foreach ($targetedUsers as $targetedUser) {
				if ($targetedUser instanceof \yii\db\ActiveRecord) {
					$model->link('users', $targetedUser);
				}
			}
		}

		return true;
	}

	/**
	 * Checks if a notification already exists.
	 *
	 * @param $attributes
	 * @return bool
	 */
	protected function notificationExists($attributes)
	{
		if (!isset($attributes['model']) || !isset($attributes['model_key'])) {
			return false;
		}

		return ($this->model)::find()
			->where([
				'model_key' => $attributes['model_key'],
				'model' => $attributes['model'],
			])
			->exists();
	}
}
