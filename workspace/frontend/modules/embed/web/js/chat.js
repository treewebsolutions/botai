/**
 * Markdown output is not safe to inject as-is.
 *
 * The widget renders assistant replies inside the tenant's own origin, on the
 * customer's site, and the reply is model output steered by the knowledge base
 * and by whatever the visitor types - so an indirect prompt injection is an XSS
 * vector. marked (v15) deliberately performs no sanitisation: it passes raw
 * <script>, inline event handlers and `javascript:` / `data:` URLs straight
 * through.
 *
 * Everything below runs over an inert document parsed by DOMParser, so no
 * script, image or iframe is ever fetched or executed while we inspect it. Only
 * the elements markdown can legitimately produce survive, with only the
 * attributes those elements need, and links are pinned to safe schemes.
 */
const MARKDOWN_ALLOWED_TAGS = {
	a: ['href', 'title'],
	img: ['src', 'alt', 'title'],
	p: [], br: [], hr: [],
	strong: [], b: [], em: [], i: [], s: [], del: [], ins: [], mark: [],
	code: [], pre: [], blockquote: [], span: [],
	ul: [], ol: ['start'], li: [],
	h1: [], h2: [], h3: [], h4: [], h5: [], h6: [],
	table: [], thead: [], tbody: [], tfoot: [], tr: [],
	th: ['align'], td: ['align'],
};

