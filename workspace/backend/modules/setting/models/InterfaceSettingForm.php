<?php

namespace backend\modules\setting\models;

use common\models\Setting;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

class InterfaceSettingForm extends Setting
{
	/**
	 * @var string The Chat Widget Allowed Url.
	 */
	public $chatUrl;

	/**
	 * @var string The Chat Widget Color.
	 */
	public $chatColor;

	/**
	 * @var bool Flag that indicates if The Chat Widget is Visible.
	 */
	public $chatVisible;

	/**
	 * @var bool Flag that indicates if The Chat Widget is Expanded.
	 */
	public $chatExpanded;

	/**
	 * @var bool Flag that indicates ID or class of element to be removed from parent page.
	 */
	public $chatRemove;

	/**
	 * @var string The font family for the interface (Google Fonts compatible with UTF-8/Romanian diacritics).
	 */
	public $fontFamily;

	// Chat Toggle (Bubble) Properties
	/**
	 * @var string Chat toggle background color
	 */
	public $chatToggleBgColor;

	/**
	 * @var string Chat toggle text/icon color
	 */
	public $chatToggleTextColor;

	/**
	 * @var int Chat toggle width (px)
	 */
	public $chatToggleWidth;

	/**
	 * @var int Chat toggle height (px)
	 */
	public $chatToggleHeight;

	/**
	 * @var int Chat toggle font size (px)
	 */
	public $chatToggleFontSize;

	/**
	 * @var int Chat toggle bottom position (px)
	 */
	public $chatToggleBottom;

	/**
	 * @var int Chat toggle right position (px)
	 */
	public $chatToggleRight;

	/**
	 * @var int Chat toggle z-index
	 */
	public $chatToggleZIndex;

	/**
	 * @var int Chat toggle border radius (px)
	 */
	public $chatToggleBorderRadius;

	/**
	 * @var string Chat toggle icon (Font Awesome class)
	 */
	public $chatToggleIcon;

	/**
	 * @var string Chat toggle hover background color
	 */
	public $chatToggleHoverBgColor;

	/**
	 * @var string Chat toggle hover text color
	 */
	public $chatToggleHoverTextColor;

	/**
	 * @var string Chat microphone button hover background color
	 */
	public $chatMicrophoneHoverBgColor;

	/**
	 * @var string Chat microphone button hover text color
	 */
	public $chatMicrophoneHoverTextColor;

	/**
	 * @var string Chat envelope button hover background color
	 */
	public $chatEnvelopeHoverBgColor;

	/**
	 * @var string Chat envelope button hover text color
	 */
	public $chatEnvelopeHoverTextColor;

	/**
	 * @var string Chat send button hover background color
	 */
	public $chatSendHoverBgColor;

	/**
	 * @var string Chat send button hover text color
	 */
	public $chatSendHoverTextColor;

	/**
	 * @var string Chat microphone button background color
	 */
	public $chatMicrophoneBgColor;

	/**
	 * @var string Chat microphone button text color
	 */
	public $chatMicrophoneTextColor;

	/**
	 * @var string Chat envelope button background color
	 */
	public $chatEnvelopeBgColor;

	/**
	 * @var string Chat envelope button text color
	 */
	public $chatEnvelopeTextColor;

	/**
	 * @var string Chat send button background color
	 */
	public $chatSendBgColor;

	/**
	 * @var string Chat send button text color
	 */
	public $chatSendTextColor;

	// Chat Panel Properties
	/**
	 * @var string Chat typing dot background color
	 */
	public $chatTypingDotBgColor;

	/**
	 * @var string Chat panel background color
	 */
	public $chatPanelBgColor;

	/**
	 * @var int Chat panel width (px)
	 */
	public $chatPanelWidth;

	/**
	 * @var int Chat panel max height (px)
	 */
	public $chatPanelMaxHeight;

	/**
	 * @var int Chat panel bottom position (px)
	 */
	public $chatPanelBottom;

	/**
	 * @var int Chat panel right position (px)
	 */
	public $chatPanelRight;

	/**
	 * @var int Chat panel border radius (px)
	 */
	public $chatPanelBorderRadius;

	/**
	 * @var string Chat panel box shadow
	 */
	public $chatPanelBoxShadow;

	/**
	 * @var string Chat microphone button icon (Font Awesome class)
	 */
	public $chatMicrophoneIcon;

	/**
	 * @var string Chat envelope button icon (Font Awesome class)
	 */
	public $chatEnvelopeIcon;

	/**
	 * @var string Chat send button icon (Font Awesome class)
	 */
	public $chatSendIcon;

	/**
	 * @var int Chat input container border radius (top-left and bottom-left corners) (px)
	 */
	public $chatInputContainerBorderRadius;

	/**
	 * @var int Chat input border radius (top-left and bottom-left corners) (px)
	 */
	public $chatInputBorderRadius;

	/**
	 * @var int Chat envelope button border radius (top-right and bottom-right corners) (px)
	 */
	public $chatEnvelopeButtonBorderRadius;

	/**
	 * @var int Chat send button border radius (px)
	 */
	public $chatSendButtonBorderRadius;

	// Chat Header Properties
	/**
	 * @var string Chat header background color
	 */
	public $chatHeaderBgColor;

	/**
	 * @var string Chat header text color
	 */
	public $chatHeaderTextColor;

	/**
	 * @var int Chat header padding (px)
	 */
	public $chatHeaderPadding;

	/**
	 * @var int Chat header border radius (px) - applies to top-left and top-right corners
	 */
	public $chatHeaderBorderRadius;

	/**
	 * @var string Chat header chevron icon (Font Awesome class)
	 */
	public $chatHeaderChevronIcon;

	// Chat Modal Properties
	/**
	 * @var string Chat modal header background color
	 */
	public $chatModalHeaderBgColor;

	/**
	 * @var string Chat modal header text color
	 */
	public $chatModalHeaderTextColor;

	/**
	 * @var string Chat modal cancel button background color
	 */
	public $chatModalCancelBgColor;

	/**
	 * @var string Chat modal cancel button text color
	 */
	public $chatModalCancelTextColor;

	/**
	 * @var string Chat modal confirm button background color
	 */
	public $chatModalConfirmBgColor;

	/**
	 * @var string Chat modal confirm button text color
	 */
	public $chatModalConfirmTextColor;

	/**
	 * @var string Chat modal cancel button hover background color
	 */
	public $chatModalCancelHoverBgColor;

	/**
	 * @var string Chat modal cancel button hover text color
	 */
	public $chatModalCancelHoverTextColor;

	/**
	 * @var string Chat modal confirm button hover background color
	 */
	public $chatModalConfirmHoverBgColor;

	/**
	 * @var string Chat modal confirm button hover text color
	 */
	public $chatModalConfirmHoverTextColor;

	/**
	 * @var int Chat modal button border radius (px)
	 */
	public $chatModalBtnBorderRadius;

	/**
	 * @var string Chat header expand icon (Font Awesome class)
	 */
	public $chatHeaderExpandIcon;

	/**
	 * @var string Chat header compress icon (Font Awesome class)
	 */
	public $chatHeaderCompressIcon;

