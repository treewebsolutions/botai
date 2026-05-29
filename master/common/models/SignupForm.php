<?php

namespace common\models;

use Yii;
use yii\base\Model;
use tws\helpers\Url;

/**
 * Signup form
 */
class SignupForm extends Model
{
    /**
     * @var string The email address.
     */
    public $email;

    /**
     * @var string The phone number.
     */
    public $phone;

    /**
     * @var string The password.
     */
    public $password;

    /**
     * @var string The first name.
     */
    public $first_name;

    /**
     * @var string The middle name.
     */
    public $middle_name;

    /**
     * @var string The last name.
     */
    public $last_name;

    /**
     * @var int The Package model ID.
     */
    public $package_id;

    /**
     * @var string The custom Package model requirements.
     */
    public $custom_package_requirements;

    /**
     * @var bool Accept TOS flag.
     */
    public $acceptTerms;

    /**
     * @var string The honeypot field.
     */
    public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;

    /**
     * @var int The Company Taxpayer Identification Number.
     * FIELD TO MATCH COMPANY TARGET ATTRIBUTE
     */
    public $tin;

    /**
     * @var int The Company Registration number.
     * FIELD TO MATCH COMPANY TARGET ATTRIBUTE
     */
    public $registration_number;

    /**
     * @var int The Company name.
     */
    public $company_name;

    /**
     * @var int The Company mail.
     */
    public $company_email;

    /**
     * @var int The Company phone.
     */
    public $company_phone;

    /**
     * @var int The Company fax.
     */
    public $company_fax;

	/**
	 * @var int The Company address.
	 */
	public $company_address;

    /**
     * @var int The Company street name.
     */
    public $company_street_name;

    /**
     * @var int The Company street number.
     */
    public $company_street_number;

    /**
     * @var int The Company street number.
     */
    public $company_locality;

    /**
     * @var int The Company zip_code.
     */
    public $company_zip_code;

    /**
     * @var int The Company county.
     */
    public $company_county;

    /**
     * @var int The Company country.
     */
    public $company_country;

    /**
     * @var User The User model.
     */
    private $_user;

    /**
     * @var Subscriber The Subscriber model.
     */
    private $_subscriber;

    /**
     * @var Package The Package model.
     */
    private $_package;

    /**
     * @var Subscription The Subscription model.
     */
    private $_subscription;

    /**
     * @var Company The Company model.
     */
    private $_company;

