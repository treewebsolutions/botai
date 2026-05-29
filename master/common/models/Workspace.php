<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use tws\helpers\DbHelper;
use tws\helpers\FileHelper;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%workspace}}".
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $code
 * @property string $url
 * @property int $type
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Subscription $subscription
 * @property WorkspaceHasSubscriptionFeature[] $workspaceHasSubscriptionFeatures
 * @property SubscriptionFeature[] $subscriptionFeatures
 * @property WorkspaceHasUser[] $workspaceHasUsers
 * @property User[] $users
 * @property User $creator
 * @property User $updater
 */
class Workspace extends CommonActiveRecord
{
	const TYPE_SUBSCRIBER = 1;
	const TYPE_DEMO = 2;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return '{{%workspace}}';
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['subscription_id', 'type', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['code', 'url', 'status'], 'required'],
			[['created_at', 'updated_at'], 'safe'],
			[['code', 'url'], 'string', 'max' => 255],
			[['subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscription::class, 'targetAttribute' => ['subscription_id' => 'id']],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => Yii::t('label', 'ID'),
			'subscription_id' => Yii::t('label', 'Subscription ID'),
			'code' => Yii::t('label', 'Code'),
			'url' => Yii::t('label', 'Url'),
			'type' => Yii::t('label', 'Type'),
			'created_by' => Yii::t('label', 'Created By'),
			'updated_by' => Yii::t('label', 'Updated By'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
			'status' => Yii::t('label', 'Status'),
			'deleted' => Yii::t('label', 'Deleted'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscription()
	{
		return $this->hasOne(Subscription::class, ['id' => 'subscription_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getWorkspaceHasSubscriptionFeatures()
	{
		return $this->hasMany(WorkspaceHasSubscriptionFeature::class, ['workspace_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getSubscriptionFeatures()
	{
		return $this->hasMany(SubscriptionFeature::class, ['id' => 'subscription_feature_id'])->viaTable('{{%workspace_has_subscription_feature}}', ['workspace_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getWorkspaceHasUsers()
	{
		return $this->hasMany(WorkspaceHasUser::class, ['workspace_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::class, ['id' => 'user_id'])->viaTable('{{%workspace_has_user}}', ['workspace_id' => 'id']);
	}

    /**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Model type labels.
	 *
	 * @return array
	 */
	public static function getTypeLabels()
	{
		return [
			static::TYPE_SUBSCRIBER => Yii::t('label', 'Subscriber'),
			static::TYPE_DEMO => Yii::t('label', 'Demo'),
		];
	}

	/**
	 * Gets the absoluteUrl.
	 *
	 * @param bool $administration Flag that indicates if the administration URL should be returned.
	 * @return string
	 */
	public function getAbsoluteUrl($administration = false)
	{
		$segments = [Yii::$app->urlManager->hostInfo, $this->url];

		if ($administration === true) {
			$segments[] = 'admin';
		}

		return implode('/', $segments);
	}

	/**
	 * Gets if isDefault.
	 *
	 * @return bool
	 */
	public function getIsDefault()
	{
		return WorkspaceHasUser::find()
			->where([
				'workspace_id' => $this->id,
				'default' => WorkspaceHasUser::YES,
			])
			->exists();
	}

	/**
	 * Gets the WorkspaceHasSubscriptionFeature model by SubscriptionFeature name property.
	 *
	 * @param string $featureName
	 * @return \yii\db\ActiveRecord|WorkspaceHasSubscriptionFeature|null
	 */
	public function getWorkspaceSubscriptionFeature($featureName)
	{
		return $this->getWorkspaceHasSubscriptionFeatures()
			->alias('whsa')
			->joinWith([
				'subscriptionFeature sa' => function (ActiveQuery $query) use ($featureName) {
					$query->andWhere([
						'sa.subscription_id' => $this->subscription_id,
						'sa.name' => $featureName,
						'sa.deleted' => SubscriptionFeature::NO,
					]);
				},
			])
			->one();
	}

	/**
	 * Generates an unique code.
	 *
	 * @param int $length
	 * @return string
	 * @throws \yii\base\Exception
	 */
	public static function generateUniqueCode($length = 8)
	{
		$code = Yii::$app->security->generateRandomString($length);

		// Ensure that the generated string is alphanumeric
		if (!preg_match('/^[a-zA-Z0-9]*$/', $code)) {
			return static::generateUniqueCode($length);
		}

		// Ensure that the generated string is unique
		if (static::find()->where(['code' => $code])->limit(1)->exists()) {
			return static::generateUniqueCode($length);
		}

		return $code;
	}

	/**
	 * Finds all records by user model ID.
	 *
	 * @param int $user_id
	 * @return array|\yii\db\ActiveRecord[]|static[]
	 */
	public static function findAllWorkspacesByUser($user_id)
	{
		return static::find()
			->alias('w')
			->joinWith([
				'workspaceHasUsers whu' => function (\yii\db\ActiveQuery $query) use ($user_id) {
					$query->andWhere([
						'whu.user_id' => $user_id,
						'whu.status' => WorkspaceHasUser::STATUS_ACTIVE,
						'whu.deleted' => WorkspaceHasUser::NO,
					]);
				}
			], false)
			->andWhere([
				'w.status' => static::STATUS_ACTIVE,
				'w.deleted' => static::NO,
			])
			->all();
	}

	//region Workspace Config
	/**
	 * Gets the Workspace directory path.
	 *
	 * @return bool|string
	 */
	public function getDirectoryPath()
	{
		if ($this->isNewRecord) {
			return null;
		}

		return Yii::getAlias("@workspace/workspaces/{$this->id}");
	}

	/**
	 * Gets the Workspace database name.
	 *
	 * @return string|null
	 */
	public function getWorkspaceDbName()
	{
		if ($this->isNewRecord) {
			return null;
		}

		$dbNameParts = preg_split('/_|-/', DbHelper::getDsnAttribute('dbname', static::getDb()));
		$dbNameParts[count($dbNameParts) - 1] = $this->code;

		return implode('_', $dbNameParts);
	}

	/**
	 * Gets the Workspace database instance.
	 *
	 * @return null|object|\yii\db\Connection
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getWorkspaceDb()
	{
		if ($this->isNewRecord) {
			return null;
		}

		$db = static::getDb();

		return Yii::createObject([
			'class' => 'yii\db\Connection',
			'dsn' => str_replace(DbHelper::getDsnAttribute('dbname', $db), $this->getWorkspaceDbName(), $db->dsn),
			'username' => $db->username,
			'password' => $db->password,
			'charset' => $db->charset,
		]);
	}

	/**
	 * Prepares the rows that need to be imported to Workspace Database.
	 * This method alters the metadata attributes (creator, updater etc.)
	 *
	 * @param array $rows
	 * @return array
	 * @throws \Exception
	 */
	protected function prepareImportRows($rows)
	{
		$currentDate = new \DateTime();
		$user = $this->subscription->subscriber->user;
		$metadataRow = [
			'created_by' => $user->id,
			'updated_by' => $user->id,
			'created_at' => $currentDate->format('Y-m-d H:i:s'),
			'updated_at' => $currentDate->format('Y-m-d H:i:s'),
		];

		return array_map(function ($row) use ($metadataRow) {
			return array_merge($row, $metadataRow);
		}, $rows);
	}

	/**
	 * Installs the Workspace database.
	 *
	 * @return bool
	 */
	protected function installDatabase()
	{
		$db = static::getDb();

		try {
			// Create the database
			if (YII_ENV_DEV && Yii::$app->request->getUserIP() === '127.0.0.1') {
				$sql = [
					"CREATE DATABASE IF NOT EXISTS {$this->getWorkspaceDbName()} CHARACTER SET utf8 COLLATE utf8_unicode_ci",
					"GRANT ALL ON `{$this->getWorkspaceDbName()}`.* TO '{$db->username}'@'%' IDENTIFIED BY '{$db->password}'",
					"FLUSH PRIVILEGES",
				];
				$db->createCommand(implode(";\n", $sql))->execute();
			} else {
				// Create the database using the cPanel API
				Yii::$app->cPanel->uapi->Mysql->create_database(['name' => $this->getWorkspaceDbName()]);
				Yii::$app->cPanel->uapi->Mysql->set_privileges_on_database([
					'user' => $db->username,
					'database' => $this->getWorkspaceDbName(),
					'privileges' => 'ALL PRIVILEGES',
				]);
			}

			// Get the database instance
			$workspaceDb = $this->getWorkspaceDb();
			$workspaceDbPath = Yii::getAlias("@workspace/install/db/{$this->type}");

			// Import the database structure and data
			$workspaceDb->createCommand()->setRawSql(file_get_contents("{$workspaceDbPath}/_01_structure.sql"))->execute();
			$workspaceDb->createCommand()->setRawSql(file_get_contents("{$workspaceDbPath}/_02_permissions.sql"))->execute();
			$workspaceDb->createCommand()->setRawSql(file_get_contents("{$workspaceDbPath}/_03_common.sql"))->execute();
			$workspaceDb->createCommand()->setRawSql(file_get_contents("{$workspaceDbPath}/_04_translations.sql"))->execute();
			$workspaceDb->createCommand()->setRawSql(file_get_contents("{$workspaceDbPath}/_05_data.sql"))->execute();

			// Create the super admin user
			$user = $this->subscription->subscriber->user;
			$user->parent_id = null;

			$workspaceDb->createCommand()->insert('{{%user}}', $user->attributes)->execute();
			$workspaceDb->createCommand()->insert('{{%auth_assignment}}', [
				'item_name' => 'superAdmin',
				'user_id' => $user->id,
				'created_at' => time(),
			])->execute();

			// Create related records, if any
//			if ($rows = WsTemplate::findAllForImport()) {
//				$workspaceDb->createCommand()->batchInsert('{{%template}}', array_keys($rows[0]), $this->prepareImportRows($rows))->execute();
//				if ($translationRows = WsTemplateTranslation::findAllByTemplate(array_column($rows, 'id'), true)) {
//					$workspaceDb->createCommand()->batchInsert('{{%template_translation}}', array_keys($translationRows[0]), $translationRows)->execute();
//				}
//			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Installs the Workspace directory.
	 *
	 * @return bool
	 */
	protected function installDirectory()
	{
		$db = static::getDb();
		$dirPath = $this->getDirectoryPath();

		try {
			FileHelper::createDirectory($dirPath, 0755);
			FileHelper::copyDirectory(Yii::getAlias("@workspace/install/dir/{$this->type}"), $dirPath, ['dirMode' => 0755]);

			// Create symbolic links for static assets
			FileHelper::symlink([
				Yii::getAlias("@workspace/backend/web/assets") => "{$dirPath}/backend/web/assets",
				Yii::getAlias("@workspace/backend/web/audio") => "{$dirPath}/backend/web/audio",
				Yii::getAlias("@workspace/backend/web/img") => "{$dirPath}/backend/web/img",
				Yii::getAlias("@workspace/backend/web/img/flags") => "{$dirPath}/backend/web/img/flags",
				Yii::getAlias("@workspace/backend/web/img/ico") => "{$dirPath}/backend/web/img/ico",
				Yii::getAlias("@workspace/backend/web/img/tpl") => "{$dirPath}/backend/web/img/tpl",
				Yii::getAlias("@workspace/backend/web/css") => "{$dirPath}/backend/web/css",
				Yii::getAlias("@workspace/backend/web/js") => "{$dirPath}/backend/web/js",
				Yii::getAlias("@workspace/frontend/web/assets") => "{$dirPath}/frontend/web/assets",
				Yii::getAlias("@workspace/frontend/web/img") => "{$dirPath}/frontend/web/img",
				Yii::getAlias("@workspace/frontend/web/img/flags") => "{$dirPath}/frontend/web/img/flags",
				Yii::getAlias("@workspace/frontend/web/img/ico") => "{$dirPath}/frontend/web/img/ico",
				Yii::getAlias("@workspace/frontend/web/img/mail") => "{$dirPath}/frontend/web/img/mail",
				Yii::getAlias("@workspace/frontend/web/css") => "{$dirPath}/frontend/web/css",
				Yii::getAlias("@workspace/frontend/web/fonts") => "{$dirPath}/frontend/web/fonts",
				Yii::getAlias("@workspace/frontend/web/plugins") => "{$dirPath}/frontend/web/plugins",
				Yii::getAlias("@workspace/frontend/web/js") => "{$dirPath}/frontend/web/js",
			]);

			// Update the configuration files
			$filePaths = [
				"{$dirPath}/common/config/main.php",
				"{$dirPath}/api/config/main.php",
				"{$dirPath}/backend/config/main.php",
				"{$dirPath}/frontend/config/main.php",
				"{$dirPath}/console/config/main.php",
			];
			foreach ($filePaths as $filePath) {
				if (is_file($filePath)) {
					file_put_contents($filePath, strtr(file_get_contents($filePath), [
						'{{DB_NAME}}' => $this->getWorkspaceDbName(),
						'{{DB_USERNAME}}' => $db->username,
						'{{DB_PASSWORD}}' => $db->password,
						'{{ID}}' => $this->id,
						'{{NAME}}' => $this->code,
						'{{URL}}' => $this->url,
					]));
				}
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Updates the crontab file.
	 *
	 * @param bool $remove
	 * @return bool
	 */
	protected function updateCrontab($remove = false)
	{
		if (YII_ENV_DEV && Yii::$app->request->getUserIP() === '127.0.0.1') {
			return true;
		}

		$cronJob = [
			'minute' => '*',
			'hour' => '*',
			'day' => '*',
			'month' => '*',
			'weekday' => '*',
			'command' => "/usr/local/bin/php " . Yii::getAlias("@workspace/workspaces/{$this->id}/yii") . " schedule/run >/dev/null 2>&1",
		];

		if ($remove === false) {
			Yii::$app->cPanel->api2->Cron->add_line($cronJob);
		} else {
			$response = Yii::$app->cPanel->api2->Cron->fetchcron();
			if ($response && is_array($response['cpanelresult']['data'])) {
				foreach ($response['cpanelresult']['data'] as $line) {
					if ($line['type'] == 'command' && $line['command'] == $cronJob['command']) {
						Yii::$app->cPanel->api2->Cron->remove_line(['linekey' => $line['linekey']]);
						break;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Updates the root .htaccess file.
	 *
	 * @param bool $remove
	 * @return bool
	 */
	protected function updateHtaccess($remove = false)
	{
		$htaccess = \tws\textfile\TextFile::load(Yii::getAlias('@base/.htaccess'));
		$line = "\tRewriteRule ^{$this->url}/?(.*)$ workspace/workspaces/{$this->id}/$1 [NC,L]";
		$workspace = Workspace::findOne([
			'LIKE', 'url', $this->url
		]);

		if ($remove === true) {
			return $htaccess->deleteLine($line);
		}

		// Delete similar rewrite rules with the same workspace ID
		if ($workspaceLines = $htaccess->getLines("workspace/workspaces/{$this->id}/$1")) {
			$htaccess->deleteLines($workspaceLines);
		}

		if ($workspace->url) {
			return $htaccess
				->addLine($line)
				->beforeLine('# END Workspace Rules')
				->save();
		} else {
			return $htaccess
				->addLine($line)
				->afterLine('# BEGIN Workspace Rules')
				->save();
		}
	}

	/**
	 * Installs the Workspace database and its directory structure.
	 *
	 * @return bool
	 */
	public function install($reinstall = false)
	{
		try {
			if ($reinstall) {
				if (!$this->uninstall()) {
					throw new \Exception('Cannot reinstall the workspace.');
				}
			}
			if (!$this->installDatabase()) {
				throw new \Exception('Cannot create the workspace database.');
			}
			if (!$this->installDirectory()) {
				throw new \Exception('Cannot create the workspace directory.');
			}
			if (!$this->updateCrontab()) {
				throw new \Exception('Cannot update the crontab file.');
			}
			if (!$this->updateHtaccess()) {
				throw new \Exception('Cannot update the .htaccess file.');
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Uninstalls the Workspace database and its directory structure.
	 *
	 * @return bool
	 */
	public function uninstall()
	{
		try {
			if (YII_ENV_DEV && Yii::$app->request->getUserIP() === '127.0.0.1') {
				static::getDb()->createCommand("DROP DATABASE IF EXISTS {$this->getWorkspaceDbName()}")->execute();
			} else {
				Yii::$app->cPanel->uapi->Mysql->delete_database(['name' => $this->getWorkspaceDbName()]);
			}

			if (!$this->updateCrontab(true)) {
				throw new \Exception('Cannot update the crontab file.');
			}
			if (!$this->updateHtaccess(true)) {
				throw new \Exception('Cannot update the .htaccess file.');
			}
			FileHelper::removeDirectory($this->getDirectoryPath());
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}
	//endregion Workspace Config
}
