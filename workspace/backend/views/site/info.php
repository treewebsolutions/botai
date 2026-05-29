<?php
/* @var $this yii\web\View */
/* @var $data array */

use tws\helpers\Url;
use yii\helpers\Html;
use yii\helpers\Inflector;

$this->title = Yii::t('common', 'Info');

/** @var \common\models\User $user */
$user = Yii::$app->user->identity;
$workspace = $user->workspace;
$subscription = $workspace->subscription;
$subscriber = $subscription->subscriber;

// TODO: update the below messages to be more specific for regular user and for workspace owner user (display direct links to master app).
?>
<h1 class="page-title"><?= Yii::t('common', 'Something happened...') ?></h1>

<?php if ($data['category'] == 'workspace.inactive'): ?>
	<div class="note note-info">
		<h4 class="block"><?= Yii::t('common', 'This workspace is not active') ?></h4>
		<p><?= Yii::t('common', 'Please contact us if you think this is a problem.') ?></p>
	</div>
<?php endif; ?>

<?php if ($data['category'] == 'subscription.inactive'): ?>

	<div class="note note-info">
		<h4 class="block"><?= Yii::t('common', 'Your subscription is not active') ?></h4>
		<p><?= Yii::t('common', 'Consider activating your subscription in order to use the app.') ?></p><br>
		<?php if (Yii::$app->user->identity->authAssignment->item_name == 'superAdmin'): ?>
			<?php if (in_array($subscription->status, [\common\models\master\Subscription::STATUS_INACTIVE, \common\models\master\Subscription::STATUS_SUSPENDED, \common\models\master\Subscription::STATUS_INCOMPLETE])): ?>
				<?php $url = Url::to(['account/payment/subscription', 'id' => Yii::$app->security->maskToken((string) $subscription->id)], true, '@master/frontend') ?>
				<?= Html::a(Yii::t('common','Update'), $url, ['class' => 'btn btn-primary']) ?>
			<?php else: ?>
				<?php $url = Url::to(['account/subscription/index'], true, '@master/frontend') ?>
				<?= Html::a(Yii::t('common','Update'), $url, ['class' => 'btn btn-primary']) ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>

<?php endif; ?>

<?php if ($data['category'] == 'feature.access'): ?>
	<div class="note note-info">
		<h4 class="block"><?= Yii::t('common', 'You don\'t have access to this feature.') ?></h4>
		<p><?= Yii::t('common', 'Consider upgrading your subscription or extend limit in order to use this feature.') ?></p>
	</div>
<?php endif; ?>

<?php if ($data['category'] == 'workingPoint.outOfSchedule'): ?>
	<div class="note note-info">
		<h4 class="block"><?= Yii::t('backend', 'You cannot work outside the schedule.') ?></h4>
	</div>
<?php endif; ?>
