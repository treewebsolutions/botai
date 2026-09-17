<?php

namespace tests\unit;

use backend\modules\nomenclature\models\PackageForm;
use common\models\PaymentMetadata;
use common\models\ScheduledTask;
use tests\DatabaseTestCase;
use tests\support\FakeSettings;
use Yii;

/**
 * Updating a package from the nomenclature must not depend on Stripe answering.
 *
 * With card payments on and Stripe among the processors, saveModel() calls out to the
 * Stripe API. Locally - no key, no network - that call fails, and the package save has
 * to survive it with what is already stored.
 */
class PackageSaveTest extends DatabaseTestCase
{
	/**
	 * @var mixed the settings component the application had before the test
	 */
	private $settings;

	protected function setUp(): void
	{
		parent::setUp();

		$this->settings = Yii::$app->get('settings');
		// Card payments on, Stripe among the processors: the branch that talks to Stripe.
		Yii::$app->set('settings', new FakeSettings([
			'data' => [
				'payment' => [
					'paymentMethods' => [
						PaymentMetadata::PAYMENT_METHOD_CARD => 1,
					],
					'paymentProcessors' => [
						PaymentMetadata::PAYMENT_METHOD_CARD => [
							PaymentMetadata::PAYMENT_PROCESSOR_STRIPE => 1,
						],
					],
				],
			],
		]));
	}

	protected function tearDown(): void
	{
		Yii::$app->set('settings', $this->settings);

		parent::tearDown();
	}

	/**
	 * The regression: Stripe answers with nothing, and reading ['id'] off that empty
	 * array threw, rolled the transaction back and returned false - the error the
	 * nomenclature showed on every package update.
	 */
	public function testPackageUpdatesWhileStripeIsUnreachable()
	{
		$id = $this->insertRow('package', [
			'type' => PackageForm::TYPE_STANDARD,
			'price' => 900,
			'currency' => 'RON',
			'trial_period' => 1,
			'trial_cycle' => ScheduledTask::CYCLE_YEAR,
			'billing_period' => 1,
			'billing_cycle' => ScheduledTask::CYCLE_YEAR,
			'external_id' => 'prod_existing',
			'status' => PackageForm::STATUS_ACTIVE,
		]);

		$package = PackageForm::findOne($id);
		$this->assertNotNull($package);

		$package->price = 1000;

		$this->assertNotFalse($package->saveModel(), 'The package was not saved.');

		$saved = PackageForm::findOne($id);
		$this->assertSame(1000.0, (float) $saved->price);
		// The id Stripe never returned must not wipe the one already stored.
		$this->assertSame('prod_existing', $saved->external_id);
	}
}
