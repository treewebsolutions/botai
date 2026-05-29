<?php
/* @var $this yii\web\View */
/* @var $model common\models\ActivateAccountForm */

use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<div class="site-form">
	<div class="content-container">
		<div class="row">
			<div class="col-md-offset-4 col-md-4">
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
					<?= $form->field($model, 'token')->textInput() ?>
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
					<?= Html::submitButton(Yii::t('common', 'Activate'), ['class' => 'btn btn-lg btn-block btn-primary btn-slide-right']) ?>
				</div>
				<?php ActiveForm::end(); ?>
				<div class="text-center">
					<p><?= Yii::t('common', 'Already have an account?') ?> <?= Html::a(Yii::t('common', 'Log In'), ['/site/login']) ?></p>
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
