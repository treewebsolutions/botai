<?php

use tws\helpers\Url;

$workspace = \common\models\master\Workspace::findOne(end(explode('-', Yii::$app->id)));

// Get all interface settings at once
$settings = Yii::$app->settings->getCategory('interface');

// Get icon settings with defaults
$chatToggleIcon = Yii::$app->settings->get('chatToggleIcon', 'interface') ?: 'fa fa-comments-o';
$chatHeaderChevronIcon = Yii::$app->settings->get('chatHeaderChevronIcon', 'interface') ?: 'fa fa-chevron-down';
$chatHeaderExpandIcon = Yii::$app->settings->get('chatHeaderExpandIcon', 'interface') ?: 'fa fa-expand';
$chatHeaderCompressIcon = Yii::$app->settings->get('chatHeaderCompressIcon', 'interface') ?: 'fa fa-compress';
$chatHeaderNewConversationIcon = Yii::$app->settings->get('chatHeaderNewConversationIcon', 'interface') ?: 'fa fa-refresh';
$chatMicrophoneIcon = Yii::$app->settings->get('chatMicrophoneIcon', 'interface') ?: 'fa fa-microphone';
$chatEnvelopeIcon = Yii::$app->settings->get('chatEnvelopeIcon', 'interface') ?: 'fa fa-envelope';
$chatSendIcon = Yii::$app->settings->get('chatSendIcon', 'interface') ?: 'fa fa-send';

// Visible / expanded: optional URL query (embed script data-visible / data-expanded) overrides interface settings.
$chatVisibleRaw = Yii::$app->request->get('visible');
if ($chatVisibleRaw === null || $chatVisibleRaw === '') {
	$chatVisibleRaw = Yii::$app->settings->get('chatVisible', 'interface');
}
if ($chatVisibleRaw === null || $chatVisibleRaw === '') {
	$chatVisibleRaw = Yii::$app->settings->get('chatVisible');
}
$isChatVisible = filter_var($chatVisibleRaw, FILTER_VALIDATE_BOOLEAN);

$chatExpandedRaw = Yii::$app->request->get('expanded');
if ($chatExpandedRaw === null || $chatExpandedRaw === '') {
	$chatExpandedRaw = Yii::$app->settings->get('chatExpanded', 'interface');
}
if ($chatExpandedRaw === null || $chatExpandedRaw === '') {
	$chatExpandedRaw = Yii::$app->settings->get('chatExpanded');
}
$isChatExpanded = filter_var($chatExpandedRaw, FILTER_VALIDATE_BOOLEAN);

// Border radius is now handled by CSS variables in variables.css, no need for inline styles

?>

<!-- Chat Bubble Toggle Button -->
<button class="chat-toggle chat-bubble" title="<?= Yii::$app->name ?>">
	<i class="<?= $chatToggleIcon ?>"></i>
</button>

<!-- Chat Container -->
<div class="chat-container <?= $isChatExpanded ? 'expanded' : ''; ?> <?= $isChatVisible ? 'visible' : ''; ?>">
	<div class="chat-header">
		<?= Yii::$app->name ?> <span class="chat-toggle chat-chevron"><i class="<?= $chatHeaderChevronIcon ?>"></i></span> <?= $isChatExpanded ? '<span class="chat-resize chat-compress"><i class="' . $chatHeaderCompressIcon . '"></i></span>' : '<span class="chat-resize chat-expand"><i class="' . $chatHeaderExpandIcon . '"></i></span>'; ?> <span class="chat-new-conversation" title="<?= Yii::t('label', 'New Conversation') ?>"><i class="<?= $chatHeaderNewConversationIcon ?>"></i></span>
	</div>
	<div class="chat-body <?= $isChatExpanded ? 'expanded' : ''; ?>" id="chat-body">
	</div>
	<div class="chat-input-container">
		<input type="text" class="chat-input" id="chat-input" placeholder="<?= Yii::t('label', 'Message') ?>...">
		<button class="microphone-button" id="microphone-button"><i class="<?= $chatMicrophoneIcon ?>"></i></button>
		<button class="envelope-button" id="envelope-button"><i class="<?= $chatEnvelopeIcon ?>"></i></button>
		<button class="send-button" id="send-button"><i class="<?= $chatSendIcon ?>"></i></button>
	</div>
</div>

<!-- New Conversation Confirmation Modal -->
<div id="new-conversation-modal" class="chat-modal">
	<div class="chat-modal-content">
		<div class="chat-modal-header">
			<h4><?= Yii::t('label', 'New Conversation') ?></h4>
			<button class="chat-modal-close" title="<?= Yii::t('label', 'Close') ?>">&times;</button>
		</div>
		<div class="chat-modal-body">
			<p><?= Yii::t('label', 'Are you sure you want to start a new conversation? This will clear the current conversation history.') ?></p>
		</div>
		<div class="chat-modal-footer">
			<button class="chat-modal-btn chat-modal-cancel"><?= Yii::t('label', 'Cancel') ?></button>
			<button class="chat-modal-btn chat-modal-confirm"><?= Yii::t('label', 'Start New') ?></button>
		</div>
	</div>
</div>

<?php
$this->registerJs('
	$(document).ready(function() {
	    const chat = new Chat({
	        language: "' . Yii::$app->language . '",
	        url: "' . implode('/', [
				Yii::$app->request->hostInfo,
				$workspace->url,
				'embed',
				'chat'
			]) . '",
			ttsUrl: "' . implode('/', [
				Yii::$app->request->hostInfo,
				$workspace->url,
				'embed',
				'chat',
				'speak',
			]) . '",
	    });
	    chat.init();
});
', \yii\web\View::POS_READY);
?>