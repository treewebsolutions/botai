<?php
/* @var $this yii\web\View */
/* @var $model common\models\SubscribeForm */

use common\models\Feature;
use common\models\Package;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use tws\widgets\carousel\Carousel;
use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header gap-b-xlg"><?= $content ?></header>
		<?php endif; ?>

		<?php $form = ActiveForm::begin([
			'id' => mb_strtolower($model->formName()),
			'options' => [
				'novalidate' => true,
				'class' => 'panel panel-default',
			],
			'validateOnType' => true,
			'validateOnBlur' => true,
		]); ?>
			<div class="panel-body">
				<div class="form-group field-<?= Html::getInputId($model, 'package_id') ?> required" role="radiogroup" aria-required="true">
					<label class="control-label"><?= Yii::t('common', 'Choose a package') ?></label>
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
						$featureModuleLabels = FeatureModule::getModuleLabels();
						$featureLabels = Feature::getFeatureLabels();
						$packages = Package::findPackagesByType([Package::TYPE_STANDARD, Package::TYPE_CUSTOM]);
						?>
						<?php foreach ($packages as $package): ?>
							<?php
							$packageTranslation = $package->getTranslation();
							/** @var PackageFeature[] $packageFeatures */
							$packageFeatures = $package->getPackageFeatures()->indexBy('name')->all();
							?>
							<div class="carousel-item swiper-slide">
								<div class="card card-pricing card-hover bg-white <?= $package->id == $model->package_id ? 'active' : '' ?>" data-pricing-package="true">
									<?php if ($package->type == Package::TYPE_STANDARD): ?>
										<header class="card-header">
											<h3 class="card-heading text-uppercase"><?= $packageTranslation->name ?></h3>
											<div class="card-jumbotron color-default"><?= Yii::$app->formatter->asCurrency($package->price, $package->currency) ?></div>
										</header>
										<div class="list-icon list-spacing equal-package">
											<?php if (!empty($packageTranslation->content)): ?>
												<?= $packageTranslation->content ?>
											<?php endif; ?>
										</div>
									<?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
										<header class="card-header">
											<h3 class="card-heading text-uppercase"><?= $packageTranslation->name ?></h3>
											<div class="card-jumbotron color-default"><?= Yii::t('common', 'Custom') ?></div>
										</header>
										<div class="list-icon list-spacing">
											<?php if (!empty($packageTranslation->content)): ?>
												<?= $packageTranslation->content ?>
											<?php endif; ?>
										</div>
									<?php endif; ?>
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
													'custom' => $package->type == Package::TYPE_CUSTOM ? 'true' : 'false',
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
							</div>
							<?php $i++; ?>
						<?php endforeach; ?>
					<?php Carousel::end(); ?>
					<div class="error-<?= Html::getInputId($model, 'package_id') ?> help-block help-block-error gap-t-sm"></div>
				</div>
				<?= $form->field($model, 'custom_package_requirements', [
					'options' => [
						'id' => 'custom-package-fields-container',
						'class' => 'required gap-b-lg' . ($model->getPackage()->type == Package::TYPE_CUSTOM ? '' : ' hidden'),
					],
				])->textarea([
					'rows' => 3,
					'placeholder' => Yii::t('common', 'Example') . ': 15 ' . mb_strtolower(Yii::t('common', 'Workspaces')) . ', 10 ' . mb_strtolower(Yii::t('common', 'Users'))
				]) ?>
				<?= $form->field($model, 'acceptTerms')->checkbox([
					'uncheck' => null,
					'label' => Yii::t('common', 'I understand and agree to the {0} and {1} of {2}.', [
						Html::a(Yii::t('common', 'Terms and Conditions'), ['/site/terms-and-conditions'], ['target' => '_blank']),
						Html::a(Yii::t('common', 'Privacy Policy'), ['/site/privacy-policy'], ['target' => '_blank']),
						Yii::$app->name
					]),
				]) ?>
                <?= $form->field($model, 'workEmail', [
                    'options' => [
                        'class' => 'work-email',
                    ],
                    'template' => '{input}',
                ])->input('email', ['required' => 'required'])->label(false) ?>
                <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                    <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                    <?= $form->field($model, 'captchaResponse', [
                        'template' => '{input}',
                    ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                <?php endif; ?>
			</div>
			<div class="panel-footer text-center">
				<button type="submit" class="btn btn-block btn-default btn-slide-right"><?= Yii::t('common', 'Subscribe') ?></button>
			</div>
		<?php ActiveForm::end(); ?>
	</div>
</div>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>

<?php
$this->registerJs('	
	var equalPackageMaxHeight = 0;
	$(".equal-package").each(function(){
		if ($(this).height() > equalPackageMaxHeight) { equalPackageMaxHeight = $(this).height(); }
	});
	$(".equal-package").height(equalPackageMaxHeight);
');
?>



