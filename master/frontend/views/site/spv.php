<?php
/* @var $this yii\web\View */
/* @var $model common\models\ActivateAccountForm */

use common\widgets\ActiveForm;
use tws\helpers\Url;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = Html::encode($this->title);
?>
<h1 class="hidden"><?= $this->title ?></h1>
<div class="section section-md">
	<div class="container-fluid">
		<?php if (Yii::$app->user->identity->authAssignment->item_name === 'superAdmin' || !empty($workspace)): ?>
			<?php if (empty($integration)): ?>
				<div class="row gap-b-xlg">
					<div class="col-md-12">
						<?= $this->context->currentPage->content ?>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6">
						<?php $form = ActiveForm::begin([
							'action' =>['/spv'],
							'id' => 'spv-form-authorize',
							'options' => [
								'novalidate' => true,
								'class' => 'panel panel-default',
							],
							'validateOnType' => true,
							'validateOnBlur' => false,
						]); ?>
						<div class="panel-heading">
							<h2 class="panel-title text-uppercase"><?= Yii::t('frontend', 'I have access with a digital certificate') ?></h2>
						</div>
						<div class="panel-body">
							<p>
								<?= Yii::t('frontend', 'Choose this option if you have a digital certificate and access to S.P.V. for this company.') ?>
							</p>
						</div>
						<div class="panel-footer">
							<?= Html::a($anchor, $link, ['class' => 'btn btn-lg btn-block btn-default btn-slide-up']) ?>
						</div>
						<?php ActiveForm::end() ?>
					</div>
					<div class="col-md-6">
						<?php $form = ActiveForm::begin([
							'options' => [
								'novalidate' => true,
								'class' => 'panel panel-default',
							],
							'validateOnType' => true,
							'validateOnBlur' => false,
						]); ?>
						<div class="panel-heading">
							<h2 class="panel-title text-uppercase"><?= Yii::t('frontend', 'Obtain access through the accountant') ?></h2>
						</div>
						<div class="panel-body">
							<p>
								<?= Yii::t('frontend', 'Copy and send the link below to your accountant so they can authorize your {item} account in S.P.V.', ['item' => Yii::$app->name]) ?>
							</p>
						</div>
						<div class="panel-footer">
							<div class="input-group">
								 <span id="copyButton" class="input-group-addon btn-default" title="<?= Yii::t('common', 'Click To Copy') ?>">
							      <i class="fa fa-clipboard" aria-hidden="true"></i>
							    </span>
								<input type="text" id="copyTarget" class="form-control" value="<?= $link ?>">
								<span class="copied"><?= Yii::t('common', 'Copied') ?></span>
							</div>
						</div>
						<?php ActiveForm::end() ?>
					</div>
				</div>
			<?php else: ?>
				<div class="alert alert-success alert-icon gap-t-md" role="alert"><?= Yii::t('common', 'The authorization of account access for {item} in S.P.V. is completed.', ['item' => Yii::$app->name]) ?></div>
				<div class="text-center">
					<a class="btn btn-lg btn-default btn-slide-up gap-t-lg" href="<?= Url::to(['/site/index']) ?>"><?= Yii::t('frontend', 'Go to Home Page') ?></a>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php
$this->registerJs('
	(function() {
		"use strict";
		function copyToClipboard(elem) {
			var target = elem;
			// select the content
			var currentFocus = document.activeElement;
			target.focus();
			target.setSelectionRange(0, target.value.length);
			// copy the selection
			var succeed;
			try {
				succeed = document.execCommand("copy");
			} catch (e) {
				console.warn(e);
				succeed = false;
			}
			// Restore original focus
			if (currentFocus && typeof currentFocus.focus === "function") {
				currentFocus.focus();
			}
			if (succeed) {
				$(".copied").animate({ top: -25, opacity: 1 }, 700, function() {
					$(this).css({ top: 0, opacity: 0 });
				});
			}
			return succeed;
		}
		$("#copyButton, #copyTarget").on("click", function() {
			copyToClipboard(document.getElementById("copyTarget"));
		});
	})();
');
?>