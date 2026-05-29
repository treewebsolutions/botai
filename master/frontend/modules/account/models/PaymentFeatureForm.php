<?php

namespace frontend\modules\account\models;

use common\models\Company;
use common\models\Feature;
use common\models\PaymentMetadata;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\User;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PaymentFeatureForm extends Model
{
	/**
	 * @var int|string The parent Subscription model ID.
	 */
	public $parent_subscription_id;

	/**
	 * @var array The Feature model ID(s).
	 */
	public $features = [];

	/**
	 * @var int|string The Company model ID.
	 */
	public $company_id;

	/**
	 * @var int The payment method.
	 */
	public $payment_method;

	/**
	 * @var string The payment method.
	 */
	public $payment_processor;

	/**
	 * @var bool Flag that indicates if the payer agrees with the automatic payment.
	 */
	public $enable_recurring_payment;

	/**
	 * @var User The User model.
	 */
	private $_user;

	/**
	 * @var Subscription The parent Subscription model.
	 */
	private $_parentSubscription;

	/**
	 * @var Subscription The Subscription model.
	 */
	private $_subscription;

	/**
	 * @var Feature[] The Feature models.
	 */
	private $_features = [];


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->payment_method = PaymentMetadata::PAYMENT_METHOD_CARD;
		$this->payment_processor = PaymentMetadata::PAYMENT_PROCESSOR_STRIPE;
		$this->enable_recurring_payment = Yii::$app->settings->get('enableRecurringPayment', 'payment');
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['parent_subscription_id', 'payment_method', 'payment_processor'], 'required'],
			[['payment_method', 'payment_processor'], 'integer'],
			[['enable_recurring_payment'], 'boolean'],
			[['parent_subscription_id'], 'exist', 'skipOnError' => true, 'targetClass' => Subscription::class, 'targetAttribute' => ['parent_subscription_id' => 'id']],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
			['features', function ($attribute) {
				if (empty(array_filter($this->features))) {
					$this->addError('', Yii::t('yii', '{attribute} cannot be blank.', ['attribute' => $this->getAttributeLabel($attribute)]));
				}
			}],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'parent_subscription_id' => Yii::t('label', 'Subscription'),
			'features' => Yii::t('label', 'Features'),
			'company_id' => Yii::t('label', 'Company'),
			'payment_method' => Yii::t('label', 'Payment Method'),
			'payment_processor' => Yii::t('label', 'Payment Processor'),
			'enable_recurring_payment' => Yii::t('label', 'Enable Recurring Payment'),
		]);
	}

	/**
	 * Gets the User model.
	 *
	 * @return User|null
	 */
	public function getUser()
	{
		if (!$this->_user) {
			$this->_user = Yii::$app->user->identity;
		}
		return $this->_user;
	}

	/**
	 * Gets the parent Subscription model.
	 *
	 * @return Subscription|null
	 */
	public function getParentSubscription()
	{
		if (!$this->_parentSubscription) {
			$this->_parentSubscription = Subscription::findOne([
				'id' => $this->parent_subscription_id,
				'subscriber_id' => $this->getUser()->subscriber->id,
				'type' => [Subscription::TYPE_STANDARD, Subscription::TYPE_CUSTOM],
				'status' => Subscription::STATUS_ACTIVE,
				'deleted' => Subscription::NO,
			]);
		}
		return $this->_parentSubscription;
	}

	/**
	 * Gets the Subscription model.
	 *
	 * @return Subscription|null
	 */
	public function getSubscription()
	{
		return $this->_subscription;
	}

	/**
	 * Gets the Feature models.
	 *
	 * @return Feature[]|array
	 */
	public function getFeatures()
	{
		if (!$this->_features) {
			$this->_features = Feature::findAll([
				'name' => array_keys(array_filter($this->features)),
				'status' => Feature::STATUS_ACTIVE,
				'deleted' => Feature::NO,
			]);
		}
		return $this->_features;
	}

	/**
	 * Gets the features total price.
	 *
	 * @return float|int
	 */
	protected function getFeaturesTotalPrice()
	{
		$total = 0;

		if (!is_array($this->features) || empty($this->features)) {
			return $total;
		}

		foreach ($this->getFeatures() as $feature) {
			$total += ($this->features[$feature->name] * $feature->price);
		}

		return $total;
	}

	/**
	 * Saves the Subscription model.
	 *
	 * @return bool
	 */
	protected function saveSubscription()
	{
		try {
			$user = $this->getUser();
			$subscriber = $user->subscriber;
			$parentSubscription = $this->getParentSubscription();

			// Check if an incomplete subscription exists
			$subscription = Subscription::findOne([
				'subscriber_id' => $subscriber->id,
				'type' => Subscription::TYPE_FEATURE,
				'status' => Subscription::STATUS_INCOMPLETE,
				'deleted' => Subscription::NO,
			]);
			if (!$subscription) {
				$subscription = new Subscription();
				$subscription->code = Subscription::generateUniqueCode();
			}
			$subscription->parent_id = $parentSubscription->id;
			$subscription->subscriber_id = $parentSubscription->subscriber_id;
			$subscription->package_id = $parentSubscription->package_id;
			$subscription->company_id = $this->company_id;
			$subscription->trial_period = null;
			$subscription->trial_cycle = null;
			$subscription->billing_period = $parentSubscription->billing_period;
			$subscription->billing_cycle = $parentSubscription->billing_cycle;
			$subscription->price = $this->getFeaturesTotalPrice();
			$subscription->currency = $parentSubscription->currency;
			$subscription->type = Subscription::TYPE_FEATURE;
			$subscription->status = Subscription::STATUS_INCOMPLETE;
			if (!$subscription->save()) {
				throw new \Exception($subscription->getErrorSummary(false)[0]);
			}
			$this->_subscription = $subscription;

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the SubscriptionFeature model(s).
	 *
	 * @return bool
	 */
	protected function saveSubscriptionFeatures()
	{
		try {
			$subscription = $this->getSubscription();

			SubscriptionFeature::deleteAll([
				'subscription_id' => $subscription->id,
			]);

			$columns = ['subscription_id', 'feature_id', 'name', 'value', 'price', 'currency', 'renewable', 'type'];
			$rows = [];
			foreach ($this->getFeatures() as $feature) {
				$rows[] = [
					'subscription_id' => $subscription->id,
					'feature_id' => $feature->id,
					'name' => $feature->name,
					'value' => $this->features[$feature->name],
					'price' => $feature->price,
					'currency' => $feature->currency,
					'renewable' => $feature->renewable,
					'type' => SubscriptionFeature::TYPE_ADDON_FEATURE,
				];
			}
			if (!Yii::$app->db->createCommand()->batchInsert(SubscriptionFeature::tableName(), $columns, $rows)->execute()) {
				throw new \Exception('SubscriptionFeature cannot be saved.');
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the PaymentMetadata model.
	 *
	 * @return bool
	 */
	protected function savePaymentMetadata()
	{
		try {
			$subscription = $this->getSubscription();

			if (!($paymentMetadata = $subscription->paymentMetadata)) {
				$paymentMetadata = new PaymentMetadata();
				$paymentMetadata->subscription_id = $subscription->id;
			}
			$paymentMetadata->payment_method = $this->payment_method;
			$paymentMetadata->payment_processor = $this->payment_processor;
			$paymentMetadata->recurring_payment = $this->enable_recurring_payment;
			$paymentMetadata->ip_address = Yii::$app->request->userIP;
			$paymentMetadata->status = PaymentMetadata::STATUS_ACTIVE;
			if (!$paymentMetadata->save()) {
				throw new \Exception($paymentMetadata->getErrorSummary(false)[0]);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the models.
	 *
	 * @return bool
	 */
	public function save()
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!$this->saveSubscription()) {
				throw new \Exception();
			}
			if (!$this->saveSubscriptionFeatures()) {
				throw new \Exception();
			}
			if (!$this->savePaymentMetadata()) {
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