// Anything not absolute is resolved against the widget's own origin, so the
// relative forms are safe; the dangerous part is an explicit hostile scheme.
const MARKDOWN_SAFE_URL = /^(?:https?:|mailto:|tel:|#|\/|\.\.?\/|[^a-z0-9+.-]|[a-z0-9+.-]*[^a-z0-9+.:-])/i;

function isSafeUrl(value) {
	const url = String(value == null ? '' : value).trim();
	if (url === '') {
		return false;
	}
	// Strip characters a browser ignores when resolving the scheme, so
	// "java\tscript:" and "  javascript:" cannot slip past the test.
	const normalised = url.replace(/[\u0000-\u0020]/g, '');
	const scheme = normalised.match(/^([a-z][a-z0-9+.-]*):/i);
	if (!scheme) {
		return true; // relative, fragment or protocol-relative path
	}
	return ['http', 'https', 'mailto', 'tel'].indexOf(scheme[1].toLowerCase()) !== -1;
}

function sanitizeMarkdownHtml(html) {
	const doc = new DOMParser().parseFromString(String(html == null ? '' : html), 'text/html');
	const walk = (node) => {
		// Copy the list first: the loop reparents and removes as it goes.
		Array.prototype.slice.call(node.childNodes).forEach((child) => {
			if (child.nodeType === Node.TEXT_NODE) {
				return;
			}
			if (child.nodeType !== Node.ELEMENT_NODE) {
				child.remove(); // comments, CDATA, processing instructions
				return;
			}

			const tag = child.tagName.toLowerCase();
			const allowedAttributes = MARKDOWN_ALLOWED_TAGS[tag];

			if (!allowedAttributes) {
				// Keep what the author wrote, drop the markup carrying it -
				// except for tags whose content is itself the payload.
				if (['script', 'style', 'iframe', 'object', 'embed', 'template', 'noscript', 'svg', 'math'].indexOf(tag) !== -1) {
					child.remove();
				} else {
					walk(child);
					child.replaceWith(...child.childNodes);
				}
				return;
			}

			Array.prototype.slice.call(child.attributes).forEach((attribute) => {
				const name = attribute.name.toLowerCase();
				if (allowedAttributes.indexOf(name) === -1) {
					child.removeAttribute(attribute.name);
					return;
				}
				if ((name === 'href' || name === 'src') && !isSafeUrl(attribute.value)) {
					child.removeAttribute(attribute.name);
				}
			});

			if (tag === 'a') {
				// The widget is framed on someone else's page; never hand the
				// opener over to a link the model produced.
				child.setAttribute('rel', 'nofollow noopener noreferrer');
				child.setAttribute('target', '_blank');
			}

			walk(child);
		});
	};
	walk(doc.body);
	return doc.body.innerHTML;
}

/**
 * Renders assistant markdown into an element, sanitised.
 */
function renderMarkdown($element, markdown) {
	$element.html(sanitizeMarkdownHtml(marked.parse(String(markdown == null ? '' : markdown))));
}

class Chat {
	constructor(config) {
		this.language = config.language || 'ro-RO';
		this.color = config.color;
		this.url = config.url;
		this.ttsUrl = config.ttsUrl || 'https://botai/demo/embed/tts';
		this.chatContainer = $('.chat-container');
		this.chatBody = $('#chat-body');
		this.chatInput = $('#chat-input');
		this.microphoneButton = $('#microphone-button');
		this.sendButton = $('#send-button');
		this.envelopeButton = $('#envelope-button');
		this.newConversationButton = $('.chat-new-conversation');
		this.modal = $('#new-conversation-modal');
		this.isRecognitionActive = false;
		this.conversationId = this.getStoredConversationId();
	}

	async init() {
		if (this.conversationId) {
			const isValid = await this.validateConversation(this.conversationId);
			if (!isValid) {
				console.warn('Stored conversation ID is invalid. Creating a new one.');
				this.conversationId = null;
				localStorage.removeItem('conversation_id');
			}
		}
		if (!this.conversationId) {
			await this.createConversation();
		}
		this.setupEventListeners();
		this.loadConversation();
		this.sendDimensions();
	}

	getStoredConversationId() {
		return localStorage.getItem('conversation_id') || null;
	}

	setStoredConversationId(conversationId) {
		this.conversationId = conversationId;
		localStorage.setItem('conversation_id', conversationId);
	}

	async validateConversation(conversationId) {
		try {
			const response = await $.get(`chat/validate-conversation?id=${conversationId}`);
			return response.valid;
		} catch (e) {
			console.error('Conversation validation failed:', e);
			return false;
		}
	}

	async createConversation() {
		try {
			const res = await $.post('chat/conversation');
			if (res.conversation_id) {
				this.setStoredConversationId(res.conversation_id);
			} else {
				console.error('Failed to create a conversation:', res.error);
			}
		} catch (e) {
			console.error('Conversation creation error:', e);
		}
	}

	setupEventListeners() {
		window.addEventListener('message', this.handleMessageEvent.bind(this));
		window.parent.postMessage('requestDimensions', '*');

		$('.chat-toggle').click(this.toggleChatContainer.bind(this));
		$('.chat-resize').click(this.toggleExpandedState.bind(this));
		this.newConversationButton.click(this.showNewConversationModal.bind(this));
		this.microphoneButton.click(this.toggleSpeechRecognition.bind(this));
		window.addEventListener('message', this.handleSpeechRecognition.bind(this));
		this.sendButton.click(this.sendMessage.bind(this));
		this.chatInput.keypress(this.handleEnterKeyPress.bind(this));
		
		// Modal event listeners
		$('.chat-modal-cancel').click(this.hideNewConversationModal.bind(this));
		$('.chat-modal-confirm').click(this.startNewConversation.bind(this));
		$('.chat-modal-close').click(this.hideNewConversationModal.bind(this));
		$(this.modal).click((e) => {
			if ($(e.target).is(this.modal)) {
				this.hideNewConversationModal();
			}
		});

		this.envelopeButton.click(() => {
			const inputVal = this.chatInput.val().trim();

			if (!this.chatInput.hasClass('email-mode')) {
				this.chatInput.val('');
				this.chatInput.attr('placeholder', 'Email...');
				this.chatInput.addClass('email-mode');

				this.envelopeButton.find('i').removeClass('fa-envelope').addClass('fa-send');

				this.sendButton.find('i').removeClass('fa-send').addClass('fa-refresh');
				return;
			}

			if (this.isValidEmail(inputVal)) {
				this.sendConversationByEmail(inputVal);
			} else {
				this.chatInput.val('');
				this.chatInput.attr('placeholder', 'Please enter a valid email');
				this.chatInput.focus();
			}
		});
	}

	handleMessageEvent(event) {
		if (event.data && typeof event.data.width === 'number' && typeof event.data.height === 'number') {
			this.sendDimensions(event.data.height, event.data.width);
		}
	}

	sendDimensions(height, width) {
		var $toggle = $('.chat-toggle');
		var toggleHeight = $toggle.outerHeight();
		var containerHeight = this.chatContainer.outerHeight();
		var containerWidth = this.chatContainer.outerWidth();
		var isVisible = this.chatContainer.is(':visible');

		var message = {
			type: 'resize',
			height: height != null ? height : toggleHeight + containerHeight + 50,
			width: width != null ? width : containerWidth + 10,
			visible: isVisible
		};

		window.parent.postMessage(message, '*');
	}

	toggleChatContainer(e) {
		e.preventDefault();
		this.chatContainer.toggleClass('visible');
		this.sendDimensions();
	}

	toggleExpandedState(e) {
		e.preventDefault();
		this.chatContainer.toggleClass('expanded');
		this.chatBody.toggleClass('expanded');
		window.parent.postMessage('requestDimensions', '*');

		if (this.chatContainer.hasClass('expanded')) {
			$('.chat-resize').removeClass('chat-expand').addClass('chat-compress');
			$('.chat-resize i').removeClass('fa-expand').addClass('fa-compress');
		} else {
			$('.chat-resize').removeClass('chat-compress').addClass('chat-expand');
			$('.chat-resize i').removeClass('fa-compress').addClass('fa-expand');
		}
	}

	toggleSpeechRecognition() {
		const icon = this.microphoneButton.find('i');
		if (!this.isRecognitionActive) {
			window.parent.postMessage({ type: 'start-speech-recognition', language: this.language }, '*');
			icon.removeClass('fa-microphone').addClass('fa-microphone-slash');
			this.isRecognitionActive = true;
		} else {
			window.parent.postMessage({ type: 'stop-speech-recognition' }, '*');
			icon.removeClass('fa-microphone-slash').addClass('fa-microphone');
			this.isRecognitionActive = false;
		}
	}

	handleSpeechRecognition(event) {
		if (event.data && event.data.type === 'speech-recognition-result') {
			const transcript = event.data.transcript;
			this.chatInput.val(transcript);
			this.sendMessage();
			this.microphoneButton.find('i').removeClass('fa-microphone-slash').addClass('fa-microphone');
			this.isRecognitionActive = false;
		}
	}

	async sendMessage() {
		if (this.chatInput.hasClass('email-mode')) {
			this.chatInput.val('');
			this.chatInput.removeClass('email-mode');
			this.chatInput.attr('placeholder', 'Message...');

			// Reset icons
			this.sendButton.find('i').removeClass('fa-refresh').addClass('fa-send');
			this.envelopeButton.find('i').removeClass('fa-send').addClass('fa-envelope');
			return;
		}

		const userMessage = this.chatInput.val().trim();
		if (!userMessage) return;

		// The visitor's own text is never markup - .text() escapes it.
		this.chatBody.append($('<div class="chat-message user-message"></div>').text(userMessage));
		this.updatePadding();
		this.chatInput.val('');

		const botMessageDiv = $(`
			<div class="chat-message bot-message">
				<div class="loader"><div class="loader-inner">
					<div class="dot" style="${this.color ? 'background-color:' + this.color + ';' : ''}"></div>
					<div class="dot" style="${this.color ? 'background-color:' + this.color + ';' : ''}"></div>
					<div class="dot" style="${this.color ? 'background-color:' + this.color + ';' : ''}"></div>
				</div></div></div>`);
		this.chatBody.append(botMessageDiv);
		this.chatBody.scrollTop(this.chatBody[0].scrollHeight);
		window.parent.postMessage('requestDimensions', '*');
		this.sendDimensions();

		if (!this.conversationId) {
			try {
				const res = await $.post('chat/conversation');
				if (res.conversation_id) {
					this.setStoredConversationId(res.conversation_id);
				} else {
					throw new Error('No conversation ID returned');
				}
			} catch (e) {
				botMessageDiv.text('Error: Unable to create a conversation.');
				return;
			}
		}

		$.ajax({
			url: this.url,
			type: 'POST',
			data: {
				prompt: userMessage,
				conversation_id: this.conversationId
			},
			success: async (response) => {
				if (response.reply) {
					renderMarkdown(botMessageDiv, response.reply);
					this.addTextToSpeechButton(botMessageDiv, response.reply);

					let conversation = await this.getConversation();
					conversation.push({ user: userMessage, bot: response.reply });
					this.setConversation(conversation);
				} else {
					botMessageDiv.text('Error: No response received.');
				}
				this.updatePadding();
				this.sendDimensions();
				this.chatBody.scrollTop(this.chatBody[0].scrollHeight);
			},
			error: (xhr, status, error) => {
				botMessageDiv.text('Error: Unable to get a response. ' + error);
				this.updatePadding();
				this.sendDimensions();
			}
		});
	}

	handleEnterKeyPress(event) {
		if (event.which === 13) {
			this.sendMessage();
		}
	}

	updatePadding() {
		this.chatBody.css('padding', this.chatBody.children().length > 0 ? '12px' : '0');
	}

	async loadConversation() {
		const messages = await this.getConversation();
		messages.forEach((message) => {
			// The visitor's own text is never markup - .text() escapes it.
			this.chatBody.append($('<div class="chat-message user-message"></div>').text(message.user));
			let botMsgDiv = $('<div class="chat-message bot-message"></div>');
			renderMarkdown(botMsgDiv, message.bot);
			this.chatBody.append(botMsgDiv);
			this.addTextToSpeechButton(botMsgDiv, message.bot);
		});
		this.updatePadding();
		setTimeout(() => {
			this.chatBody.scrollTop(this.chatBody[0].scrollHeight);
			this.sendDimensions();
			window.parent.postMessage('requestDimensions', '*');
		}, 100);
	}

	getConversation() {
		return new Promise((resolve) => {
			const requestId = `getConversation_${Date.now()}`;
			const listener = (event) => {
				if (event.data?.type === 'conversation-data' && event.data.requestId === requestId) {
					window.removeEventListener('message', listener);
					resolve(event.data.conversation || []);
				}
			};
			window.addEventListener('message', listener);
			window.parent.postMessage({
				type: 'get-conversation',
				requestId,
				sessionId: this.conversationId
			}, '*');
		});
	}

	setConversation(conversation) {
		window.parent.postMessage({
			type: 'set-conversation',
			sessionId: this.conversationId,
			conversation
		}, '*');
	}

	isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	async sendConversationByEmail(email) {
		if (!this.conversationId) {
			alert('No conversation ID available.');
			return;
		}

		const icon = this.envelopeButton.find('i');
		icon.removeClass('fa-send').addClass('fa-spinner fa-spin');

		$.ajax({
			url: 'chat/send-conversation',
			type: 'POST',
			data: JSON.stringify({
				email,
				conversation_id: this.conversationId
			}),
			contentType: 'application/json; charset=utf-8',
			dataType: 'json',
			success: (res) => {
				if (res.success) {
					this.chatInput.val('');
					this.chatInput.removeClass('email-mode');
					this.chatInput.attr('placeholder', 'Message...');

					// Reset icons
					icon.removeClass('fa-spinner fa-spin fa-send').addClass('fa-envelope');
					this.sendButton.find('i').removeClass('fa-refresh').addClass('fa-send');
				} else {
					alert('Failed to send: ' + res.error);
					icon.removeClass('fa-spinner fa-spin').addClass('fa-send');
				}
			},
			error: (xhr, status, err) => {
				alert('Error sending conversation: ' + err);
				icon.removeClass('fa-spinner fa-spin').addClass('fa-send');
			}
		});
	}

	showNewConversationModal() {
		if (this.modal && this.modal.length) {
			this.modal.addClass('show');
		}
	}

	hideNewConversationModal() {
		if (this.modal && this.modal.length) {
			this.modal.removeClass('show');
		}
	}

	startNewConversation() {
		// Store old conversation ID before clearing
		const oldConversationId = this.conversationId;
		
		// Clear local storage
		localStorage.removeItem('conversation_id');
		
		// Clear conversation from local storage using the old conversation ID
		if (oldConversationId) {
			const conversationKey = 'conversation_' + oldConversationId;
			localStorage.removeItem(conversationKey);
		}
		
		// Clear conversation from parent window
		window.parent.postMessage({
			type: 'clear-conversation',
			sessionId: oldConversationId
		}, '*');
		
		// Reset conversation ID
		this.conversationId = null;
		
		// Clear chat body
		this.chatBody.empty();
		this.updatePadding();
		
		// Create new conversation
		this.createConversation().then(() => {
			this.hideNewConversationModal();
			this.loadConversation();
		});
	}

	addTextToSpeechButton(botMessageDiv, botMessage) {
		const startButton = $('<span class="tts-btn start-btn" style="cursor: pointer; margin-right: 5px;"><i class="fa fa-volume-up"></i></span>');
		const stopButton  = $('<span class="tts-btn stop-btn" style="cursor: pointer; display: none;"><i class="fa fa-stop"></i></span>');

		let isSpeaking = false;
		let audioPlayer;

		const fallbackSpeak = () => {
			const utterance = new SpeechSynthesisUtterance(botMessage);
			utterance.lang = this.language;
			window.speechSynthesis.speak(utterance);
			isSpeaking = true;
			startButton.hide();
			stopButton.show();

			utterance.onend = () => {
				startButton.show();
				stopButton.hide();
				isSpeaking = false;
			};
		};

		startButton.click(() => {
			if (isSpeaking) return;
			if (this.ttsUrl) {
				$.ajax({
					url: this.ttsUrl,
					type: 'POST',
					data: JSON.stringify({ text: botMessage, language: this.language }),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					success: (response) => {
						if (response.audioData) {
							const audioSrc = 'data:audio/mp3;base64,' + response.audioData;
							audioPlayer = new Audio(audioSrc);
							audioPlayer.play().catch(fallbackSpeak);
							isSpeaking = true;
							startButton.hide();
							stopButton.show();
							audioPlayer.onended = () => {
								startButton.show();
								stopButton.hide();
								isSpeaking = false;
							};
						} else {
							fallbackSpeak();
						}
					},
					error: fallbackSpeak
				});
			} else {
				fallbackSpeak();
			}
		});

		stopButton.click(() => {
			if (!isSpeaking) return;
			if (audioPlayer) {
				audioPlayer.pause();
				audioPlayer.currentTime = 0;
			} else {
				window.speechSynthesis.cancel();
			}
			isSpeaking = false;
			startButton.show();
			stopButton.hide();
		});

		botMessageDiv.append(startButton, stopButton);
	}
}