    /**
     * @var Workspace The Workspace model.
     */
    private $_workspace;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
		$this->company_country = 'DE';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['first_name', 'last_name', 'email', 'phone', 'password', 'package_id', 'acceptTerms', 'company_name', 'company_email', 'company_phone', 'company_address', 'company_locality', 'company_country'], 'required'],
            ['custom_package_requirements', 'required', 'when' => function () {
                return $this->getPackage()->type == Package::TYPE_CUSTOM;
            }, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[package_id]\"]:checked").attr("data-custom") == "true";
			}'],
            [['first_name', 'middle_name', 'last_name', 'email', 'phone', 'registration_number', 'tin', 'company_name', 'company_email', 'company_phone', 'company_address', 'company_locality', 'company_country'], 'string', 'max' => 255],
            [['first_name', 'middle_name', 'last_name', 'email', 'phone'], 'trim'],
            ['password', 'string', 'min' => 6],
            [['email'], 'unique', 'targetClass' => 'common\models\User'],
            ['acceptTerms', 'boolean'],
            [['tin', 'registration_number'], 'unique', 'targetClass' => 'common\models\Company'],
            ['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
            [['package_id'], 'exist', 'skipOnError' => true, 'targetClass' => Package::class, 'targetAttribute' => ['package_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'first_name' => Yii::t('label', 'First Name'),
            'middle_name' => Yii::t('label', 'Middle Name'),
            'last_name' => Yii::t('label', 'Last Name'),
            'email' => Yii::t('label', 'Email'),
            'phone' => Yii::t('label', 'Phone'),
            'password' => Yii::t('label', 'Password'),
            'package_id' => Yii::t('common', 'Package'),
            'custom_package_requirements' => Yii::t('common', 'What are your requirements?'),
            'acceptTerms' => Yii::t('common', 'Terms and Conditions'),
            'registration_number' => Yii::t('label', 'Registration Number'),
            'tin' => Yii::t('label', 'TIN'),
            'company_name' => Yii::t('label', 'Name'),
            'company_address' => Yii::t('label', 'Address'),
            'company_locality' => Yii::t('label', 'Locality'),
            'company_country' => Yii::t('label', 'Country'),
            'company_phone' => Yii::t('label', 'Phone'),
            'company_email' => Yii::t('label', 'Email'),
        ];
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
     * Gets the Subscriber model.
     *
     * @return Subscriber
     */
    public function getSubscriber()
    {
        return $this->_subscriber;
    }

    /**
     * Gets the Package model.
     *
     * @return Package
     */
    public function getPackage()
    {
        if (!$this->_package) {
            $this->_package = Package::findOne([
                'id' => $this->package_id,
                'status' => Package::STATUS_ACTIVE,
                'deleted' => Package::NO,
            ]);
        }
        return $this->_package;
    }

    /**
     * Gets the Subscription model.
     *
     * @return Subscription
     */
    public function getSubscription()
    {
        return $this->_subscription;
    }

    /**
     * Gets the Company model.
     *
     * @return Company
     */
    public function getCompany()
    {
        return $this->_company;
    }

    /**
     * Gets the Company model.
     *
     * @return Workspace
     */
    public function getWorkspace()
    {
        return $this->_workspace;
    }

    /**
     * Saves the Subscriber model.
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveSubscriber()
    {
        try {
            $user = $this->getUser();

            $subscriber = new Subscriber();
            $subscriber->user_id = $user->id;
            $subscriber->code = Subscriber::generateUniqueCode();
            $subscriber->setSerializedValue('data', [
                'package_id' => $this->package_id,
            ]);
            $subscriber->status = Subscriber::STATUS_INACTIVE;
            if (!$subscriber->save()) {
                throw new \Exception($subscriber->getErrorSummary(false)[0]);
            }

            $this->_subscriber = $subscriber;

            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }

    protected function saveCompany()
    {
        try {
            $user = $this->getUser();

            $company = new Company();
            $company->detachBehavior('BlameableBehavior');
            $company->user_id = $user->id;
            $company->name = $this->company_name;
            $company->address = $this->company_address;
            $company->locality = $this->company_locality;
            $company->country = $this->company_country;
            $company->phone = $this->company_phone;
            $company->email = $this->company_email;
            $company->created_by = $user->id;
            $company->updated_by = $user->id;
            $company->status = Company::STATUS_ACTIVE;
            if (!$company->save()) {
                throw new \Exception($company->getErrorSummary(false)[0]);
            }

            $this->_company = $company;

            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }

    /**
     * Saves the Subscription model.
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveSubscription()
    {
        try {
            $subscriber = $this->getSubscriber();
            $package = $this->getPackage();

            $subscription = new Subscription();
            $subscription->subscriber_id = $subscriber->id;
            $subscription->package_id = $package->id;
            $subscription->code = Subscription::generateUniqueCode();
            $subscription->trial_period = $package->trial_period;
            $subscription->trial_cycle = $package->trial_cycle;
            $subscription->billing_period = $package->billing_period;
            $subscription->billing_cycle = $package->billing_cycle;
            $subscription->price = $package->price;
            $subscription->currency = $package->currency;
            $subscription->type = $package->type;
            $subscription->status = Subscription::STATUS_INACTIVE;
            if (!$subscription->save()) {
                throw new \Exception($subscription->getErrorSummary(false)[0]);
            }
            if (Yii::$app->settings->get('userAccountActivation') == User::ACCOUNT_ACTIVATION_AUTOMATIC) {
                if (!$subscription->activate('now', true)) {
                    throw new \Exception($subscription->getErrorSummary(false)[0]);
                }
            }

            $columns = ['subscription_id', 'feature_id', 'name', 'value', 'price', 'renewable', 'type'];
            $rows = [];
            foreach ($package->packageFeatures as $packageFeature) {
                $rows[] = [
                    'subscription_id' => $subscription->id,
                    'feature_id' => $packageFeature->feature_id,
                    'name' => $packageFeature->name,
                    'value' => $packageFeature->value,
                    'price' => $packageFeature->price,
                    'renewable' => $packageFeature->renewable,
                    'type' => SubscriptionFeature::TYPE_PACKAGE_FEATURE,
                ];
            }
            if (!Yii::$app->db->createCommand()->batchInsert(SubscriptionFeature::tableName(), $columns, $rows)->execute()) {
                throw new \Exception('SubscriptionFeature cannot be saved.');
            }

            $this->_subscription = $subscription;

            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }

    protected function saveWorkspace()
    {

        try {
            $user = $this->getUser();
            $subscription = $this->getSubscription();

            $lastWorkspace = Workspace::find()
                ->select([
                    "MAX(CAST(REPLACE(url, 'scp', '') AS UNSIGNED)) + 1 AS url",
                ])
                ->where([
                    'AND',
                    ['=', 'deleted', 0],
                    ['LIKE', 'url', 'scp'],
                ])
                ->limit(1)
                ->one();

            $workspace = new Workspace();
            $workspace->detachBehavior('BlameableBehavior');
            $workspace->subscription_id = $subscription->id;
            $workspace->code = Workspace::generateUniqueCode();
            $workspace->url = 'scp' . $lastWorkspace->url ?: 1;
            $workspace->created_by = $user->id;
            $workspace->updated_by = $user->id;
            $workspace->type = Workspace::TYPE_SUBSCRIBER;
            $workspace->status = Workspace::STATUS_ACTIVE;
            if (!$workspace->save()) {
                throw new \Exception($workspace->getErrorSummary(false)[0]);
            }

            $this->_workspace = $workspace;

            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
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
            $user = $this->getUser();

            $workspaceHasUser = WorkspaceHasUser::findOne([
                'workspace_id' => $this->_workspace->id,
                'user_id' => $user->id,
            ]);
            if (!$workspaceHasUser) {
                $workspaceHasUser = new WorkspaceHasUser();
            }
            $workspaceHasUser->setAttributes($user->getAttributes());
            $workspaceHasUser->workspace_id = $this->_workspace->id;
            $workspaceHasUser->user_id = $user->id;
            $workspaceHasUser->deleted = Workspace::NO;
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
     * Saves the WorkspaceHasSubscriptionFeature models.
     *
     * @return bool
     */
    public function saveWorkspaceHasSubscriptionFeatures()
    {
        try {
            /** @var SubscriptionFeature[] $subscriptionFeatures */
            $subscriptionFeatures = array_diff_key(
                $this->_subscription->getSubscriptionFeatures()->indexBy('id')->all(),
                $this->_workspace->getWorkspaceHasSubscriptionFeatures()->indexBy('subscription_feature_id')->all()
            );

            if (!empty($subscriptionFeatures)) {
                $attributes = ['workspace_id', 'subscription_feature_id'];
                $rows = [];
                foreach ($subscriptionFeatures as $subscriptionFeature) {
                    $rows[] = [
                        'workspace_id' => $this->_workspace->id,
                        'subscription_feature_id' => $subscriptionFeature->id,
                    ];
                }
                if (!Yii::$app->db->createCommand()->batchInsert(WorkspaceHasSubscriptionFeature::tableName(), $attributes, $rows)->execute()) {
                    throw new \Exception();
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function saveWorkspaceCompany()
    {
        try {
            $company = $this->_company;
            $user = $this->getUser();

            // Get the database instance
            $workspaceDb = $this->_workspace->getWorkspaceDb();

            $workspaceDb->createCommand()->insert('{{%company}}', [
                'tin' => $this->tin,
                'registration_number' => $this->registration_number,
                'name' => $this->company_name,
                'email' => $this->company_email,
                'phone' => $this->company_phone,
                'country' => $this->company_country,
                'address' => $this->_company->fullAddress,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'status' => Company::STATUS_ACTIVE,
            ])->execute();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sends account activation email.
     *
     * @return bool
     */
    public function sendActivationEmail()
    {
        $user = $this->getUser();
	    $template = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_ACCOUNT_ACTIVATION);
	    if (!$template) {
		    return false;
	    }
	    if (!($templateTranslation = $template->getTranslation()) || !($page = Page::findPageByRoute(['/site/activate'])->getTranslation())) {
		    $templateTranslation = $template->getTranslation(Yii::$app->settings->get('defaultLanguage'));
		    $page = Page::findPageByRoute(['/site/activate'])->getTranslation(Yii::$app->settings->get('defaultLanguage'));
		    $language = mb_substr(Yii::$app->settings->get('defaultLanguage'), 0, 2);
		    if (empty($templateTranslation) || empty($page)) {
			    return false;
		    }
	    } else {
		    $language = mb_substr(Yii::$app->language, 0, 2);
	    }
	    $appUrl = Yii::$app->request->hostInfo;
	    $activationPageUrl = implode('/', array_filter([
		    $appUrl,
		    $language,
		    $page->slug
	    ]));
	    $activationUrl = implode('', [
		    $activationPageUrl,
		    '?token=' . $user->getSignupToken()
	    ]);
        $shortCodeValues = [
            '{{APP_NAME}}' => Yii::$app->name,
            '{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
            '{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
            '{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
            '{{FIRST_NAME}}' => $user->first_name,
            '{{MIDDLE_NAME}}' => $user->middle_name,
            '{{LAST_NAME}}' => $user->last_name,
            '{{ACCOUNT_ACTIVATION_CODE}}' => $user->getSignupToken(),
            '{{ACCOUNT_ACTIVATION_PAGE_URL}}' => $activationPageUrl,
            '{{ACCOUNT_ACTIVATION_URL}}' => $activationUrl,
        ];

        return Yii::$app->mailer->compose()
            ->setTo([$this->email => $user->fullName])
            ->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
            ->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
            ->send();
    }

    /**
     * Sends email for custom package request.
     *
     * @return bool
     */
    public function sendCustomPackageRequestEmail()
    {
        $user = $this->getUser();

        return Yii::$app->mailer->compose([
            'html' => 'custom-package-request-html',
            'text' => 'custom-package-request-text',
        ], [
            'user' => $user,
            'message' => $this->custom_package_requirements,
        ])
            ->setTo(Yii::$app->settings->get('email', 'contact'))
            ->setReplyTo([$user->email => $user->fullName])
            ->setSubject('[' . Yii::$app->name . ']: ' . Yii::t('common', 'Enterprise package request from {0}', [$user->fullName]))
            ->send();
    }

	public function saveDocumentationUser()
	{
		try {
			$user = $this->getUser();
			$model = \common\models\documentation\User::findOne(['id' => $user->id]);
			if (empty($model)) {
				$model = new \common\models\documentation\User();
			}
			$model->setAttributes($user->attributes, false);
			if (!$model->save(false)) {
				throw new \Exception();
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

    /**
     * Signs user up.
     *
     * @return bool
     */
    public function signup()
    {
        if (!empty($this->workEmail)) {
            return false;
        }
        if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
            if (!empty($this->captchaResponse)) {
                $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
                $response = json_decode($result);
                if (empty($response->success)) {
                    return false;
                }
            }
        }
        if (!$this->validate()) {
            return false;
        }
        $dbTransaction = Yii::$app->getDb()->beginTransaction();
        try {
            $user = new User();
            $user->email = $this->email;
            $user->phone = $this->phone;
            $user->first_name = $this->first_name;
            $user->last_name = $this->last_name;
            $user->setPassword($this->password);
            $user->generateAuthKey();
            $user->status = User::STATUS_ACTIVE;
            if (Yii::$app->settings->get('userAccountActivation') == User::ACCOUNT_ACTIVATION_CONFIRMATION) {
                $user->signup_token = User::generateSignupToken();
                $user->status = User::STATUS_INACTIVE;
            }
            if (!$user->save(false)) {
                $this->addErrors($user->getErrors());
                throw new \Exception();
            }
            $this->_user = $user;
            if (!$this->saveSubscriber()) {
                throw new \Exception();
            }
	        if (!$this->saveDocumentationUser()) {
		        throw new \Exception();
	        }
            if (!$this->saveCompany()) {
                throw new \Exception();
            }
            if (!$this->saveSubscription()) {
                throw new \Exception();
            }
            if (!$this->saveWorkspace()) {
                throw new \Exception();
            }
            if (!$this->saveWorkspaceHasUser()) {
                throw new \Exception();
            }
            if (!$this->saveWorkspaceHasSubscriptionFeatures()) {
                throw new \Exception();
            }
            if (!$this->_workspace->install()) {
                throw new \Exception();
            }
            if (!$this->saveWorkspaceCompany()) {
                throw new \Exception();
            }
            if ($this->getPackage()->type == Package::TYPE_CUSTOM) {
                if (!$this->sendCustomPackageRequestEmail()) {
                    throw new \Exception();
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
