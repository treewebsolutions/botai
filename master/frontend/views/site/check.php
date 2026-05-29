<?php
/* @var $this yii\web\View */
/* @var $model frontend\models\CheckForm */
/* @var $dataset \common\models\Workspace[]|array */

use backend\widgets\ActiveForm;
use yii\helpers\Html;
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
				'novalidate' => true,
			],
			'validateOnType' => true,
			'validateOnBlur' => false,
		]); ?>
			<?php if ($model->hasErrors() && empty(array_intersect_key($model->getErrors(), array_flip($model->safeAttributes())))): ?>
				<?= $form->errorSummary($model, [
					'header' => false,
					'class' => 'alert alert-danger alert-icon',
				]) ?>
			<?php endif; ?>

			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model, 'criterion', [
						'template' => '{label}<div class="input-group">{input}<div class="input-group-btn with-control"><button type="submit" class="btn btn-success btn-slide-right">' . Yii::t('common', 'Check') . '</button></div></div>{hint}{error}',
					])->textInput([
						'placeholder' => Yii::t('common', 'Example') . ': 0712345678',
					]) ?>
				</div>
			</div>
            <?= $form->field($model, 'workEmail', [
                'options' => [
                    'class' => 'work-email',
                ],
                'template' => '{input}',
            ])->input('email', ['required' => 'required'])->label(false) ?>
            <?php if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')): ?>
                <div class="hidden g-recaptcha" data-sitekey="<?= Yii::$app->settings->get('reCaptchaSiteKey', 'general') ?>" data-badge="inline" data-size="invisible" data-callback="setResponse"></div>
                <?= $form->field($model, 'captchaResponse', [
                    'template' => '{input}',
                ])->hiddenInput(['id' => 'captcha-response'])->label(false) ?>
            <?php endif; ?>
			<?php if (!empty($dataset)): ?>
				<?php foreach ($dataset as $i => $workspace): ?>
					<?= Html::tag('iframe', null, [
						"id" => "gsg-iframe-{$i}",
						"class" => "embed-iframe",
						"src" => "{$workspace->getAbsoluteUrl()}/embed/input?" . http_build_query([
							'language' => Yii::$app->language,
							'displayForm' => false,
							'criterion' => $model->criterion,
						]),
					]) ?>
				<?php endforeach; ?>
			<?php endif; ?>
		<?php ActiveForm::end(); ?>
	</div>
</div>

<?php
$this->registerJs('
		$( "#' . Html::getInputId($model, 'workEmail') . '").removeAttr("required");
	');
?>

<?php $this->registerJs('
	$("iframe[id^=\"gsg-iframe-\"]").each(function (index, iframe) {
		window.iFrameResize({}, "#" + iframe.id);
	});
') ?>
