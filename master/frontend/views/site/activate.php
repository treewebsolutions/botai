<?php
/* @var $this yii\web\View */
/* @var $model common\models\ActivateAccountForm */

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
					],
					'validateOnType' => true,
					'validateOnBlur' => false,
				]); ?>
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
					<div class="text-center">
						<button type="submit" class="btn btn-lg btn-default btn-slide-right"><?= Yii::t('common', 'Activate') ?></button>
					</div>
				<?php ActiveForm::end(); ?>
				<div class="text-center gap-t-sm">
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
