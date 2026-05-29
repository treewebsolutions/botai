<?php
/* @var $this yii\web\View */
/* @var $model common\models\LoginForm */

use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header gap-b-xlg"><?= $content ?></header>
		<?php endif; ?>

		<div class="row">
			<div class="col-md-offset-3 col-md-6">
				<?php $form = ActiveForm::begin([
					'id' => mb_strtolower($model->formName()),
					'options' => [
						'novalidate' => true,
						'class' => 'panel panel-default',
					],
					'validateOnType' => true,
					'validateOnBlur' => false,
				]); ?>
					<div class="panel-body">
						<?= $form->field($model, 'username')->input('email') ?>
						<?= $form->field($model, 'password')->passwordInput() ?>
						<div class="clearfix form-group">
							<?= $form->field($model, 'rememberMe', [
								'options' => [
									'class' => 'pull-left',
								],
							])->checkbox() ?>
							<?= Html::a(Yii::t('common', 'Forgot Password?'), ['/site/reset-password'], ['class' => 'pull-right']) ?>
						</div>
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
						<?= Html::submitButton(Yii::t('common', 'Log In'), ['class' => 'btn btn-lg btn-block btn-default btn-slide-right']) ?>
					</div>
				<?php ActiveForm::end(); ?>
				<div class="text-center gap-t-sm">
					<p><?= Yii::t('common', 'Don\'t have an account yet?') ?> <?= Html::a(Yii::t('common', 'Sign Up'), ['/site/signup']) ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>