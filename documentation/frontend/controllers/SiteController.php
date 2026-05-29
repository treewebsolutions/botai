<?php

namespace frontend\controllers;

use common\models\ActivateAccountForm;
use common\models\LoginForm;
use common\models\ResetPasswordForm;
use common\models\ResetPasswordRequestForm;
use common\models\SignupForm;
use common\models\User;
use frontend\models\SearchForm;
use tws\helpers\Url;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

/**
 * Site controller
 */
class SiteController extends MainController
{
	public $successUrl = 'success';

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
						'actions' => ['login', 'reset-password', 'activate'],
						'allow' => true,
						'roles' => ['?'],
					],
					[
						'actions' => ['logout'],
						'allow' => true,
						'roles' => ['@'],
					],
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			]
		];
	}

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        return parent::beforeAction($action);
    }

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'error' => [
				'class' => 'yii\web\ErrorAction',
			],
		];
	}

	/**
	 * Displays homepage.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
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
			Yii::$app->session->setFlash('info', Yii::t('common', 'You are already authenticated.'));
			return $this->goHome();
		}

		$model = new LoginForm();

		if ($model->load(Yii::$app->request->post())) {
			if ($model->login()) {
				return $this->goBack();
			}
			Yii::$app->session->setFlash('error', Yii::t('common', 'The provided credentials are invalid.'));
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
		Yii::$app->user->logout();

		return $this->goHome();
	}

	/**
	 * Signs user up.
	 *
	 * @return mixed
	 */
	public function actionSignup()
	{
		if (!Yii::$app->user->isGuest) {
			Yii::$app->session->setFlash('info', Yii::t('common', 'You already have an account.'));
			return $this->goHome();
		}

		$model = new SignupForm();

		if ($model->load(Yii::$app->request->post()) && $model->signup()) {
			Yii::$app->trigger('user.afterSignup', new \yii\base\Event(['sender' => $model->getUser()]));
			if (Yii::$app->settings->get('userAccountActivation') == User::ACCOUNT_ACTIVATION_CONFIRMATION) {
				if ($model->sendActivationEmail()) {
					Yii::$app->session->setFlash('success', Yii::t('common', 'Please check your email to activate your account.'));
				} else {
					Yii::$app->session->setFlash('error', Yii::t('common', 'Cannot send the activation message.'));
				}
				return $this->redirect(['/site/index']);
			} else {
				Yii::$app->session->setFlash('success', Yii::t('common', 'Your account was successfully created.'));
				if (Yii::$app->getUser()->login($model->getUser(), (int) Yii::$app->settings->get('userLoginDuration'))) {
					return $this->goHome();
				}
				return $this->redirect(['/site/index']);
			}
		}

		return $this->render('signup', [
			'model' => $model,
		]);
	}

	/**
	 * Activates user account.
	 *
	 * @param string|int|null $token
	 * @return mixed
	 */
	public function actionActivate($token = null)
	{
		if (!Yii::$app->user->isGuest) {
			Yii::$app->session->setFlash('info', Yii::t('common', 'Your account is already active.'));
			return $this->goHome();
		}

		$model = new ActivateAccountForm();
		$model->token = $token;

		if (($model->load(Yii::$app->request->post()) || !empty($token)) && $model->validate()) {
			if ($model->activate()) {
				Yii::$app->trigger('user.afterAccountActivation', new \yii\base\Event(['sender' => $model->getUser()]));
				Yii::$app->session->setFlash('success', Yii::t('common', 'Your account has been activated.'));
				if (Yii::$app->getUser()->login($model->getUser(), (int) Yii::$app->settings->get('userLoginDuration'))) {
					return $this->redirect(['/account/default/index']);
				}
				return $this->redirect(['/site/login']);
			}
			Yii::$app->session->setFlash('error', Yii::t('common', 'Cannot activate the account due to invalid data provided.'));
		}

		return $this->render('activate', [
			'model' => $model,
		]);
	}

	/**
	 * Resets password.
	 *
	 * @param null|string $token
	 * @return mixed
	 */
	public function actionResetPassword($token = null)
	{
		if (!Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$resetPasswordRequestModel = new ResetPasswordRequestForm();
		$resetPasswordModel = new ResetPasswordForm();
		$resetPasswordModel->token = $token;
		$resetPasswordModel->setScenario(ResetPasswordForm::SCENARIO_TOKEN);

		if ($resetPasswordRequestModel->load(Yii::$app->request->post()) && $resetPasswordRequestModel->validate() && $resetPasswordRequestModel->sendRequest()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'You have requested a password reset. Please check the message that was sent to {0}.', [$resetPasswordRequestModel->username]));
			return $this->redirect(['reset-password']);
		} else {
			$resetPasswordModel->load(Yii::$app->request->post());
			// Switch to enter password scenario if the provided token is valid
			if (!empty($resetPasswordModel->token) && $resetPasswordModel->getUser()) {
				$resetPasswordModel->setScenario(ResetPasswordForm::SCENARIO_PASSWORD);
				if ($resetPasswordModel->token != $token) {
					return $this->redirect(['reset-password', 'token' => $resetPasswordModel->token]);
				}
			}
			if ($resetPasswordModel->load(Yii::$app->request->post()) && $resetPasswordModel->validate() && $resetPasswordModel->resetPassword()) {
				Yii::$app->session->setFlash('success', Yii::t('common', 'New password saved.'));
				if ($user = $resetPasswordModel->getUser()) {
					$user = is_array($user) ? reset($user) : $user;
					if (Yii::$app->getUser()->login($user, (int) Yii::$app->settings->get('userLoginDuration'))) {
						return $this->goHome();
					}
				}
			}
		}

		return $this->render('reset-password', [
			'resetPasswordRequestModel' => $resetPasswordRequestModel,
			'resetPasswordModel' => $resetPasswordModel,
		]);
	}

	/**
	 * Site search action.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{
		$model = new SearchForm();
		$model->search = Yii::$app->request->post('search');
		$model->backUrl = Yii::$app->request->referrer;
		if ($model->setCookies()) {
			return $this->redirect($model->backUrl);
		}
		return $this->redirect(Url::base(true) . '/' . (Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' :  mb_substr(Yii::$app->language, 0, 2)));
	}

	/**
	 * Displays any dynamic page.
	 *
	 * @return mixed
	 */
	public function actionPage()
	{
		return $this->render('page');
	}
}
