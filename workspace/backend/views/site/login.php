<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model common\models\LoginForm */

use backend\widgets\ActiveForm;
use yii\helpers\Html;

$this->title = Yii::t('backend', 'Authentication');
?>

<?php $form = ActiveForm::begin([
	'id' => 'login-form',
	'validateOnType' => true,
]); ?>

	<?= $form->field($model, 'username')->textInput(['autofocus' => true])->label(Yii::t('common', 'Email') . ' / ' . Yii::t('common', 'Phone')) ?>

	<?= $form->field($model, 'password')->passwordInput() ?>

	<?= $form->field($model, 'rememberMe')->checkbox() ?>

	<div class="form-actions">
		<?= Html::submitButton('<span class="fa fa-sign-in"></span> ' . Yii::t('common', 'Login'), ['class' => 'btn btn-block blue']) ?>
	</div>

	<div class="form-links text-center">
		<?= Html::a(Yii::t('backend', 'Reset your password'), ['/site/request-password-reset']) ?>
	</div>

<?php ActiveForm::end(); ?>
