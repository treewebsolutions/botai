<?php
/* @var $this yii\web\View */
/* @var $model frontend\models\TestimonialForm */
/* @var $dataProvider yii\data\ActiveDataProvider */

use kartik\rating\StarRating;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\widgets\LinkPager;
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<?php if ($testimonials = $dataProvider->getModels()): ?>
			<?php foreach ($testimonials as $testimonial) : ?>
			<?php /** @var common\models\Testimonial $testimonial */ ?>
			<?php $testimonialTranslation = $testimonial->getTranslation(); ?>
				<div class="card card-xs card-bordered gap-t-md">
					<header class="card-header">
						<?= StarRating::widget([
							'id' => 'rating-' . rand(),
							'name' => '',
							'value' => $testimonial->rating ?: 0,
							'pluginOptions' => [
								'displayOnly' => true,
								'showCaption' => false,
								'showClear' => false,
								'size' => 'xxs',
								'min' => 0,
								'max' => 5,
								'stars' => 5,
								'step' => 1,
							],
						]) ?>
					</header>
					<div class="feedback-block-caption"><?= $testimonial->translation->message ?></div>
					<footer class="card-footer">
						<div class="font-bold gap-t-sm">&mdash; <?= implode(', ', array_filter([$testimonial->name, $testimonialTranslation->role, $testimonial->organization])) ?></div>
					</footer>
				</div>
			<?php endforeach; ?>

			<div class="text-center gap-t-md">
				<?= LinkPager::widget([
					'options' => [
						'class' => 'pagination gap0',
					],
					'pagination' => $dataProvider->pagination,
					'maxButtonCount' => 5,
					'registerLinkTags' => true,
					'prevPageLabel' => '&lsaquo;',
					'nextPageLabel' => '&rsaquo;',
					'firstPageLabel' => '&laquo;',
					'lastPageLabel' => '&raquo;',
				]) ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php if (!Yii::$app->user->isGuest): ?>
	<section class="section section-md bg-light">
		<div class="container-fluid">
			<header class="section-header text-center">
				<h2 class="section-heading color-primary text-uppercase"><?= Yii::t('frontend', 'SECTION_TESTIMONIALS_FORM_TITLE') ?></h2>
				<h3 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_TESTIMONIALS_FORM_DESCRIPTION') ?></h3>
			</header>
			<?php $form = ActiveForm::begin([
				'id' => 'testimonial-form',
				'options' => [
					'class' => 'testimonial-form',
				],
			]); ?>
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'name')->textInput([
						'placeholder' => Yii::t('common', 'Example') . ': Pop Ioan',
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'phone')->input('tel', [
						'placeholder' => Yii::t('common', 'Example') . ': ' . ((Yii::$app->settings->get('mobilePhone') ?: Yii::$app->settings->get('fixedPhone')) ?: '0256 123 456'),
					]) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'organization')->textInput([
						'placeholder' => Yii::t('common', 'Example') . ': ' . Yii::$app->name,
					]) ?>
				</div>
			</div>
			<?= $form->field($model, 'message')->textarea([
				'rows' => 7,
				'placeholder' => Yii::t('common', 'Example') . ': ' . Yii::t('frontend', '{0} rocks!', Yii::$app->name),
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
			<?= $form->field($model, 'rating')->widget(StarRating::class, [
				'pluginOptions' => [
					'displayOnly' => false,
					'showCaption' => false,
					'showClear' => false,
					'size' => 'sm',
					'min' => 0,
					'max' => 5,
					'stars' => 5,
					'step' => 1,
				],
			]) ?>
			<div>
				<?= Html::submitButton(Yii::t('common', 'Submit'), [
					'class' => 'btn btn-default',
					'name' => 'contact-button',
				]) ?>
				<span class="pull-right">
					<span class="color-danger">*</span> <?= Yii::t('common', 'Required Fields') ?>
				</span>
			</div>
			<?php ActiveForm::end(); ?>
		</div>
	</section>
<?php endif; ?>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>

