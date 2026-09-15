<?php

namespace tests\support;

/**
 * Test double for yii\web\User that passes every RBAC check, so backend
 * controllers guarded by AccessControl role rules can run without seeding the
 * whole auth_* hierarchy.
 */
class FakeAdminWebUser extends \yii\web\User
{
	/**
	 * @inheritdoc
	 */
	public function can($permissionName, $params = [], $allowCaching = true)
	{
		return true;
	}
}
