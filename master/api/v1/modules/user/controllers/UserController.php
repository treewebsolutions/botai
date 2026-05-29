<?php

namespace api\v1\modules\user\controllers;

use api\v1\modules\user\models\LoginForm;
use api\v1\modules\user\models\SignupForm;
use api\v1\modules\user\models\User;
use api\v1\modules\user\models\UserForm;
use api\v1\modules\user\services\UserService;
use common\models\ActivateAccountForm;
use common\models\AuthAssignment;
use common\models\ResetPasswordRequestForm;
use common\models\ResetPasswordForm;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\UnprocessableEntityHttpException;

class UserController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $modelClass = 'api\v1\modules\user\models\User';

	private UserService $userService;

	public function __construct($id, $module, UserService $userService, $config = [])
	{
		parent::__construct($id, $module, $config);
		$this->userService = $userService;
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$role = Yii::$app->request->bodyParams['bypass'] ? '@' : '';
		return ArrayHelper::merge(parent::behaviors(), [
			'authenticator' => [
				'class' => HttpBearerAuth::class,
				'except' => ['login', 'request-password-reset', 'reset-password', 'signup', 'activate'],
			],
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['login', 'request-password-reset', 'reset-password', 'signup', 'activate'],
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'actions' => ['profile'],
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'actions' => ['index', 'view'],
						'roles' => [$role ?: 'viewUser'],
					],
					[
						'allow' => true,
						'actions' => ['create'],
						'roles' => [$role ?: 'createUser'],
					],
					[
						'allow' => true,
						'actions' => ['update'],
						'roles' => [$role ?: 'updateUser'],
					],
					[
						'allow' => true,
						'actions' => ['delete'],
						'roles' => [$role ?: 'deleteUser'],
					],
					[
						'allow' => true,
						'actions' => ['restore'],
						'roles' => [$role ?: 'restoreUser'],
					],
				],
			],
		]);
	}

	/**
	 * Displays all [[User]] models.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$requestParams = Yii::$app->request->queryParams;
		if (empty($requestParams)) {
			$requestParams = Yii::$app->request->bodyParams;
		}

		return $this->userService->list($requestParams);
	}

	public function actionProfile()
	{
		$id = Yii::$app->user->id;

		if (!empty(Yii::$app->request->bodyParams)) {
			$model = $this->findModel($id, UserForm::class);
			try {
				if ($model->load(Yii::$app->request->bodyParams, '') && $model->save()) {
					if ($model->image) {
						$model->image = $model->getImageUrl(true);
					}
					return [
						'message' => Yii::t('api', 'Record successfully updated.'),
						'data' => $model,
					];
				}

				if ($model->hasErrors()) {
					throw new UnprocessableEntityHttpException();
				}

				throw new ServerErrorHttpException();
			} catch (\Throwable $e) {
				Yii::$app->response->statusCode = $e->statusCode ?? 500;
				Yii::$app->response->data['message'] = Yii::t('api', 'Record update failed.');
				Yii::$app->response->data['errors'] = $model->getErrors();
			}

			return Yii::$app->response;
		}

		return  $this->findModel($id);
	}

	/**
	 * Displays a single [[User]] model.
	 *
	 * @param int|string $id The User model ID.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 * @throws ForbiddenHttpException if user does not have the proper permissions
	 */
	public function actionView($id)
	{
		return  $this->findModel($id);
	}

	/**
	 * Creates a new [[User]] model.
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to create the model
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionCreate()
	{
		$model = new UserForm(['scenario' => UserForm::SCENARIO_CREATE]);
		try {
			if ($model->load(Yii::$app->request->bodyParams, '') && $model->save()) {
				Yii::$app->response->statusCode = 201;
				if ($model->image) {
					$model->image = $model->getImageUrl(true);
				}
				return [
					'message' => Yii::t('api', 'Record successfully created.'),
					'data' => $model,
				];
			}
			if ($model->hasErrors()) {
				throw new UnprocessableEntityHttpException();
			}
			throw new ServerErrorHttpException();
		} catch (\Throwable $e) {
			Yii::$app->response->statusCode = $e->statusCode ?? 500;
			Yii::$app->response->data['message'] = Yii::t('api', 'Record creation failed.');
			Yii::$app->response->data['errors'] = $model->getErrors();
		}

		return Yii::$app->response;
	}

	/**
	 * Updates an existing [[User]] model.
	 *
	 * @param int|string $id The User model ID.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to update the model
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionUpdate($id)
	{
		$model = $this->findModel($id, UserForm::class);
		try {
			if ($model->load(Yii::$app->request->bodyParams, '') && $model->save()) {
				if ($model->image) {
					$model->image = $model->getImageUrl(true);
				}
				return [
					'message' => Yii::t('api', 'Record successfully updated.'),
					'data' => $model,
				];
			}
			if ($model->hasErrors()) {
				throw new UnprocessableEntityHttpException();
			}
			throw new ServerErrorHttpException();
		} catch (\Throwable $e) {
			Yii::$app->response->statusCode = $e->statusCode ?? 500;
			Yii::$app->response->data['message'] = Yii::t('api', 'Record update failed.');
			Yii::$app->response->data['errors'] = $model->getErrors();
		}

		return Yii::$app->response;
	}

	/**
	 * Deletes an existing [[User]] model.
	 * Handles bulk delete by sending an array of model IDs in bodyParams.
	 *
	 * @param int|null $id The [[User]] model ID. If this is null, the request bodyParams will be used instead.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model(s) cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to delete the [[User]]
	 */
	public function actionDelete($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->bodyParams;
		}
		$models = $this->findModel($id, null, true);
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$deletedModels = [];
			/** @var User $model */
			foreach ($models->each() as $model) {
				$isPermanent = $model->deleted ?: !Yii::$app->settings->get('enableSoftDelete');
				if ($model->delete($isPermanent)) {
					$deletedModels[] = $model->id;
				} else {
					throw new \Exception();
				}
			}
			if ($isPermanent) {
				foreach ($deletedModels as $deletedModel) {
					FileHelper::removeDirectory(Yii::getAlias("@uploads/user/{$deletedModel}"));
				}
				AuthAssignment::deleteAll(['user_id' => $deletedModels]);
			}
			$dbTransaction->commit();
			Yii::$app->response->statusCode = 204;
			return [
				'message' => count($deletedModels) === 1 ?
					Yii::t('api', 'Record successfully deleted.') :
					Yii::t('api', 'Records successfully deleted.')
			];
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
		}

		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Restores [[User]] models that are marked as deleted.
	 * @param int|null $id The [[User]] model ID. If this is null, the request bodyParams will be used instead.
	 * @return mixed
	 * @throws \yii\web\NotFoundHttpException if the model(s) with deleted = 1 cannot be found
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to restore the [[User]]
	 */
	public function actionRestore($id = null)
	{
		if ($id === null) {
			$id = Yii::$app->request->bodyParams;
		}
		$models = $this->findModel($id, null, true);
		if (!User::find()->andWhere([
			'id' => $id,
			'deleted' => User::YES,
		])->count()) {
			throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
		}
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$restoredModels = [];
			/** @var User $model */
			foreach ($models->each() as $model) {
				if ($model->deleted = User::YES) {
					if ($model->restore()) {
						$restoredModels[] = $model->id;
						$data[] = $model;
					} else {
						throw new \Exception();
					}
				}
			}
			$dbTransaction->commit();
			return [
				'message' => count($restoredModels) === 1 ?
					Yii::t('api', 'Record successfully restored.') :
					Yii::t('api', 'Records successfully restored.'),
				'data' => $data,
			];
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Signs up a new [[User]].
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to signup the user
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 * @throws \yii\base\Exception
	 */
	public function actionSignup()
	{
		$model = new SignupForm();
		if ($model->load(Yii::$app->request->bodyParams, '') && $model->signup()) {
			Yii::$app->response->statusCode = 201;
			Yii::$app->response->headers->set('Location', Url::to(['view', 'id' => $model->getUser()->id], true));
			if (Yii::$app->params['user.accountActivation'] == User::ACCOUNT_ACTIVATION_CONFIRMATION) {
				$model->getUser()->sendActivationEmail(); // TODO: check if message was successfully sent
				return [
					'message' => Yii::t('api', 'Please check your email in order to activate your account.'),
				];
			}
			return [
				'message' => Yii::t('api', 'Signup successful.'),
				'data' => User::findProfile($model->getUser()->id),
			];
		}
		if ($model->hasErrors()) {
			throw new UnprocessableEntityHttpException($model->getErrorSummary(false)[0]);
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Logs in a [[User]].
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to authenticate the user
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionLogin()
	{
		$model = new LoginForm();
		if ($model->load(Yii::$app->request->bodyParams, '') && $model->login()) {
			return [
				'message' => Yii::t('api', 'Login successful.'),
				'data' => User::findProfile($model->username),
			];
		}
		if ($model->hasErrors()) {
			throw new UnprocessableEntityHttpException($model->getErrorSummary(false)[0]);
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Requests password reset.
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to request the password reset
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionRequestPasswordReset()
	{
		$model = new ResetPasswordRequestForm();
		if ($model->load(Yii::$app->request->bodyParams, '') && $model->validate() && $model->sendRequest()) {
			return [
				'message' => Yii::t('api', 'Check your email/phone for further instructions.'),
			];
		}
		if ($model->hasErrors()) {
			throw new UnprocessableEntityHttpException($model->getErrorSummary(false)[0]);
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Resets the password.
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to reset the password
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionResetPassword()
	{
		$model = new ResetPasswordForm(['scenario' => ResetPasswordForm::SCENARIO_TOKEN]);
		if ($model->load(Yii::$app->request->bodyParams, '') && $model->validate()) {
			$model->setScenario(ResetPasswordForm::SCENARIO_PASSWORD);
			if ($model->load(Yii::$app->request->bodyParams, '') && $model->validate() && $model->resetPassword()) {
				return [
					'message' => Yii::t('api', 'New password saved.'),
					'data' => User::findProfile($model->getUser()->id),
				];
			}
		}
		if ($model->hasErrors()) {
			throw new UnprocessableEntityHttpException($model->getErrorSummary(false)[0]);
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Activates a [[User]] account.
	 *
	 * @return mixed
	 * @throws \yii\web\ServerErrorHttpException if there is any unknown error when trying to activate the account
	 * @throws \yii\web\UnprocessableEntityHttpException if there are validation errors
	 */
	public function actionActivate()
	{
		$model = new ActivateAccountForm();

		if ($model->load(Yii::$app->request->bodyParams, '') && $model->validate() && $model->activate()) {
			Yii::$app->trigger(User::EVENT_AFTER_ACTIVATE_ACCOUNT, new \yii\base\Event(['sender' => $model->getUser()]));
			return [
				'message' => Yii::t('api', 'Your account has been activated.'),
				'data' => User::findProfile($model->getUser()->id),
			];
		}
		if ($model->hasErrors()) {
			throw new UnprocessableEntityHttpException($model->getErrorSummary(false)[0]);
		}
		throw new ServerErrorHttpException(Yii::t('api', 'Operation failed for unknown reason.'));
	}

	/**
	 * Finds the [[User]] model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param int $id
	 * @param \yii\db\ActiveRecord|string|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|User|UserForm the loaded model
	 * @throws \yii\web\NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : User::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);
		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
			}
			return $query;
		}
		$query->andWhere(['deleted' => User::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}
		throw new NotFoundHttpException(Yii::t('api', 'The requested resource does not exist.'));
	}
}
