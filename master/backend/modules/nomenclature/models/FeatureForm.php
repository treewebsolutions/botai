<?php

namespace backend\modules\nomenclature\models;

use common\models\Feature;
use common\models\PaymentMetadata;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class FeatureForm extends Feature
{
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->currency = Yii::$app->settings->get('currencyCode');
		$this->status = static::STATUS_ACTIVE;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['price', 'currency'], 'required'],
			[['price'], 'number', 'min' => 0],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [

		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	public function saveStripeProduct($isNewRecord = true)
	{
		try {
			$payment = Yii::$app->payment->via(PaymentMetadata::PAYMENT_PROCESSOR_STRIPE);
			$stripe = new \Stripe\StripeClient(
				$payment->privateKey
			);
			if ($isNewRecord) {
				$product = $stripe->products->create([
					'name' => Feature::getFeatureLabels()[$this->name],
					'active' => (bool)$this->status,
					'metadata' => [
						'feature_id' => $this->id,
					],
					'default_price_data' => [
						'unit_amount_decimal' => $this->price * 100,
						'currency' => $this->currency,
						'recurring' => [
							'interval' => 'month',
							'interval_count' => 1,
						],
					],
				]);
			} else {
				if ($this->external_id) {
					$product = $stripe->products->retrieve(
						$this->external_id,
						[]
					);
					if ($product['id']) {
						$product = $stripe->products->update(
							$product['id'],
							[
								'active' => (bool)$this->status,
								'metadata' => [
									'feature_id' => $this->id,
								],
							]
						);
					}
				} else {
					$product = $stripe->products->create([
						'name' => Feature::getFeatureLabels()[$this->name],
						'active' => (bool)$this->status,
						'metadata' => [
							'feature_id' => $this->id,
						],
						'default_price_data' => [
							'unit_amount_decimal' => $this->price * 100,
							'currency' => $this->currency,
							'recurring' => [
								'interval' => 'month',
								'interval_count' => 1,
							],
						],
					]);
				}
			}
			return $product;
		} catch(\Exception $e) {
			return [];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		$isNewRecord = $this->getIsNewRecord();
		$paymentSettings = Yii::$app->settings->getCategory('payment');
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if (!parent::save($runValidation, $attributeNames)) {
				throw new \Exception();
			}
			if (array_key_exists(\common\models\PaymentMetadata::PAYMENT_METHOD_CARD, (array) $paymentSettings['paymentMethods'])) {
				$activePaymentProcessors = (array) $paymentSettings['paymentProcessors'][\common\models\PaymentMetadata::PAYMENT_METHOD_CARD];
				if (array_key_exists(\common\models\PaymentMetadata::PAYMENT_PROCESSOR_STRIPE, $activePaymentProcessors)) {
					$product = $this->saveStripeProduct($isNewRecord);
					$this->external_id = $product['id'] ?: null;
					if (!$this->save(false, ['external_id'])) {
						throw new \Exception();
					}
				}
			}
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
