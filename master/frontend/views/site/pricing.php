<?php
/* @var $this \yii\web\View */

use common\models\Feature;
use common\models\Package;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use common\models\Survey;
use common\widgets\datatable\DataTable;
use tws\helpers\Url;
use yii\helpers\Html;

Yii::$app->formatter->locale = Yii::$app->language;
?>

<?php if ($content = $this->context->currentPage->content): ?>
<div class="section section-md">
	<div class="container-fluid">
		<?= $content ?>
	</div>
</div>
<?php endif; ?>

<div class="section section-md bg-light">
	<div class="container-fluid">
		<header class="section-header text-center">
			<h1 class="section-heading color-primary text-uppercase"><?= Yii::t('frontend', 'SECTION_COMPAREPACKAGES_TITLE') ?></h1>
			<h2 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_COMPAREPACKAGES_DESCRIPTION') ?></h2>
		</header>

		<?php
		$featureModuleLabels = FeatureModule::getModuleLabels();
		$featureLabels = Feature::getFeatureLabels();
		$packages = Package::findPaidPackages();
		$booleanFeatures = [];

		// Group PackageFeatures by name for all Package models
		$packagesFeatures = [];
		foreach ($packages as $package) {
			foreach ($package->packageFeatures as $packageFeature) {
				// Set the module name
				$groupLabel = Yii::t('label', 'General');
				if ($packageFeature->feature && $packageFeature->feature->featureModule) {
					$groupLabel = $featureModuleLabels[$packageFeature->feature->featureModule->name];
				}

				// Format the value
				$value = $packageFeature->value;
				if (in_array($packageFeature->name, $booleanFeatures)) {
					$value = Html::tag('span', null, ['class' => 'fa ' . ($value ? 'fa-check color-success' : 'fa-times color-danger')]);
				} elseif ($value == 0) {
					$value = Html::tag('span', null, ['class' => 'fa fa-times color-danger']);
				}

				$packagesFeatures[$packageFeature->name]['rowGroup'] = $groupLabel;
				$packagesFeatures[$packageFeature->name]['feature'] = $featureLabels[$packageFeature->name] . ' <small>(' . Yii::t('frontend', 'Unit Price: {0}', [Yii::$app->formatter->asCurrency($packageFeature->feature->price, $packageFeature->feature->currency)]) . ')<small>';
				$packagesFeatures[$packageFeature->name]['packages'][$package->id] = $value;
			}
		}

		// Build DataTable data set
		$dataSet = [];
		foreach ($featureLabels as $featureName => $featureLabel) {
			$row = $packagesFeatures[$featureName];

			$dataSet[] = array_replace([
				'rowGroup' => $row['rowGroup'],
				'feature' => $row['feature'],
			], (array)$row['packages']);
		}

		// Build DataTable columns
		$dtColumns = [];
		foreach ($packages as $package) {
			$dtColumns[] = [
				'data' => $package->id,
				'title' => $package->translation->name,
			];

			// Build buttons table row
			if ($package->type == Package::TYPE_STANDARD) {
				if (Yii::$app->user->isGuest) {
					$buttonsRow[$package->id] = Html::a(Yii::t('common', 'Sign Up'), ['/site/signup', 'package_id' => Yii::$app->security->maskToken((string) $package->id)], [
						'class' => 'btn btn-block btn-default btn-outline btn-slide-right'
					]);
				} elseif (Yii::$app->user->identity->subscriber) {
					$buttonsRow[$package->id] = Html::a(Yii::t('common', 'Buy Now'), ['/account/payment/package', 'id' => Yii::$app->security->maskToken((string) $package->id)], [
						'class' => 'btn btn-block btn-default btn-outline btn-slide-right'
					]);
				} else {
					$buttonsRow[$package->id] = Html::a(Yii::t('common', 'Subscribe'), ['/site/subscribe', 'package_id' => Yii::$app->security->maskToken((string) $package->id)], [
						'class' => 'btn btn-block btn-default btn-outline btn-slide-right'
					]);
				}
			} elseif ($package->type == Package::TYPE_CUSTOM) {
				$buttonsRow[$package->id] = Html::a(Yii::t('common', 'Contact Us'), ['/site/contact', 'request' => 'enterprise'], [
					'class' => 'btn btn-block btn-default btn-outline btn-slide-right',
				]);
			}
		}

		if (isset($buttonsRow)) {
			$dataSet[] = array_replace([
				'rowGroup' => Yii::t('frontend', 'Are you decided?'),
				'feature' => '',
			], $buttonsRow);
		}
		?>

		<div class="table-responsive">
			<?= DataTable::widget([
				'id' => 'dt-pricing',
				'options' => [
					'class' => 'table table-bordered dt-pricing',
				],
				'showColumnFilters' => true,
				'clientOptions' => [
					'data' => $dataSet,
					'deferRender' => true,
					'processing' => true,
					'serverSide' => false,
					'dom' => 't',
					'paging' => false,
					'searching' => false,
					'ordering' => false,
					'autoWidth' => false,
//					'scrollX' => true,
//					'scrollCollapse' => true,
//					'fixedColumns' => [
//						'leftColumns' => 1,
//					],
					'rowGroup' => [
						'dataSrc' => 'rowGroup',
					],
					'columns' => array_merge([
						[
							'data' => 'feature',
							'title' => Yii::t('label', 'Feature'),
							'width' => '260px',
						],
					], $dtColumns),
				],
			]) ?>
		</div>
	</div>
