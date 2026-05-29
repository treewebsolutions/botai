<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model common\models\ResetPasswordForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = Yii::t('backend', 'Reset Password');
?>

<?php $form = ActiveForm::begin([
	'id' => 'reset-password-form',
	'validateOnType' => true,
]); ?>

	<?= $form->field($model, 'password')->passwordInput(['autofocus' => true])->label(Yii::t('backend', 'Please enter your new password')) ?>

	<div class="form-actions">
		<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-block blue']) ?>
	</div>

	<div class="form-links text-center">
		<?= Html::a(Yii::t('backend', 'Login'), ['/site/login']) ?>
	</div>

<?php ActiveForm::end(); ?>
