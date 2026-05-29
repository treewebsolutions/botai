<?php
/* @var $this yii\web\View */
/* @var $model common\models\Subscription */

use common\models\Feature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var \common\models\SubscriptionFeature[] $subscriptionFeatures */
$subscriptionFeatures = ArrayHelper::index($model->subscriptionFeatures, 'name');
$featureModuleLabels = FeatureModule::getModuleLabels();
$featureLabels = Feature::getFeatureLabels();
?>

<?php $accordionId = rand(); ?>
<div class="panel-group" id="accordion-<?= $accordionId ?>" role="tablist" aria-multiselectable="true">
	<div class="panel panel-primary">
		<?php $panelId = rand(); ?>
		<div class="panel-heading" role="tab" id="heading-<?= $panelId ?>">
			<h3 class="panel-title">
				<a class="collapsed" data-toggle="collapse" data-parent="#accordion-<?= $accordionId ?>" href="#collapse-<?= $panelId ?>" role="button" aria-expanded="true" aria-controls="collapse-<?= $panelId ?>"><?= Yii::t('common', 'General') ?></a>
			</h3>
		</div>
		<div id="collapse-<?= $panelId ?>" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="heading-<?= $panelId ?>">
			<div class="panel-body">
				<table class="table table-bordered table-condensed">
					<tbody>
						<tr>
							<td class="col-autowidth"><?= $featureLabels[Feature::WORKSPACES] ?></td>
							<td><?= $subscriptionFeatures[Feature::WORKSPACES]->value ?: 0 ?><?= $subscriptionFeatures[Feature::WORKSPACES]->price > 0? ' x ' . Yii::$app->formatter->asCurrency($subscriptionFeatures[Feature::WORKSPACES]->price, $subscriptionFeatures[Feature::WORKSPACES]->currency) : '' ?></td>
						</tr>
						<tr>
							<td class="col-autowidth"><?= $featureLabels[Feature::WORKING_POINTS] ?></td>
							<td><?= $subscriptionFeatures[Feature::WORKING_POINTS]->value ?: 0 ?><?= $subscriptionFeatures[Feature::WORKING_POINTS]->price > 0 ? ' x ' . Yii::$app->formatter->asCurrency($subscriptionFeatures[Feature::WORKING_POINTS]->price, $subscriptionFeatures[Feature::WORKING_POINTS]->currency) : '' ?></td>
						</tr>
						<tr>
							<td class="col-autowidth"><?= $featureLabels[Feature::USERS] ?></td>
							<td><?= $subscriptionFeatures[Feature::USERS]->value ?: 0 ?><?= $subscriptionFeatures[Feature::USERS]->price > 0 ? ' x ' . Yii::$app->formatter->asCurrency($subscriptionFeatures[Feature::USERS]->price, $subscriptionFeatures[Feature::USERS]->currency) : '' ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
