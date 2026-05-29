<?php
/* @var $this yii\web\View */
/* @var $model common\models\SignupForm */

use common\models\Feature;
use common\models\Package;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use common\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use tws\widgets\carousel\Carousel;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

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
                'class' => 'panel package-form',
            ],
            'validateOnType' => true,
            'validateOnBlur' => true,
        ]); ?>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?= Yii::t('common', 'User Data') ?>
                </h3>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'last_name')->textInput() ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'first_name')->textInput() ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <?= $form->field($model, 'email')->input('email') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'phone')->input('tel') ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    <?= Yii::t('label', 'Company Data') ?>
                </h3>
            </div>
            <div class="panel-body">
                <div class="row">
	                <div class="col-sm-4">
		                <?= $form->field($model, 'company_name')->textInput() ?>
	                </div>
                    <div class="col-sm-4">
                        <?= $form->field($model, 'company_email')->textInput() ?>
                    </div>
	                <div class="col-sm-4">
		                <?= $form->field($model, 'company_phone')->textInput() ?>
	                </div>
                </div>
	            <div class="row">
		            <div class="col-sm-4">
			            <?= $form->field($model, 'company_address')->textInput() ?>
		            </div>
		            <div class="col-sm-4">
			            <?= $form->field($model, 'company_locality')->textInput() ?>
		            </div>
		            <div class="col-sm-4">
			            <?= $form->field($model, 'company_country')->widget(Select2::class, [
				            'data' => ArrayHelper::map(\common\models\Country::findAllCountries(), 'iso_alpha2', 'translation.name'),
				            'pluginLoading' => false,
				            'pluginOptions' => [
					            'allowClear' => true,
					            'placeholder' => Yii::t('common', 'Choose'),
				            ],
			            ]) ?>
		            </div>
	            </div>
            </div>
        </div>

        <?php if (Package::findDefaultPackage()->id && $model->package_id == Package::findDefaultPackage()->id): ?>
            <div class="panel panel-default">
            <div class="panel-body">
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
                <button type="submit" class="btn btn-block btn-default btn-slide-right"><?= Yii::t('common', 'Sign Up') ?></button>
            </div>
        </div>
        <?php else: ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <?= Yii::t('label', 'Package') ?>
                    </h3>
                </div>
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
                                        'slidesPerView' => 3,
                                    ],
                                    1200 => [
                                        'slidesPerView' => 4,
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
                                        <ul class="list-icon list-spacing">
                                            <?php if (Yii::$app->user->isGuest): ?>
                                                <li class="fa-check-circle"><?= Yii::t('common', 'Trial Period') ?>: <?= $package->getFormattedTrialPeriod() ?></li>
                                            <?php endif; ?>
                                            <li class="fa-check-circle"><?= Yii::t('common', 'Billed') ?>: <?= $package->getFormattedBillingCycle() ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::WORKSPACES] ?>: <?= $packageFeatures[Feature::WORKSPACES]->value ?: 0 ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::WORKING_POINTS] ?>: <?= $packageFeatures[Feature::WORKING_POINTS]->value ?: 0 ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::USERS] ?>: <?= $packageFeatures[Feature::USERS]->value ?: 0 ?></li>
                                        </ul>
                                    <?php elseif ($package->type == Package::TYPE_CUSTOM): ?>
                                        <header class="card-header">
                                            <h3 class="card-heading text-uppercase"><?= $packageTranslation->name ?></h3>
                                            <div class="card-jumbotron color-default"><?= Yii::t('common', 'Custom') ?></div>
                                        </header>
                                        <ul class="list-icon list-spacing">
                                            <?php if (Yii::$app->user->isGuest): ?>
                                                <li class="fa-check-circle"><?= Yii::t('common', 'Trial Period') ?>: <?= $package->getFormattedTrialPeriod() ?></li>
                                            <?php endif; ?>
                                            <li class="fa-check-circle"><?= Yii::t('common', 'Billed') ?>: <?= Yii::t('common', 'Custom') ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::WORKSPACES] ?>: <?= Yii::t('common', 'Custom') ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::WORKING_POINTS] ?>: <?= Yii::t('common', 'Custom') ?></li>
                                            <li class="fa-check-circle"><?= $featureLabels[Feature::USERS] ?>: <?= Yii::t('common', 'Custom') ?></li>
                                        </ul>
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
                    <button type="submit" class="btn btn-block btn-default btn-slide-right"><?= Yii::t('common', 'Sign Up') ?></button>
                </div>
            </div>
        <?php endif; ?>
        <?php ActiveForm::end(); ?>

        <div class="text-center">
            <p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login']) ?></p>
            <p><?= Html::a(Yii::t('common', 'Forgot Password?'), ['/site/reset-password']) ?></p>
        </div>
    </div>
</div>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>
