<?php

namespace frontend\controllers;

use common\models\ActivateAccountForm;
use common\models\Article;
use common\models\ArticleCategory;
use common\models\ArticleCategoryTranslation;
use common\models\ArticleTranslation;
use common\models\AssumeIdentityForm;
use common\models\AuthenticateByTokenForm;
use common\models\Contractor;
use common\models\Integration;
use common\models\Language;
use common\models\LoginForm;
use common\models\Package;
use common\models\Page;
use common\models\PageTranslation;
use common\models\PaymentMetadata;
use common\models\ResetPasswordForm;
use common\models\ResetPasswordRequestForm;
use common\models\Service;
use common\models\ServiceCategory;
use common\models\ServiceCategoryTranslation;
use common\models\ServiceTranslation;
use common\models\SignupForm;
use common\models\SubscribeForm;
use common\models\Subscription;
use common\models\Testimonial;
use common\models\TwoFactorAuthenticationForm;
use common\models\User;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\overlays\InfoWindow;
use dosamigos\google\maps\overlays\Marker;
use frontend\models\AcceptCookiesForm;
use frontend\models\CheckForm;
use frontend\models\ContractorForm;
use frontend\models\QrCodeForm;
use frontend\models\SpvForm;
use frontend\models\TestimonialForm;
use frontend\modules\account\models\PaymentResult;
use tws\helpers\StringHelper;
use tws\helpers\Url;
use tws\widgets\sitemap\Sitemap;
use Yii;
use frontend\models\ContactForm;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\web\Response;
use Zelenin\Feed;
use Zelenin\yii\extensions\Rss\RssView;

/**
 * Site controller
 */
