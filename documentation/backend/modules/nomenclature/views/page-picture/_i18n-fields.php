<?php
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\Picture */
/* @var $language common\models\Language */

use yii\helpers\Html;
?>

<?= $form->field($model, "title[{$language->language_id}]", [
	'options' => [
		'class' => 'form-group' . ($language->language_id == Yii::$app->language ? ' required' : ''),
	],
])->textInput() ?>
