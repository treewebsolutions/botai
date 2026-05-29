<?php

namespace frontend\modules\account\models;

use common\models\Company;
use common\models\PaymentMetadata;
use common\models\Subscription;
use Yii;
use yii\base\Model;

class SubscriptionForm extends Subscription
{
	/**
	 * @var bool Flag that indicates if the payer agrees with the automatic payment.
	 */
	public $enable_recurring_payment;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['enable_recurring_payment'], 'boolean'],
			[['company_id'], 'exist', 'skipOnError' => true, 'targetClass' => Company::class, 'targetAttribute' => ['company_id' => 'id']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'enable_recurring_payment' => Yii::t('label', 'Enable Recurring Payment'),
			'company_id' => Yii::t('label', 'Company'),
		];
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->enable_recurring_payment = $this->paymentMetadata->recurring_payment;
	}

	/**
	 * Saves the PaymentMetadata model.
	 *
	 * @return bool
	 */
	protected function savePaymentMetadata()
	{
		try {
			if (!($paymentMetadata = $this->paymentMetadata)) {
				$paymentMetadata = new PaymentMetadata();
				$paymentMetadata->subscription_id = $this->id;
			}
			$paymentMetadata->recurring_payment = $this->enable_recurring_payment;
			$paymentMetadata->ip_address = Yii::$app->request->userIP;
			$paymentMetadata->status = PaymentMetadata::STATUS_ACTIVE;
			if (!$paymentMetadata->save()) {
				throw new \Exception('Payment metadata cannot be saved.');
			}

			if ($paymentMetadata->payment_processor && $paymentMetadata->payment_method == PaymentMetadata::PAYMENT_METHOD_CARD) {
				$paymentProcessor = Yii::$app->payment->via($paymentMetadata->payment_processor);
				if ($paymentMetadata->recurring_payment) {
					$payment = $paymentProcessor->updatePayment($paymentMetadata->payment_id, [
						'payerId' => $paymentMetadata->payer_id,
						'cardId' => $paymentMetadata->card_id,
						'ipAddress' => $paymentMetadata->ip_address,
						'status' => $paymentProcessor::PAYMENT_STATUS_IN_PROGRESS,
					]);
					if (!$payment) {
						throw new \Exception('Recurring payment cannot be updated.');
					}
				} else {
					if (!$paymentProcessor->cancelRecurringPayment($paymentMetadata->payment_id)) {
						throw new \Exception('Recurring payment cannot be cancelled.');
					}
				}
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
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
