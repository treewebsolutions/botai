<?php

namespace backend\controllers;

use backend\models\SearchForm;
use backend\models\UserProfileForm;
use common\models\LoginForm;
use common\models\PasswordResetRequestForm;
use common\models\ResetPasswordForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class SiteController extends MainController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'actions' => ['login', 'request-password-reset', 'reset-password'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'logout' => ['POST'],
					'set-working-point' => ['POST'],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
				'layout' => 'blank',
			],
		];
	}

	/**
	 * Displays a generic success page.
	 *
	 * @param string $data
	 * @return mixed
	 */
	public function actionSuccess($data)
	{
		$this->layout = 'blank';

		return $this->render('success', [
			'data' => Json::decode(Yii::$app->security->unmaskToken($data)),
		]);
	}

	/**
	 * Displays homepage.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		Yii::$app->cache->flush();
		return $this->render('index');
	}

	/**
	 * Logs in a user.
	 *
	 * @return mixed
	 */
	public function actionLogin()
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$this->layout = 'authentication';

		$model = new LoginForm();

		if ($model->load(Yii::$app->request->post()) && $model->login()) {
			Yii::$app->eventLog->create([
				'user_id' => Yii::$app->user->id,
				'operation' => (Yii::$app->eventLog)::ACTION_LOGIN,
				'ip_address' => Yii::$app->request->userIP,
			]);

			return $this->goBack();
		}

		return $this->render('login', [
			'model' => $model,
		]);
	}

	/**
	 * Logs out the current user.
	 *
	 * @return mixed
	 */
	public function actionLogout()
	{
		Yii::$app->eventLog->create([
			'user_id' => Yii::$app->user->id,
			'operation' => (Yii::$app->eventLog)::ACTION_LOGOUT,
			'ip_address' => Yii::$app->request->userIP,
		]);

		Yii::$app->user->logout();

		return $this->goHome();
	}

	/**
	 * Requests password reset.
	 *
	 * @return mixed
	 * @throws \yii\base\Exception
	 */
	public function actionRequestPasswordReset()
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$this->layout = 'authentication';

		$model = new PasswordResetRequestForm();

		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->sendEmail()) {
				Yii::$app->session->setFlash('success', Yii::t('common', 'Check your email for further instructions.'));
				return $this->goHome();
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'Sorry, we are unable to reset password for the provided email address.'));
			}
		}

		return $this->render('request-password-reset', [
			'model' => $model,
		]);
	}

	/**
	 * Resets password.
	 *
	 * @param string $token
	 * @return mixed
	 * @throws \yii\web\BadRequestHttpException
	 * @throws \yii\base\Exception
	 */
	public function actionResetPassword($token)
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$this->layout = 'authentication';

		try {
			$model = new ResetPasswordForm($token);
		} catch (InvalidArgumentException $e) {
			throw new BadRequestHttpException($e->getMessage());
		}

		if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'New password saved.'));

			if ($user = $model->getUser()) {
				if (Yii::$app->getUser()->login($user)) {
					Yii::$app->eventLog->create([
						'user_id' => $user->id,
						'operation' => (Yii::$app->eventLog)::ACTION_LOGIN,
						'ip_address' => Yii::$app->request->userIP,
					]);
				}
			}

			return $this->goHome();
		}

		return $this->render('reset-password', [
			'model' => $model,
		]);
	}

	/**
	 * Displays profile page.
	 *
	 * @return mixed
	 * @throws \yii\db\Exception
	 */
	public function actionProfile()
	{
		$model = UserProfileForm::findOne(Yii::$app->user->id);

        if (!$model->authAssignment->item_name) {
            throw new ForbiddenHttpException(Yii::t('common', 'Profile cannot be updated.'));
        }

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Record has been updated.'));
			return $this->refresh();
		}

		return $this->render('profile', [
			'model' => $model
		]);
	}

	/**
	 * Searches globally in the site.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{
		$dataset = [];
		if ($criteria = Yii::$app->request->post('contractor', null)) {
			if ($contractor = \common\models\master\Contractor::findByAttributes(['email' => $criteria])) {
				$attributes = $contractor->attributes;
				$dataset[] = [
					'email' => $attributes['email'],
					'name' => $attributes['name'],
					'first_name' => $attributes['first_name'],
					'middle_name' => $attributes['middle_name'],
					'last_name' => $attributes['last_name'],
					'phone' => $attributes['phone'],
					'fax' => $attributes['fax'],
					'url' => $attributes['url'],
					'country' => $attributes['country'],
					'county' => $attributes['county'],
					'locality' => $attributes['locality'],
					'zip_code' => $attributes['zip_code'],
					'address' => $attributes['address'],
					'latitude' => $attributes['latitude'],
					'longitude' => $attributes['longitude'],
				];
			}
			return $this->asJson([
				'results' => $dataset,
			]);
		}

		$searchModel = new SearchForm();
		$searchModel->load(Yii::$app->request->get());

		$dataProvider = $searchModel->search();

		return $this->render('search', [
			'searchModel' => $searchModel,
			'dataProvider' => $dataProvider,
		]);
	}

	/**
	 * Displays info page.
	 *
	 * @return mixed
	 */
	public function actionInfo()
	{
		$response = Yii::$app->getResponse();

		// This page can't be accessed without providing valid data.
		if (empty($response->data)) {
			return $this->redirect(['/site/index']);
		}

		return $this->render('info', [
			'data' => Json::decode($response->data),
		]);
	}
}
