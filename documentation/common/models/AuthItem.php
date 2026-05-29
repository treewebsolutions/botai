<?php

namespace common\models;

use Yii;
use yii\rbac\Item;

/**
 * This is the model class for table "{{%auth_item}}".
 *
 * @property string $name
 * @property int $type
 * @property string $description
 * @property string $rule_name
 * @property resource $data
 * @property int $created_at
 * @property int $updated_at
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthRule $ruleName
 * @property AuthItemChild[] $authItemChildren
 * @property AuthItemChild[] $authItemChildren0
 * @property AuthItem[] $children
 * @property AuthItem[] $parents
 */
class AuthItem extends CommonActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_item}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name', 'type'], 'required'],
			[['type', 'created_at', 'updated_at'], 'integer'],
			[['description', 'data'], 'string'],
			[['name', 'rule_name'], 'string', 'max' => 64],
			[['name'], 'unique'],
			[['rule_name'], 'exist', 'skipOnError' => true, 'targetClass' => AuthRule::class, 'targetAttribute' => ['rule_name' => 'name']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name' => Yii::t('label', 'Name'),
			'type' => Yii::t('label', 'Type'),
			'description' => Yii::t('label', 'Description'),
			'rule_name' => Yii::t('label', 'Rule Name'),
			'data' => Yii::t('label', 'Data'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthAssignments()
	{
		return $this->hasMany(AuthAssignment::class, ['item_name' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getRuleName()
	{
		return $this->hasOne(AuthRule::class, ['name' => 'rule_name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthItemChildren()
	{
		return $this->hasMany(AuthItemChild::class, ['parent' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthItemChildren0()
	{
		return $this->hasMany(AuthItemChild::class, ['child' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getChildren()
	{
		return $this->hasMany(AuthItem::class, ['name' => 'child'])->viaTable('{{%auth_item_child}}', ['parent' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getParents()
	{
		return $this->hasMany(AuthItem::class, ['name' => 'parent'])->viaTable('{{%auth_item_child}}', ['child' => 'name']);
	}

	/**
	 * Finds all roles.
	 *
	 * @return mixed
	 */
	public static function findAllRoles()
	{
		return static::find()
			->select([
				'name',
				'description',
			])
			->where([
				'AND',
				['=', 'type', Item::TYPE_ROLE],
				['!=', 'name', 'superAdmin'],
			])
			->all();
	}

	/**
	 * Gets the permissions items array.
	 *
	 * @return array
	 */
	public static function getAllPermissions()
	{
		return [
			'Nomenclature' => [
				'heading' => Yii::t('common', 'Nomenclature'),
				'groups' => [
					'Page' => [
						'heading' => Yii::t('common', 'Pages'),
						'items' => [
							'viewPage' => Yii::t('common', 'View'),
							'createPage' => Yii::t('common', 'Create'),
							'updatePage' => Yii::t('common', 'Update'),
							'deletePage' => Yii::t('common', 'Delete'),
							'restorePage' => Yii::t('common', 'Restore'),
						],
					],
					'Menu' => [
						'heading' => Yii::t('common', 'Menus'),
						'items' => [
							'viewMenu' => Yii::t('common', 'View'),
							'createMenu' => Yii::t('common', 'Create'),
							'updateMenu' => Yii::t('common', 'Update'),
							'deleteMenu' => Yii::t('common', 'Delete'),
							'restoreMenu' => Yii::t('common', 'Restore'),
						],
					],
					'Carousel' => [
						'heading' => Yii::t('common', 'Carousels'),
						'items' => [
							'viewCarousel' => Yii::t('common', 'View'),
							'createCarousel' => Yii::t('common', 'Create'),
							'updateCarousel' => Yii::t('common', 'Update'),
							'deleteCarousel' => Yii::t('common', 'Delete'),
							'restoreCarousel' => Yii::t('common', 'Restore'),
						],
					],
					'EmailTemplate' => [
						'heading' => Yii::t('common', 'Email Templates'),
						'items' => [
							'viewEmailTemplate' => Yii::t('common', 'View'),
							'createEmailTemplate' => Yii::t('common', 'Create'),
							'updateEmailTemplate' => Yii::t('common', 'Update'),
							'deleteEmailTemplate' => Yii::t('common', 'Delete'),
							'restoreEmailTemplate' => Yii::t('common', 'Restore'),
						],
					],
				],
			],
			'User' => [
				'heading' => Yii::t('common', 'Users'),
				'groups' => [
					'User' => [
						'heading' => Yii::t('common', 'Users'),
						'items' => [
							'viewUser' => Yii::t('common', 'View'),
							'createUser' => Yii::t('common', 'Create'),
							'updateUser' => Yii::t('common', 'Update'),
							'deleteUser' => Yii::t('common', 'Delete'),
							'restoreUser' => Yii::t('common', 'Restore'),
						],
					],
					'Role' => [
						'heading' => Yii::t('common', 'Roles'),
						'items' => [
							'viewUserRole' => Yii::t('common', 'View'),
							'createUserRole' => Yii::t('common', 'Create'),
							'updateUserRole' => Yii::t('common', 'Update'),
							'deleteUserRole' => Yii::t('common', 'Delete'),
						],
					],
				],
			],
			'Setting' => [
				'heading' => Yii::t('common', 'Settings'),
				'groups' => [
					'Setting' => [
						'heading' => Yii::t('common', 'Settings'),
						'items' => [
							'updateGeneralSetting' => Yii::t('common', 'General'),
							'updateEmailSetting' => Yii::t('common', 'Email'),
							'updateSeoSetting' => Yii::t('common', 'SEO'),
							'updateSocialNetworkSetting' => Yii::t('common', 'Social Networks'),
							'updateContactSetting' => Yii::t('common', 'Contact'),
							'updateScriptSetting' => Yii::t('common', 'Script'),
							'clearCacheSetting' => Yii::t('common', 'Clear Cache'),
						],
					],
					'Language' => [
						'heading' => Yii::t('common', 'Languages'),
						'items' => [
							'viewLanguageSetting' => Yii::t('common', 'View'),
							'updateLanguageSetting' => Yii::t('common', 'Update'),
							'translateIntoLanguageSetting' => Yii::t('common', 'Translate'),
						],
					],
					'Currency' => [
						'heading' => Yii::t('common', 'Currencies'),
						'items' => [
							'viewCurrencySetting' => Yii::t('common', 'View'),
							'updateCurrencySetting' => Yii::t('common', 'Update'),
						],
					],
				],
			],
			'EventLog' => [
				'heading' => Yii::t('common', 'Event Logs'),
				'items' => [
					'viewEventLog' => Yii::t('common', 'View'),
				],
			],
		];
	}

	/**
	 * Filters the permissions list by the current authenticated user permissions.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function filterPermissions($data)
	{
		$permissions = [];

		foreach ($data as $key => $val) {
			if (isset($val['visible']) && $val['visible'] === false) {
				continue;
			}
			if (isset($val['groups'])) {
				// Filter the permissions for the group items
				$groups = self::filterPermissions($val['groups']);
				// Push to the stack only if the group items array is not empty
				if (!empty($groups)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'groups' => $groups,
					];
				}
			} elseif (isset($val['items'])) {
				// Filter the permissions for the child items
				$items = self::filterPermissions($val['items']);
				// Push to the stack only if the child items array is not empty
				if (!empty($items)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'items' => $items,
					];
				}
			} else {
				// Check the user permission
				if (Yii::$app->user->can($key)) {
					$permissions[$key] = $val;
				}
			}
		}

		return $permissions;
	}

	/**
	 * Gets the filtered permissions for the current authenticated user.
	 *
	 * @return array
	 */
	public static function getFilteredPermissions()
	{
		return self::filterPermissions(self::getAllPermissions());
	}
}
