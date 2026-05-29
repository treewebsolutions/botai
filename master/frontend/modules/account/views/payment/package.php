<?php
/* @var $this yii\web\View */
/* @var $model frontend\modules\account\models\PaymentPackageForm */

use common\models\Feature;
use common\models\Package;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\PaymentMetadata;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use tws\widgets\carousel\Carousel;
use yii\helpers\Html;

$this->title = Yii::t('frontend', 'Payment for {item}', ['item' => Yii::t('common', 'Package')]);
$this->params['breadcrumbs'][] = Html::encode($this->title);
\frontend\modules\account\assets\PaymentAsset::register($this);

$featureModuleLabels = FeatureModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
$paymentSettings = Yii::$app->settings->getCategory('payment');
$activePaymentMethods = array_intersect_key(PaymentMetadata::getPaymentMethodLabels(), (array) $paymentSettings['paymentMethods']);
$activePaymentProcessors = array_intersect_key(PaymentMetadata::getPaymentProcessorLabels(), (array) $paymentSettings['paymentProcessors'][PaymentMetadata::PAYMENT_METHOD_CARD]);
Yii::$app->formatter->locale = Yii::$app->language;
?>

<div class="section section-md">
	<div class="container-fluid">
		<header class="section-header">
			<p><?= Yii::t('frontend', 'Buy a package and enjoy its features.') ?> <?= Yii::t('frontend', 'Please contact us if you have other requirements.') ?></p>
		</header>

		<?php $form = ActiveForm::begin([
			'id' => 'payment-form',
			'options' => [
				'novalidate' => true,
			],
			'validateOnType' => true,
		]); ?>
			<div class="gap-b-sm form-group field-<?= Html::getInputId($model, 'package_id') ?> required" role="radiogroup" aria-required="true">
				<label class="control-label"><?= $model->getAttributeLabel('package_id') ?></label>
				<?php Carousel::begin([
					'options' => [
						'class' => 'carousel-pricing',
					],
					'pagination' => false,
					'navigation' => false,
					'scrollbar' => false,
					'clientOptions' => [
						'autoplay' => [
							'delay' => 7000,
						],
						'speed' => 1000,
						'effect' => 'slide',
						'slidesPerView' => 1,
						'spaceBetween' => 15,
						'breakpointsInverse' => true,
						'breakpoints' => [
							650 => [
								'slidesPerView' => 2,
							],
							992 => [
								'slidesPerView' => 2,
							],
							1200 => [
								'slidesPerView' => 2,
							],
						],
					],
				]); ?>
					<?php
					$i = 0;
					$packages = Package::findPackagesByType([Package::TYPE_STANDARD, Package::TYPE_CUSTOM]);
					?>
					<?php foreach ($packages as $package): ?>
						<?php
						$packageTranslation = $package->getTranslation();
						/** @var PackageFeature[] $packageFeatures */
						$packageFeatures = $package->getPackageFeatures()->indexBy('name')->all();
						?>
						<div class="carousel-item swiper-slide">
							<?php if ($package->type == Package::TYPE_STANDARD): ?>
								<div class="card card-pricing card-hover bg-white <?= $package->id == $model->package_id ? 'active' : '' ?>" data-package="<?= $package->id ?>">
									<header class="card-header">
										<h3 class="card-heading text-uppercase"><?= $packageTranslation->name ?></h3>
										<div class="card-jumbotron color-default" data-package-price="<?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?>"><?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?></div>
									</header>
									<div class="list-icon list-spacing">
										<?php if (!empty($packageTranslation->content)): ?>
											<?= $packageTranslation->content ?>
										<?php endif; ?>
									</div>
									<footer class="card-footer">
										<span class="btn btn-block btn-default btn-outline btn-slide-right"><?= Yii::t('common', 'Choose') ?></span>
										<div class="hidden">
											<?= $form->field($model, 'package_id', [
												'options' => [
													'tag' => false,
												],
												'selectors' => [
													'container' => '.field-' . Html::getInputId($model, 'package_id'),
													'input' => '.input-' . Html::getInputId($model, 'package_id'),
													'error' => '.error-' . Html::getInputId($model, 'package_id'),
												],
											])->radio([
												'id' => null,
												'class' => 'input-' . Html::getInputId($model, 'package_id'),
												'value' => $package->id,
												'data' => [
													'name' => $packageTranslation->name,
													'price' => $package->price,
													'currency' => $package->currency,
												],
												'uncheck' => null,
												'label' => '',
												'labelOptions' => [
													'class' => 'hidden',
												],
											]) ?>
										</div>
									</footer>
								</div>
							<?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
								<div class="card card-pricing bg-white">
									<header class="card-header">
										<h3 class="card-heading text-uppercase"><?= $packageTranslation->name ?></h3>
										<?php if ($package->trial_period): ?>
											<div class="card-jumbotron color-default"><?= Yii::t('common', 'Custom') ?></div>
										<?php endif; ?>
									</header>
									<ul class="list-icon list-spacing equal-package">
										<li class="fa-check-circle"><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></li>
										<li class="fa-check-circle"><?= $featureLabels[Feature::WORKSPACES] ?>: <?= Yii::t('common', 'Custom') ?></li>
										<li class="fa-check-circle"><?= $featureLabels[Feature::WORKING_POINTS] ?>: <?= Yii::t('common', 'Custom') ?></li>
										<li class="fa-check-circle"><?= $featureLabels[Feature::USERS] ?>: <?= Yii::t('common', 'Custom') ?></li>
									</ul>
									<footer class="card-footer">
										<?= Html::a(Yii::t('common', 'Contact Us'), ['/site/contact', 'request' => 'enterprise'], ['class' => 'btn btn-block btn-default btn-outline btn-slide-right']) ?>
									</footer>
								</div>
							<?php endif; ?>
						</div>
						<?php $i++; ?>
					<?php endforeach; ?>
				<?php Carousel::end(); ?>
				<div class="error-<?= Html::getInputId($model, 'package_id') ?> help-block help-block-error"></div>
			</div>

			<?= $this->render('_shared-form-fields', [
				'form' => $form,
				'model' => $model,
			]) ?>

			<div class="form-group text-center gap-t-lg">
				<button type="submit" class="btn btn-lg btn-default btn-slide-right" id="btn-submit-payment">
					<span class="btn-submit-label-payment <?= $model->payment_method == PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>">
						<?= Yii::t('common', 'Pay') ?>
						<?php if ($package = $model->getPackage()): ?>
							<span class="btn-submit-amount"><?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?></span>
						<?php endif; ?>
					</span>
					<?php if (array_key_exists(PaymentMetadata::PAYMENT_METHOD_BANK, $activePaymentMethods)): ?>
						<span class="btn-submit-label-submit <?= $model->payment_method != PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>"><?= Yii::t('common', 'Submit') ?></span>
					<?php endif; ?>
				</button>
			</div>
		<?php ActiveForm::end(); ?>
	</div>
</div>

<?php
$this->registerJs('	
	var equalPackageMaxHeight = 0;
	$(".equal-package").each(function(){
		if ($(this).height() > equalPackageMaxHeight) { equalPackageMaxHeight = $(this).height(); }
	});
	$(".equal-package").height(equalPackageMaxHeight);
');
?>
