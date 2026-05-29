<?php
/* @var $this yii\web\View */
/* @var $model common\models\User */

use common\models\User;
use common\widgets\ActiveForm;
use tws\widgets\datetimepicker\DateTimePicker;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>

<div class="section section-md">
	<div class="container-fluid">
		<?php if ($content = $this->context->currentPage->content): ?>
			<header class="section-header">
				<?= $content ?>
			</header>
		<?php endif; ?>

		<?php $form = ActiveForm::begin([
			'id' => mb_strtolower($model->formName()),
			'options' => [
				'class' => 'profile-form',
				'novalidate' => true,
			],
			'validateOnType' => true,
			'validateOnBlur' => false,
		]); ?>
			<div class="row">
				<div class="col-sm-4 col-md-3 gap-b-sm">
					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title">
								<?= $model->fullName ?>
								<div class="font-xs opacity-80 text-ellipsis">(<?= $model->email ?>)</div>
							</h3>
						</div>
						<div class="panel-body">
							<?= FileInput::widget([
								'options' => [
									'accept' => 'image/*',
									'data' => [
										'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
									],
								],
								'model' => $model,
								'attribute' => 'imageFile',
								'resizeImages' => false,
								'sortThumbs' => false,
								'purifyHtml' => false,
								'pluginOptions' => [
									'allowedFileExtensions' => ['jpg', 'png', 'gif'],
									'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
									'browseClass' => 'btn btn-block btn-default btn-outline btn-slide-right',
									'browseIcon' => '<span class="fa fa-camera"></span> ',
									'browseLabel' => Yii::t('common', 'Change Image'),
									'uploadUrl' => Url::to(['/account/profile/upload-file']),
									'uploadExtraData' => [
										'attribute' => 'image',
										'fileName' => "{$model->formName()}[imageFile]",
									],
									'showUploadedThumbs' => true,
									'showAjaxErrorDetails' => false,
									'previewClass' => 'file-preview-custom',
									'frameClass' => 'file-preview-frame-custom',
									'layoutTemplates' => [
										'footer' => '',
										'progress' => '',
									],
									'dropZoneEnabled' => false,
									'showClose' => false,
									'showUpload' => false,
									'showCaption' => false,
									'showRemove' => false,
									'showCancel' => false,
									'showPreview' => true,
									'fileActionSettings' => [
										'showDownload' => false,
										'showRemove' => false,
										'showUpload' => false,
										'showZoom' => false,
										'showDrag' => false,
									],
									'initialPreview' => $model->imageUrl ?: Url::to('@web/img/img-placeholder-user.png'),
									'initialPreviewAsData' => true,
									'overwriteInitial' => true,
								],
								'pluginEvents' => [
									'filebatchselected' => new \yii\web\JsExpression('function () {
										$(this).fileinput("upload");
									}'),
								],
							]) ?>
						</div>
					</div>
					<div class="panel panel-default gap-t-md">
						<div class="panel-heading">
							<h3 class="panel-title"><?= Yii::t('common', 'Notifications') ?></h3>
						</div>
						<div class="panel-body">
							<?= $form->field($model, 'marketing_recipient')->checkbox() ?>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title"><?= Yii::t('common', 'Security') ?></h3>
						</div>
						<div class="panel-body">
							<?= $form->field($model, 'new_password')->passwordInput(['autocomplete' => 'new-password']) ?>
							<?= $form->field($model, 'new_password_confirm')->passwordInput(['autocomplete' => 'new-password']) ?>
						</div>
					</div>
				</div>
				<div class="col-sm-8 col-md-9">
					<?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
						<?= $form->errorSummary($model, [
							'header' => false,
							'class' => 'alert alert-danger alert-icon',
						]) ?>
					<?php endif; ?>

					<div class="panel panel-default">
						<div class="panel-heading">
							<h3 class="panel-title"><?= Yii::t('common', 'Personal Data') ?></h3>
						</div>
						<div class="panel-body">
							<div class="row">
								<div class="col-md-4">
									<?= $form->field($model, 'last_name')->textInput() ?>
								</div>
								<div class="col-md-4">
									<?= $form->field($model, 'first_name')->textInput() ?>
								</div>
								<div class="col-md-4">
									<?= $form->field($model, 'middle_name')->textInput() ?>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<?= $form->field($model, 'phone')->input('tel') ?>
								</div>
								<div class="col-md-4">
									<?= $form->field($model, 'pin')->textInput() ?>
								</div>
								<div class="col-md-4">
									<?= $form->field($model, 'gender')->radioList(User::getGenderLabels(), [
										'class' => 'radio-inline',
									]) ?>
								</div>
							</div>
							<div class="row">
								<div class="col-md-4">
									<?= $form->field($model, 'date_of_birth')->widget(DateTimePicker::class, [
										'options' => [
											'value' => $model->date_of_birth ? Yii::$app->formatter->asDate($model->date_of_birth) : null,
											'placeholder' => Yii::$app->settings->get('dateFormat'),
										],
										'clientOptions' => [
											'format' => 'icu:' . Yii::$app->settings->get('dateFormat'),
											'maxDate' => (new DateTime)->format(DATE_ATOM),
											'ignoreReadonly' => true,
											'showTodayButton' => false,
											'showClear' => true,
											'showClose' => true,
											'allowInputToggle' => true,
											'useCurrent' => false,
										],
									]) ?>
								</div>
							</div>
							<fieldset class="fieldset">
								<legend><?= Yii::t('label', 'Address') ?></legend>
								<div class="row">
									<div class="col-md-4">
										<?= $form->field($model, 'street_name')->textInput() ?>
									</div>
									<div class="col-md-4">
										<?= $form->field($model, 'street_number')->textInput() ?>
									</div>
									<div class="col-md-4">
										<?= $form->field($model, 'locality')->textInput() ?>
									</div>
								</div>
								<div class="row">
									<div class="col-md-4">
										<?= $form->field($model, 'zip_code')->textInput() ?>
									</div>
									<div class="col-md-4">
										<?= $form->field($model, 'county')->textInput() ?>
									</div>
									<div class="col-md-4">
										<?= $form->field($model, 'country')->widget(Select2::class, [
											'data' => ArrayHelper::map(\common\models\Country::findAllCountries(), 'iso_alpha2', 'translation.name'),
											'pluginLoading' => false,
											'pluginOptions' => [
												'allowClear' => true,
												'placeholder' => Yii::t('common', 'Choose'),
											],
										]) ?>
									</div>
								</div>
							</fieldset>
						</div>
					</div>
					<div class="gap-t-lg text-center">
						<button type="submit" class="btn btn-default btn-slide-right"><?= Yii::t('common', 'Save') ?></button>
					</div>
                </div>
			</div>
		<?php ActiveForm::end(); ?>
	</div>
</div>
