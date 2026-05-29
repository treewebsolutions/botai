<?php

namespace backend\modules\user\models;

use common\models\AuthItem;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use common\helpers\Inflector;

class RoleForm extends AuthItem
{
	/**
	 * @var array The list of permissions.
	 */
	public $permissions = [];

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['description'], 'required'],
			[['name', 'description'], 'trim'],
			[['permissions'], 'each', 'rule' => ['string', 'max' => 64]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'description' => Yii::t('label', 'Name'),
		];
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

		$rolePermissions = Yii::$app->authManager->getPermissionsByRole($this->name);
		$this->permissions = array_keys($rolePermissions);
	}

	/**
	 * Saves the permissions for a specific Role.
	 *
	 * @param \yii\rbac\Role $role
	 * @return bool
	 * @throws \Exception
	 */
	protected function savePermissionsForRole($role)
	{
		try {
			$authManager = Yii::$app->authManager;

			$existingPermissions = array_keys($authManager->getPermissionsByRole($role->name));
			$newPermissions = array_diff($this->permissions, $existingPermissions);
			$removePermissions = array_diff($existingPermissions, $this->permissions);

			// Assign new permissions to the role
			if (!empty($newPermissions)) {
				foreach ($newPermissions as $permissionName) {
					if (!($permission = $authManager->getPermission($permissionName))) {
						throw new \Exception(Yii::t('common', 'The «{item}» permission does not exist.', ['item' => $permissionName]));
					}
					$authManager->addChild($role, $permission);
				}
			}

			// Remove the permissions from the role
			if (!empty($removePermissions)) {
				foreach ($removePermissions as $permissionName) {
					if (!($permission = $authManager->getPermission($permissionName))) {
						throw new \Exception(Yii::t('common', 'The «{item}» permission does not exist.', ['item' => $permissionName]));
					}
					$authManager->removeChild($role, $permission);
				}
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());

			return false;
		}
	}

	/**
	 * Saves the model.
	 *
	 * @return bool
	 */
	public function saveModel()
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			$authManager = Yii::$app->authManager;

			// Set the name only on CREATE scenario
			if ($this->isNewRecord) {
				$this->name = Inflector::variablize($this->description);
				if (Yii::$app->authManager->getRole($this->name)) {
					$this->addError('description', Yii::t('yii', '{attribute} "{value}" has already been taken.', [
						'attribute' => $this->getAttributeLabel('name'),
						'value' => $this->description,
					]));
					throw new \Exception();
				}
			}

			// Create the role
			$role = $authManager->createRole($this->name);
			$role->description = $this->description;

			// Add or update the role
			if ($this->isNewRecord) {
				$authManager->add($role);
			} else {
				$authManager->update($this->oldAttributes['name'], $role);
			}

			// Save the role permissions
			if (!$this->savePermissionsForRole($role)) {
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			$dbTransaction->rollBack();
			return false;
		}
	}
}
