<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\PasswordResetRequestForm */

use backend\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('backend', 'Request Password Reset');
?>

<?php $form = ActiveForm::begin([
	'id' => 'request-password-reset-form',
	'validateOnType' => true,
]); ?>

	<p><?= Yii::t('backend', 'Please fill out your email. A link to reset password will be sent there.') ?></p>

	<?= $form->field($model, 'email')->textInput(['autofocus' => true]) ?>

	<div class="form-actions">
		<?= Html::submitButton(Yii::t('common', 'Send'), ['class' => 'btn btn-block blue']) ?>
	</div>

	<div class="form-links text-center">
		<?= Html::a(Yii::t('backend', 'Login'), ['/site/login']) ?>
	</div>

<?php ActiveForm::end(); ?>
