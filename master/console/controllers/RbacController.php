<?php
namespace console\controllers;

use common\models\AuthItem;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;

class RbacController extends Controller
{
	/**
	 * @var array The permissions list.
	 */
	public $permissions = [];

	/**
	 * @var \yii\rbac\Role The superAdmin Role object.
	 */
	private $_superAdminRole;

	/**
	 * @inheritdoc
	 * @throws \Exception
	 */
	public function init()
	{
		parent::init();

		$this->permissions = AuthItem::getAllPermissions();
	}

	/**
	 * Initializes RBAC items.
	 *
	 * @throws \yii\base\Exception
	 * @throws \Exception
	 */
	public function actionInit()
	{
		if (!$this->confirm('Are you sure? Old RBAC records will be removed!')) {
			return false;
		}

		$authManager = Yii::$app->authManager;

		// Start clean
		$authManager->removeAll();

		// Add modules permissions
		$permissions = self::getPermissionsList($this->permissions);

		foreach ($permissions as $permissionName) {
			$permission = $authManager->createPermission($permissionName);
			$authManager->add($permission);
			$authManager->addChild($this->getSuperAdminRole(), $permission);
		}

		// TODO: assign superAdmin role to the user
		$authManager->assign($this->getSuperAdminRole(), 1);

		echo "\n[RBAC] successfully initialized!\n";

		return true;
	}

	/**
	 * Optimize RBAC items.
	 *
	 * @throws \yii\base\Exception
	 * @throws \Exception
	 */
	public function actionOptimize()
	{
		if (!$this->confirm('Are you sure? RBAC records could be added or removed from database!')) {
			return false;
		}

		$authManager = Yii::$app->authManager;
		$existingPermissions = array_keys($authManager->getPermissions());
		$allPermissions = self::getPermissionsList($this->permissions);
		$newPermissions = array_diff($allPermissions, $existingPermissions);
		$deletePermissions = array_diff($existingPermissions, $allPermissions);

		foreach ($newPermissions as $newPermission) {
			$permission = $authManager->createPermission($newPermission);
			$authManager->add($permission);
			$authManager->addChild($this->getSuperAdminRole(), $permission);
		}
		echo "\n[RBAC] successfully added " . count($newPermissions) . " permissions!\n";

		foreach ($deletePermissions as $deletePermission) {
			$permission = $authManager->getPermission($deletePermission);
			$authManager->remove($permission);
		}
		echo "[RBAC] successfully deleted " . count($deletePermissions) . " permissions!\n";

		return true;
	}

	/**
	 * Removes all RBAC items.
	 */
	public function actionRemoveAll()
	{
		if (!$this->confirm('Are you sure you want to remove all RBAC records?')) {
			return false;
		}

		Yii::$app->authManager->removeAll();

		echo "\n[RBAC] Successfully removed all permissions!\n";

		return true;
	}

	/**
	 * Gets the superAdmin Role object.
	 *
	 * @return null|string|\yii\rbac\Role
	 * @throws \Exception
	 */
	public function getSuperAdminRole()
	{
		if (!$this->_superAdminRole) {
			$authManager = Yii::$app->authManager;

			if ($superAdmin = $authManager->getRole('superAdmin')) {
				$this->_superAdminRole = $superAdmin;
			} else {
				$superAdmin = $authManager->createRole('superAdmin');
				$superAdmin->description = 'Super Admin';
				$authManager->add($superAdmin);

				$this->_superAdminRole = $superAdmin;
			}
		}
		return $this->_superAdminRole;
	}

	/**
	 * Gets the permissions list as a single dimensional array.
	 *
	 * @param array $data
	 * @return array
	 */
	protected static function getPermissionsList($data)
	{
		$permissions = [];

		foreach ($data as $key => $val) {
			if (is_array($val)) {
				$permissions = ArrayHelper::merge($permissions, self::getPermissionsList($val));
			} elseif ($key !== 'heading' && $key !== 'items') {
				$permissions[] = $key;
			}
		}

		return $permissions;
	}
}