	/**
	 * @var string Chat header new conversation icon (Font Awesome class)
	 */
	public $chatHeaderNewConversationIcon;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['chatVisible', 'chatExpanded'], 'boolean'],
			[['chatUrl', 'chatColor', 'chatRemove', 'fontFamily'], 'string'],
			[['chatUrl', 'chatColor', 'chatRemove', 'fontFamily'], 'trim'],
			// Chat toggle properties
			[['chatToggleBgColor', 'chatToggleTextColor', 'chatToggleIcon', 'chatToggleHoverBgColor', 'chatToggleHoverTextColor'], 'string'],
			[['chatToggleBgColor', 'chatToggleTextColor', 'chatToggleIcon', 'chatToggleHoverBgColor', 'chatToggleHoverTextColor'], 'trim'],
			// Chat button hover properties
			[['chatMicrophoneHoverBgColor', 'chatMicrophoneHoverTextColor', 'chatEnvelopeHoverBgColor', 'chatEnvelopeHoverTextColor', 'chatSendHoverBgColor', 'chatSendHoverTextColor'], 'string'],
			[['chatMicrophoneHoverBgColor', 'chatMicrophoneHoverTextColor', 'chatEnvelopeHoverBgColor', 'chatEnvelopeHoverTextColor', 'chatSendHoverBgColor', 'chatSendHoverTextColor'], 'trim'],
			[['chatToggleWidth', 'chatToggleHeight', 'chatToggleFontSize', 'chatToggleBottom', 'chatToggleRight', 'chatToggleZIndex', 'chatToggleBorderRadius'], 'integer', 'min' => 0],
			// Chat panel properties
			[['chatTypingDotBgColor', 'chatPanelBgColor', 'chatPanelBoxShadow', 'chatMicrophoneIcon', 'chatEnvelopeIcon', 'chatSendIcon', 'chatMicrophoneBgColor', 'chatMicrophoneTextColor', 'chatEnvelopeBgColor', 'chatEnvelopeTextColor', 'chatSendBgColor', 'chatSendTextColor'], 'string'],
			[['chatTypingDotBgColor', 'chatPanelBgColor', 'chatPanelBoxShadow', 'chatMicrophoneIcon', 'chatEnvelopeIcon', 'chatSendIcon', 'chatMicrophoneBgColor', 'chatMicrophoneTextColor', 'chatEnvelopeBgColor', 'chatEnvelopeTextColor', 'chatSendBgColor', 'chatSendTextColor'], 'trim'],
			[['chatPanelWidth', 'chatPanelMaxHeight', 'chatPanelBottom', 'chatPanelRight', 'chatPanelBorderRadius', 'chatInputContainerBorderRadius', 'chatInputBorderRadius', 'chatEnvelopeButtonBorderRadius', 'chatSendButtonBorderRadius'], 'integer', 'min' => 0],
			// Chat header properties
			[['chatHeaderBgColor', 'chatHeaderTextColor', 'chatHeaderChevronIcon', 'chatHeaderExpandIcon', 'chatHeaderCompressIcon', 'chatHeaderNewConversationIcon'], 'string'],
			[['chatHeaderBgColor', 'chatHeaderTextColor', 'chatHeaderChevronIcon', 'chatHeaderExpandIcon', 'chatHeaderCompressIcon', 'chatHeaderNewConversationIcon'], 'trim'],
			[['chatHeaderPadding', 'chatHeaderBorderRadius'], 'integer', 'min' => 0],
			// Chat modal properties
			[['chatModalHeaderBgColor', 'chatModalHeaderTextColor', 'chatModalCancelBgColor', 'chatModalCancelTextColor', 'chatModalConfirmBgColor', 'chatModalConfirmTextColor', 'chatModalCancelHoverBgColor', 'chatModalCancelHoverTextColor', 'chatModalConfirmHoverBgColor', 'chatModalConfirmHoverTextColor'], 'string'],
			[['chatModalHeaderBgColor', 'chatModalHeaderTextColor', 'chatModalCancelBgColor', 'chatModalCancelTextColor', 'chatModalConfirmBgColor', 'chatModalConfirmTextColor', 'chatModalCancelHoverBgColor', 'chatModalCancelHoverTextColor', 'chatModalConfirmHoverBgColor', 'chatModalConfirmHoverTextColor'], 'trim'],
			[['chatModalBtnBorderRadius'], 'integer', 'min' => 0],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'chatUrl' => Yii::t('label', 'Allowed Domain Url'),
			'chatColor' => Yii::t('label', 'Color'),
			'chatVisible' => Yii::t('label', 'Visible'),
			'chatExpanded' => Yii::t('label', 'Expanded'),
			'chatRemove' => Yii::t('label', 'Remove Element By ID Or Class'),
			'fontFamily' => Yii::t('label', 'Font Family'),
			// Chat toggle labels
			'chatToggleBgColor' => Yii::t('label', 'Toggle Background Color'),
			'chatToggleTextColor' => Yii::t('label', 'Toggle Text/Icon Color'),
			'chatToggleWidth' => Yii::t('label', 'Toggle Width (px)'),
			'chatToggleHeight' => Yii::t('label', 'Toggle Height (px)'),
			'chatToggleFontSize' => Yii::t('label', 'Toggle Font Size (px)'),
			'chatToggleBottom' => Yii::t('label', 'Toggle Bottom Position (px)'),
			'chatToggleRight' => Yii::t('label', 'Toggle Right Position (px)'),
			'chatToggleZIndex' => Yii::t('label', 'Toggle Z-Index'),
			'chatToggleBorderRadius' => Yii::t('label', 'Toggle Border Radius (px)'),
			'chatToggleIcon' => Yii::t('label', 'Toggle Icon'),
			'chatToggleHoverBgColor' => Yii::t('label', 'Toggle Hover Background Color'),
			'chatToggleHoverTextColor' => Yii::t('label', 'Toggle Hover Text/Icon Color'),
			// Chat panel labels
			'chatTypingDotBgColor' => Yii::t('label', 'Typing Dot Background Color'),
			'chatPanelBgColor' => Yii::t('label', 'Panel Background Color'),
			'chatPanelWidth' => Yii::t('label', 'Panel Width (px)'),
			'chatPanelMaxHeight' => Yii::t('label', 'Panel Max Height (px)'),
			'chatPanelBottom' => Yii::t('label', 'Panel Bottom Position (px)'),
			'chatPanelRight' => Yii::t('label', 'Panel Right Position (px)'),
			'chatPanelBorderRadius' => Yii::t('label', 'Panel Border Radius (px)'),
			'chatPanelBoxShadow' => Yii::t('label', 'Panel Box Shadow'),
			'chatMicrophoneIcon' => Yii::t('label', 'Microphone Button Icon'),
			'chatMicrophoneHoverBgColor' => Yii::t('label', 'Microphone Button Hover Background Color'),
			'chatMicrophoneHoverTextColor' => Yii::t('label', 'Microphone Button Hover Text Color'),
			'chatEnvelopeIcon' => Yii::t('label', 'Envelope Button Icon'),
			'chatEnvelopeHoverBgColor' => Yii::t('label', 'Envelope Button Hover Background Color'),
			'chatEnvelopeHoverTextColor' => Yii::t('label', 'Envelope Button Hover Text Color'),
			'chatSendIcon' => Yii::t('label', 'Send Button Icon'),
			'chatSendHoverBgColor' => Yii::t('label', 'Send Button Hover Background Color'),
			'chatSendHoverTextColor' => Yii::t('label', 'Send Button Hover Text Color'),
			'chatInputContainerBorderRadius' => Yii::t('label', 'Input Container Border Radius (px)'),
			'chatInputBorderRadius' => Yii::t('label', 'Input Border Radius (px)'),
			'chatEnvelopeButtonBorderRadius' => Yii::t('label', 'Envelope Button Border Radius (px)'),
			// Chat header labels
			'chatHeaderBgColor' => Yii::t('label', 'Header Background Color'),
			'chatHeaderTextColor' => Yii::t('label', 'Header Text Color'),
			'chatHeaderPadding' => Yii::t('label', 'Header Padding (px)'),
			'chatHeaderBorderRadius' => Yii::t('label', 'Header Border Radius (px)'),
			'chatHeaderChevronIcon' => Yii::t('label', 'Header Chevron Icon'),
			'chatHeaderExpandIcon' => Yii::t('label', 'Header Expand Icon'),
			// Chat modal labels
			'chatModalHeaderBgColor' => Yii::t('label', 'Modal Header Background Color'),
			'chatModalHeaderTextColor' => Yii::t('label', 'Modal Header Text Color'),
			'chatModalCancelBgColor' => Yii::t('label', 'Modal Cancel Button Background Color'),
			'chatModalCancelTextColor' => Yii::t('label', 'Modal Cancel Button Text Color'),
			'chatModalConfirmBgColor' => Yii::t('label', 'Modal Confirm Button Background Color'),
			'chatModalConfirmTextColor' => Yii::t('label', 'Modal Confirm Button Text Color'),
			'chatModalCancelHoverBgColor' => Yii::t('label', 'Modal Cancel Button Hover Background Color'),
			'chatModalCancelHoverTextColor' => Yii::t('label', 'Modal Cancel Button Hover Text Color'),
			'chatModalConfirmHoverBgColor' => Yii::t('label', 'Modal Confirm Button Hover Background Color'),
			'chatModalConfirmHoverTextColor' => Yii::t('label', 'Modal Confirm Button Hover Text Color'),
			'chatModalBtnBorderRadius' => Yii::t('label', 'Modal Button Border Radius (px)'),
			'chatHeaderCompressIcon' => Yii::t('label', 'Header Compress Icon'),
			'chatHeaderNewConversationIcon' => Yii::t('label', 'Header New Conversation Icon'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$settings = $this->getUnserializedValue('setting');
		if (is_array($settings)) {
			$this->setAttributes($settings);
		}
		
		// Set default values from current embed CSS if not set
		// Use !== null && !== '' to preserve 0 values
		if (empty($this->chatToggleBgColor)) {
			$this->chatToggleBgColor = '#10a37f';
		}
		if (empty($this->chatToggleTextColor)) {
			$this->chatToggleTextColor = '#ffffff';
		}
		if ($this->chatToggleWidth === null || $this->chatToggleWidth === '') {
			$this->chatToggleWidth = 58;
		}
		if ($this->chatToggleHeight === null || $this->chatToggleHeight === '') {
			$this->chatToggleHeight = 58;
		}
		if ($this->chatToggleFontSize === null || $this->chatToggleFontSize === '') {
			$this->chatToggleFontSize = 26;
		}
		if ($this->chatToggleBottom === null || $this->chatToggleBottom === '') {
			$this->chatToggleBottom = 20;
		}
		if ($this->chatToggleRight === null || $this->chatToggleRight === '') {
			$this->chatToggleRight = 10;
		}
		if ($this->chatToggleZIndex === null || $this->chatToggleZIndex === '') {
			$this->chatToggleZIndex = 3;
		}
		if ($this->chatToggleBorderRadius === null || $this->chatToggleBorderRadius === '') {
			$this->chatToggleBorderRadius = 50;
		}
		if (empty($this->chatToggleIcon)) {
			$this->chatToggleIcon = 'fa fa-comments-o';
		}
		if (empty($this->chatToggleHoverBgColor)) {
			$this->chatToggleHoverBgColor = $this->darkenColor($this->chatToggleBgColor ?: '#10a37f', 0.1);
		}
		if (empty($this->chatToggleHoverTextColor)) {
			$this->chatToggleHoverTextColor = $this->chatToggleTextColor ?: '#ffffff';
		}
		
		// Chat panel defaults
		if (empty($this->chatTypingDotBgColor)) {
			$this->chatTypingDotBgColor = '#10a37f';
		}
		if (empty($this->chatPanelBgColor)) {
			$this->chatPanelBgColor = '#ffffff';
		}
		if ($this->chatPanelWidth === null || $this->chatPanelWidth === '') {
			$this->chatPanelWidth = 340;
		}
		if ($this->chatPanelMaxHeight === null || $this->chatPanelMaxHeight === '') {
			$this->chatPanelMaxHeight = 500;
		}
		if ($this->chatPanelBottom === null || $this->chatPanelBottom === '') {
			$this->chatPanelBottom = 90;
		}
		if ($this->chatPanelRight === null || $this->chatPanelRight === '') {
			$this->chatPanelRight = 10;
		}
		if ($this->chatPanelBorderRadius === null || $this->chatPanelBorderRadius === '') {
			$this->chatPanelBorderRadius = 10;
		}
		if (empty($this->chatPanelBoxShadow)) {
			$this->chatPanelBoxShadow = '0 4px 10px rgba(0, 0, 0, 0.2)';
		}
		if (empty($this->chatMicrophoneIcon)) {
			$this->chatMicrophoneIcon = 'fa fa-microphone';
		}
		if (empty($this->chatMicrophoneBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatMicrophoneBgColor = $chatColor;
		}
		if (empty($this->chatMicrophoneTextColor)) {
			$this->chatMicrophoneTextColor = '#ffffff';
		}
		if (empty($this->chatMicrophoneHoverBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatMicrophoneHoverBgColor = $this->darkenColor($chatColor, 0.1);
		}
		if (empty($this->chatMicrophoneHoverTextColor)) {
			$this->chatMicrophoneHoverTextColor = '#ffffff';
		}
		if (empty($this->chatEnvelopeIcon)) {
			$this->chatEnvelopeIcon = 'fa fa-envelope';
		}
		if (empty($this->chatEnvelopeBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatEnvelopeBgColor = $chatColor;
		}
		if (empty($this->chatEnvelopeTextColor)) {
			$this->chatEnvelopeTextColor = '#ffffff';
		}
		if (empty($this->chatEnvelopeHoverBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatEnvelopeHoverBgColor = $this->darkenColor($chatColor, 0.1);
		}
		if (empty($this->chatEnvelopeHoverTextColor)) {
			$this->chatEnvelopeHoverTextColor = '#ffffff';
		}
		if (empty($this->chatSendIcon)) {
			$this->chatSendIcon = 'fa fa-send';
		}
		if (empty($this->chatSendBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatSendBgColor = $chatColor;
		}
		if (empty($this->chatSendTextColor)) {
			$this->chatSendTextColor = '#ffffff';
		}
		if (empty($this->chatSendHoverBgColor)) {
			$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: '#10a37f');
			$this->chatSendHoverBgColor = $this->darkenColor($chatColor, 0.1);
		}
		if (empty($this->chatSendHoverTextColor)) {
			$this->chatSendHoverTextColor = '#ffffff';
		}
		if ($this->chatInputContainerBorderRadius === null || $this->chatInputContainerBorderRadius === '') {
			$this->chatInputContainerBorderRadius = 12;
		}
		if ($this->chatInputBorderRadius === null || $this->chatInputBorderRadius === '') {
			$this->chatInputBorderRadius = 6;
		}
		if ($this->chatEnvelopeButtonBorderRadius === null || $this->chatEnvelopeButtonBorderRadius === '') {
			$this->chatEnvelopeButtonBorderRadius = 6;
		}
		if ($this->chatSendButtonBorderRadius === null || $this->chatSendButtonBorderRadius === '') {
			$this->chatSendButtonBorderRadius = 50;
		}
		
		// Chat header defaults
		if (empty($this->chatHeaderBgColor)) {
			$this->chatHeaderBgColor = '#10a37f';
		}
		if (empty($this->chatHeaderTextColor)) {
			$this->chatHeaderTextColor = '#ffffff';
		}
		if ($this->chatHeaderPadding === null || $this->chatHeaderPadding === '') {
			$this->chatHeaderPadding = 12;
		}
		if ($this->chatHeaderBorderRadius === null || $this->chatHeaderBorderRadius === '') {
			$this->chatHeaderBorderRadius = 12;
		}
		if (empty($this->chatHeaderChevronIcon)) {
			$this->chatHeaderChevronIcon = 'fa fa-chevron-down';
		}
		
		// Chat modal defaults
		if (empty($this->chatModalHeaderBgColor)) {
			$this->chatModalHeaderBgColor = '#10a37f';
		}
		if (empty($this->chatModalHeaderTextColor)) {
			$this->chatModalHeaderTextColor = '#ffffff';
		}
		if (empty($this->chatModalCancelBgColor)) {
			$this->chatModalCancelBgColor = '#e0e0e0';
		}
		if (empty($this->chatModalCancelTextColor)) {
			$this->chatModalCancelTextColor = '#333333';
		}
		if (empty($this->chatModalCancelHoverBgColor)) {
			$this->chatModalCancelHoverBgColor = '#d0d0d0';
		}
		if (empty($this->chatModalCancelHoverTextColor)) {
			$this->chatModalCancelHoverTextColor = $this->chatModalCancelTextColor ?: '#333333';
		}
		if (empty($this->chatModalConfirmBgColor)) {
			$this->chatModalConfirmBgColor = '#10a37f';
		}
		if (empty($this->chatModalConfirmTextColor)) {
			$this->chatModalConfirmTextColor = '#ffffff';
		}
		if (empty($this->chatModalConfirmHoverBgColor)) {
			$this->chatModalConfirmHoverBgColor = $this->darkenColor($this->chatModalConfirmBgColor ?: '#10a37f', 0.1);
		}
		if (empty($this->chatModalConfirmHoverTextColor)) {
			$this->chatModalConfirmHoverTextColor = $this->chatModalConfirmTextColor ?: '#ffffff';
		}
		if ($this->chatModalBtnBorderRadius === null || $this->chatModalBtnBorderRadius === '') {
			$this->chatModalBtnBorderRadius = 4;
		}
		if (empty($this->chatHeaderExpandIcon)) {
			$this->chatHeaderExpandIcon = 'fa fa-expand';
		}
		if (empty($this->chatHeaderCompressIcon)) {
			$this->chatHeaderCompressIcon = 'fa fa-compress';
		}
		if (empty($this->chatHeaderNewConversationIcon)) {
			$this->chatHeaderNewConversationIcon = 'fa fa-refresh';
		}
	}

	/**
	 * Saves the setting.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 */
	public function saveModel()
	{
		$this->name = 'interface';
		$this->type = static::TYPE_APP;
		$this->status = static::STATUS_ACTIVE;
		
		// Get existing settings to preserve other values
		$existingSettings = $this->getUnserializedValue('setting', []);
		if (!is_array($existingSettings)) {
			$existingSettings = [];
		}
		
		// Build settings array - use array_replace to ensure 0 values override existing ones
		$settings = array_replace($existingSettings, [
			'chatUrl' => $this->chatUrl,
			'chatColor' => $this->chatColor,
			'chatVisible' => $this->chatVisible,
			'chatExpanded' => $this->chatExpanded,
			'chatRemove' => $this->chatRemove,
			'fontFamily' => $this->fontFamily,
			// Chat toggle settings
			'chatToggleBgColor' => $this->chatToggleBgColor,
			'chatToggleTextColor' => $this->chatToggleTextColor,
			'chatToggleHoverBgColor' => $this->chatToggleHoverBgColor,
			'chatToggleHoverTextColor' => $this->chatToggleHoverTextColor,
			'chatToggleWidth' => $this->chatToggleWidth !== null && $this->chatToggleWidth !== '' ? (int)$this->chatToggleWidth : ($this->chatToggleWidth === 0 ? 0 : ($existingSettings['chatToggleWidth'] ?? null)),
			'chatToggleHeight' => $this->chatToggleHeight !== null && $this->chatToggleHeight !== '' ? (int)$this->chatToggleHeight : ($this->chatToggleHeight === 0 ? 0 : ($existingSettings['chatToggleHeight'] ?? null)),
			'chatToggleFontSize' => $this->chatToggleFontSize !== null && $this->chatToggleFontSize !== '' ? (int)$this->chatToggleFontSize : ($this->chatToggleFontSize === 0 ? 0 : ($existingSettings['chatToggleFontSize'] ?? null)),
			'chatToggleBottom' => $this->chatToggleBottom !== null && $this->chatToggleBottom !== '' ? (int)$this->chatToggleBottom : ($this->chatToggleBottom === 0 ? 0 : ($existingSettings['chatToggleBottom'] ?? null)),
			'chatToggleRight' => $this->chatToggleRight !== null && $this->chatToggleRight !== '' ? (int)$this->chatToggleRight : ($this->chatToggleRight === 0 ? 0 : ($existingSettings['chatToggleRight'] ?? null)),
			'chatToggleZIndex' => $this->chatToggleZIndex !== null && $this->chatToggleZIndex !== '' ? (int)$this->chatToggleZIndex : ($this->chatToggleZIndex === 0 ? 0 : ($existingSettings['chatToggleZIndex'] ?? null)),
			'chatToggleBorderRadius' => $this->chatToggleBorderRadius !== null && $this->chatToggleBorderRadius !== '' ? (int)$this->chatToggleBorderRadius : ($this->chatToggleBorderRadius === 0 ? 0 : ($existingSettings['chatToggleBorderRadius'] ?? null)),
			'chatToggleIcon' => $this->chatToggleIcon,
			// Chat panel settings
			'chatTypingDotBgColor' => $this->chatTypingDotBgColor,
			'chatPanelBgColor' => $this->chatPanelBgColor,
			'chatPanelWidth' => $this->chatPanelWidth !== null && $this->chatPanelWidth !== '' ? (int)$this->chatPanelWidth : ($this->chatPanelWidth === 0 ? 0 : ($existingSettings['chatPanelWidth'] ?? null)),
			'chatPanelMaxHeight' => $this->chatPanelMaxHeight !== null && $this->chatPanelMaxHeight !== '' ? (int)$this->chatPanelMaxHeight : ($this->chatPanelMaxHeight === 0 ? 0 : ($existingSettings['chatPanelMaxHeight'] ?? null)),
			'chatPanelBottom' => $this->chatPanelBottom !== null && $this->chatPanelBottom !== '' ? (int)$this->chatPanelBottom : ($this->chatPanelBottom === 0 ? 0 : ($existingSettings['chatPanelBottom'] ?? null)),
			'chatPanelRight' => $this->chatPanelRight !== null && $this->chatPanelRight !== '' ? (int)$this->chatPanelRight : ($this->chatPanelRight === 0 ? 0 : ($existingSettings['chatPanelRight'] ?? null)),
			'chatPanelBorderRadius' => $this->chatPanelBorderRadius !== null && $this->chatPanelBorderRadius !== '' ? (int)$this->chatPanelBorderRadius : ($this->chatPanelBorderRadius === 0 ? 0 : ($existingSettings['chatPanelBorderRadius'] ?? null)),
			'chatPanelBoxShadow' => $this->chatPanelBoxShadow,
			'chatMicrophoneIcon' => $this->chatMicrophoneIcon,
			'chatMicrophoneBgColor' => $this->chatMicrophoneBgColor,
			'chatMicrophoneTextColor' => $this->chatMicrophoneTextColor,
			'chatMicrophoneHoverBgColor' => $this->chatMicrophoneHoverBgColor,
			'chatMicrophoneHoverTextColor' => $this->chatMicrophoneHoverTextColor,
			'chatEnvelopeIcon' => $this->chatEnvelopeIcon,
			'chatEnvelopeBgColor' => $this->chatEnvelopeBgColor,
			'chatEnvelopeTextColor' => $this->chatEnvelopeTextColor,
			'chatEnvelopeHoverBgColor' => $this->chatEnvelopeHoverBgColor,
			'chatEnvelopeHoverTextColor' => $this->chatEnvelopeHoverTextColor,
			'chatSendIcon' => $this->chatSendIcon,
			'chatSendBgColor' => $this->chatSendBgColor,
			'chatSendTextColor' => $this->chatSendTextColor,
			'chatSendHoverBgColor' => $this->chatSendHoverBgColor,
			'chatSendHoverTextColor' => $this->chatSendHoverTextColor,
			'chatInputContainerBorderRadius' => $this->chatInputContainerBorderRadius !== null && $this->chatInputContainerBorderRadius !== '' ? (int)$this->chatInputContainerBorderRadius : ($this->chatInputContainerBorderRadius === 0 ? 0 : ($existingSettings['chatInputContainerBorderRadius'] ?? null)),
			'chatInputBorderRadius' => $this->chatInputBorderRadius !== null && $this->chatInputBorderRadius !== '' ? (int)$this->chatInputBorderRadius : ($this->chatInputBorderRadius === 0 ? 0 : ($existingSettings['chatInputBorderRadius'] ?? null)),
			'chatEnvelopeButtonBorderRadius' => $this->chatEnvelopeButtonBorderRadius !== null && $this->chatEnvelopeButtonBorderRadius !== '' ? (int)$this->chatEnvelopeButtonBorderRadius : ($this->chatEnvelopeButtonBorderRadius === 0 ? 0 : ($existingSettings['chatEnvelopeButtonBorderRadius'] ?? null)),
			'chatSendButtonBorderRadius' => $this->chatSendButtonBorderRadius !== null && $this->chatSendButtonBorderRadius !== '' ? (int)$this->chatSendButtonBorderRadius : ($this->chatSendButtonBorderRadius === 0 ? 0 : ($existingSettings['chatSendButtonBorderRadius'] ?? null)),
			// Chat header settings
			'chatHeaderBgColor' => $this->chatHeaderBgColor,
			'chatHeaderTextColor' => $this->chatHeaderTextColor,
			'chatHeaderPadding' => $this->chatHeaderPadding !== null && $this->chatHeaderPadding !== '' ? (int)$this->chatHeaderPadding : ($this->chatHeaderPadding === 0 ? 0 : ($existingSettings['chatHeaderPadding'] ?? null)),
			'chatHeaderBorderRadius' => $this->chatHeaderBorderRadius !== null && $this->chatHeaderBorderRadius !== '' ? (int)$this->chatHeaderBorderRadius : ($this->chatHeaderBorderRadius === 0 ? 0 : ($existingSettings['chatHeaderBorderRadius'] ?? null)),
			'chatHeaderChevronIcon' => $this->chatHeaderChevronIcon,
			'chatHeaderExpandIcon' => $this->chatHeaderExpandIcon,
			// Chat modal settings
			'chatModalHeaderBgColor' => $this->chatModalHeaderBgColor,
			'chatModalHeaderTextColor' => $this->chatModalHeaderTextColor,
			'chatModalCancelBgColor' => $this->chatModalCancelBgColor,
			'chatModalCancelTextColor' => $this->chatModalCancelTextColor,
			'chatModalConfirmBgColor' => $this->chatModalConfirmBgColor,
			'chatModalConfirmTextColor' => $this->chatModalConfirmTextColor,
			'chatModalBtnBorderRadius' => $this->chatModalBtnBorderRadius !== null && $this->chatModalBtnBorderRadius !== '' ? (int)$this->chatModalBtnBorderRadius : ($this->chatModalBtnBorderRadius === 0 ? 0 : ($existingSettings['chatModalBtnBorderRadius'] ?? null)),
			'chatHeaderCompressIcon' => $this->chatHeaderCompressIcon,
			'chatHeaderNewConversationIcon' => $this->chatHeaderNewConversationIcon,
		]);
		
		// Remove null and empty string values but keep 0, false, and '0'
		$settings = array_filter($settings, function($value) {
			return $value !== null && $value !== '';
		}, ARRAY_FILTER_USE_BOTH);
		
		// Use setAttribute directly with serialize to avoid array_merge issue with 0 values
		$this->setAttribute('setting', serialize($settings));

		$saved = $this->save();
		
		// Save variables.css to uploads directory after successful save
		if ($saved) {
			$this->saveVariablesCss();
		}

		return $saved ? $this : null;
	}

	/**
	 * Generates variables.css content from interface settings
	 * @return string CSS content
	 */
	public function generateVariablesCss()
	{
		$defaults = $this->getDefaultChatValues();
		
		// Get chat color (use chatColor or chatToggleBgColor as fallback)
		$chatColor = $this->chatColor ?: ($this->chatToggleBgColor ?: $defaults['chatToggleBgColor']);
		// Generate hover color (darken by ~10%)
		$chatColorHover = $this->darkenColor($chatColor, 0.1);
		
		// Get font family
		$fontFamily = $this->fontFamily ?: 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
		
		$css = [];
		$css[] = '/* CSS Variables - Generated from Interface Settings */';
		$css[] = '/* This file is auto-generated. Do not edit manually. */';
		$css[] = '';
		$css[] = ':root {';
		$css[] = '  /* Font Family */';
		$css[] = '  --chat-font-family: ' . $fontFamily . ';';
		$css[] = '';
		$css[] = '  /* Chat Toggle (Bubble) Variables */';
		$css[] = '  --chat-toggle-width: ' . (($this->chatToggleWidth !== null && $this->chatToggleWidth !== '') ? $this->chatToggleWidth : $defaults['chatToggleWidth']) . 'px;';
		$css[] = '  --chat-toggle-height: ' . (($this->chatToggleHeight !== null && $this->chatToggleHeight !== '') ? $this->chatToggleHeight : $defaults['chatToggleHeight']) . 'px;';
		$css[] = '  --chat-toggle-bg-color: ' . ($this->chatToggleBgColor ?: $defaults['chatToggleBgColor']) . ';';
		$css[] = '  --chat-toggle-text-color: ' . ($this->chatToggleTextColor ?: $defaults['chatToggleTextColor']) . ';';
		$css[] = '  --chat-toggle-font-size: ' . (($this->chatToggleFontSize !== null && $this->chatToggleFontSize !== '') ? $this->chatToggleFontSize : $defaults['chatToggleFontSize']) . 'px;';
		$css[] = '  --chat-toggle-bottom: ' . (($this->chatToggleBottom !== null && $this->chatToggleBottom !== '') ? $this->chatToggleBottom : $defaults['chatToggleBottom']) . 'px;';
		$css[] = '  --chat-toggle-right: ' . (($this->chatToggleRight !== null && $this->chatToggleRight !== '') ? $this->chatToggleRight : $defaults['chatToggleRight']) . 'px;';
		$css[] = '  --chat-toggle-z-index: ' . (($this->chatToggleZIndex !== null && $this->chatToggleZIndex !== '') ? $this->chatToggleZIndex : $defaults['chatToggleZIndex']) . ';';
		$css[] = '  --chat-toggle-border-radius: ' . (($this->chatToggleBorderRadius !== null && $this->chatToggleBorderRadius !== '') ? $this->chatToggleBorderRadius : $defaults['chatToggleBorderRadius']) . 'px;';
		$css[] = '  --chat-toggle-hover-bg-color: ' . ($this->chatToggleHoverBgColor ?: $defaults['chatToggleHoverBgColor']) . ';';
		$css[] = '  --chat-toggle-hover-text-color: ' . ($this->chatToggleHoverTextColor ?: $defaults['chatToggleHoverTextColor']) . ';';
		$css[] = '';
		$css[] = '  /* Chat Panel Variables */';
		$css[] = '  --chat-typing-dot-bg-color: ' . ($this->chatTypingDotBgColor ?: $defaults['chatTypingDotBgColor']) . ';';
		$css[] = '  --chat-panel-bottom: ' . (($this->chatPanelBottom !== null && $this->chatPanelBottom !== '') ? $this->chatPanelBottom : $defaults['chatPanelBottom']) . 'px;';
		$css[] = '  --chat-panel-right: ' . (($this->chatPanelRight !== null && $this->chatPanelRight !== '') ? $this->chatPanelRight : $defaults['chatPanelRight']) . 'px;';
		$css[] = '  --chat-panel-width: ' . (($this->chatPanelWidth !== null && $this->chatPanelWidth !== '') ? $this->chatPanelWidth : $defaults['chatPanelWidth']) . 'px;';
		$css[] = '  --chat-panel-max-height: ' . (($this->chatPanelMaxHeight !== null && $this->chatPanelMaxHeight !== '') ? $this->chatPanelMaxHeight : $defaults['chatPanelMaxHeight']) . 'px;';
		$css[] = '  --chat-panel-bg-color: ' . ($this->chatPanelBgColor ?: $defaults['chatPanelBgColor']) . ';';
		$css[] = '  --chat-panel-border-radius: ' . (($this->chatPanelBorderRadius !== null && $this->chatPanelBorderRadius !== '') ? $this->chatPanelBorderRadius : $defaults['chatPanelBorderRadius']) . 'px;';
		$css[] = '  --chat-panel-box-shadow: ' . ($this->chatPanelBoxShadow ?: $defaults['chatPanelBoxShadow']) . ';';
		$css[] = '';
		$css[] = '  /* Chat Header Variables */';
		$css[] = '  --chat-header-bg-color: ' . ($this->chatHeaderBgColor ?: $defaults['chatHeaderBgColor']) . ';';
		$css[] = '  --chat-header-text-color: ' . ($this->chatHeaderTextColor ?: $defaults['chatHeaderTextColor']) . ';';
		$css[] = '  --chat-header-padding: ' . (($this->chatHeaderPadding !== null && $this->chatHeaderPadding !== '') ? $this->chatHeaderPadding : $defaults['chatHeaderPadding']) . 'px;';
		$css[] = '  --chat-header-border-radius: ' . (($this->chatHeaderBorderRadius !== null && $this->chatHeaderBorderRadius !== '') ? $this->chatHeaderBorderRadius : $defaults['chatHeaderBorderRadius']) . 'px;';
		$css[] = '';
		$css[] = '  /* Chat Modal Variables */';
		$css[] = '  --chat-modal-header-bg-color: ' . ($this->chatModalHeaderBgColor ?: $defaults['chatModalHeaderBgColor']) . ';';
		$css[] = '  --chat-modal-header-text-color: ' . ($this->chatModalHeaderTextColor ?: $defaults['chatModalHeaderTextColor']) . ';';
		$css[] = '  --chat-modal-cancel-bg-color: ' . ($this->chatModalCancelBgColor ?: $defaults['chatModalCancelBgColor']) . ';';
		$css[] = '  --chat-modal-cancel-text-color: ' . ($this->chatModalCancelTextColor ?: $defaults['chatModalCancelTextColor']) . ';';
		$css[] = '  --chat-modal-cancel-hover-bg-color: ' . ($this->chatModalCancelHoverBgColor ?: $defaults['chatModalCancelHoverBgColor']) . ';';
		$css[] = '  --chat-modal-cancel-hover-text-color: ' . ($this->chatModalCancelHoverTextColor ?: $defaults['chatModalCancelHoverTextColor']) . ';';
		$css[] = '  --chat-modal-confirm-bg-color: ' . ($this->chatModalConfirmBgColor ?: $defaults['chatModalConfirmBgColor']) . ';';
		$css[] = '  --chat-modal-confirm-text-color: ' . ($this->chatModalConfirmTextColor ?: $defaults['chatModalConfirmTextColor']) . ';';
		$css[] = '  --chat-modal-confirm-hover-bg-color: ' . ($this->chatModalConfirmHoverBgColor ?: $defaults['chatModalConfirmHoverBgColor']) . ';';
		$css[] = '  --chat-modal-confirm-hover-text-color: ' . ($this->chatModalConfirmHoverTextColor ?: $defaults['chatModalConfirmHoverTextColor']) . ';';
		$css[] = '  --chat-modal-btn-border-radius: ' . (($this->chatModalBtnBorderRadius !== null && $this->chatModalBtnBorderRadius !== '') ? $this->chatModalBtnBorderRadius : $defaults['chatModalBtnBorderRadius']) . 'px;';
		$css[] = '';
		$css[] = '  /* Chat Input Variables */';
		$css[] = '  --chat-input-container-border-radius: ' . (($this->chatInputContainerBorderRadius !== null && $this->chatInputContainerBorderRadius !== '') ? $this->chatInputContainerBorderRadius : $defaults['chatInputContainerBorderRadius']) . 'px;';
		$css[] = '  --chat-input-border-radius: ' . (($this->chatInputBorderRadius !== null && $this->chatInputBorderRadius !== '') ? $this->chatInputBorderRadius : $defaults['chatInputBorderRadius']) . 'px;';
		$css[] = '  --chat-envelope-button-border-radius: ' . (($this->chatEnvelopeButtonBorderRadius !== null && $this->chatEnvelopeButtonBorderRadius !== '') ? $this->chatEnvelopeButtonBorderRadius : $defaults['chatEnvelopeButtonBorderRadius']) . 'px;';
		$css[] = '  --chat-send-button-border-radius: ' . (($this->chatSendButtonBorderRadius !== null && $this->chatSendButtonBorderRadius !== '') ? $this->chatSendButtonBorderRadius : $defaults['chatSendButtonBorderRadius']) . 'px;';
		$css[] = '';
		$css[] = '  /* Chat Button Variables */';
		$css[] = '  --chat-microphone-bg-color: ' . ($this->chatMicrophoneBgColor ?: $defaults['chatMicrophoneBgColor']) . ';';
		$css[] = '  --chat-microphone-text-color: ' . ($this->chatMicrophoneTextColor ?: $defaults['chatMicrophoneTextColor']) . ';';
		$css[] = '  --chat-envelope-bg-color: ' . ($this->chatEnvelopeBgColor ?: $defaults['chatEnvelopeBgColor']) . ';';
		$css[] = '  --chat-envelope-text-color: ' . ($this->chatEnvelopeTextColor ?: $defaults['chatEnvelopeTextColor']) . ';';
		$css[] = '  --chat-send-bg-color: ' . ($this->chatSendBgColor ?: $defaults['chatSendBgColor']) . ';';
		$css[] = '  --chat-send-text-color: ' . ($this->chatSendTextColor ?: $defaults['chatSendTextColor']) . ';';
		$css[] = '';
		$css[] = '  /* Chat Button Hover Variables */';
		$css[] = '  --chat-microphone-hover-bg-color: ' . ($this->chatMicrophoneHoverBgColor ?: $defaults['chatMicrophoneHoverBgColor']) . ';';
		$css[] = '  --chat-microphone-hover-text-color: ' . ($this->chatMicrophoneHoverTextColor ?: $defaults['chatMicrophoneHoverTextColor']) . ';';
		$css[] = '  --chat-envelope-hover-bg-color: ' . ($this->chatEnvelopeHoverBgColor ?: $defaults['chatEnvelopeHoverBgColor']) . ';';
		$css[] = '  --chat-envelope-hover-text-color: ' . ($this->chatEnvelopeHoverTextColor ?: $defaults['chatEnvelopeHoverTextColor']) . ';';
		$css[] = '  --chat-send-hover-bg-color: ' . ($this->chatSendHoverBgColor ?: $defaults['chatSendHoverBgColor']) . ';';
		$css[] = '  --chat-send-hover-text-color: ' . ($this->chatSendHoverTextColor ?: $defaults['chatSendHoverTextColor']) . ';';
		$css[] = '}';
		$css[] = '';
		$css[] = '.chat-container, .chat-bubble, .chat-header, .chat-body, .chat-input-container, .chat-input, .chat-modal-content, .chat-modal-header, .chat-modal-body, .chat-modal-footer, .chat-modal-btn {';
		$css[] = '  font-family: var(--chat-font-family);';
		$css[] = '}';
		
		return implode("\n", $css);
	}

	/**
	 * Saves variables.css to workspace uploads directory
	 * @return bool
	 */
	public function saveVariablesCss()
	{
		try {
			// Get workspace ID from application ID
			$workspaceId = end(explode('-', Yii::$app->id));
			$workspace = \common\models\master\Workspace::findOne($workspaceId);
			
			if (!$workspace) {
				return false;
			}
			
			// Generate CSS content
			$cssContent = $this->generateVariablesCss();
			
			// Get uploads directory path
			$uploadsPath = Yii::getAlias('@workspaces') . '/' . $workspace->id . '/uploads';
			
			// Ensure directory exists
			if (!is_dir($uploadsPath)) {
				\yii\helpers\FileHelper::createDirectory($uploadsPath, 0755, true);
			}
			
			// Save variables.css
			$filePath = $uploadsPath . '/variables.css';
			$saved = file_put_contents($filePath, $cssContent) !== false;
			
			return $saved;
		} catch (\Exception $e) {
			Yii::error('Failed to save variables.css: ' . $e->getMessage(), __METHOD__);
			return false;
		}
	}

	/**
	 * Darkens a hex color by a percentage
	 * @param string $color Hex color (with or without #)
	 * @param float $percent Percentage to darken (0.0 to 1.0)
	 * @return string Darkened hex color
	 */
	private function darkenColor($color, $percent)
	{
		// Remove # if present
		$color = ltrim($color, '#');
		
		// Convert to RGB
		$r = hexdec(substr($color, 0, 2));
		$g = hexdec(substr($color, 2, 2));
		$b = hexdec(substr($color, 4, 2));
		
		// Darken by percentage
		$r = max(0, min(255, round($r * (1 - $percent))));
		$g = max(0, min(255, round($g * (1 - $percent))));
		$b = max(0, min(255, round($b * (1 - $percent))));
		
		// Convert back to hex
		return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . 
		       str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . 
		       str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
	}

	/**
	 * Gets default values for chat styling properties
	 * @return array
	 */
	private function getDefaultChatValues()
	{
		return [
			'chatToggleBgColor' => '#10a37f',
			'chatToggleTextColor' => '#ffffff',
			'chatToggleHoverBgColor' => '#0c8a6b',
			'chatToggleHoverTextColor' => '#ffffff',
			'chatToggleWidth' => 58,
			'chatToggleHeight' => 58,
			'chatToggleFontSize' => 26,
			'chatToggleBottom' => 20,
			'chatToggleRight' => 10,
			'chatToggleZIndex' => 3,
			'chatToggleBorderRadius' => 50,
			'chatTypingDotBgColor' => '#10a37f',
			'chatPanelBgColor' => '#ffffff',
			'chatPanelWidth' => 340,
			'chatPanelMaxHeight' => 500,
			'chatPanelBottom' => 90,
			'chatPanelRight' => 10,
			'chatPanelBorderRadius' => 10,
			'chatPanelBoxShadow' => '0 4px 10px rgba(0, 0, 0, 0.2)',
			'chatMicrophoneIcon' => 'fa fa-microphone',
			'chatMicrophoneBgColor' => '#10a37f',
			'chatMicrophoneTextColor' => '#ffffff',
			'chatEnvelopeIcon' => 'fa fa-envelope',
			'chatEnvelopeBgColor' => '#10a37f',
			'chatEnvelopeTextColor' => '#ffffff',
			'chatSendIcon' => 'fa fa-send',
			'chatSendBgColor' => '#10a37f',
			'chatSendTextColor' => '#ffffff',
			'chatInputContainerBorderRadius' => 12,
			'chatInputBorderRadius' => 6,
			'chatEnvelopeButtonBorderRadius' => 6,
			'chatHeaderBgColor' => '#10a37f',
			'chatHeaderTextColor' => '#ffffff',
			'chatHeaderPadding' => 12,
			'chatHeaderBorderRadius' => 12,
			'chatHeaderChevronIcon' => 'fa fa-chevron-down',
			'chatHeaderExpandIcon' => 'fa fa-expand',
			// Chat modal defaults
			'chatModalHeaderBgColor' => '#10a37f',
			'chatModalHeaderTextColor' => '#ffffff',
			'chatModalCancelBgColor' => '#e0e0e0',
			'chatModalCancelTextColor' => '#333333',
			'chatModalCancelHoverBgColor' => '#d0d0d0',
			'chatModalCancelHoverTextColor' => '#333333',
			'chatModalConfirmBgColor' => '#10a37f',
			'chatModalConfirmTextColor' => '#ffffff',
			'chatModalConfirmHoverBgColor' => '#0c8a6b',
			'chatModalConfirmHoverTextColor' => '#ffffff',
			'chatModalBtnBorderRadius' => 4,
			'chatHeaderCompressIcon' => 'fa fa-compress',
			'chatHeaderNewConversationIcon' => 'fa fa-refresh',
		];
	}

	/**
	 * Generates embeddable CSS for chat widget customization
	 * @return string CSS code
	 */
	public function generateChatEmbedCss()
	{
		$defaults = $this->getDefaultChatValues();
		$css = [];
		
		// Chat Toggle (Bubble) Styles
		$css[] = '/* Chat Toggle (Bubble) Styles */';
		$css[] = '.chat-bubble {';
		$css[] = '  background-color: ' . ($this->chatToggleBgColor ?: $defaults['chatToggleBgColor']) . ';';
		$css[] = '  color: ' . ($this->chatToggleTextColor ?: $defaults['chatToggleTextColor']) . ';';
		$css[] = '  width: ' . ($this->chatToggleWidth ?: $defaults['chatToggleWidth']) . 'px;';
		$css[] = '  height: ' . ($this->chatToggleHeight ?: $defaults['chatToggleHeight']) . 'px;';
		$css[] = '  font-size: ' . ($this->chatToggleFontSize ?: $defaults['chatToggleFontSize']) . 'px;';
		$css[] = '  bottom: ' . (($this->chatToggleBottom !== null && $this->chatToggleBottom !== '') ? $this->chatToggleBottom : $defaults['chatToggleBottom']) . 'px;';
		$css[] = '  right: ' . (($this->chatToggleRight !== null && $this->chatToggleRight !== '') ? $this->chatToggleRight : $defaults['chatToggleRight']) . 'px;';
		$css[] = '  z-index: ' . (($this->chatToggleZIndex !== null && $this->chatToggleZIndex !== '') ? $this->chatToggleZIndex : $defaults['chatToggleZIndex']) . ';';
		$css[] = '  border-radius: ' . (($this->chatToggleBorderRadius !== null && $this->chatToggleBorderRadius !== '') ? $this->chatToggleBorderRadius : $defaults['chatToggleBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Panel (Container) Styles
		$css[] = '/* Chat Panel (Container) Styles */';
		$css[] = '.chat-container {';
		$css[] = '  background-color: ' . ($this->chatPanelBgColor ?: $defaults['chatPanelBgColor']) . ';';
		$css[] = '  width: ' . ($this->chatPanelWidth ?: $defaults['chatPanelWidth']) . 'px;';
		$css[] = '  max-height: ' . ($this->chatPanelMaxHeight ?: $defaults['chatPanelMaxHeight']) . 'px;';
		$css[] = '  bottom: ' . (($this->chatPanelBottom !== null && $this->chatPanelBottom !== '') ? $this->chatPanelBottom : $defaults['chatPanelBottom']) . 'px;';
		$css[] = '  right: ' . (($this->chatPanelRight !== null && $this->chatPanelRight !== '') ? $this->chatPanelRight : $defaults['chatPanelRight']) . 'px;';
		$css[] = '  border-radius: ' . ($this->chatPanelBorderRadius ?: $defaults['chatPanelBorderRadius']) . 'px !important;';
		$css[] = '  box-shadow: ' . ($this->chatPanelBoxShadow ?: $defaults['chatPanelBoxShadow']) . ';';
		$css[] = '}';
		$css[] = '';
		
		// Chat Header Styles
		$css[] = '/* Chat Header Styles */';
		$css[] = '.chat-header {';
		$css[] = '  background-color: ' . ($this->chatHeaderBgColor ?: $defaults['chatHeaderBgColor']) . ';';
		$css[] = '  color: ' . ($this->chatHeaderTextColor ?: $defaults['chatHeaderTextColor']) . ';';
		$css[] = '  padding: ' . (($this->chatHeaderPadding !== null && $this->chatHeaderPadding !== '') ? $this->chatHeaderPadding : $defaults['chatHeaderPadding']) . 'px;';
		$css[] = '  border-top-left-radius: ' . (($this->chatHeaderBorderRadius !== null && $this->chatHeaderBorderRadius !== '') ? $this->chatHeaderBorderRadius : $defaults['chatHeaderBorderRadius']) . 'px !important;';
		$css[] = '  border-top-right-radius: ' . (($this->chatHeaderBorderRadius !== null && $this->chatHeaderBorderRadius !== '') ? $this->chatHeaderBorderRadius : $defaults['chatHeaderBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Input Container Styles
		$css[] = '/* Chat Input Container Styles */';
		$css[] = '.chat-input-container {';
		$css[] = '  border-bottom-left-radius: ' . (($this->chatInputContainerBorderRadius !== null && $this->chatInputContainerBorderRadius !== '') ? $this->chatInputContainerBorderRadius : $defaults['chatInputContainerBorderRadius']) . 'px !important;';
		$css[] = '  border-bottom-right-radius: ' . (($this->chatInputContainerBorderRadius !== null && $this->chatInputContainerBorderRadius !== '') ? $this->chatInputContainerBorderRadius : $defaults['chatInputContainerBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Input Styles
		$css[] = '/* Chat Input Styles */';
		$css[] = '.chat-input {';
		$css[] = '  border-top-left-radius: ' . (($this->chatInputBorderRadius !== null && $this->chatInputBorderRadius !== '') ? $this->chatInputBorderRadius : $defaults['chatInputBorderRadius']) . 'px !important;';
		$css[] = '  border-bottom-left-radius: ' . (($this->chatInputBorderRadius !== null && $this->chatInputBorderRadius !== '') ? $this->chatInputBorderRadius : $defaults['chatInputBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Envelope Button Styles
		$css[] = '/* Chat Envelope Button Styles */';
		$css[] = '.envelope-button {';
		$css[] = '  border-bottom-right-radius: ' . (($this->chatEnvelopeButtonBorderRadius !== null && $this->chatEnvelopeButtonBorderRadius !== '') ? $this->chatEnvelopeButtonBorderRadius : $defaults['chatEnvelopeButtonBorderRadius']) . 'px !important;';
		$css[] = '  border-top-right-radius: ' . (($this->chatEnvelopeButtonBorderRadius !== null && $this->chatEnvelopeButtonBorderRadius !== '') ? $this->chatEnvelopeButtonBorderRadius : $defaults['chatEnvelopeButtonBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Send Button Styles
		$css[] = '/* Chat Send Button Styles */';
		$css[] = '.send-button {';
		$css[] = '  border-radius: ' . (($this->chatSendButtonBorderRadius !== null && $this->chatSendButtonBorderRadius !== '') ? $this->chatSendButtonBorderRadius : $defaults['chatSendButtonBorderRadius']) . 'px !important;';
		$css[] = '}';
		$css[] = '';
		
		// Chat Modal Styles
		$css[] = '/* Chat Modal Styles */';
		$css[] = '.chat-modal-header {';
		$css[] = '  background-color: ' . ($this->chatModalHeaderBgColor ?: $defaults['chatModalHeaderBgColor']) . ';';
		$css[] = '  color: ' . ($this->chatModalHeaderTextColor ?: $defaults['chatModalHeaderTextColor']) . ';';
		$css[] = '}';
		$css[] = '';
		$css[] = '.chat-modal-cancel {';
		$css[] = '  background-color: ' . ($this->chatModalCancelBgColor ?: $defaults['chatModalCancelBgColor']) . ';';
		$css[] = '  color: ' . ($this->chatModalCancelTextColor ?: $defaults['chatModalCancelTextColor']) . ';';
		$css[] = '}';
		$css[] = '';
		$css[] = '.chat-modal-confirm {';
		$css[] = '  background-color: ' . ($this->chatModalConfirmBgColor ?: $defaults['chatModalConfirmBgColor']) . ';';
		$css[] = '  color: ' . ($this->chatModalConfirmTextColor ?: $defaults['chatModalConfirmTextColor']) . ';';
		$css[] = '}';
		$css[] = '';
		$css[] = '.chat-modal-btn {';
		$css[] = '  border-radius: ' . (($this->chatModalBtnBorderRadius !== null && $this->chatModalBtnBorderRadius !== '') ? $this->chatModalBtnBorderRadius : $defaults['chatModalBtnBorderRadius']) . 'px;';
		$css[] = '}';
		
		return implode("\n", $css);
	}


	/**
	 * Gets filtered icons for chevron/dropdown navigation
	 * @return array
	 */
	public static function getChevronIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for chevron/navigation icons
		$keywords = ['chevron', 'angle', 'arrow', 'caret', 'sort'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for expand/maximize
	 * @return array
	 */
	public static function getExpandIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for expand/maximize icons
		$keywords = ['expand', 'maximize', 'arrows-alt', 'plus', 'external-link', 'fullscreen'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for compress/minimize
	 * @return array
	 */
	public static function getCompressIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for compress/minimize icons
		$keywords = ['compress', 'minimize', 'arrows-alt', 'minus', 'times', 'close', 'remove'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for new conversation/refresh
	 * @return array
	 */
	public static function getNewConversationIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for new conversation/refresh icons
		$keywords = ['refresh', 'reload', 'repeat', 'redo', 'plus', 'new', 'comment', 'comments', 'envelope', 'paper-plane', 'send'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for microphone/audio related
	 * @return array
	 */
	public static function getMicrophoneIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for microphone/audio icons
		$keywords = ['microphone', 'mic', 'audio', 'sound', 'volume', 'headphones', 'music', 'play', 'pause', 'stop'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for envelope/message related
	 * @return array
	 */
	public static function getEnvelopeIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for envelope/message icons
		$keywords = ['envelope', 'mail', 'message', 'letter', 'paper-plane', 'send', 'inbox', 'outbox'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for send/submit related
	 * @return array
	 */
	public static function getSendIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for send/submit icons
		$keywords = ['send', 'paper-plane', 'arrow', 'check', 'check-circle', 'check-square', 'play', 'forward', 'share', 'upload'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}

	/**
	 * Gets filtered icons for chat toggle (chat/message/communication related)
	 * @return array
	 */
	public static function getToggleIcons()
	{
		$allIcons = \common\helpers\FontIcon::getDropdownIcons();
		$filtered = [];
		
		// Keywords for chat toggle icons
		$keywords = ['comment', 'comments', 'envelope', 'paper-plane', 'send', 'chat', 'message', 'phone', 'headphones', 'microphone', 'bell', 'notification', 'user', 'users', 'support', 'question', 'help', 'robot', 'bot', 'reddit'];
		
		foreach ($allIcons as $category => $icons) {
			$filtered[$category] = [];
			foreach ($icons as $key => $value) {
				$iconName = strtolower($key);
				foreach ($keywords as $keyword) {
					if (strpos($iconName, $keyword) !== false) {
						$filtered[$category][$key] = $value;
						break;
					}
				}
			}
		}
		
		return $filtered;
	}
}

