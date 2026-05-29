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
		this.threadId = this.getStoredThreadId();
	}

	async init() {
		if (this.threadId) {
			const isValid = await this.validateThread(this.threadId);
			if (!isValid) {
				console.warn('Stored thread ID is invalid. Creating a new one.');
				this.threadId = null;
				localStorage.removeItem('thread_id');
			}
		}
		if (!this.threadId) {
			await this.createThread();
		}
		this.setupEventListeners();
		this.loadConversation();
		this.sendDimensions();
	}

	getStoredThreadId() {
		return localStorage.getItem('thread_id') || null;
	}

	setStoredThreadId(threadId) {
		this.threadId = threadId;
		localStorage.setItem('thread_id', threadId);
	}

	async validateThread(threadId) {
		try {
			const response = await $.get(`chat/validate-thread?id=${threadId}`);
			return response.valid;
		} catch (e) {
			console.error('Thread validation failed:', e);
			return false;
		}
	}

	async createThread() {
		try {
			const res = await $.post('chat/thread');
			if (res.thread_id) {
				this.setStoredThreadId(res.thread_id);
			} else {
				console.error('Failed to create a thread:', res.error);
			}
		} catch (e) {
			console.error('Thread creation error:', e);
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

		this.chatBody.append(`<div class="chat-message user-message">${userMessage}</div>`);
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

		if (!this.threadId) {
			try {
				const res = await $.post('chat/thread');
				if (res.thread_id) {
					this.setStoredThreadId(res.thread_id);
				} else {
					throw new Error('No thread ID returned');
				}
			} catch (e) {
				botMessageDiv.text('Error: Unable to create a thread.');
				return;
			}
		}

		$.ajax({
			url: this.url,
			type: 'POST',
			data: {
				prompt: userMessage,
				thread_id: this.threadId
			},
			success: async (response) => {
				if (response.reply) {
					botMessageDiv.html(marked.parse(response.reply));
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
			this.chatBody.append(`<div class="chat-message user-message">${message.user}</div>`);
			let botMsgDiv = $('<div class="chat-message bot-message"></div>');
			botMsgDiv.html(marked.parse(message.bot));
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
				sessionId: this.threadId
			}, '*');
		});
	}

	setConversation(conversation) {
		window.parent.postMessage({
			type: 'set-conversation',
			sessionId: this.threadId,
			conversation
		}, '*');
	}

	isValidEmail(email) {
		return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
	}

	async sendConversationByEmail(email) {
		if (!this.threadId) {
			alert('No thread ID available.');
			return;
		}

		const icon = this.envelopeButton.find('i');
		icon.removeClass('fa-send').addClass('fa-spinner fa-spin');

		$.ajax({
			url: 'chat/send-conversation',
			type: 'POST',
			data: JSON.stringify({
				email,
				thread_id: this.threadId
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
		// Store old thread ID before clearing
		const oldThreadId = this.threadId;
		
		// Clear local storage
		localStorage.removeItem('thread_id');
		
		// Clear conversation from local storage using the old thread ID
		if (oldThreadId) {
			const conversationKey = 'conversation_thread_' + oldThreadId;
			localStorage.removeItem(conversationKey);
		}
		
		// Clear conversation from parent window
		window.parent.postMessage({
			type: 'clear-conversation',
			sessionId: oldThreadId
		}, '*');
		
		// Reset thread ID
		this.threadId = null;
		
		// Clear chat body
		this.chatBody.empty();
		this.updatePadding();
		
		// Create new thread
		this.createThread().then(() => {
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
