<?php

/* @var $this yii\web\View */
/* @var $model backend\modules\setting\models\InterfaceSettingForm */
/* @var $form backend\widgets\ActiveForm */

use backend\widgets\ActiveForm;
use backend\widgets\GoogleFontsSelector;
use common\helpers\FontIcon;
use kartik\color\ColorInput;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\web\JsExpression;

$this->title = Yii::t('common', 'Interface Settings');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Settings'),
		'url' => ['index'],
	],
	$this->title,
];
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('common', 'Chat Widget') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-6">
					<?= $form->field($model, 'chatUrl')->textInput([
						'type' => 'url',
					]) ?>
				</div>
				<div class="col-sm-6">
					<?= $form->field($model, 'chatRemove')->textInput()->hint(Yii::t('backend', 'IDs or classes from the parent page, separated by commas.')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6">
					<?= $form->field($model, 'chatVisible')->checkbox() ?>
				</div>
				<div class="col-sm-6">
					<?= $form->field($model, 'chatExpanded')->checkbox() ?>
				</div>
			</div>
		</div>
	</div>

	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Chat Toggle Styling') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#0c8a6b',
						],
					])->hint(Yii::t('backend', 'Default: #0c8a6b (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff (hover)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleWidth')->textInput([
						'type' => 'number',
						'placeholder' => '58',
					])->hint(Yii::t('backend', 'Default: 58px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleHeight')->textInput([
						'type' => 'number',
						'placeholder' => '58',
					])->hint(Yii::t('backend', 'Default: 58px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleBottom')->textInput([
						'type' => 'number',
						'placeholder' => '20',
					])->hint(Yii::t('backend', 'Default: 20px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleRight')->textInput([
						'type' => 'number',
						'placeholder' => '10',
					])->hint(Yii::t('backend', 'Default: 10px')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleFontSize')->textInput([
						'type' => 'number',
						'placeholder' => '26',
					])->hint(Yii::t('backend', 'Default: 26px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleZIndex')->textInput([
						'type' => 'number',
						'placeholder' => '3',
					])->hint(Yii::t('backend', 'Default: 3')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '50',
					])->hint(Yii::t('backend', 'Default: 50px (for circle when width/height is 58px, use at least 29px)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatToggleIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getToggleIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-comments-o')) ?>
				</div>
			</div>
		</div>
	</div>

	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Chat Panel Styling') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'chatTypingDotBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'chatPanelBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'chatPanelBoxShadow')->textInput([
						'placeholder' => '0 4px 10px rgba(0, 0, 0, 0.2)',
					])->hint(Yii::t('backend', 'Default: 0 4px 10px rgba(0, 0, 0, 0.2)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatPanelWidth')->textInput([
						'type' => 'number',
						'placeholder' => '340',
					])->hint(Yii::t('backend', 'Default: 340px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatPanelMaxHeight')->textInput([
						'type' => 'number',
						'placeholder' => '500',
					])->hint(Yii::t('backend', 'Default: 500px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatPanelBottom')->textInput([
						'type' => 'number',
						'placeholder' => '90',
					])->hint(Yii::t('backend', 'Default: 90px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatPanelRight')->textInput([
						'type' => 'number',
						'placeholder' => '10',
					])->hint(Yii::t('backend', 'Default: 10px')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatPanelBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '10',
					])->hint(Yii::t('backend', 'Default: 10px')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatInputContainerBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '12',
					])->hint(Yii::t('backend', 'Default: 12px (top-left & bottom-left)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatInputBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '6',
					])->hint(Yii::t('backend', 'Default: 6px (top-left & bottom-left)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeButtonBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '6',
					])->hint(Yii::t('backend', 'Default: 6px (top-right & bottom-right)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatMicrophoneIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getMicrophoneIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-microphone')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getEnvelopeIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-envelope')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getSendIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-send')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendButtonBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '50',
					])->hint(Yii::t('backend', 'Default: 50px (circular)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatMicrophoneBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatMicrophoneTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatMicrophoneHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#0c8a6b',
						],
					])->hint(Yii::t('backend', 'Default: #0c8a6b (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatMicrophoneHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff (hover)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#0c8a6b',
						],
					])->hint(Yii::t('backend', 'Default: #0c8a6b (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatEnvelopeHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff (hover)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#0c8a6b',
						],
					])->hint(Yii::t('backend', 'Default: #0c8a6b (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatSendHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff (hover)')) ?>
				</div>
			</div>
		</div>
	</div>

	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Chat Header Styling') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'chatHeaderBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'chatHeaderTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-2">
					<?= $form->field($model, 'chatHeaderPadding')->textInput([
						'type' => 'number',
						'placeholder' => '12',
					])->hint(Yii::t('backend', 'Default: 12px')) ?>
				</div>
				<div class="col-sm-2">
					<?= $form->field($model, 'chatHeaderBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '12',
					])->hint(Yii::t('backend', 'Default: 12px (applies to top-left and top-right corners)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatHeaderChevronIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getChevronIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-chevron-down')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatHeaderExpandIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getExpandIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-expand')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatHeaderCompressIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getCompressIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-compress')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatHeaderNewConversationIcon')->widget(Select2::class, [
						'data' => \backend\modules\setting\models\InterfaceSettingForm::getNewConversationIcons(),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => true,
							'escapeMarkup' => new JsExpression('function (markup) { return markup; }'),
							'placeholder' => Yii::t('backend', 'Select an icon...'),
						],
					])->hint(Yii::t('backend', 'Default: fa-refresh')) ?>
				</div>
			</div>
		</div>
	</div>

	<!-- Chat Modal Styling -->
	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Chat Modal Styling') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-4">
					<?= $form->field($model, 'chatModalHeaderBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'chatModalHeaderTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-4">
					<?= $form->field($model, 'chatModalBtnBorderRadius')->textInput([
						'type' => 'number',
						'placeholder' => '4',
					])->hint(Yii::t('backend', 'Default: 4px')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalCancelBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#e0e0e0',
						],
					])->hint(Yii::t('backend', 'Default: #e0e0e0')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalCancelTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#333333',
						],
					])->hint(Yii::t('backend', 'Default: #333333')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalCancelHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#d0d0d0',
						],
					])->hint(Yii::t('backend', 'Default: #d0d0d0 (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalCancelHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#333333',
						],
					])->hint(Yii::t('backend', 'Default: #333333 (hover)')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalConfirmBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#10a37f',
						],
					])->hint(Yii::t('backend', 'Default: #10a37f')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalConfirmTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalConfirmHoverBgColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#0c8a6b',
						],
					])->hint(Yii::t('backend', 'Default: #0c8a6b (hover)')) ?>
				</div>
				<div class="col-sm-3">
					<?= $form->field($model, 'chatModalConfirmHoverTextColor')->widget(ColorInput::class, [
						'useNative' => true,
						'options' => [
							'placeholder' => '#ffffff',
						],
					])->hint(Yii::t('backend', 'Default: #ffffff (hover)')) ?>
				</div>
			</div>
		</div>
	</div>

	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('common', 'Typography') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-12">
					<?= $form->field($model, 'fontFamily')->widget(GoogleFontsSelector::class, [
						'options' => [
							'placeholder' => Yii::t('backend', 'Select a font family...'),
						],
					])->hint(Yii::t('backend', 'Select a Google Font compatible with UTF-8 and Romanian diacritics (ă, â, î, ș, ț).')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<div id="font-preview-container" class="font-preview" style="margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9; <?= !$model->fontFamily ? 'display: none;' : '' ?>">
						<h4><?= Yii::t('backend', 'Font Preview') ?></h4>
						<p id="font-preview-text" style="font-family: <?= $model->fontFamily ? \backend\widgets\GoogleFontsSelector::getFontFamilyCss($model->fontFamily) : '' ?>; font-size: 16px; line-height: 1.6;">
							<?= Yii::t('backend', 'The quick brown fox jumps over the lazy dog.') ?><br>
							<?= Yii::t('backend', 'Romanian: Ăă Ââ Îî Șș Țț') ?>
						</p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="panel blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Embed CSS Preview') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-12">
					<?php
					$variablesCss = $model->generateVariablesCss();
					if ($variablesCss):
					?>
						<h5><?= Yii::t('backend', 'CSS Variables (variables.css)') ?></h5>
						<pre style="background: #f5f5f5; padding: 15px; border-radius: 4px; max-height: 300px; overflow-y: auto;"><code><?= Html::encode($variablesCss) ?></code></pre>
						<p class="text-muted"><?= Yii::t('backend', 'This CSS is automatically saved to uploads/variables.css and loaded by the embed layout.') ?></p>
					<?php else: ?>
						<p class="text-muted"><?= Yii::t('backend', 'Configure the chat styling options above to generate CSS variables.') ?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions floating">
		<?= Html::submitButton('<span class="fa fa-check"></span>', [
			'class' => 'btn btn-xlg btn-fab btn-success',
			'title' => Yii::t('common', 'Save'),
			'data' => [
				'toggle' => 'tooltip',
			],
		]) ?>
	</div>
<?php ActiveForm::end(); ?>

<?php
// Load Google Font if selected
if ($model->fontFamily && !\backend\widgets\GoogleFontsSelector::isSystemFont($model->fontFamily)) {
    $fontUrl = \backend\widgets\GoogleFontsSelector::getGoogleFontsUrl($model->fontFamily);
    if ($fontUrl) {
        $this->registerCssFile($fontUrl, ['depends' => [\yii\web\JqueryAsset::class]]);
    }
}

// JavaScript for dynamic font preview
$fontPreviewJs = '
(function() {
    var fontInput = jQuery("#interfacesettingform-fontfamily");
    var previewContainer = jQuery("#font-preview-container");
    var previewText = jQuery("#font-preview-text");
    var loadedFonts = {};
    
    function loadGoogleFont(fontName) {
        if (loadedFonts[fontName] || !fontName) {
            return;
        }
        
        // Check if it\'s a system font
        var systemFonts = ["Arial", "Helvetica", "Georgia", "Times New Roman", "Verdana", "Courier New"];
        if (systemFonts.indexOf(fontName) !== -1) {
            updatePreview(fontName);
            return;
        }
        
        // Load Google Font
        var fontNameEncoded = fontName.replace(/ /g, "+");
        var fontUrl = "https://fonts.googleapis.com/css2?family=" + fontNameEncoded + ":wght@300;400;500;600;700&subset=latin,latin-ext&display=swap";
        
        var link = document.createElement("link");
        link.rel = "stylesheet";
        link.href = fontUrl;
        link.onload = function() {
            loadedFonts[fontName] = true;
            updatePreview(fontName);
        };
        document.head.appendChild(link);
    }
    
    function updatePreview(fontName) {
        if (!fontName) {
            previewContainer.hide();
            return;
        }
        
        var systemFonts = ["Arial", "Helvetica", "Georgia", "Times New Roman", "Verdana", "Courier New"];
        var fontFamilyCss = systemFonts.indexOf(fontName) !== -1 
            ? fontName 
            : \'"\' + fontName + \'", sans-serif\';
        
        previewText.css("font-family", fontFamilyCss);
        previewContainer.show();
    }
    
    // Listen for changes on the font input
    fontInput.on("change input", function() {
        var fontName = jQuery(this).val();
        if (fontName) {
            loadGoogleFont(fontName);
        } else {
            previewContainer.hide();
        }
    });
    
    // Also listen for typeahead selection
    jQuery(document).on("click", ".typeahead__list li", function() {
        setTimeout(function() {
            var fontName = fontInput.val();
            if (fontName) {
                loadGoogleFont(fontName);
            }
        }, 100);
    });
    
    // Initialize preview if font is already set
    if (fontInput.val()) {
        loadGoogleFont(fontInput.val());
    }
})();
';

$this->registerJs($fontPreviewJs, \yii\web\View::POS_READY);
?>

