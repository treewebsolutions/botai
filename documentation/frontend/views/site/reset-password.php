<?php
/* @var $this yii\web\View */
/* @var $resetPasswordRequestModel common\models\ResetPasswordRequestForm */
/* @var $resetPasswordModel common\models\ResetPasswordForm */

use common\models\ResetPasswordForm;
use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<div class="site-form">
	<div class="content-container">

		<?php if ($resetPasswordModel->scenario == ResetPasswordForm::SCENARIO_PASSWORD): ?>
			<div class="row">
				<div class="col-md-offset-3 col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading">
							<h2 class="panel-title"><?= Yii::t('common', 'Set your new password') ?></h2>
						</div>
						<div class="panel-body">
							<div class="row">
								<div class="col-md-12">
									<?= $form->field($resetPasswordModel, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
								</div>
								<div class="col-md-12">
									<?= $form->field($resetPasswordModel, 'password_confirm')->passwordInput(['autocomplete' => 'new-password']) ?>
								</div>
							</div>
                            <?= $form->field($resetPasswordModel, 'workEmail', [
                                'options' => [
                                    'class' => 'work-email',
                                ],
                                'template' => '{input}',
                            ])->input('email', ['required' => 'required'])->label(false) ?>
                            <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                                <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                                <?= $form->field($resetPasswordModel, 'captchaResponse', [
                                    'template' => '{input}',
                                ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                            <?php endif; ?>
						</div>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Reset Password'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
			</div>
		<?php else: ?>
			<div class="row">
				<div class="col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordRequestModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading">
							<h2 class="panel-title"><?= Yii::t('common', 'Request a password reset code') ?></h2>
						</div>
						<div class="panel-body">
							<?= $form->field($resetPasswordRequestModel, 'username')->textInput() ?>
                            <?= $form->field($resetPasswordRequestModel, 'workEmail', [
                                'options' => [
                                    'class' => 'work-email',
                                ],
                                'template' => '{input}',
                            ])->input('email', ['required' => 'required'])->label(false) ?>
                            <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                                <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                                <?= $form->field($resetPasswordRequestModel, 'captchaResponse', [
                                    'template' => '{input}',
                                ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                            <?php endif; ?>
						</div>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Request Code'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
				<div class="col-md-6">
					<?php $form = ActiveForm::begin([
						'id' => mb_strtolower($resetPasswordModel->formName()),
						'options' => [
							'novalidate' => true,
							'class' => 'panel panel-default',
						],
						'validateOnType' => true,
						'validateOnBlur' => false,
					]); ?>
						<div class="panel-heading">
							<h2 class="panel-title"><?= Yii::t('common', 'Already have a password reset code?') ?></h2>
						</div>
						<div class="panel-body">
							<?= $form->field($resetPasswordModel, 'token')->textInput() ?>
                            <?= $form->field($resetPasswordModel, 'workEmail', [
                                'options' => [
                                    'class' => 'work-email',
                                ],
                                'template' => '{input}',
                            ])->input('email', ['required' => 'required'])->label(false) ?>
                            <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                                <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                                <?= $form->field($resetPasswordModel, 'captchaResponse', [
                                    'template' => '{input}',
                                ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
                            <?php endif; ?>
						</div>
						<div class="panel-footer text-center">
							<?= Html::submitButton(Yii::t('common', 'Validate Code'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
						</div>
					<?php ActiveForm::end(); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="text-center gap-t-sm">
			<p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login']) ?></p>
		</div>
	</div>
</div>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($resetPasswordModel, 'workEmail') . '").removeAttr("required");
	');
?>
<?php
$this->registerJs('
		$( "#' . Html::getInputId($resetPasswordRequestModel, 'workEmail') . '").removeAttr("required");
	');
?>
