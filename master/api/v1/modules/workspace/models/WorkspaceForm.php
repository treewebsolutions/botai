<?php

namespace api\v1\modules\workspace\models;

use api\v1\modules\user\models\UserForm;
use common\models\Contractor;
use common\models\Workspace;
use common\models\WorkspaceHasUser;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class WorkspaceForm extends Workspace
{

	/**
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * @var Contractor The Contractor model.
	 */
	private $_contractor;

	/**
	 * {@inheritdoc}
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		$this->type = static::TYPE_CONTRACTOR;
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['contractor_id'], 'required'],
			['url', 'string', 'min' => 3, 'max' => 255],
			['url', 'match', 'pattern' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/i'],
			['url', 'trim'],
			['url', 'filter', 'filter' => function ($value) {
				return mb_strtolower($value);
			}],
			['url', 'unique', 'targetClass' => Workspace::class, 'targetAttribute' => ['url' => 'url'], 'when' => function () {
				return $this->isAttributeChanged('url');
			}],
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'contractor_id' => Yii::t('label', 'Contractor'),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * Gets the User model.
	 *
	 * @return User
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * Gets the Contractor model.
	 *
	 * @return Contractor
	 */
	public function getContractor()
	{
		return $this->_contractor;
	}

	/**
	 * Connect user account
	 *
	 * @return bool
	 */
	protected function saveUser()
	{
		$transaction = Yii::$app->getDb()->beginTransaction();
		try {
			$contractor = Contractor::findOne(['id' => $this->contractor_id]);
			$user = UserForm::find()
				->select([
					'*',
				])
				->andWhere([
					'AND',
					['IS NOT', 'email', null],
					['=', 'email', $contractor->email],
				])
				->one();
			if (!$user) {
				$user = new UserForm(['scenario' => UserForm::SCENARIO_CREATE]);
				$user->first_name = $contractor->first_name ?: $contractor->name;
				$user->last_name = $contractor->last_name ?: 'admin';
				$user->email = $contractor->email;
				$user->phone = $contractor->phone;
				$user->password = date('Ymd');
				$user->password_confirm = date('Ymd');
				if (!$user->save()) {
					$this->addErrors($user->getErrors());
					throw new \Exception();
				}
			}
			$this->_user = $user;
			$transaction->commit();
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the Contractor model.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function saveContractor()
	{
		try {
			$user = $this->getUser();
			$contractor = Contractor::findOne(['id' => $this->contractor_id]);
			$contractor->user_id = $user->id;
			if (!$contractor->save()) {
				$this->addErrors($contractor->getErrors());
				throw new \Exception();
			}
			$this->_contractor = $contractor;
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the WorkspaceHasUser model.
	 *
	 * @return bool
	 */
	public function saveWorkspaceHasUser()
	{
		try {
			if (!($user = $this->contractor->user)) {
				throw new \Exception();
			}

			$workspaceHasUser = WorkspaceHasUser::findOne([
				'workspace_id' => $this->id,
				'user_id' => $user->id,
			]);
			if (!$workspaceHasUser) {
				$workspaceHasUser = new WorkspaceHasUser();
			}
			$workspaceHasUser->setAttributes($user->getAttributes());
			$workspaceHasUser->workspace_id = $this->id;
			$workspaceHasUser->user_id = $user->id;
			if (!WorkspaceHasUser::find()->where(['user_id' => $user->id, 'default' => WorkspaceHasUser::YES])->exists()) {
				$workspaceHasUser->default = WorkspaceHasUser::YES;
			}
			if (!$workspaceHasUser->save()) {
				$this->addErrors($workspaceHasUser->getErrors());
				throw new \Exception('Cannot save WorkspaceHasUser model.');
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Save url for current workspace
	 *
	 * @return bool
	 */
	public function saveUrl($url)
	{
		$this->updateHtaccess();
		$dirPath = Yii::getAlias("@workspace/workspaces/{$this->id}");
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
					"'baseUrl' => '/$url'" => "'baseUrl' => '/$this->url'",
				]));
				file_put_contents($filePath, strtr(file_get_contents($filePath), [
					"'baseUrl' => '/$url/api'" => "'baseUrl' => '/$this->url/api'",
				]));
			}
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$isNewRecord = $this->getIsNewRecord();
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if ($isNewRecord || !$this->code) {
				$this->code = static::generateUniqueCode();
			}
			if (!$isNewRecord) {
				$workspace = static::findOne(['id' => $this->id]);
			}
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!$this->saveUser()) {
				throw new \Exception();
			}
			if (!$this->saveContractor()) {
				throw new \Exception();
			}
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (!$this->saveWorkspaceHasUser()) {
				throw new \Exception();
			}
			if ($isNewRecord) {
				if (!$this->install()) {
					throw new \Exception();
				}
			} else {
				if ($workspace->url != $this->url) {
					$this->saveUrl($workspace->url);
				}
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
