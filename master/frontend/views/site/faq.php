<?php
/* @var $this \yii\web\View */

use common\models\Survey;
use tws\helpers\Url;
?>

<?php if ($content = $this->context->currentPage->content): ?>
<div class="section section-md">
	<div class="container-fluid">
		<?= $content ?>
	</div>
</div>
<?php endif; ?>

<?php if ($survey = Survey::find()->andWhere(['type' => Survey::TYPE_FAQ])->default()->active()->deleted(false)->one()): ?>
	<section class="section section-md section-faq pad-t-0">
		<div class="container-fluid">
			<div class="panel-group" id="faq-accordion" role="tablist" aria-multiselectable="true">
				<?php
				/** @var \common\models\Survey $survey */
				/** @var \common\models\SurveyQuestion[] $surveyQuestions */
				$surveyQuestions = $survey->getSurveyQuestions()->active()->deleted(false)->orderBy(['sort_order' => SORT_ASC])->all();
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
		</div>
	</section>
<?php endif; ?>
