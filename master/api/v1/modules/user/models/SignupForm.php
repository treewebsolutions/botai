<?php

namespace api\v1\modules\user\models;

use Yii;
use yii\base\Model;

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
	 * @var User The User model.
	 */
	private $_user;


	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			[['first_name', 'last_name', 'email', 'phone', 'password'], 'required'],
			[['first_name', 'middle_name', 'last_name', 'email', 'phone'], 'string', 'max' => 255],
			[['first_name', 'middle_name', 'last_name', 'email', 'phone'], 'trim'],
			['password', 'string', 'min' => 6],
			['email', 'email'],
			[['email'], 'unique', 'targetClass' => \common\models\User::class],
		];
	}

	/**
	 * {@inheritdoc}
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
	 * Signs user up.
	 *
	 * @return bool
	 */
	public function signup()
	{
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

			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
