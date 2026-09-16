<?php

namespace common\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use tws\helpers\DbHelper;
use common\helpers\FileHelper;
use yii\httpclient\Client;
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
 * @property string|null $domain
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
			[['code', 'url', 'domain'], 'string', 'max' => 255],
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
			'domain' => Yii::t('label', 'Domain'),
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
	 * Gets the directory name used for this Workspace under `<root>/workspaces/`.
	 *
	 * Tenants are keyed by their domain (`domain` column), falling back to the URL
	 * slug (`url` column) when no domain is set. The `domain` column may hold a full
	 * URL (e.g. `https://www.example.ro/`), so it is reduced to a bare lowercase host
	 * name without the `www.` prefix (`example.ro`).
	 *
	 * @param string|null $domain overrides the model's domain (used when the model is being renamed)
	 * @param string|null $url overrides the model's url (used when the model is being renamed)
	 * @return string|null
	 */
	public function getDirectoryName($domain = null, $url = null)
	{
		$domain = trim((string) ($domain ?? $this->domain));
		$url = trim((string) ($url ?? $this->url), "/ \t");

		$key = $domain !== '' ? static::normalizeHost($domain) : $url;

		$key = strtolower(trim((string) $key, "/ \t"));

		return $key === '' ? null : $key;
	}

	/**
	 * Reduces whatever the `domain` column holds to the bare host name that keys the
	 * tenant directory: `https://www.Example.ro/` and `example.ro` both give `example.ro`.
	 *
	 * Shared with the form validation, so what is checked on save is exactly what the
	 * directory is later named after.
	 *
	 * @param string|null $domain
	 * @return string
	 */
	public static function normalizeHost($domain)
	{
		$domain = trim((string) $domain);
		if ($domain === '') {
			return '';
		}

		$host = parse_url(strpos($domain, '://') === false ? "http://{$domain}" : $domain, PHP_URL_HOST);

		return strtolower(trim(preg_replace('/^www\./i', '', $host ?: trim($domain, '/')), "/ \t"));
	}

	/**
	 * Gets the Workspace directory path (`<root>/workspaces/<domain>`).
	 *
	 * @return string|null
	 */
	public function getDirectoryPath()
	{
		if ($this->isNewRecord || ($name = $this->getDirectoryName()) === null) {
			return null;
		}

		return Yii::getAlias("@base/workspaces/{$name}");
	}

	/**
	 * Gets the workspace directory path relative to the web root (for .htaccess rules).
	 *
	 * @return string
	 */
	public function getRelativeDirectoryPath()
	{
		return 'workspaces/' . $this->getDirectoryName();
	}

	/**
	 * Builds the map of shared @workspace directories that are linked into the tenant
	 * directory (source => destination). Used both when installing (to create the
	 * symbolic links) and when uninstalling (to remove them safely).
	 *
	 * Includes the shared source directories resolved at runtime via @app/... (modules,
	 * views) and the static web assets served from the tenant document root.
	 *
	 * @return array
	 */
	public function getSymlinkMap(): array
	{
		$dirPath = $this->getDirectoryPath();
		if (!$dirPath) {
			return [];
		}

		$relativePaths = [
			// Shared source directories referenced at runtime through @app/...
			'frontend/modules',
			'frontend/views',
			'backend/modules',
			'backend/views',
			// Static web assets served directly from the tenant document root
			'backend/web/assets',
			'backend/web/audio',
			'backend/web/img',
			'backend/web/css',
			'backend/web/js',
			'backend/web/fonts',
			'frontend/web/assets',
			'frontend/web/img',
			'frontend/web/css',
			'frontend/web/fonts',
			'frontend/web/js',
		];

		$map = [];
		foreach ($relativePaths as $relativePath) {
			$map[Yii::getAlias("@workspace/{$relativePath}")] = "{$dirPath}/{$relativePath}";
		}

		return $map;
	}

	/**
	 * Gets the root .htaccess rewrite rule that routes `/<url>/...` to this Workspace directory.
	 *
	 * @param string|null $url overrides the model's url (used when the model is being renamed)
	 * @param string|null $target overrides the relative directory path
	 * @return string
	 */
	public function getHtaccessRewriteRule($url = null, $target = null)
	{
		$url = $url ?? $this->url;
		$target = $target ?? $this->getRelativeDirectoryPath();

		return "\tRewriteRule ^{$url}/?(.*)$ {$target}/$1 [NC,L]";
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
	 * Whether a usable cPanel component is configured. When it is not, provisioning
	 * falls back to direct SQL (CREATE DATABASE / DROP DATABASE) and skips the crontab.
	 *
	 * Unlike the previous `YII_ENV_DEV && request IP === 127.0.0.1` check, this also
	 * works from console/cron contexts where there is no HTTP request.
	 *
	 * @return bool
	 */
	public function isCPanelConfigured()
	{
		try {
			$cpanel = Yii::$app->get('cPanel');
		} catch (\Throwable $e) {
			return false;
		}
		if (!$cpanel instanceof \tws\cpanel\CPanel) {
			return false;
		}
		// common\components\CPanel also knows whether it holds real credentials rather
		// than the CPANEL_* placeholders of an environment file that was never filled in.
		return !method_exists($cpanel, 'isConfigured') || $cpanel->isConfigured();
	}

	/**
	 * Whether the Workspace is provisioned locally (Docker, Laragon, OrbStack...) rather
	 * than through cPanel: either the application runs in the `dev` environment or no
	 * cPanel component is configured. In this case the database is created/dropped
	 * directly via PDO and no crontab entry is managed (see docker-compose `scheduler`).
	 *
	 * The `YII_ENV_DEV` check comes first so the cPanel component (which validates its
	 * `baseUrl` on init) is never instantiated with the placeholder dev values.
	 *
	 * @return bool
	 */
	public function isLocalInstallEnvironment()
	{
		if (defined('YII_ENV_DEV') && YII_ENV_DEV) {
			return true;
		}

		return !$this->isCPanelConfigured();
	}

	/**
	 * Whether the tenant database still has to be created and seeded.
	 *
	 * True when it does not exist, and also when it exists but holds no tables: a database
	 * created by hand, or left behind by an install that failed before the first import, is
	 * as unusable as a missing one and has nothing in it to lose.
	 *
	 * A tenant that already has tables answers false, so {@see install()} never imports the
	 * install structure over live data.
	 *
	 * @return bool
	 */
	protected function workspaceDatabaseNeedsInstall()
	{
		$db = static::getDb();
		$dbName = $this->getWorkspaceDbName();

		try {
			$exists = (int) $db->createCommand(
				'SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :name',
				[':name' => $dbName]
			)->queryScalar() > 0;

			if (!$exists) {
				return true;
			}

			return (int) $db->createCommand(
				'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :name',
				[':name' => $dbName]
			)->queryScalar() === 0;
		} catch (\Exception $e) {
			// information_schema is the cheap answer, not the only truth - a shared host can
			// restrict it. Answer "nothing to do" when it cannot be read: skipping the import
			// leaves a working tenant alone, while guessing the other way would write the
			// install structure over a database we were unable to look inside.
			Yii::warning([
				'message' => 'Cannot tell whether the workspace database exists; leaving it alone.',
				'workspace' => $this->code,
				'database' => $dbName,
				'error' => $e->getMessage(),
			], __METHOD__);

			return false;
		}
	}

	/**
	 * Installs the Workspace database.
	 *
	 * Import order: the shared `@workspace/install/db/_01_structure.sql`, `_03_common.sql`,
	 * `_04_translations.sql` and `_05_data.sql` (when present), then every
	 * `@workspace/install/db/<type>/*.sql` (e.g. `_02_permissions.sql`) in name order.
	 *
	 * @return bool
	 */
	protected function installDatabase()
	{
		$db = static::getDb();
		$dbName = $this->getWorkspaceDbName();
		$grantFailure = null;

		try {
			// Create the database
			if ($this->isLocalInstallEnvironment()) {
				$db->createCommand("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8 COLLATE utf8_unicode_ci")->execute();
				// Best effort: the master user usually owns the server locally (root), and
				// MySQL 8 rejects the legacy `GRANT ... IDENTIFIED BY` form, so a refused
				// grant must not abort the install. A truly unusable database fails loudly
				// at the connection check below anyway.
				try {
					$db->createCommand("GRANT ALL ON `{$dbName}`.* TO '{$db->username}'@'%'")->execute();
					$db->createCommand('FLUSH PRIVILEGES')->execute();
				} catch (\Exception $e) {
					Yii::warning([
						'message' => 'Cannot grant privileges on the workspace database (ignored).',
						'workspace' => $this->code,
						'error' => $e->getMessage(),
					], __METHOD__);
				}
			} else {
				// Create the database using the cPanel API. Failures are logged but not
				// fatal here: "already exists" on a reinstall is benign, and a truly
				// unusable database makes the connection check below fail loudly anyway.
				$response = Yii::$app->cPanel->uapi->Mysql->create_database(['name' => $dbName]);
				$this->logCpanelUapiFailure('Mysql::create_database', $response);
				$response = Yii::$app->cPanel->uapi->Mysql->set_privileges_on_database([
					'user' => $db->username,
					'database' => $dbName,
					'privileges' => 'ALL PRIVILEGES',
				]);
				$this->logCpanelUapiFailure('Mysql::set_privileges_on_database', $response);
				if (!is_array($response) || (int) ($response['status'] ?? 0) !== 1) {
					// Not fatal on its own — the user may already hold the grant from an
					// earlier install — but remembered, so the connection check below can
					// name the real cause instead of leaving a bare "Access denied".
					$grantFailure = $this->describeCpanelFailure($response);
				}

				// cPanel's create_database uses the server default charset (latin1 on many
				// shared hosts). Every CREATE TABLE in _01_structure.sql pins its own charset,
				// so this only realigns the database for anything created later. Best effort:
				// a shared host withholds ALTER on the database itself (ERROR 1044) from a
				// user that only holds table-level grants.
				try {
					$db->createCommand("ALTER DATABASE `{$dbName}` CHARACTER SET utf8 COLLATE utf8_unicode_ci")->execute();
				} catch (\Exception $e) {
					Yii::warning([
						'message' => 'Cannot set the workspace database default charset; the tables pin their own.',
						'workspace' => $this->code,
						'error' => $e->getMessage(),
					], 'cpanel');
				}
			}

			// Get the database instance and fail here, with the cause, rather than midway
			// through the first import: the cPanel grant call above only warns, so without
			// this the operator sees PDO's bare "Access denied" and nothing about why.
			$workspaceDb = $this->getWorkspaceDb();
			try {
				$workspaceDb->open();
			} catch (\Exception $e) {
				throw new \Exception(sprintf(
					'The workspace database %s is not reachable with the master credentials (%s): %s %s',
					$dbName,
					$db->username,
					$e->getMessage(),
					$grantFailure === null
						? 'Check the cPanel MySQL privileges for this database.'
						: "cPanel refused the privilege grant: {$grantFailure}"
				), 0, $e);
			}

			$workspaceDbPath = Yii::getAlias('@workspace/install/db');

			// Import the shared database structure and data. `_05_data.sql` is git-ignored
			// (may contain credentials), so a missing file is skipped rather than fatal.
			foreach (['_01_structure.sql', '_03_common.sql', '_04_translations.sql', '_05_data.sql'] as $fileName) {
				$this->importSqlFile($workspaceDb, "{$workspaceDbPath}/{$fileName}", $fileName === '_05_data.sql');
			}

			// Type-specific seeds (install/db/<type>/*.sql, e.g. _02_permissions.sql), imported
			// after the shared data so they can reference the seeded rows.
			$typeSqlFiles = glob("{$workspaceDbPath}/{$this->type}/*.sql") ?: [];
			sort($typeSqlFiles, SORT_STRING);
			foreach ($typeSqlFiles as $typeSqlFile) {
				$this->importSqlFile($workspaceDb, $typeSqlFile);
			}

			// Create the super admin user by copying the subscriber's master account into
			// the tenant (same id and password hash, so the credentials match).
			if ($user = $this->subscription->subscriber->user ?? null) {
				$attributes = $user->attributes;
				$attributes['parent_id'] = null;

				$workspaceDb->createCommand()->insert('{{%user}}', $attributes)->execute();
				$workspaceDb->createCommand()->insert('{{%auth_assignment}}', [
					'item_name' => 'superAdmin',
					'user_id' => $user->id,
					'created_at' => time(),
				])->execute();
			}

			// Create related records, if any
//			if ($rows = WsTemplate::findAllForImport()) {
//				$workspaceDb->createCommand()->batchInsert('{{%template}}', array_keys($rows[0]), $this->prepareImportRows($rows))->execute();
//				if ($translationRows = WsTemplateTranslation::findAllByTemplate(array_column($rows, 'id'), true)) {
//					$workspaceDb->createCommand()->batchInsert('{{%template_translation}}', array_keys($translationRows[0]), $translationRows)->execute();
//				}
//			}

			return true;
		} catch (\Exception $e) {
			Yii::error(['message' => $e->getMessage(), 'workspace' => $this->code, 'exception' => (string) $e], __METHOD__);
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Imports a multi-statement SQL file into the given connection, failing on ANY
	 * broken statement.
	 *
	 * PDO only throws for the first statement of a multi-statement string; when a
	 * later statement fails, execution stops there and the rest of the file is
	 * silently skipped, leaving a half-imported schema behind. Draining every
	 * result set via nextRowset() surfaces the real error.
	 *
	 * @param \yii\db\Connection $db
	 * @param string $filePath
	 * @param bool $optional when true a missing file is skipped instead of failing
	 * @throws \Exception when the file cannot be read or any statement fails
	 */
	protected function importSqlFile($db, string $filePath, bool $optional = false): void
	{
		if ($optional && !is_file($filePath)) {
			return;
		}

		$sql = is_file($filePath) ? file_get_contents($filePath) : false;
		if ($sql === false) {
			throw new \Exception("Cannot read the SQL file: {$filePath}");
		}
		if (trim($sql) === '') {
			return;
		}

		$db->open();
		$statement = $db->pdo->prepare($sql);
		$statement->execute();

		try {
			while ($statement->nextRowset()) {
				// advance through every statement's result set
			}
		} catch (\PDOException $e) {
			throw new \Exception('SQL import failed in ' . basename($filePath) . ': ' . $e->getMessage(), 0, $e);
		}

		// Some driver versions report the failed statement via errorInfo() instead
		// of throwing from nextRowset().
		$error = $statement->errorInfo();
		if (!in_array($error[0] ?? '00000', ['00000', null], true)) {
			throw new \Exception('SQL import failed in ' . basename($filePath) . ': ' . ($error[2] ?? $error[0]));
		}
	}

	/**
	 * Turns a failed cPanel UAPI response into one readable sentence, for the errors
	 * shown to the operator.
	 *
	 * @param mixed $response
	 * @return string
	 */
	protected function describeCpanelFailure($response): string
	{
		if (!is_array($response)) {
			// CPanel::makeRequest() returns false when the HTTP request itself failed or
			// the answer was not JSON: a wrong base URL, wrong credentials or a blocked
			// port — not a cPanel refusal.
			return 'the HTTP request failed (check the cPanel base URL, credentials and firewall)';
		}

		$errors = $response['errors'] ?? null;

		return $errors ? implode('; ', (array) $errors) : 'no error message returned';
	}

	/**
	 * Logs a cPanel UAPI response when it reports failure (status != 1) or came back
	 * malformed. Logging instead of aborting keeps reinstalls idempotent ("database
	 * already exists" is benign) while leaving a trace for real failures.
	 *
	 * @param string $operation
	 * @param mixed $response
	 */
	protected function logCpanelUapiFailure(string $operation, $response): void
	{
		if (is_array($response) && (int) ($response['status'] ?? 0) === 1) {
			return;
		}

		Yii::warning([
			'message' => "cPanel {$operation} failed.",
			'workspace' => $this->code,
			'response' => $response,
		], 'cpanel');
	}

	/**
	 * Gets the tenant configuration files that carry install-time placeholders / baseUrls.
	 *
	 * @return array
	 */
	protected function getConfigFilePaths()
	{
		$dirPath = $this->getDirectoryPath();

		return [
			"{$dirPath}/common/config/main.php",
			"{$dirPath}/backend/config/main.php",
			"{$dirPath}/frontend/config/main.php",
			"{$dirPath}/console/config/main.php",
		];
	}

	/**
	 * Installs the Workspace directory.
	 *
	 * Copies the shared skeleton (`@workspace/install/dir`, minus the per-type overlay
	 * directories), overlays `@workspace/install/dir/<type>` on top, links the shared
	 * @workspace source/asset directories into the tenant and fills in the config placeholders.
	 *
	 * @return bool
	 */
	protected function installDirectory()
	{
		$db = static::getDb();
		$dirPath = $this->getDirectoryPath();

		try {
			if (!$dirPath) {
				throw new \Exception('The workspace has no domain/url to derive its directory from.');
			}

			FileHelper::createDirectory($dirPath, 0755);

			// The type overlays (install/dir/<type>/) are not part of the shared skeleton:
			// exclude every known type directory from the base copy. The pattern is
			// deliberately unanchored ("1/", not "/1/"): matchPathname does not match
			// anchored directory patterns at the copy root.
			$typeExcludes = array_map(function ($type) {
				return "{$type}/";
			}, array_keys(static::getTypeLabels()));
			FileHelper::copyDirectory(Yii::getAlias('@workspace/install/dir'), $dirPath, [
				'dirMode' => 0755,
				'except' => $typeExcludes,
			]);

			// Type-specific directory overlay (install/dir/<type>/), the filesystem
			// counterpart of the install/db/<type>/ seeds: whatever it contains is copied
			// over the skeleton just laid down (e.g. seed uploads).
			$typeDirPath = Yii::getAlias("@workspace/install/dir/{$this->type}");
			if (is_dir($typeDirPath)) {
				FileHelper::copyDirectory($typeDirPath, $dirPath, ['dirMode' => 0755]);
			}

			// Link the shared source/asset directories from the @workspace app into the tenant.
			FileHelper::symlink($this->getSymlinkMap());

			// On cPanel the workspace is served from its addon domain's document root, so the
			// app baseUrls lose the "/<url>" path prefix ("" / "/admin"). Locally the workspace
			// is served as a path under the master domain (root .htaccess) and keeps it.
			$baseUrlPrefix = $this->isLocalInstallEnvironment() ? '/' . $this->url : '';

			// Update the configuration files
			foreach ($this->getConfigFilePaths() as $filePath) {
				if (is_file($filePath)) {
					// strtr tries the longest keys first, so "/{{URL}}" wins over "{{URL}}"
					// inside the baseUrl templates while "{{URL}}" still covers plain uses.
					file_put_contents($filePath, strtr(file_get_contents($filePath), [
						'{{DB_HOST}}' => DbHelper::getDsnAttribute('host', $db) ?: 'localhost',
						'{{DB_NAME}}' => $this->getWorkspaceDbName(),
						'{{DB_USERNAME}}' => $db->username,
						'{{DB_PASSWORD}}' => $db->password,
						// The numeric workspace id (botai keeps INT primary keys).
						'{{ID}}' => $this->id,
						'{{NAME}}' => $this->code,
						'/{{URL}}' => $baseUrlPrefix,
						'{{URL}}' => $this->url,
					]));
				}
			}

			return true;
		} catch (\Exception $e) {
			Yii::error(['message' => $e->getMessage(), 'workspace' => $this->code, 'exception' => (string) $e], __METHOD__);
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Updates the crontab file (cPanel only; locally the docker `scheduler` service
	 * runs `yii schedule/run` for every tenant under <root>/workspaces/).
	 *
	 * @param bool $remove
	 * @return bool
	 */
	protected function updateCrontab($remove = false)
	{
		if ($this->isLocalInstallEnvironment()) {
			return true;
		}

		$cronJob = [
			'minute' => '*',
			'hour' => '*',
			'day' => '*',
			'month' => '*',
			'weekday' => '*',
			'command' => "/usr/local/bin/php " . $this->getDirectoryPath() . "/yii schedule/run >/dev/null 2>&1",
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
		$target = $this->getRelativeDirectoryPath();
		$line = $this->getHtaccessRewriteRule(null, $target);

		if ($remove === true) {
			return $htaccess->deleteLine($line);
		}

		// Delete similar rewrite rules with the same workspace directory
		if ($workspaceLines = $htaccess->getLines("{$target}/$1")) {
			$htaccess->deleteLines($workspaceLines);
		}

		// The rule goes at the top of the Workspace Rules block (newest tenant first).
		return $htaccess
			->addLine($line)
			->afterLine('# BEGIN Workspace Rules')
			->save();
	}

	/**
	 * Applies a URL slug or domain change to an installed Workspace: rewrites the root
	 * .htaccess rule and the baseUrl entries of the tenant config files, renaming the
	 * tenant directory when the key it is named after changed.
	 *
	 * The domain wins over the URL in {@see getDirectoryName()}, so setting, clearing or
	 * editing it moves the directory just as renaming the slug of a domain-less tenant
	 * does. Pass the previous domain explicitly - `null` means "unchanged", while a
	 * tenant that had no domain must pass `''`, or the new one would be read back here
	 * and the old directory left orphaned.
	 *
	 * @param string $previousUrl the URL slug before the change
	 * @param string|null $previousDomain the domain before the change, or null when it did not change
	 * @return bool
	 */
	public function saveUrl($previousUrl, $previousDomain = null)
	{
		$dirPath = $this->getDirectoryPath();
		$previousDirName = $this->getDirectoryName($previousDomain, $previousUrl);
		$previousDirPath = $previousDirName === null ? null : Yii::getAlias("@base/workspaces/{$previousDirName}");

		if ($dirPath && $previousDirPath && $previousDirPath !== $dirPath) {
			// The directory key changed: move the tenant and drop its old rewrite rule
			// (updateHtaccess() only cleans up rules pointing at the new directory).
			if (is_dir($previousDirPath) && !file_exists($dirPath)) {
				rename($previousDirPath, $dirPath);
			}
			\tws\textfile\TextFile::load(Yii::getAlias('@base/.htaccess'))
				->deleteLine($this->getHtaccessRewriteRule($previousUrl, "workspaces/{$previousDirName}"));
		}

		$this->updateHtaccess();

		if ($previousUrl === $this->url) {
			return true;
		}

		// Update the configuration files
		foreach ($this->getConfigFilePaths() as $filePath) {
			if (is_file($filePath)) {
				file_put_contents($filePath, strtr(file_get_contents($filePath), [
					"'baseUrl' => '/{$previousUrl}'" => "'baseUrl' => '/{$this->url}'",
					"'baseUrl' => '/{$previousUrl}/admin'" => "'baseUrl' => '/{$this->url}/admin'",
				]));
			}
		}

		return true;
	}

	/**
	 * Clears the tenant application's runtime file cache, so a direct write to the tenant
	 * database (a database update run from master, a settings change) is not masked by the
	 * tenant's cached values. The tenant caches through TagDependency with no expiry, so
	 * without this it would keep serving the old values.
	 */
	public function flushTenantCache()
	{
		$dir = $this->getDirectoryPath();
		if (!$dir) {
			return;
		}
		foreach (['backend', 'frontend', 'console'] as $app) {
			$cacheDir = "{$dir}/{$app}/runtime/cache";
			if (!is_dir($cacheDir)) {
				continue;
			}
			foreach (glob("{$cacheDir}/*") ?: [] as $path) {
				if (is_dir($path)) {
					\yii\helpers\FileHelper::removeDirectory($path);
				} elseif (is_file($path)) {
					@unlink($path);
				}
			}
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
			// The database is touched only when there is nothing to lose.
			//
			// installDatabase() imports install/db/_01_structure.sql and the rest, which on
			// a live tenant means the install structure on top of real data - so reinstall
			// used to skip it entirely. That left the other half broken: a workspace whose
			// database was never created had no way to get one, and the reinstall button
			// could not repair it.
			//
			// Installing only when the database is missing or empty serves both. A tenant
			// with tables keeps its conversations, messages and assistants untouched and is
			// brought up to date through workspace/_database/<date>-tenant-update.sql.
			if ($this->workspaceDatabaseNeedsInstall() && !$this->installDatabase()) {
				throw new \Exception('Cannot create the workspace database.');
			}
			if (!$this->installDirectory()) {
				throw new \Exception('Cannot create the workspace directory.');
			}
			if (!$this->ensureCpanelAddonDomain()) {
				throw new \Exception('Cannot create the cPanel addon domain.');
			}
			if (!$this->updateCrontab()) {
				throw new \Exception('Cannot update the crontab file.');
			}
			if (!$this->updateHtaccess()) {
				throw new \Exception('Cannot update the .htaccess file.');
			}
			return true;
		} catch (\Exception $e) {
			Yii::error(['message' => $e->getMessage(), 'workspace' => $this->code, 'modelErrors' => $this->errors, 'exception' => (string) $e], __METHOD__);
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
			// Baza de date NU se mai şterge.
			//
			// install($reinstall = true) trece prin aici, deci butonul de reinstall din
			// interfaţă arunca baza tenantului cu tot cu conversaţii, mesaje şi asistenţi
			// — fără confirmare şi fără cale de întoarcere. Dezinstalarea lasă acum baza
			// pe loc; ştergerea ei e o decizie separată, făcută manual.
			//
			// if ($this->isLocalInstallEnvironment()) {
			//     static::getDb()->createCommand("DROP DATABASE IF EXISTS `{$this->getWorkspaceDbName()}`")->execute();
			// } else {
			//     $response = Yii::$app->cPanel->uapi->Mysql->delete_database(['name' => $this->getWorkspaceDbName()]);
			//     $this->logCpanelUapiFailure('Mysql::delete_database', $response);
			// }

			if (!$this->updateCrontab(true)) {
				throw new \Exception('Cannot update the crontab file.');
			}
			if (!$this->updateHtaccess(true)) {
				throw new \Exception('Cannot update the .htaccess file.');
			}

			// Remove the directory links first so the recursive delete below never
			// traverses into the shared @workspace sources. On Windows PHP is_link()
			// does not detect junctions, so FileHelper::removeDirectory() would otherwise
			// follow them; rmdir() removes the junction itself without touching the target.
			foreach (array_values($this->getSymlinkMap()) as $linkPath) {
				if (is_link($linkPath)) {
					@unlink($linkPath);
				} elseif (is_dir($linkPath)) {
					@rmdir($linkPath);
				}
			}

			if ($dirPath = $this->getDirectoryPath()) {
				FileHelper::removeDirectory($dirPath);
			}
			return true;
		} catch (\Exception $e) {
			Yii::error(['message' => $e->getMessage(), 'workspace' => $this->code, 'exception' => (string) $e], __METHOD__);
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Ensures the cPanel addon domain that serves this workspace exists. The addon domain
	 * is the workspace `domain` column (a real registrable name, e.g. "primadentalclinic.ro");
	 * its document root is the tenant directory. No-op locally (the root .htaccess routes
	 * the tenant instead) and when the cPanel component holds placeholder credentials.
	 *
	 * @return bool
	 */
	protected function ensureCpanelAddonDomain(): bool
	{
		if ($this->isLocalInstallEnvironment()) {
			return true;
		}

		$cpanel = Yii::$app->cPanel;
		$baseUrl = rtrim((string) ($cpanel->baseUrl ?? ''), '/');
		$username = (string) ($cpanel->username ?? '');
		$password = (string) ($cpanel->password ?? '');
		$apiToken = (string) ($cpanel->apiToken ?? '');

		if (empty($baseUrl) || empty($username) || (empty($password) && empty($apiToken))) {
			return true;
		}
		foreach ([$baseUrl, $username, $password, $apiToken] as $value) {
			if (stripos($value, 'CPANEL_') !== false) {
				return true;
			}
		}

		// The `url` column is only the path slug ("primadentalclinic"), which cPanel
		// rejects ("must have a valid TLD label"); the addon domain needs the real domain.
		$addonDomain = strtolower(trim((string) $this->domain));
		if ($addonDomain === '' || strpos($addonDomain, '.') === false) {
			$this->addError('', Yii::t('common', 'The workspace domain must be a full domain name (with TLD) to create the cPanel addon domain.'));
			return false;
		}

		$existingDomains = array_map('strtolower', $this->getCpanelAddonDomains());
		if (in_array($addonDomain, $existingDomains, true)) {
			return true;
		}

		$dirPath = $this->getCpanelDirectoryPath();
		if (empty($dirPath)) {
			$this->addError('', Yii::t('common', 'Missing cPanel document root.'));
			return false;
		}

		// API2 AddonDomain::addaddondomain parameters:
		// - newdomain: the real domain with extension (workspace domain)
		// - subdomain: the addon domain name without extension
		// - dir: document root, relative to the account home
		// - pass: password of the FTP user cPanel creates alongside the addon domain.
		//   Nothing reads that account afterwards, so a fresh random secret does the job
		//   without putting the cPanel account password on the wire (API2 carries the
		//   parameters in the query string, i.e. into the access log).
		$payload = [
			'newdomain' => $addonDomain,
			'subdomain' => $this->getCpanelSubdomain(),
			'dir' => $dirPath,
			'pass' => Yii::$app->security->generateRandomString(24) . 'aA1!',
		];

		$response = $this->cpanelRequestApi2('AddonDomain', 'addaddondomain', $payload);

		// API2 signals two layers: event.result says the call executed, while the
		// function's own outcome is in data[0].result/reason — a failed creation
		// (domain limit, disabled feature, invalid domain) still has event.result = 1.
		$cpRes = $response['cpanelresult'] ?? [];
		$event = $cpRes['event'] ?? [];
		$data = $cpRes['data'][0] ?? [];
		$ok = (int) ($event['result'] ?? 0) === 1 && (int) ($data['result'] ?? 0) === 1;
		$errText = (string) ($cpRes['error'] ?? '');
		if ($errText === '' && (int) ($data['result'] ?? 0) !== 1) {
			$errText = (string) ($data['reason'] ?? '');
		}

		if (!$ok) {
			$error = $errText !== '' ? $errText : Yii::t('common', 'Unknown cPanel error.');

			Yii::error([
				'message' => 'cPanel addon domain create failed (API2).',
				'workspace' => $this->code,
				'payload' => array_merge($payload, ['pass' => '***']),
				'response' => $response,
			], 'cpanel');

			$this->addError('', $error);
			return false;
		}

		return true;
	}

	/**
	 * Executes a cPanel API2 request.
	 *
	 * @param string $module
	 * @param string $func
	 * @param array $params
	 * @return array|null
	 */
	protected function cpanelRequestApi2(string $module, string $func, array $params = []): ?array
	{
		$cpanel = Yii::$app->cPanel;
		$baseUrl = rtrim((string) ($cpanel->baseUrl ?? ''), '/');
		$username = (string) ($cpanel->username ?? '');
		$authorization = $this->getCpanelAuthorization();
		if ($authorization === null) {
			return null;
		}

		$query = array_merge([
			'cpanel_jsonapi_user' => $username,
			'cpanel_jsonapi_apiversion' => 2,
			'cpanel_jsonapi_module' => $module,
			'cpanel_jsonapi_func' => $func,
		], $params);

		$url = $baseUrl . '/json-api/cpanel?' . http_build_query($query);

		$client = new \GuzzleHttp\Client(['verify' => false, 'timeout' => 30]);

		try {
			// http_errors off on purpose: cPanel answers a refusal with 403 AND a JSON
			// body that names the reason, which the callers already read. Letting Guzzle
			// throw instead would replace that with an exception whose message quotes the
			// full request URL, secrets included.
			$res = $client->get($url, [
				'headers' => [
					'Authorization' => $authorization,
					'Accept' => 'application/json',
				],
				'http_errors' => false,
			]);
		} catch (\Throwable $e) {
			Yii::error([
				'message' => 'cPanel API2 request failed.',
				'workspace' => $this->code,
				'module' => $module,
				'func' => $func,
				'error' => static::redactCpanelSecrets($e->getMessage()),
			], 'cpanel');

			return null;
		}

		$json = json_decode((string) $res->getBody(), true);

		return is_array($json) ? $json : null;
	}

	/**
	 * The Authorization header value for a direct cPanel call, or null when the component
	 * holds nothing usable. An API token is preferred over the account password: it is the
	 * only thing that works for an account with two-factor authentication, and it keeps
	 * the real password out of the request.
	 *
	 * @return string|null
	 */
	protected function getCpanelAuthorization(): ?string
	{
		$cpanel = Yii::$app->cPanel;

		$token = (string) ($cpanel->apiToken ?? '');
		$username = (string) ($cpanel->username ?? '');
		$password = (string) ($cpanel->password ?? '');

		if (empty($username)) {
			return null;
		}
		if (!empty($token)) {
			return "cpanel {$username}:{$token}";
		}
		if (!empty($password)) {
			return 'Basic ' . base64_encode("{$username}:{$password}");
		}

		return null;
	}

	/**
	 * Masks the credentials cPanel expects as query parameters, so a request URL can
	 * be logged or shown without handing out the password with it.
	 *
	 * @param string $text
	 * @return string
	 */
	protected static function redactCpanelSecrets(string $text): string
	{
		return (string) preg_replace('/\b(pass|password)=[^&\s"\']*/i', '$1=***', $text);
	}

	/**
	 * Fetches the existing addon domains from cPanel.
	 *
	 * @return array
	 */
	protected function getCpanelAddonDomains(): array
	{
		$response = $this->cpanelRequest('DomainInfo/list_domains');
		if (!$response || (int) ($response['status'] ?? 0) !== 1) {
			Yii::error([
				'message' => 'cPanel list domains failed.',
				'workspace' => $this->code,
				'response' => $response,
			], 'cpanel');
			return [];
		}

		$addons = $response['data']['addon_domains'] ?? [];
		if (!is_array($addons)) {
			return [];
		}

		return array_values(array_filter($addons));
	}

	/**
	 * Executes a cPanel UAPI request.
	 *
	 * @param string $endpoint e.g. "DomainInfo/list_domains"
	 * @param array $params
	 * @return array|null
	 */
	protected function cpanelRequest(string $endpoint, array $params = []): ?array
	{
		$cpanel = Yii::$app->cPanel;
		$baseUrl = rtrim((string) ($cpanel->baseUrl ?? ''), '/');
		if (empty($baseUrl) || ($authorization = $this->getCpanelAuthorization()) === null) {
			return null;
		}

		$client = new Client(['transport' => 'yii\httpclient\CurlTransport']);
		$request = $client->createRequest()
			->setMethod('GET')
			->setUrl("{$baseUrl}/execute/" . ltrim($endpoint, '/'))
			->setData($params)
			->setHeaders([
				'Authorization' => $authorization,
				'Accept' => 'application/json',
			]);

		$response = $request->send();
		if (!$response->isOk) {
			Yii::error([
				'message' => 'cPanel request failed.',
				'endpoint' => $endpoint,
				'params' => $params,
				'statusCode' => $response->getStatusCode(),
				'body' => $response->getContent(),
			], 'cpanel');
			return null;
		}

		return $response->getData();
	}

	/**
	 * Gets the cPanel document root for the workspace, relative to the account home:
	 * public_html/workspaces/<domain>.
	 *
	 * @return string
	 */
	protected function getCpanelDirectoryPath(): string
	{
		if ($this->getDirectoryName() === null) {
			return '';
		}

		return 'public_html/' . $this->getRelativeDirectoryPath();
	}

	/**
	 * Gets the cPanel addon domain name (the name without extension), e.g.
	 * "primadentalclinic" for the domain "primadentalclinic.ro".
	 *
	 * @return string
	 */
	protected function getCpanelSubdomain(): string
	{
		$domain = strtolower($this->getDirectoryName() ?: (string) $this->url);

		return explode('.', $domain, 2)[0];
	}
	//endregion Workspace Config
}
