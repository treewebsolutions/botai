<?php

namespace common\models;

use Yii;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;
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
	 */
	public function getChildren()
	{
		return $this->hasMany(AuthItem::class, ['name' => 'child'])->viaTable('{{%auth_item_child}}', ['parent' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
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
		try {
			return static::getDb()->cache(function ($db) {
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
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Gets the permissions items array.
	 *
	 * @return array
	 * @todo filter this list based on the subscription features
	 */
	public static function getAllPermissions()
	{
		return [
			'Nomenclature' => [
				'heading' => Yii::t('label', 'Nomenclature'),
				'groups' => [
					'Page' => [
						'heading' => Yii::t('label', 'Pages'),
						'items' => [
							'viewPage' => Yii::t('label', 'View'),
							'createPage' => Yii::t('label', 'Create'),
							'updatePage' => Yii::t('label', 'Update'),
							'deletePage' => Yii::t('label', 'Delete'),
							'restorePage' => Yii::t('label', 'Restore'),
						],
					],
					'Integration' => [
						'heading' => Yii::t('label', 'Integrations'),
						'items' => [
							'viewIntegration' => Yii::t('label', 'View'),
							'createIntegration' => Yii::t('label', 'Create'),
							'updateIntegration' => Yii::t('label', 'Update'),
							'deleteIntegration' => Yii::t('label', 'Delete'),
							'restoreIntegration' => Yii::t('label', 'Restore'),
						],
					],
					'VectorStore' => [
						'heading' => Yii::t('label', 'Vector Stores'),
						'items' => [
							'viewVectorStore' => Yii::t('label', 'View'),
							'createVectorStore' => Yii::t('label', 'Create'),
							'updateVectorStore' => Yii::t('label', 'Update'),
							'deleteVectorStore' => Yii::t('label', 'Delete'),
							'restoreVectorStore' => Yii::t('label', 'Restore'),
						],
					],
					'VectorStoreFile' => [
						'heading' => Yii::t('label', 'Vector Store Files'),
						'items' => [
							'viewVectorStoreFile' => Yii::t('label', 'View'),
							'createVectorStoreFile' => Yii::t('label', 'Create'),
							'updateVectorStoreFile' => Yii::t('label', 'Update'),
							'deleteVectorStoreFile' => Yii::t('label', 'Delete'),
							'restoreVectorStoreFile' => Yii::t('label', 'Restore'),
						],
					],
					'Assistant' => [
						'heading' => Yii::t('label', 'Assistants'),
						'items' => [
							'viewAssistant' => Yii::t('label', 'View'),
							'createAssistant' => Yii::t('label', 'Create'),
							'updateAssistant' => Yii::t('label', 'Update'),
							'deleteAssistant' => Yii::t('label', 'Delete'),
							'restoreAssistant' => Yii::t('label', 'Restore'),
						],
					],
				],
			],
			'Conversation' => [
				'heading' => Yii::t('label', 'Conversations'),
				'groups' => [
					'Thread' => [
						'heading' => Yii::t('label', 'Threads'),
						'items' => [
							'viewThread' => Yii::t('label', 'View'),
							'createThread' => Yii::t('label', 'Create'),
							'updateThread' => Yii::t('label', 'Update'),
							'deleteThread' => Yii::t('label', 'Delete'),
							'restoreThread' => Yii::t('label', 'Restore'),
						],
					],
					'Message' => [
						'heading' => Yii::t('label', 'Messages'),
						'items' => [
							'viewMessage' => Yii::t('label', 'View'),
							'createMessage' => Yii::t('label', 'Create'),
							'updateMessage' => Yii::t('label', 'Update'),
							'deleteMessage' => Yii::t('label', 'Delete'),
							'restoreMessage' => Yii::t('label', 'Restore'),
						],
					],
					'Participant' => [
						'heading' => Yii::t('label', 'Participants'),
						'items' => [
							'viewParticipant' => Yii::t('label', 'View'),
							'createParticipant' => Yii::t('label', 'Create'),
							'updateParticipant' => Yii::t('label', 'Update'),
							'deleteParticipant' => Yii::t('label', 'Delete'),
							'restoreParticipant' => Yii::t('label', 'Restore'),
						],
					],
				],
			],
			'User' => [
				'heading' => Yii::t('label', 'Users'),
				'groups' => [
					'User' => [
						'heading' => Yii::t('label', 'Users'),
						'items' => [
							'viewUser' => Yii::t('label', 'View'),
							'createUser' => Yii::t('label', 'Create'),
							'updateUser' => Yii::t('label', 'Update'),
							'deleteUser' => Yii::t('label', 'Delete'),
							'restoreUser' => Yii::t('label', 'Restore'),
						],
					],
					'Role' => [
						'heading' => Yii::t('label', 'Roles'),
						'items' => [
							'viewUserRole' => Yii::t('label', 'View'),
							'createUserRole' => Yii::t('label', 'Create'),
							'updateUserRole' => Yii::t('label', 'Update'),
							'deleteUserRole' => Yii::t('label', 'Delete'),
						],
					],
				],
			],
			'Setting' => [
				'heading' => Yii::t('label', 'Settings'),
				'groups' => [
					'Setting' => [
						'heading' => Yii::t('label', 'Settings'),
						'items' => [
							'updateGeneralSetting' => Yii::t('label', 'General'),
							'updateEmailSetting' => Yii::t('label', 'Email'),
							'updateInterfaceSetting' => Yii::t('label', 'Interface'),
							'clearCacheSetting' => Yii::t('label', 'Clear Cache'),
						],
					],
					'Language' => [
						'heading' => Yii::t('label', 'Languages'),
						'items' => [
							'viewLanguage' => Yii::t('label', 'View'),
							'updateLanguage' => Yii::t('label', 'Update'),
							'translateIntoLanguage' => Yii::t('label', 'Translate'),
						],
					],
					'Currency' => [
						'heading' => Yii::t('label', 'Currencies'),
						'items' => [
							'viewCurrency' => Yii::t('label', 'View'),
							'updateCurrency' => Yii::t('label', 'Update'),
						],
					],
				],
			],
			'Notification' => [
				'heading' => Yii::t('label', 'Notifications'),
				'items' => [
					'viewNotification' => Yii::t('label', 'View'),
					'createNotification' => Yii::t('label', 'Create'),
					'updateNotification' => Yii::t('label', 'Update'),
					'deleteNotification' => Yii::t('label', 'Delete'),
					'restoreNotification' => Yii::t('label', 'Restore'),
				],
			],
            'Backup' => [
                'heading' => Yii::t('label', 'Backups'),
                'items' => [
                    'viewBackup' => Yii::t('label', 'View'),
                    'createBackup' => Yii::t('label', 'Create'),
                    'downloadBackup' => Yii::t('label', 'Download'),
                    'recoverBackup' => Yii::t('label', 'Recover'),
                    'deleteBackup' => Yii::t('label', 'Delete'),
                    'restoreBackup' => Yii::t('label', 'Restore'),
                ],
            ],
			'EventLog' => [
				'heading' => Yii::t('label', 'Event Logs'),
				'items' => [
					'viewEventLog' => Yii::t('label', 'View'),
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
	 * Filters the permissions list by ignoring the keys given as argument.
	 *
	 * @param array $data
	 * @param null|array $ignore
	 * @return array
	 */
	public static function ignorePermissions($data, $ignore)
	{
		if (empty($ignore)) {
			return $data;
		}
		if (!is_array($ignore)) {
			$ignore = (array) $ignore;
		}
		$permissions = [];

		foreach ($data as $key => $val) {
			if (!ArrayHelper::isAssociative($ignore) && in_array($key, $ignore)) {
				continue;
			}
			if (isset($val['groups'])) {
				// Filter the permissions for the group items
				$groups = self::ignorePermissions($val['groups'], $ignore[$key]);
				// Push to the stack only if the group items array is not empty
				if (!empty($groups)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'groups' => $groups,
					];
				}
			} elseif (isset($val['items'])) {
				// Filter the permissions for the child items
				$items = self::ignorePermissions($val['items'], $ignore[$key]);
				// Push to the stack only if the child items array is not empty
				if (!empty($items)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'items' => $items,
					];
				}
			} else {
				$permissions[$key] = $val;
			}
		}
		return $permissions;
	}

	/**
	 * Gets the filtered permissions for the current authenticated user.
	 *
	 * @param null|array $ignore
	 * @return array
	 */
	public static function getFilteredPermissions($ignore = null)
	{
		return self::filterPermissions(self::ignorePermissions(self::getAllPermissions(), $ignore));
	}
}
