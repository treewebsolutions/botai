<?php
/* @var $this yii\web\View */
/* @var $model frontend\models\ContactForm */
/* @var $contact array */
/* @var $address string */
/* @var $map dosamigos\google\maps\Map */

use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<div class="row">
			<div class="col-md-7">
				<?php if ($content = $this->context->currentPage->content): ?>
					<header class="section-header gap-b-xlg"><?= $content ?></header>
				<?php endif; ?>

				<?php $form = ActiveForm::begin([
					'id' => mb_strtolower($model->formName()),
					'options' => [
						'class' => 'contact-form',
					],
				]); ?>
					<?= $form->field($model, 'name')->textInput() ?>
					<div class="row">
						<div class="col-md-6">
							<?= $form->field($model, 'email')->input('email') ?>
						</div>
						<div class="col-md-6">
							<?= $form->field($model, 'phone')->input('tel') ?>
						</div>
					</div>
					<?= $form->field($model, 'subject')->textInput() ?>
					<?= $form->field($model, 'message')->textarea(['rows' => 7]) ?>
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
					<div>
						<span class="pull-right gap-t-xs gap-b-xs"><span class="color-danger">*</span> <?= Yii::t('common', 'Required Fields') ?></span>
						<?= Html::submitButton(Yii::t('common', 'Submit'), [
							'class' => 'btn btn-default btn-slide-right',
							'name' => 'contact-button',
						]) ?>
					</div>
				<?php ActiveForm::end(); ?>
			</div>
			<div class="col-md-offset-1 col-md-4 font-xs">
				<?php if ($contact['company']): ?>
					<div class="card card-xs card-bordered card-shadow gap-t-md">
						<div class="card-header">
							<span class="fa fa-briefcase font-sm gap-r-xs"></span>
							<h3 class="card-heading"><?= Yii::t('frontend', 'Fiscal Data') ?></h3>
						</div>
						<ul class="list-spacing">
							<?php if ($contact['company']): ?>
								<li>
									<?= $contact['company'] ?>
								</li>
							<?php endif; ?>
							<?php if ($contact['tin']): ?>
								<li>
									<?= Yii::t('label', 'Taxpayer Identification Number') ?>:
									<?= $contact['tin'] ?>
								</li>
							<?php endif; ?>
							<?php if ($contact['registrationNumber']): ?>
								<li>
									<?= Yii::t('label', 'Registration Number') ?>:
									<?= $contact['registrationNumber'] ?>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endif; ?>
				<?php if ($contact['schedule'][Yii::$app->language]): ?>
					<div class="card card-xs card-bordered card-shadow gap-t-md">
						<header class="card-header">
							<span class="fa fa-clock-o font-sm gap-r-xs"></span>
							<h3 class="card-heading"><?= Yii::t('label', 'Schedule') ?></h3>
						</header>
						<div><?= nl2br($contact['schedule'][Yii::$app->language]) ?></div>
					</div>
				<?php endif; ?>
				<?php if ($contact['mobilePhone'] || $contact['fixedPhone'] || $contact['fax']): ?>
					<div class="card card-xs card-bordered card-shadow gap-t-md">
						<div class="card-header">
							<span class="fa fa-phone font-sm gap-r-xs"></span>
							<h3 class="card-heading"><?= Yii::t('label', 'Contact') ?></h3>
						</div>
						<ul class="list-spacing">
							<?php if ($contact['mobilePhone']): ?>
								<li>
									<?= Yii::t('label', 'Mobile Phone') ?>:
									<a class="link-underline" href="tel:<?= $contact['mobilePhone'] ?>"><?= $contact['mobilePhone'] ?></a>
								</li>
							<?php endif; ?>
							<?php if ($contact['fixedPhone']): ?>
								<li>
									<?= Yii::t('label', 'Fixed Phone') ?>:
									<a class="link-underline" href="tel:<?= $contact['fixedPhone'] ?>"><?= $contact['fixedPhone'] ?></a>
								</li>
							<?php endif; ?>
							<?php if ($contact['fax']): ?>
								<li>
									<?= Yii::t('label', 'Fax') ?>:
									<a class="link-underline" href="tel:<?= $contact['fax'] ?>"><?= $contact['fax'] ?></a>
								</li>
							<?php endif; ?>
							<?php if ($contact['email']): ?>
								<li>
									<?= Yii::t('label', 'Email') ?>:
									<a class="link-underline" href="mailto:<?= $contact['email'] ?>"><?= $contact['email'] ?></a>
								</li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<?php
	$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>

<?php if ($map): ?>
	<?= $map->display() ?>
<?php endif; ?>
