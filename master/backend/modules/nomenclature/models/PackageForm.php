<?php

namespace backend\modules\nomenclature\models;

use common\components\ApplicationBootstrap;
use common\helpers\StringHelper;
use common\models\Feature;
use common\models\Language;
use common\models\Package;
use common\models\PackageFeature;
use common\models\PackageTranslation;
use common\models\PaymentMetadata;
use common\models\ScheduledTask;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class PackageForm extends Package
{
	/**
	 * @var array The multilingual name of the Package.
	 */
	public $name = [];

	/**
	 * @var array The multilingual content of the Package.
	 */
	public $content = [];

    /**
     * @var array Enable google translator.
     */
    public $translator = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->trial_period = 14;
		$this->trial_cycle = ScheduledTask::CYCLE_DAY;
		$this->billing_period = 1;
		$this->billing_cycle = ScheduledTask::CYCLE_MONTH;
		$this->currency = Yii::$app->settings->get('currencyCode');
		$this->type = static::TYPE_STANDARD;
		$this->status = static::STATUS_ACTIVE;

		// Package features
		$this->{Feature::WORKSPACES} = 1;
		$this->{Feature::WORKING_POINTS} = 1;
		$this->{Feature::USERS} = 1;
	}

	/**
	 * @inheritdoc
	 */
	public function attributes()
	{
		$attributes = parent::attributes();

		foreach (array_keys(Feature::getFeatureLabels()) as $packageFeatureName) {
			$attributes[] = $packageFeatureName;
		}

		return $attributes;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['type'], 'required'],
			[['trial_period', 'trial_cycle', 'billing_period', 'billing_cycle', 'price', 'type'], 'required', 'when' => function () {
				return $this->type != static::TYPE_FREE;
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[type]").val() != "' . static::TYPE_FREE . '";
    	}'],
			[['price'], 'number', 'min' => 1, 'when' => function () {
				return $this->type != static::TYPE_FREE;
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[type]").val() != "' . static::TYPE_FREE . '";
    	}'],
			[['name'], 'required', 'when' => function () {
				return empty($this->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
        return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
    	}'],
			[['name', 'content'], 'each', 'rule' => ['trim']],
			[['content'], 'each', 'rule' => ['default', 'value' => null]],

			// Package features
			[[
				Feature::WORKSPACES,
				Feature::WORKING_POINTS,
				Feature::USERS,
			], 'required'],
			[[
				Feature::WORKSPACES,
				Feature::USERS,
			], 'integer', 'min' => 1],
			[array_keys(Feature::getFeatureLabels()), 'trim'],
			[array_keys(Feature::getFeatureLabels()), 'default', 'value' => null],
            [['translator'], 'safe'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), Feature::getFeatureLabels(), [
			'billing_period' => Yii::t('label', 'Billing Cycle'),
			'name' => Yii::t('label', 'Name'),
			'content' => Yii::t('label', 'Description'),
            'translator' => Yii::t('label', 'Translator'),
		]);
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

		$this->name = ArrayHelper::map($this->packageTranslations, 'language_id', 'name');
		$this->content = ArrayHelper::map($this->packageTranslations, 'language_id', 'content');

		foreach ($this->packageFeatures as $packageFeature) {
			if ($this->hasAttribute($packageFeature->name)) {
				$this->{$packageFeature->name} = $packageFeature->value;
			}
		}
	}

    /**
     * Saves the translations.
     *
     * @return bool
     */
    protected function savePackageTranslations()
    {
        try {
            $languages = ArrayHelper::getColumn(Yii::$app->translate->discover()['data']['languages'], 'language');
            $defaultLanguage = Language::findOne(['language_id' => Yii::$app->language]);

            foreach (Language::findAllLanguages() as $language) {
                $packageTranslation = PackageTranslation::findOne([
                    'package_id' => $this->id,
                    'language_id' => $language->language_id,
                ]);
                if (!$packageTranslation) {
                    $packageTranslation = new PackageTranslation();
                    $packageTranslation->package_id = $this->id;
                    $packageTranslation->language_id = $language->language_id;
                }

                if (!empty($this->translator) && $language->language_id != Yii::$app->language && in_array($defaultLanguage->language, $languages) & in_array($language->language, $languages)) {
                    $source = $defaultLanguage->language;
                    $target = $language->language;
                }

                $packageTranslation->name = StringHelper::truncate($this->name[$language->language_id] && ($language->language_id == Yii::$app->language || !in_array(2, (array)$this->translator)) ? $this->name[$language->language_id] : ($source && $target ? Yii::$app->translate->translate($source, $target, $this->name[Yii::$app->language])['data']['translations'][0]['translatedText'] : null), 255, '');
                $parts = StringHelper::splitString($this->content[$defaultLanguage->language_id], 5000);
                $translations = [];
                foreach ($parts as $part) {
                    $translation = html_entity_decode($part);
                    $translation = $source && $target ? Yii::$app->translate->translate($source, $target, $translation)['data']['translations'][0]['translatedText'] : null;
                    $translation = html_entity_decode($translation);
                    $translation = ApplicationBootstrap::recursiveStripTags($translation);
                    $translation = str_replace(['& nbsp;', '&nbsp;', '< / p>'], [' ', ' ', ''], $translation);
                    $translations[] = $translation;
                }
                $packageTranslation->content = $this->content[$language->language_id] && ($language->language_id == Yii::$app->language || !in_array(2, (array)$this->translator)) ? $this->content[$language->language_id] : (!empty($translations) ? implode('.', $translations) : null);
                if ($packageTranslation->name) {
                    $this->link('packageTranslations', $packageTranslation);
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->addError('', $e->getMessage());
            return false;
        }
    }

	/**
	 * Saves the Package features.
	 *
	 * @return bool
	 */
	protected function savePackageFeatures()
	{
		try {
			foreach (Feature::findAllFeatures() as $feature) {
				if ($this->hasAttribute($feature->name)) {
					$packageFeature = PackageFeature::findOne([
						'package_id' => $this->id,
						'name' => $feature->name,
						'deleted' => PackageFeature::NO,
					]);
					if (!$packageFeature) {
						$packageFeature = new PackageFeature();
						$packageFeature->package_id = $this->id;
					}
					$packageFeature->feature_id = $feature->id;
					$packageFeature->name = $feature->name;
					$packageFeature->value = (string) $this->{$feature->name};
					$packageFeature->renewable = $feature->renewable;
					if (!$packageFeature->save()) {
						throw new \Exception('Cannot save the PackageFeature models.');
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
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
					'name' => $this->translation->name,
					'active' => (bool)$this->status,
					'metadata' => [
						'package_id' => $this->id,
					],
					'default_price_data' => [
						'unit_amount_decimal' => $this->price * 100,
						'currency' => $this->currency,
						'recurring' => [
							'interval' => $this->billing_cycle,
							'interval_count' =>  $this->billing_period,
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
									'package_id' => $this->id,
								],
							]
						);
					}
				} else {
					$product = $stripe->products->create([
						'name' => $this->translation->name,
						'active' => (bool)$this->status,
						'metadata' => [
							'package_id' => $this->id,
						],
						'default_price_data' => [
							'unit_amount_decimal' => $this->price * 100,
							'currency' => $this->currency,
							'recurring' => [
								'interval' => $this->billing_cycle,
								'interval_count' =>  $this->billing_period,
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
	 * Saves the model.
	 *
	 * @return bool|static
	 */
	public function saveModel()
	{
		$isNewRecord = $this->getIsNewRecord();
		$paymentSettings = Yii::$app->settings->getCategory('payment');
		$dbTransaction = static::getDb()->beginTransaction();
		try {
			if ($this->type == static::TYPE_FREE) {
				$this->trial_period = null;
				$this->trial_cycle = null;
				$this->billing_period = null;
				$this->billing_cycle = null;
				$this->price = 0;
				$this->currency = null;
			}
            if (!$this->save(true, parent::attributes())) {
                throw new \Exception();
            }
			if (!$this->savePackageTranslations()) {
				throw new \Exception();
			}
			if (!$this->savePackageFeatures()) {
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
