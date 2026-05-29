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
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * @var Customer The Customer model.
	 */
	private $_customer;


	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'email', 'phone', 'password', 'acceptTerms'], 'required'],
			[['first_name', 'middle_name', 'last_name', 'email', 'phone'], 'string', 'max' => 255],
			[['first_name', 'middle_name', 'last_name', 'email', 'phone'], 'trim'],
			['password', 'string', 'min' => 6],
			['email', 'email'],
			[['email', 'phone'], 'unique', 'targetClass' => 'common\models\User'],
			['acceptTerms', 'boolean'],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
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
			'acceptTerms' => Yii::t('common', 'Terms and Conditions'),
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
	 * Gets the Customer model.
	 *
	 * @return Customer
	 */
	public function getCustomer()
	{
		return $this->_customer;
	}

	/**
	 * Saves the Customer model.
	 *
	 * @return bool
	 * @throws \Exception
	 */
	protected function saveCustomer()
	{
		try {
			$user = $this->getUser();

			$customer = new Customer();
			$customer->user_id = $user->id;
			$customer->code = Customer::generateUniqueCode();
			$customer->status = Customer::STATUS_INACTIVE;
			if (!$customer->save()) {
				throw new \Exception($customer->getErrorSummary(false)[0]);
			}

			$this->_customer = $customer;

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
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
		if (!$template || !($templateTranslation = $template->getTranslation())) {
			return false;
		}
		$shortCodeValues = [
			'{{APP_NAME}}' => Yii::$app->name,
			'{{APP_URL}}' => Url::to(['/site/index'], true, '@frontend'),
			'{{APP_LOGO_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogo'), true) ?: Url::to('@frontend/web/img/logo.png', true),
			'{{APP_LOGO_ALT_URL}}' => Url::to('@uploads/' . Yii::$app->settings->get('appLogoAlt'), true) ?: Url::to('@frontend/web/img/logo-alt.png', true),
			'{{FIRST_NAME}}' => $user->first_name,
			'{{MIDDLE_NAME}}' => $user->middle_name,
			'{{LAST_NAME}}' => $user->last_name,
			'{{ACCOUNT_ACTIVATION_CODE}}' => $user->getSignupToken(),
			'{{ACCOUNT_ACTIVATION_PAGE_URL}}' => Url::to(['/site/activate'], true, '@frontend'),
			'{{ACCOUNT_ACTIVATION_URL}}' => Url::to(['/site/activate', 'token' => $user->getSignupToken()], true, '@frontend'),
		];

		return Yii::$app->mailer->compose()
			->setTo([$this->email => $user->fullName])
			->setSubject(strtr($templateTranslation->subject, $shortCodeValues))
			->setHtmlBody(strtr($templateTranslation->content, $shortCodeValues))
			->send();
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
		$dbTransaction = User::getDb()->beginTransaction();
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
			if (!$this->saveCustomer()) {
				throw new \Exception();
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
