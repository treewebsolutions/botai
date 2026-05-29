<?php
/* @var $this yii\web\View */
/* @var $model frontend\modules\account\models\PaymentFeatureForm */

use common\models\Feature;
use common\models\FeatureModule;
use common\models\PaymentMetadata;
use common\models\SubscriptionFeature;
use common\widgets\ActiveForm;
use kartik\touchspin\TouchSpin;
use tws\helpers\Url;
use yii\helpers\Html;

$this->title = Yii::t('frontend', 'Payment for {item}', ['item' => Yii::t('common', 'Features')]);
$this->params['breadcrumbs'][] = Html::encode($this->title);
\frontend\modules\account\assets\PaymentAsset::register($this);

$featureModuleLabels = FeatureModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
$paymentSettings = Yii::$app->settings->getCategory('payment');
$activePaymentMethods = array_intersect_key(PaymentMetadata::getPaymentMethodLabels(), (array) $paymentSettings['paymentMethods']);
$activePaymentProcessors = array_intersect_key(PaymentMetadata::getPaymentProcessorLabels(), (array) $paymentSettings['paymentProcessors'][PaymentMetadata::PAYMENT_METHOD_CARD]);
$features = Feature::findAllFeatures();
$parentSubscription = $model->getParentSubscription();
$package = $parentSubscription->package;
$packageTranslation = $package->getTranslation();
/** @var SubscriptionFeature[] $parentSubscriptionFeatures */
$parentSubscriptionFeatures = $parentSubscription->getSubscriptionFeatures()->indexBy('name')->all();

$featureExistsTag = Html::tag('span', null, [
	'class' => 'fa fa-check color-success',
	'title' => Yii::t('frontend', 'This feature is already included in your subscription'),
	'data' => [
		'toggle' => 'tooltip',
	],
]);
Yii::$app->formatter->locale = Yii::$app->language;
?>

<div class="section section-md">
	<div class="container-fluid">
		<header class="section-header">
			<p><?= Yii::t('frontend', 'Add more features to your existing subscription.') ?></p>
		</header>

		<?php $form = ActiveForm::begin([
			'id' => 'payment-form',
			'options' => [
				'novalidate' => true,
			],
			'validateOnType' => true,
		]); ?>
			<?php if ($model->hasErrors() && empty(array_intersect_key($model->getErrors(), array_flip($model->safeAttributes())))): ?>
				<?= $form->errorSummary($model, [
					'header' => false,
					'class' => 'alert alert-danger alert-icon',
				]) ?>
			<?php endif; ?>

			<div class="form-group gap-b-md">
				<label class="control-label"><?= $model->getAttributeLabel('parent_subscription_id') ?></label>
				<div>
					<a href="<?= Url::to(['/account/subscription/view', 'id' => $parentSubscription->id]) ?>" data-popup-action=""><?= $parentSubscription->getFormattedName() ?></a>
				</div>
			</div>

			<div class="panel-group form-group gap-b-md">
				<label class="control-label"><?= Yii::t('common', 'Features') ?></label>
				<div class="panel panel-primary">
					<?php $panelId = rand(); ?>
					<div class="panel-heading" role="tab" id="heading-<?= $panelId ?>">
						<h3 class="panel-title">
							<a class="collapsed" data-toggle="collapse" href="#collapse-<?= $panelId ?>" role="button" aria-expanded="true" aria-controls="collapse-<?= $panelId ?>"><?= Yii::t('common', 'General') ?></a>
						</h3>
					</div>
					<div id="collapse-<?= $panelId ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-<?= $panelId ?>">
						<div class="panel-body table-responsive">
							<div class="row">
								<div class="col-sm-6">
									<?php $feature = $features[Feature::WORKSPACES]; ?>
									<?= $form->field($model, "features[{$feature->name}]")->widget(TouchSpin::class, [
										'options' => [
											'placeholder' => '0',
											'data' => [
												'feature-price' => $feature->price,
											],
										],
										'pluginOptions' => [
											'min' => 0,
											'max' => PHP_INT_MAX,
											'step' => 1,
											'decimals' => 0,
											'boostat' => 5,
											'maxboostedstep' => 10,
											'verticalbuttons' => true,
											'postfix' => '&times; ' . Yii::$app->formatter->asCurrency($feature->price, $parentSubscription->currency),
										],
									])->label($feature->getFormattedName()) ?>
								</div>
								<div class="col-sm-6">
									<?php $feature = $features[Feature::WORKING_POINTS]; ?>
									<?= $form->field($model, "features[{$feature->name}]")->widget(TouchSpin::class, [
										'options' => [
											'placeholder' => '0',
											'data' => [
												'feature-price' => $feature->price,
											],
										],
										'pluginOptions' => [
											'min' => 0,
											'max' => PHP_INT_MAX,
											'step' => 1,
											'decimals' => 0,
											'boostat' => 5,
											'maxboostedstep' => 10,
											'verticalbuttons' => true,
											'postfix' => '&times; ' . Yii::$app->formatter->asCurrency($feature->price, $parentSubscription->currency),
										],
									])->label($feature->getFormattedName()) ?>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-6">
									<?php $feature = $features[Feature::USERS]; ?>
									<?= $form->field($model, "features[{$feature->name}]")->widget(TouchSpin::class, [
										'options' => [
											'placeholder' => '0',
											'data' => [
												'feature-price' => $feature->price,
											],
										],
										'pluginOptions' => [
											'min' => 0,
											'max' => PHP_INT_MAX,
											'step' => 1,
											'decimals' => 0,
											'boostat' => 5,
											'maxboostedstep' => 10,
											'verticalbuttons' => true,
											'postfix' => '&times; ' . Yii::$app->formatter->asCurrency($feature->price, $parentSubscription->currency),
										],
									])->label($feature->getFormattedName()) ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?= $this->render('_shared-form-fields', [
				'form' => $form,
				'model' => $model,
			]) ?>

			<div class="form-group text-center gap-t-lg">
				<button type="submit" class="btn btn-lg btn-default btn-slide-right" id="btn-submit-payment" data-price-format="<?= Yii::$app->formatter->asCurrency(0, $parentSubscription->currency) ?>">
					<span class="btn-submit-label-payment <?= $model->payment_method == PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>">
						<?= Yii::t('common', 'Pay') ?>
					</span>
					<?php if (array_key_exists(PaymentMetadata::PAYMENT_METHOD_BANK, $activePaymentMethods)): ?>
						<span class="btn-submit-label-submit <?= $model->payment_method != PaymentMetadata::PAYMENT_METHOD_CARD ? '' : 'hidden' ?>"><?= Yii::t('common', 'Submit') ?></span>
					<?php endif; ?>
				</button>
			</div>
		<?php ActiveForm::end(); ?>
	</div>
</div>
