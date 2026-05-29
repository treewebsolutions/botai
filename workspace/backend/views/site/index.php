<?php
/* @var $this yii\web\View */

use common\models\master\Feature;
use tws\helpers\Url;
use tws\widgets\carousel\Carousel;
use yii\helpers\Html;

$this->title = Yii::t('backend', 'Dashboard');
$this->params['breadcrumbs'][] = $this->title;
$this->params['bodyAttributes'] = [
	'class' => ['page-container-bg-solid'],
];
?>

<div class="row">
    <?php if (Yii::$app->user->can('viewUser')): ?>
        <div class="col-lg-3 col-md-6">
            <a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/user-manager/default/index']) ?>">
                <div class="visual">
                    <i class="icon-people"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"><?= Yii::t('backend', 'Users') ?></span>
                    </div>
                    <div class="desc"><?= Yii::t('backend', 'Go To Page') ?></div>
                </div>
            </a>
        </div>
    <?php endif; ?>
    <?php if (Yii::$app->user->can('viewUserRole')): ?>
        <div class="col-lg-3 col-md-6">
            <a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/user-manager/role/index']) ?>">
                <div class="visual">
                    <i class="icon-key"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"><?= Yii::t('common', 'Roles') ?></span>
                    </div>
                    <div class="desc"><?= Yii::t('backend', 'Go To Page') ?></div>
                </div>
            </a>
        </div>
    <?php endif; ?>
    <?php if (Yii::$app->user->can('viewLanguage')): ?>
        <?php if (Yii::$app->user->can('updateGeneralSetting')): ?>
            <div class="col-lg-3 col-md-6">
                <a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager']) ?>">
                    <div class="visual">
                        <i class="icon-settings"></i>
                    </div>
                    <div class="details">
                        <div class="number">
                            <span data-counter="counterup"><?= Yii::t('common', 'Settings') ?></span>
                        </div>
                        <div class="desc"><?= Yii::t('backend', 'Go To Page') ?></div>
                    </div>
                </a>
            </div>
        <?php endif; ?>
        <div class="col-lg-3 col-md-6">
            <a class="dashboard-stat dashboard-stat-v2 blue-hoki" href="<?= Url::to(['/setting-manager/language-manager']) ?>">
                <div class="visual">
                    <i class="icon-globe"></i>
                </div>
                <div class="details">
                    <div class="number">
                        <span data-counter="counterup"><?= Yii::t('backend', 'Languages') ?></span>
                    </div>
                    <div class="desc"><?= Yii::t('backend', 'Go To Page') ?></div>
                </div>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php if (Yii::$app->user->can('superAdmin')): ?>
    <?php
        $featureLabels = Feature::getFeatureLabels();
        /** @var \common\models\master\Workspace $workspace */
        $workspace = Yii::$app->user->identity->workspace;
    ?>
    <?php if ($workspace->subscription): ?>
        <!--<div class="panel blue-hoki">
            <div class="panel-title">
                <div class="panel-heading"><?= Yii::t('subscription', 'Subscription Statistics') ?> &mdash; <?= $workspace->subscription->getFormattedName() ?></div>
            </div>
            <div class="panel-body">
                <?php $statistics = $workspace->getWorkspaceSubscriptionFeatureStatistics(Feature::USERS); ?>
                <?php if ($statistics['value']): ?>
                    <div>
                        <div class="margin-bottom-5 bold"><?= $featureLabels[Feature::USERS] ?> (<?= $statistics['quota'] ?>/<?= $statistics['value'] ?>)</div>
                        <div class="progress">
                            <div class="progress-bar progress-bar-<?= $statistics['color'] ?>" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $statistics['usagePercentage'] ?>" style="width: <?= $statistics['usagePercentage'] ?>%;"><?= $statistics['usagePercentage'] ?>%</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>-->
    <?php endif; ?>
<?php endif; ?>

<div class="row">
	<div class="col-md-12">
		<div class="input-group">
			 <span id="copyButton" class="input-group-addon btn-primary" title="<?= Yii::t('common', 'Click To Copy') ?>">
		      <i class="fa fa-clipboard" aria-hidden="true"></i>
		    </span>
			<input type="text" id="copyTarget" class="form-control" value='<script id="chat-embed" src="<?= implode('/', [
				Yii::$app->request->hostInfo,
				Yii::$app->user->identity->workspace->url,
				'embed',
				'api'
			]) ?>" defer="defer" data-language="<?= Yii::$app->language ?>" data-visible="<?= Yii::$app->settings->get('chatVisible', 'interface') ?>" data-expanded="<?= Yii::$app->settings->get('chatExpanded', 'interface') ?>" data-remove="<?= Yii::$app->settings->get('chatRemove', 'interface') ?>"></script>'>
			<span class="copied"><?= Yii::t('common', 'Copied') ?></span>
		</div>
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
				$(target).closest("div").find(".copied").animate({ top: -25, opacity: 0 }, 700, function() {
					$(this).css({ top: 0, opacity: 1 });
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

