<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model backend\models\UserProfileForm */

use backend\widgets\ActiveForm;
use common\widgets\canvas\Canvas;
use common\widgets\clipboard\Clipboard;
use kartik\file\FileInput;
use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('backend', 'Profile');
$this->params['breadcrumbs'][] = $this->title;
?>

<?php $form = ActiveForm::begin([
	'validateOnType' => true,
]); ?>
	<div class="row">
		<div class="col-md-12 col-lg-6">
			<?= $form->field($model, 'imageFile')->widget(FileInput::class, [
				'options' => [
					'accept' => 'image/*',
					'data' => [
						'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					],
				],
				'pluginOptions' => [
					'allowedFileExtensions' => ['jpeg', 'jpg', 'png', 'gif'],
					'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
					'dropZoneEnabled' => true,
					'showClose' => false,
					'showUpload' => false,
					'showCaption' => true,
					'showRemove' => true,
					'showPreview' => true,
					'fileActionSettings' => [
						'showDownload' => true,
						'showRemove' => true,
						'showUpload' => false,
						'showZoom' => true,
						'showDrag' => false,
					],
					'defaultPreviewContent' => Html::img($model->imageUrl ?: Url::to("@web/img/img-placeholder-user.png"), ['class' => 'img-responsive', 'alt' => '']),
					'initialPreview' => $model->image ? $model->imageUrl : false,
					'initialPreviewConfig' => [
						[
							'downloadUrl' => $model->imageUrl,
						],
					],
					'initialPreviewAsData' => true,
					'initialPreviewShowDelete' => true,
					'overwriteInitial' => true,
					'deleteUrl' => Url::to(['/user-manager/user/delete-file', 'id' => $model->id])
				],
			]) ?>
		</div>
        <div class="col-md-12 col-lg-6">
            <div class="signature-wrapper">
		        <?= Html::button('<span class="fa fa-times"></span>', [
			        'type' => 'button',
			        'class' => 'btn btn-sm btn-radius btn-warning btn-clear-signature',
			        'title' => Yii::t('common', 'Clear'),
			        'data' => [
				        'toggle' => 'tooltip',
				        'canvas-clear' => '#' . Html::getInputId($model, 'signature'),
			        ],
		        ]) ?>
		        <?= $form->field($model, 'signature')->widget(Canvas::class, [
			        'containerOptions' => [
				        'class' => 'signature-container',
			        ],
			        'canvasOptions' => [
				        'width' => 300,
				        'height' => 200,
			        ],
			        'clientOptions' => [
				        'responsive' => false,
				        'aspectRatio' => 16/6,

				        'isDrawingMode' => true,
				        'selection' => false,
			        ],
		        ])->label(false)->hint(Yii::t('backend', 'Draw your signature on the screen.')) ?>
            </div>
		</div>
	</div>

	<fieldset class="fieldset">
		<legend><?= Yii::t('common', 'Personal Data') ?></legend>
		<?= $form->field($model, 'gender')->inline()->radioList([
			1 => Yii::t('common', 'Male'),
			2 => Yii::t('common', 'Female'),
		]) ?>
		<div class="row">
			<div class="col-sm-4">
				<?= $form->field($model, 'first_name')->textInput() ?>
			</div>
			<div class="col-sm-4">
				<?= $form->field($model, 'middle_name')->textInput() ?>
			</div>
			<div class="col-sm-4">
				<?= $form->field($model, 'last_name')->textInput() ?>
			</div>
		</div>
	</fieldset>

	<fieldset class="fieldset">
		<legend><?= Yii::t('common', 'Account') ?></legend>
		<div class="row">
			<div class="col-sm-6">
				<?= $form->field($model, 'email')->input('email', ['disabled' => true]) ?>
			</div>
			<div class="col-sm-6">
				<?= $form->field($model, 'phone')->input('tel') ?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<?= $form->field($model, 'new_password')->passwordInput() ?>
			</div>
			<div class="col-sm-6">
				<?= $form->field($model, 'new_password_confirm')->passwordInput() ?>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6">
				<?= $form->field($model, 'role')->textInput(['disabled' => true]) ?>
			</div>
            <?php if (Yii::$app->user->identity->workspace->subscription): ?>
                <div class="col-sm-6">
                    <?= $form->field($model, 'working_point')->textInput(['disabled' => true]) ?>
                </div>
            <?php endif; ?>
		</div>
	</fieldset>

	<div class="form-actions floating">
		<?= Html::submitButton('<span class="fa fa-check"></span>', [
			'class' => 'btn btn-xlg btn-fab btn-success',
			'title' => Yii::t('common', 'Save'),
			'data' => [
				'toggle' => 'tooltip',
			]
		]) ?>
	</div>
<?php ActiveForm::end(); ?>
