<?php
/* @var $this yii\web\View */
/* @var $model common\models\SupportTicket */

use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('common', 'View {item}', ['item' => Yii::t('common', 'Support Ticket') . " (#{$model->getDocumentSeriesNumber()}) - {$model->subject}"]);
?>

<?php if (Yii::$app->request->isAjax): ?>
<div class="modal-dialog modal-lg">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<div class="modal-title"><?= $this->title ?></div>
		</div>
		<div class="modal-body">
<?php endif; ?>

		<?php /** @var common\models\SupportTicketComment $supportTicketComment */ ?>
		<?php foreach ($model->getSupportTicketComments()->recent()->active()->deleted(false)->all() as $supportTicketComment): ?>
			<?php $isOwner = $supportTicketComment->created_by == Yii::$app->user->id; ?>
			<div class="panel <?= $isOwner ? 'panel-primary' : 'panel-success'?>">
				<div class="panel-heading">
					<div class="media">
						<div class="media-left">
							<img class="media-object media-object-sm rounded bg-white" src="<?= $supportTicketComment->creator->getImageUrl() ?: Url::to("@web/img/img-placeholder-user.png") ?>" alt="<?= $supportTicketComment->creator->getFullName() ?>"/>
						</div>
						<div class="media-body">
							<h3 class="panel-title">
								<?= $supportTicketComment->creator->getFullName() ?> <?= $isOwner ? '' : ('(' . Yii::t('common', 'Staff') . ')') ?>
								<div class="font-xxs"><?= Yii::$app->formatter->asDatetime($supportTicketComment->created_at) ?></div>
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
						<img class="media-object media-object-sm rounded bg-white" src="<?= $model->creator->getImageUrl() ?: Url::to("@web/img/img-placeholder-user.png") ?>" alt="<?= $model->creator->getFullName() ?>"/>
					</div>
					<div class="media-body">
						<h3 class="panel-title">
							<?= $model->creator->getFullName() ?>
							<div class="font-xxs"><?= Yii::$app->formatter->asDatetime($model->created_at) ?></div>
						</h3>
					</div>
				</div>
			</div>
			<div class="panel-body"><?= $model->content ?></div>
		</div>

<?php if (Yii::$app->request->isAjax): ?>
		</div>
		<div class="modal-footer">
			<button type="button" class="btn btn-light btn-slide-right" data-dismiss="modal"><?= Yii::t('common', 'Close') ?></button>
		</div>
	</div>
</div>
<?php endif; ?>
