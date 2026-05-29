<?php
/* @var $this yii\web\View */
/* @var $twoFactorAuthenticationModel common\models\TwoFactorAuthenticationForm */

use common\models\TwoFactorAuthenticationForm;
use common\widgets\ActiveForm;
use yii\helpers\Html;
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->translation->content): ?>
			<header class="section-header gap-b-xlg"><?= $content ?></header>
		<?php endif; ?>
		
		<div class="row">
			<div class="col-md-offset-3 col-md-6">
				<?php $form = ActiveForm::begin([
					'id' => mb_strtolower($twoFactorAuthenticationModel->formName()),
					'options' => [
						'novalidate' => true,
						'class' => 'panel panel-default',
					],
					'validateOnType' => true,
					'validateOnBlur' => false,
				]); ?>
				<div class="panel-heading">
					<h2 class="panel-title"><?= Yii::t('common', 'Confirm Login') ?></h2>
				</div>
				<div class="panel-body">
					<?= $form->field($twoFactorAuthenticationModel, 'token')->textInput() ?>
					<?= $form->field($twoFactorAuthenticationModel, 'verifyCode', [
						'options' => [
							'class' => 'form-verify-field',
						],
						'template' => '{input}',
					])->textInput()->label(false) ?>
				</div>
				<div class="panel-footer text-center">
					<?= Html::submitButton(Yii::t('common', 'Confirm Login'), ['class' => 'btn btn-lg btn-block btn-default btn-slide-right']) ?>
				</div>
				<?php ActiveForm::end(); ?>
			</div>
		</div>
	</div>
</div>
