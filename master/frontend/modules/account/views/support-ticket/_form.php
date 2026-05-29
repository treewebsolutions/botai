<?php
/* @var $this yii\web\View */
/* @var $form common\widgets\ActiveForm */
/* @var $model common\models\SupportTicket */
/* @var $supportTicketCommentModel common\models\SupportTicket */

use common\models\Subscription;
use common\widgets\ActiveForm;
use common\models\SupportTicketComment;
use kartik\file\FileInput;
use tws\widgets\tinymce\TinyMCE;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;
use yii\web\JsExpression;

$shouldRenderModal = Yii::$app->request->isAjax;
$attachments = $model->getAttachments(true, true);
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
		'class' => $shouldRenderModal ? 'modal-dialog modal-lg' : '',
	],
	'validateOnType' => true,
	'validateOnBlur' => false,
]); ?>
	<div class="form-body <?= $shouldRenderModal ? 'modal-content' : '' ?>">
		<?php if ($shouldRenderModal): ?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<div class="modal-title"><?= $this->title ?></div>
			</div>
		<?php endif; ?>

		<div class="form-fields <?= $shouldRenderModal ? 'modal-body' : '' ?>">
			<?php if ($model->hasErrors() && empty(array_intersect_key($model->errors, $model->attributes))): ?>
				<?= $form->errorSummary($model, [
					'header' => false,
					'class' => 'alert alert-danger alert-icon',
				]) ?>
			<?php endif; ?>

			<?php if ($model->isNewRecord): ?>
				<div class="row">
					<div class="col-sm-4">
						<?= $form->field($model, 'support_ticket_department_id')->widget(Select2::class, [
							'data' => ArrayHelper::map(\common\models\SupportTicketDepartment::findAllSupportTicketDepartments(), 'id', 'translation.name'),
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => false,
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
								'placeholder' => Yii::t('common', 'Choose'),
							],
						]) ?>
					</div>
					<div class="col-sm-4">
						<?= $form->field($model, 'support_ticket_priority_id')->widget(Select2::class, [
							'data' => ArrayHelper::map(\common\models\SupportTicketPriority::findAllSupportTicketPriorities(), 'id', 'formattedName'),
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => false,
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
								'placeholder' => Yii::t('common', 'Choose'),
							],
						]) ?>
					</div>
					<div class="col-sm-4">
						<?= $form->field($model, 'subscription_id')->widget(Select2::class, [
							'data' => ArrayHelper::map(Yii::$app->user->identity->subscriber->subscriptions, 'id', 'formattedName', function (Subscription $subscription) {
								return $subscription->parent_id ? Yii::t('common', 'Features') : Yii::t('common', 'Packages');
							}),
							'pluginLoading' => false,
							'pluginOptions' => [
								'allowClear' => true,
								'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
								'placeholder' => Yii::t('common', 'Choose'),
							],
						]) ?>
					</div>
				</div>
				<?= $form->field($model, 'subject')->textInput() ?>
				<?= $form->field($model, 'content')->widget(TinyMCE::class, [
					'clientOptions' => [
						'mode' => 'none',
						'menubar' => false,
						'forced_root_block' => '',
						'plugins' => 'paste autoresize link autolink lists wordcount contextmenu codesample',
						'toolbar1' => 'undo redo | bold italic | link | alignleft aligncenter alignright alignjustify | numlist bullist | codesample | removeformat',
						'paste_as_text' => true,
						'autoresize_on_init' => true,
						'autoresize_min_height' => 100,
						'autoresize_bottom_margin' => 5,
					],
				]) ?>
				<?= $form->field($model, 'attachmentFiles[]')->widget(FileInput::class, [
					'options' => [
						'multiple' => true,
					],
					'resizeImages' => false,
					'sortThumbs' => false,
					'purifyHtml' => false,
					'pluginOptions' => [
						'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
						'maxFileCount' => 5,
						'validateInitialCount' => true,
						'layoutTemplates' => [
							'modalMain' => '',
							'modal' => '',
						],
						'frameClass' => 'krajee-default file-preview-frame-nofigure',
						'dropZoneEnabled' => false,
						'showClose' => false,
						'showUpload' => false,
						'showCaption' => true,
						'showRemove' => true,
						'showPreview' => true,
						'hideThumbnailContent' => false,
						'fileActionSettings' => [
							'showDownload' => false,
							'downloadTitle' => Yii::t('common', 'Download'),
							'showRemove' => false,
							'removeTitle' => Yii::t('common', 'Delete'),
							'showUpload' => false,
							'showZoom' => false,
							'showDrag' => false,
						],
						'preferIconicPreview' => true,
					],
				]) ?>
			<?php else: ?>
				<?= $form->field($model, 'support_ticket_status_id')->widget(Select2::class, [
					'data' => ArrayHelper::map(\common\models\SupportTicketStatus::findAllSupportTicketStatuses(), 'id', 'formattedName'),
					'pluginLoading' => false,
					'pluginOptions' => [
						'allowClear' => false,
						'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
						'placeholder' => Yii::t('common', 'Choose'),
					],
				]) ?>
				<?= $form->field($supportTicketCommentModel, 'content')->widget(TinyMCE::class, [
					'clientOptions' => [
						'mode' => 'none',
						'menubar' => false,
						'forced_root_block' => '',
						'plugins' => 'paste autoresize link autolink lists wordcount contextmenu codesample',
						'toolbar1' => 'undo redo | bold italic | link | alignleft aligncenter alignright alignjustify | numlist bullist | codesample | removeformat',
						'paste_as_text' => true,
						'autoresize_on_init' => true,
						'autoresize_min_height' => 100,
						'autoresize_bottom_margin' => 5,
					],
				])->label(Yii::t('label', 'Reply Message')) ?>
				<?= $form->field($model, 'attachmentFiles[]')->widget(FileInput::class, [
					'options' => [
						'multiple' => true,
						'data' => [
							'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
						],
					],
					'resizeImages' => false,
					'sortThumbs' => false,
					'purifyHtml' => false,
					'pluginOptions' => [
						'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
						'maxFileCount' => 5,
						'validateInitialCount' => true,
						'layoutTemplates' => [
							'modalMain' => '',
							'modal' => '',
						],
						'frameClass' => 'krajee-default file-preview-frame-nofigure',
						'dropZoneEnabled' => false,
						'showClose' => false,
						'showUpload' => false,
						'showCaption' => true,
						'showRemove' => false,
						'showPreview' => true,
						'hideThumbnailContent' => false,
						'fileActionSettings' => [
							'showDownload' => true,
							'downloadTitle' => Yii::t('common', 'Download'),
							'showRemove' => true,
							'removeTitle' => Yii::t('common', 'Delete'),
							'showUpload' => false,
							'showZoom' => false,
							'showDrag' => false,
						],
						'initialPreview' => $attachments ? array_column($attachments, 'url') : false,
						'initialPreviewConfig' => array_map(function ($attachment) {
							return [
								'key' => $attachment['name'],
								'caption' => $attachment['name'],
								'size' => $attachment['size'],
								'downloadUrl' => $attachment['url'],
							];
						}, $attachments),
						'initialPreviewAsData' => false,
						'initialPreviewShowDelete' => true,
						'overwriteInitial' => false,
						'preferIconicPreview' => true,
						'deleteUrl' => Url::to(['/account/support-ticket/delete-file', 'id' => $model->id]),
						'deleteExtraData' => [
							'attribute' => 'attachment',
						],
					],
				]) ?>
				<label class="control-label gap-b-xs"><?= Yii::t('common', 'Messages') ?></label>
				<div class="scroll-container-sm">
					<?php /** @var SupportTicketComment $supportTicketComment */ ?>
					<?php foreach ($model->getSupportTicketComments()->recent()->active()->deleted(false)->all() as $supportTicketComment): ?>
						<?php $isOwner = $supportTicketComment->created_by == Yii::$app->user->id; ?>
						<div class="panel <?= $isOwner ? 'panel-primary' : 'panel-success'?>">
							<div class="panel-heading">
								<div class="media">
									<div class="media-left">
										<img class="media-object media-object-sm rounded bg-white" src="<?= $supportTicketComment->creator->imageUrl ?: Url::to('@web/img/img-placeholder-user.png') ?>" alt="<?= $supportTicketComment->creator->fullName ?>"/>
									</div>
									<div class="media-body">
										<h3 class="panel-title">
											<?= $supportTicketComment->creator->fullName ?> <?= $isOwner ? '' : ('(' . Yii::t('common', 'Staff') . ')') ?>
											<?php if ($supportTicketComment->created_at): ?>
												<div class="font-xxs"><?= Yii::$app->formatter->asDatetime($supportTicketComment->created_at) ?></div>
											<?php endif; ?>
										</h3>
									</div>
								</div>
							</div>
							<div class="panel-body"><?= $supportTicketComment->content ?></div>
						</div>
					<?php endforeach; ?>
					<div class="panel panel-primary">
						<div class="panel-heading">
							<div class="media">
								<div class="media-left">
									<img class="media-object media-object-sm rounded bg-white" src="<?= $model->creator->imageUrl ?: Url::to('@web/img/img-placeholder-user.png') ?>" alt="<?= $model->creator->fullName ?>"/>
								</div>
								<div class="media-body">
									<h3 class="panel-title">
										<?= $model->creator->fullName ?>
										<?php if ($model->created_at): ?>
											<div class="font-xxs"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></div>
										<?php endif; ?>
									</h3>
								</div>
							</div>
						</div>
						<div class="panel-body"><?= $model->content ?></div>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<?php if ($shouldRenderModal): ?>
			<div class="modal-footer">
				<button type="button" class="btn btn-light btn-slide-right" data-dismiss="modal"><?= Yii::t('common', 'Cancel') ?></button>
				<button type="submit" class="btn btn-default btn-slide-right"><?= Yii::t('common', 'Save') ?></button>
			</div>
		<?php else: ?>
			<div class="form-actions floating">
				<?= Html::submitButton('<span class="fa fa-check"></span>', [
					'class' => 'btn btn-xlg btn-fab btn-default',
					'title' => Yii::t('common', 'Save'),
					'data' => [
						'toggle' => 'tooltip',
					],
				]) ?>
			</div>
		<?php endif; ?>
	</div>
<?php ActiveForm::end(); ?>