class SiteController extends MainController
{
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'only' => ['logout'],
				'rules' => [
					[
						'actions' => ['logout'],
						'allow' => true,
						'roles' => ['@'],
					],
					[
						'actions' => ['qr'],
						'allow' => true,
						'roles' => ['@'],
					],
					[
						'actions' => ['spv'],
						'allow' => true,
						'roles' => ['@', '?'],
					],
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
			],
		];
	}

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        if ($action->id === 'ipn') {
            if (Yii::$app->request->isPost) {
                $this->enableCsrfValidation = false;
            }
        }
        return parent::beforeAction($action);
    }

	/**
	 * Displays homepage.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
		$this->layout = 'home';

		return $this->render('index');
	}

	/**
	 * Displays about us page.
	 *
	 * @return mixed
	 */
	public function actionAboutUs()
	{
		return $this->render('about-us');
	}

	/**
	 * Displays contact page.
	 *
	 * @return mixed
	 */
	public function actionContact()
	{
		$model = new ContactForm();

		if (!Yii::$app->user->isGuest) {
			$user = Yii::$app->user->identity;
			$model->name = $user->getFullName();
			$model->email = $user->email;
			$model->phone = $user->phone;
		}
		if (Yii::$app->request->get('request') == 'enterprise') {
			$model->subject = Yii::t('frontend', 'Request for Enterprise package');
			$model->message = Yii::t('frontend', "Hello,\n\nI am interested in subscribing to an Enterprise package.\nMy requirements are: 5 workspaces and 10 users.\n\nThank You.");
		}

		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->sendEmail()) {
				Yii::$app->session->setFlash('success', Yii::t('frontend', 'Thank you for contacting us. We will respond to you as soon as possible.'));
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'There was an error sending your message.'));
			}
			return $this->redirect(['contact']);
		}

		$contact = Yii::$app->settings->getCategory('contact');
		$address = implode(', ', array_filter([
			$contact['streetName'],
			$contact['streetNumber'],
			$contact['locality'],
			$contact['zipCode'],
			$contact['county'],
			$contact['country'] ? \common\models\Country::findAllCountries()[$contact['country']]->translation->name : null,
		]));
		if ($contact['latitude'] && $contact['longitude']) {
			$latLng = new LatLng([
				'lat' => $contact['latitude'],
				'lng' => $contact['longitude'],
			]);
			$map = new Map([
				'center' => $latLng,
				'zoom' => 17,
				'width' => '100%',
				'height' => 400,
				'mapTypeControl' => false,
			]);
			$marker = new Marker([
				'position' => $latLng,
				'title' => Yii::$app->name,
//			'icon' => Yii::getAlias('@web/img/map-marker.png'),
			]);
			if ($address) {
				$marker->attachInfoWindow(
					new InfoWindow([
						'content' => $address,
					])
				);
			}
			$map->addOverlay($marker);
		}

		return $this->render('contact', [
			'model' => $model,
			'contact' => $contact,
			'address' => $address,
			'map' => $map,
		]);
	}

	/**
	 * Displays terms and conditions page.
	 *
	 * @return mixed
	 */
	public function actionTermsAndConditions()
	{
		return $this->render('terms-and-conditions');
	}

	/**
	 * Displays privacy policy page.
	 *
	 * @return mixed
	 */
	public function actionPrivacyPolicy()
	{
		return $this->render('privacy-policy');
	}

    /**
     * Displays cookie policy page.
     *
     * @return mixed
     */
    public function actionCookiePolicy()
    {
        return $this->render('cookie-policy');
    }

	/**
	 * Displays pricing page.
	 *
	 * @return mixed
	 */
	public function actionPricing()
	{
		return $this->render('pricing');
	}

	/**
	 * DisplaysFAQ page.
	 *
	 * @return mixed
	 */
	public function actionFaq()
	{
		return $this->render('faq');
	}

	/**
	 * Displays check page.
	 *
	 * @return mixed
	 */
	public function actionCheck()
	{
		$dataset = [];
		$model = new CheckForm();

		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			$dataset = $model->search();
		}

		return $this->render('check', [
			'model' => $model,
			'dataset' => $dataset,
		]);
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
				return $this->goHome();
			}
			Yii::$app->session->setFlash('error', Yii::t('common', 'The provided credentials are invalid.'));
		}

		return $this->render('login', [
			'model' => $model,
		]);
	}

	/**
	 * Login as user.
	 *
	 * @return mixed
	 */
	public function actionAssumeIdentity($id)
	{
		if (!Yii::$app->user->isGuest || empty($id)) {
			return $this->goHome();
		}

		$model = new AssumeIdentityForm();
		$model->id = Yii::$app->security->unmaskToken($id);

		if ($model->login()) {
			return $this->goBack(['/site/index']);
		} else {
			$message = Yii::t('common', 'The provided credentials are invalid.');
			if ($user = User::findOne(['id' => Yii::$app->security->unmaskToken($id), 'deleted' => User::NO])) {
				if ($user->status == User::STATUS_INACTIVE && Yii::$app->settings->get('userAccountActivation') == User::ACCOUNT_ACTIVATION_AUTOMATIC) {
					$message = Yii::t('common', 'Your account is inactive. Please make sure you have activated your account by clicking the link in the activation email you received.');
				}
			}
			Yii::$app->session->setFlash('error', $message);
		}

		return $this->render('assume-identity', [
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
		$model->package_id = Yii::$app->request->get('package_id')? Yii::$app->security->unmaskToken(Yii::$app->request->get('package_id')) : Package::findDefaultPackage()->id;

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
					if ($model->getUser()->getIsSubscriber() && $model->getUser()->workspaces[0]) {
						return $this->redirect($model->getUser()->workspaces[0]->getAbsoluteUrl());
					}
					return $this->redirect(['/site/index']);
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
	 * Confirm Login.
	 *
	 * @param null|string $token
	 * @return mixed
	 */
	public function actionConfirmLogin($token = null)
	{
		if (Yii::$app->user->isGuest) {
			return $this->goHome();
		}

		$twoFactorAuthenticationModel = new TwoFactorAuthenticationform();
		$twoFactorAuthenticationModel->token = $token;

		if ($twoFactorAuthenticationModel->load(Yii::$app->request->post()) && $twoFactorAuthenticationModel->validate() && $twoFactorAuthenticationModel->confirmLogin()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Login confirmed.'));
			return $this->goBack();
		}

		return $this->render('confirm-login', [
			'twoFactorAuthenticationModel' => $twoFactorAuthenticationModel,
		]);
	}

	/**
	 * Subscribes a user to the system.
	 *
	 * @return mixed
	 */
	public function actionSubscribe()
	{
		if (Yii::$app->user->isGuest) {
			return $this->redirect(['/site/login']);
		}
		/** @var \common\models\User $user */
		$user = Yii::$app->user->identity;
		if ($user->getSubscriber()->active()->deleted(false)->exists()) {
			Yii::$app->session->setFlash('info', Yii::t('common', 'You are already a subscriber.'));
			return $this->redirect(['/account/subscription/index']);
		}

		$model = new SubscribeForm();
		$model->package_id = Yii::$app->security->unmaskToken(Yii::$app->request->get('package_id'));

		if ($model->load(Yii::$app->request->post()) && $model->subscribe()) {
			Yii::$app->session->setFlash('success', Yii::t('common', 'Congratulations, you are now a subscriber.'));
			return $this->redirect(['/account/profile/index']);
		}

		return $this->render('subscribe', [
			'model' => $model,
		]);
	}

	/**
	 * Displays testimonial page.
	 *
	 * @return mixed
	 */
	public function actionTestimonial()
	{
		$model = new TestimonialForm();
		if (!Yii::$app->user->isGuest && ($user = Yii::$app->user->identity)) {
			$model->name = $user->fullName;
			$model->phone = $user->phone;
		}

		if ($model->load(Yii::$app->request->post()) && $model->validate()) {
			if ($model->save()) {
				Yii::$app->session->setFlash('success', Yii::t('frontend', 'Thank you for your feedback.'));
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'There was an error sending your message.'));
			}
			return $this->refresh();
		}

		$dataProvider = new ActiveDataProvider([
			'query' => Testimonial::provideTestimonials(),
			'pagination' => [
				'defaultPageSize' => 5,
			],
		]);

		return $this->render('testimonial', [
			'model' => $model,
			'dataProvider' => $dataProvider,
		]);
	}

    /**
     * Site accept cookies action.
     *
     * @return mixed
     */
    public function actionAcceptCookies()
    {
        $model = new AcceptCookiesForm();
        $model->acceptCookies = Yii::$app->request->post('acceptCookies');
        $model->backUrl = Yii::$app->request->post('backUrl');
        if ($model->setCookies()) {
            return $this->redirect($model->backUrl);
        }
        return $this->redirect(Url::base(true) . '/' . (Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' :  mb_substr(Yii::$app->language, 0, 2)));
    }

	/**
	 * Displays rss page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionRss()
	{
		$response = Yii::$app->getResponse();
		$response->format = Response::FORMAT_RAW;

		$headers = $response->getHeaders();
		$headers->set('Content-Type', 'application/rss+xml; charset=utf-8');

		$dataProvider = new ActiveDataProvider([
			'query' => Article::provideRssArticles(),
			'pagination' => [
				'pageSize' => 10,
			],
		]);

		return RssView::widget([
			'dataProvider' => $dataProvider,
			'channel' => [
				'title' => function ($widget, Feed $feed) {
					$feed->addChannelTitle(Yii::$app->name);
				},
				'link' => Url::to(['/site/index'], true),
				'description' => Yii::t('common', 'Articles'),
				'language' => function ($widget, Feed $feed) {
					return Yii::$app->language;
				},
				'image'=> function ($widget, Feed $feed) {
					if (!($logoUrl = Url::to(Yii::$app->settings->get('appLogo')))) {
						$logoUrl = Url::to('@web/img/logo.png', true);
					}
					$feed->addChannelImage($logoUrl, Url::home(true), 144, 36, Yii::$app->name);
				},
			],
			'items' => [
				'title' => function (Article $model, $widget, Feed $feed) {
					return $model->translation->title;
				},
				'description' => function (Article $model, $widget, Feed $feed) {
					return StringHelper::truncateWords($model->translation->description, 50);
				},
				'link' => function (Article $model, $widget, Feed $feed) {
					return Url::to(['/article/default/view', 'slug' => $model->translation->slug], true);
				},
				'guid' => function (Article $model, $widget, Feed $feed) {
					return Url::to(['/article/default/view', 'slug' => $model->translation->slug], true);
				},
				'pubDate' => function (Article $model, $widget, Feed $feed) {
					return (new \DateTime($model->created_at))->format(DATE_RSS);
				},
			],
		]);
	}

    /**
     * IPN.
     *
     * @return mixed
     */
    public function actionIpn()
    {
	    if (!Yii::$app->request->isPost) {
		    return $this->redirect(['index']);
	    }

	    $paymentSettings = Yii::$app->settings->getCategory('payment');
	    $paymentProcessors = (array) $paymentSettings['paymentProcessors'][PaymentMetadata::PAYMENT_METHOD_CARD];
	    $attributes = [];
	    $model = new PaymentResult();

	    $params = Yii::$app->request->post();
	    if (!array_key_exists(PaymentMetadata::PAYMENT_METHOD_CARD, $paymentProcessors) || empty($params)) {
		    $model->addError('', Yii::t('frontend', 'Payment response is not valid.'));
	    }

		// This is your Stripe CLI webhook secret for testing your endpoint locally.
	    $endpoint_secret = $paymentSettings['stripeWebhookIPNKey'];

	    $payload = @file_get_contents('php://input');
	    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
	    $event = null;

	    try {
		    $event = \Stripe\Webhook::constructEvent(
			    $payload, $sig_header, $endpoint_secret
		    );
	    } catch (\UnexpectedValueException $e) {
		    // Invalid payload
		    http_response_code(400);
		    exit();
	    } catch (\Stripe\Exception\SignatureVerificationException $e) {
		    // Invalid signature
		    http_response_code(400);
		    exit();
	    }

		// Handle the event
	    switch ($event->type) {
		    case 'checkout.session.async_payment_failed':
		    case 'checkout.session.expired':
			    $model->addError('', Yii::t('frontend', 'Payment failed.'));
				break;
		    case 'checkout.session.async_payment_succeeded':
		    case 'checkout.session.completed':
			    $attributes = [];
			    $session = $event->data->object;
			    $payment = Yii::$app->payment->via(PaymentMetadata::PAYMENT_PROCESSOR_STRIPE);
			    $stripe = new \Stripe\StripeClient(
				    $payment->privateKey
			    );
			    if (!empty($session) && $session['payment_status'] == 'paid') {
				    $attributes = [
					    'subscription_id' => $session['metadata']['subscription_id'],
					    'payer_id' => $session['customer'],
				    ];

				    if (!empty($session['invoice'])) {
					    $invoice = $stripe->invoices->retrieve(
						    $session['invoice'],
						    []
					    );
					    if (!empty($invoice) && !empty($invoice['payment_intent'])) {
						    $paymentIntent = $stripe->paymentIntents->retrieve(
							    $invoice['payment_intent'],
							    []
						    );
						    if ($paymentIntent) {
							    $charge = $paymentIntent['charges']['data'][0]['id'];
							    if ($charge) {
								    $attributes['payment_id'] = $charge;
							    }
						    }
					    }
				    }

				    if ($subscription = Subscription::findOne(['id' => $session['metadata']['subscription_id']])) {
						if (empty($subscription->paymentMetadata)) {
							$paymentMetadata = new PaymentMetadata();
							$paymentMetadata->subscription_id = $subscription->id;
							$paymentMetadata->payment_processor = PaymentMetadata::PAYMENT_PROCESSOR_STRIPE;
							$paymentMetadata->payment_method = PaymentMetadata::PAYMENT_METHOD_CARD;
							$paymentMetadata->recurring_payment = PaymentMetadata::YES;
							$paymentMetadata->status = PaymentMetadata::STATUS_ACTIVE;
							$paymentMetadata->save(false);
						}
					}

				    $model->load($attributes, '');
				    $model->save();
				    if ($model->subscription_id && $subscription = Subscription::findOne(['id' => $model->subscription_id, 'status' => [Subscription::STATUS_INACTIVE, Subscription::STATUS_SUSPENDED, Subscription::STATUS_INCOMPLETE]])) {
					    $subscription->status = Subscription::STATUS_ACTIVE;
					    $subscription->save(false);
				    }
			    }
		        break;
		    default: break;
	    }
	    http_response_code(200);
    }

	/**
	 * Displays sitemap page.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function actionSitemap()
	{
		$response = Yii::$app->getResponse();
		$response->format = Response::FORMAT_RAW;

		$headers = $response->getHeaders();
		$headers->set('Content-Type', 'application/xml; charset=utf-8');

		$servicesPage = Page::findPageByRoute(['/service/default/index']);
		/** @var \common\models\PageTranslation[] $servicesPageTranslations */
		$servicesPageTranslations = $servicesPage->getPageTranslations()
            ->where([
                'language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id')
            ])
            ->indexBy('language_id')
            ->all();

		$articlesPage = Page::findPageByRoute(['/article/default/index']);
		/** @var \common\models\PageTranslation[] $servicesPageTranslations */
		$articlesPageTranslations = $articlesPage->getPageTranslations()
            ->where([
                'language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id')
            ])
            ->indexBy('language_id')
            ->all();

        $pagesDataProvider = new ActiveDataProvider([
            'query' => Page::find()
                ->alias('p')
                ->select([
                    'p.*',
                ])
                ->joinWith([
                    'pageTranslations pt' => function (ActiveQuery $query) {
                        return $query->andOnCondition([
                            'pt.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                            'pt.deleted' => PageTranslation::NO,
                        ]);
                    },
                ])
                ->andWhere([
                    'OR',
                    ['IS', 'p.module', null],
                    ['!=', 'p.module', 'account'],
                ])
                ->andWhere(['!=', 'p.action', 'subscribe'])
                ->andWhere([
                    'p.status' => Page::STATUS_ACTIVE,
                    'p.deleted' => Page::NO,
                    'pt.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                ]),
            'pagination' => [
                'pageSize' => 0,
            ],
        ]);

        $serviceCategoriesDataProvider = new ActiveDataProvider([
            'query' => ServiceCategory::find()
                ->alias('sc')
                ->select([
                    'sc.*',
                ])
                ->joinWith([
                    'serviceCategoryTranslations sct' => function (ActiveQuery $query) {
                        return $query->andOnCondition([
                            'sct.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                            'sct.deleted' => ServiceCategoryTranslation::NO,
                        ]);
                    },
                ])
                ->where([
                    'sc.status' => ServiceCategory::STATUS_ACTIVE,
                    'sc.deleted' => ServiceCategory::NO,
                    'sct.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                ]),
            'pagination' => [
                'pageSize' => 0,
            ],
        ]);

        $servicesDataProvider = new ActiveDataProvider([
            'query' => Service::find()
                ->alias('s')
                ->select([
                    's.*',
                ])
                ->joinWith([
                    'serviceTranslations st' => function (ActiveQuery $query) {
                        return $query->andOnCondition([
                            'st.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                            'st.deleted' => ServiceTranslation::NO,
                        ]);
                    },
                ])
                ->where([
                    's.status' => Service::STATUS_ACTIVE,
                    's.deleted' => Service::NO,
                    'st.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                ]),
            'pagination' => [
                'pageSize' => 0,
            ],
        ]);

        $articleCategoriesDataProvider = new ActiveDataProvider([
            'query' => ArticleCategory::find()
                ->alias('ac')
                ->select([
                    'ac.*',
                ])
                ->joinWith([
                    'articleCategoryTranslations act' => function (ActiveQuery $query) {
                        return $query->andOnCondition([
                            'act.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                            'act.deleted' => ArticleCategoryTranslation::NO,
                        ]);
                    },
                ])
                ->where([
                    'ac.status' => ArticleCategory::STATUS_ACTIVE,
                    'ac.deleted' => ArticleCategory::NO,
                    'act.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                ]),
            'pagination' => [
                'pageSize' => 0,
            ],
        ]);

        $articlesDataProvider = new ActiveDataProvider([
            'query' => Article::find()
                ->alias('a')
                ->select([
                    'a.*',
                ])
                ->joinWith([
                    'articleTranslations at' => function (ActiveQuery $query) {
                        return $query->andOnCondition([
                            'at.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                            'at.deleted' => ArticleTranslation::NO,
                        ]);
                    },
                ])
                ->where([
                    'a.status' => Article::STATUS_ACTIVE,
                    'a.deleted' => Article::NO,
                    'at.language_id' => ArrayHelper::getColumn(Language::findAllLanguages(), 'language_id'),
                ]),
            'pagination' => [
                'pageSize' => 0,
            ],
        ]);

		return Sitemap::widget([
			'items' => [
				[
					'dataProvider' => $pagesDataProvider,
					'i18n' => [
						'relation' => 'pageTranslations',
						'route' => function ($item, $model, $modelTranslation) {
							return [$modelTranslation->slug];
						},
					],
					'options' => [
						'changefreq' => Sitemap::CHANGEFREQ_YEARLY,
						'priority' => '1.0',
					],
				],
				[
					'dataProvider' => $serviceCategoriesDataProvider,
					'i18n' => [
						'relation' => 'serviceCategoryTranslations',
						'route' => function ($item, $model, $modelTranslation) use ($servicesPage, $servicesPageTranslations) {
							return [
								implode('/', [
									$servicesPageTranslations[$modelTranslation->language_id]->slug,
									Inflector::slug(Yii::t($servicesPage->module, 'Category', [], $modelTranslation->language_id)),
									$modelTranslation->slug
								]),
							];
						},
					],
					'options' => [
						'changefreq' => Sitemap::CHANGEFREQ_WEEKLY,
						'priority' => '1.0',
					],
				],
				[
					'dataProvider' => $servicesDataProvider,
					'i18n' => [
						'relation' => 'serviceTranslations',
						'route' => function ($item, $model, $modelTranslation) use ($servicesPage, $servicesPageTranslations) {
							return [
								implode('/', [
									$servicesPageTranslations[$modelTranslation->language_id]->slug,
									$modelTranslation->slug
								]),
							];
						},
					],
					'options' => [
						'changefreq' => Sitemap::CHANGEFREQ_YEARLY,
						'priority' => '1.0',
					],
				],
				[
					'dataProvider' => $articleCategoriesDataProvider,
					'i18n' => [
						'relation' => 'articleCategoryTranslations',
						'route' => function ($item, $model, $modelTranslation) use ($articlesPage, $articlesPageTranslations) {
							return [
								implode('/', [
									$articlesPageTranslations[$modelTranslation->language_id]->slug,
									Inflector::slug(Yii::t($articlesPage->module, 'Category', [], $modelTranslation->language_id)),
									$modelTranslation->slug
								]),
							];
						},
					],
					'options' => [
						'changefreq' => Sitemap::CHANGEFREQ_WEEKLY,
						'priority' => '1.0',
					],
				],
				[
					'dataProvider' => $articlesDataProvider,
					'i18n' => [
						'relation' => 'articleTranslations',
						'route' => function ($item, $model, $modelTranslation) use ($articlesPage, $articlesPageTranslations) {
							return [
								implode('/', [
									$articlesPageTranslations[$modelTranslation->language_id]->slug,
									$modelTranslation->slug
								]),
							];
						},
					],
					'options' => [
						'changefreq' => Sitemap::CHANGEFREQ_WEEKLY,
						'priority' => '1.0',
						'lastmod' => 'updated_at',
					],
				],
			],
		]);
	}

	/**
	 * Site search action.
	 *
	 * @return mixed
	 */
	public function actionSearch()
	{
		$dataset = [];
		if (!($criteria = Yii::$app->request->post('county', null)) && !($criteria = Yii::$app->request->post('locality', null))) {
			return $this->asJson($dataset);
		}

		if ($criteria = Yii::$app->request->post('county', null)) {
			$params = [
				'country_code' => Yii::$app->request->post('country_code', null),
			];
			$counties = \common\models\County::findByCriteria($criteria, $params);
			foreach ($counties as $county) {
				$dataset[] = [
					'label' => $county->name,
				];
			}
		}

		if ($criteria = Yii::$app->request->post('locality', null)) {
			$params = [
				'county' => Yii::$app->request->post('county', null),
			];
			$localities = \common\models\Locality::findByCriteria($criteria, $params);
			foreach ($localities as $locality) {
				$dataset[] = [
					'label' => $locality->name,
				];
			}
		}

		return $this->asJson([
			'results' => $dataset,
		]);
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

	/**
	 *
	 * @return mixed
	 */
	public function actionSpv()
	{
		if ((!Yii::$app->user->isGuest && Yii::$app->user->identity->authAssignment->item_name !== 'superAdmin') || (Yii::$app->user->isGuest && empty(Yii::$app->request->get('authorize')) && empty(Yii::$app->request->get('code')))) {
			return $this->goHome();
		}

		$model = new SPVForm();
		$currentDate = date('Y-m-d H:i:s');
		$integration = Integration::find()
			->where([
				'type' => Integration::TYPE_SPV,
				'status' => Integration::STATUS_ACTIVE,
				'deleted' => Integration::NO,
			])
			->andWhere(new Expression("TIMESTAMPDIFF(SECOND, '{$currentDate}', [[expire_at]]) > 0"))
			->one();

		if (empty($integration) && !Yii::$app->user->isGuest) {
			if (!Yii::$app->getRequest()->getCookies()->has('spv')) {
				$model->spv = $model->generateShortUrlToken([Yii::$app->params['spv.clientId'], Yii::$app->params['spv.clientSecret']]);
				if ($model->setCookies()) {
					return $this->redirect(Yii::$app->params['spv.redirect']);
				}
			} else {
				$anchor = Yii::t('frontend', 'Authorize');
				$link = implode('?', [
					Yii::$app->params['spv.redirect'],
					http_build_query([
						"authorize" => Yii::$app->getRequest()->cookies->getValue('spv'),
					]),
				]);
			}
		}

		if (!empty(Yii::$app->request->get('authorize'))) {
			$token = Yii::$app->request->get('authorize');
			if ($model->verifyShortUrlToken($token, [Yii::$app->params['spv.clientId'], Yii::$app->params['spv.clientSecret']])) {
				$url = implode('?', [
					'https://logincert.anaf.ro/anaf-oauth2/v1/authorize',
					http_build_query([
						"response_type" => "code",
						"client_id" => Yii::$app->params['spv.clientId'],
						"redirect_uri" => Yii::$app->params['spv.redirect'],
						"token_content_type" => "jwt",
					]),
				]);
				return $this->redirect($url);
			}
		}

		if (!empty(Yii::$app->request->get('code'))) {
			$model = new SPVForm();
			$model->spv = Yii::$app->request->get('code');
			if (empty($integration)) {
				if (!$model->saveModel()) {
					if (!empty($model->errors)) {
						$errors = array_values($model->errors)[0];
						$message = implode('<br>', $errors);
						Yii::$app->session->setFlash('error', $message);
					}
				} else {
					Yii::$app->session->setFlash('success', Yii::t('frontend', 'The authorization of account access in S.P.V. has been successfully completed.'));
				}
			}
			return $this->goHome();
		}

		return $this->render('spv', [
			'integration' => $integration,
			'anchor' => $anchor,
			'link' => $link,
		]);
	}
}