</div>

<?php if ($survey = Survey::find()->andWhere(['type' => Survey::TYPE_FAQ])->default()->active()->deleted(false)->one()): ?>
	<section class="section section-md section-faq">
		<div class="container-fluid">
			<header class="section-header text-center">
				<h1 class="section-heading color-primary text-uppercase"><?= Yii::t('frontend', 'SECTION_FAQ_TITLE') ?></h1>
				<h2 class="section-subheading color-primary"><?= Yii::t('frontend', 'SECTION_FAQ_DESCRIPTION') ?></h2>
			</header>
			<div class="panel-group" id="faq-accordion" role="tablist" aria-multiselectable="true">
				<?php
				/** @var \common\models\Survey $survey */
				/** @var \common\models\SurveyQuestion[] $surveyQuestions */
				$surveyQuestions = $survey->getSurveyQuestions()
					->andWhere([
						'featured' => \common\models\SurveyQuestion::YES,
						'status' => \common\models\SurveyQuestion::STATUS_ACTIVE,
						'deleted' => \common\models\SurveyQuestion::NO,
					])
					->orderBy(['sort_order' => SORT_ASC])->all();
				?>
				<?php foreach ($surveyQuestions as $i => $surveyQuestion): ?>
					<div class="panel">
						<div class="panel-heading" role="tab" id="faq-heading-<?= $i ?>">
							<h3 class="panel-title">
								<a href="#faq-content-<?= $i ?>" data-toggle="collapse" data-parent="#faq-accordion" role="button" aria-expanded="true" aria-controls="faq-content-<?= $i ?>">
									<span class="faq-symbol" data-toggle="tooltip" title="<?= Yii::t('common', 'Question') ?>">
										<span class="fa fa-question fa-lg"></span>
									</span>
									<?= $surveyQuestion->translation->content ?>
								</a>
							</h3>
						</div>
						<div id="faq-content-<?= $i ?>" class="panel-collapse collapse" role="tabpanel" aria-labelledby="faq-heading-<?= $i ?>">
							<div class="panel-body">
								<span class="faq-symbol" data-toggle="tooltip" title="<?= Yii::t('common', 'Answer') ?>">
									<span class="fa fa-align-left"></span>
								</span>
								<?= $surveyQuestion->getSurveyAnswers()->active()->deleted(false)->one()->translation->content ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<div class="text-center gap-t-lg">
				<a class="btn btn-default btn-slide-right" href="<?= Url::to(['/site/faq']) ?>"><?= Yii::t('frontend', 'Read More') ?></a>
			</div>
		</div>
	</section>
<?php endif; ?>
