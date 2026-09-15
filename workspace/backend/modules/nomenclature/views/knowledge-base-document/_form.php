<?php

/* @var $this yii\web\View */
/* @var $form backend\widgets\ActiveForm */
/* @var $model backend\modules\nomenclature\models\KnowledgeBaseDocumentForm */

use backend\widgets\ActiveForm;
use common\models\KnowledgeBase;
use common\models\KnowledgeBaseDocument;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
		'enctype' => 'multipart/form-data',
		'class' => Yii::$app->request->isAjax ? 'modal-dialog modal-lg' : '',
	],
	'validateOnType' => true,
]); ?>
	<div class="form-body <?= Yii::$app->request->isAjax ? 'modal-content' : '' ?>">
		<?php if (Yii::$app->request->isAjax): ?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div class="modal-title"><?= $this->title ?></div>
			</div>
		<?php endif; ?>

		<div class="form-fields <?= Yii::$app->request->isAjax ? 'modal-body' : '' ?>">
			<?php if ($model->hasErrors()): ?>
				<div class="alert alert-danger alert-dismissible" role="alert">
					<div class="alert-table">
						<div class="row">
							<div class="icon">
								<span class="glyphicon glyphicon-remove-sign"></span>
							</div>
							<?= $form->errorSummary($model, ['header' => '']) ?>
							<div class="close">
								<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
							</div>
						</div>
					</div>
				</div>
			<?php endif; ?>
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'status')->widget(Select2::class, [
						'data' => ArrayHelper::getColumn(KnowledgeBaseDocument::getStatusLabels(), 'label'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
				<div class="col-sm-8">
					<?= $form->field($model, 'knowledge_base_id')->widget(Select2::class, [
						'data' => ArrayHelper::map(KnowledgeBase::findAllKnowledgeBases(), 'id', 'name'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
							'allowClear' => true,
						],
					]) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'name')->textInput(['maxlength' => true])->hint(Yii::t('common', 'Optional; the file name is used when left empty.')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'upload')->widget(FileInput::class, [
						'options' => [
							'accept' => '.' . implode(',.', KnowledgeBaseDocument::ALLOWED_EXTENSIONS),
						],
						'resizeImages' => false,
						'sortThumbs' => false,
						'purifyHtml' => false,
						'pluginOptions' => [
							'allowedFileExtensions' => KnowledgeBaseDocument::ALLOWED_EXTENSIONS,
							'maxFileSize' => KnowledgeBaseDocument::MAX_FILE_SIZE / 1024,
							'dropZoneEnabled' => true,
							'showClose' => false,
							'showUpload' => false,
							'showCaption' => true,
							'showRemove' => true,
							'showPreview' => false,
							'initialCaption' => $model->file,
						],
					])->hint(Yii::t('common', 'Allowed formats: {formats}. Maximum size: {size}.', [
						'formats' => strtoupper(implode(', ', KnowledgeBaseDocument::ALLOWED_EXTENSIONS)),
						'size' => Yii::$app->formatter->asShortSize(KnowledgeBaseDocument::MAX_FILE_SIZE, 0),
					])) ?>
				</div>
			</div>

		<?php if (Yii::$app->request->isAjax): ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
				<?= Html::submitButton(Yii::t('common', 'Save'), ['class' => 'btn btn-success']) ?>
			</div>
		<?php else: ?>
			<div class="form-actions floating">
				<?= Html::submitButton('<span class="fa fa-check"></span>', [
					'class' => 'btn btn-xlg btn-fab btn-success',
					'title' => Yii::t('common', 'Save'),
					'data' => [
						'toggle' => 'tooltip',
					],
				]) ?>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>
