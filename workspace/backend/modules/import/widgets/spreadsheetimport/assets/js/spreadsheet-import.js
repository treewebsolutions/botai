(function ($, window, document, undefined) {
	/**
	 * Constants
	 * @constant {String} PLUGIN_NAME
	 * @constant {String} PLUGIN_VERSION
	 * @constant {String} DATA_KEY
	 * @constant {Object} DEFAULTS
	 */
	var PLUGIN_NAME = 'spreadsheetImport',
		PLUGIN_VERSION = '0.0.1',
		EVENT_NS = '.' + PLUGIN_NAME,
		DATA_KEY = 'plugin_' + PLUGIN_NAME,
		DEFAULTS = {
			url: '/import-manager/spreadsheet',

			onInit: function () {},
			onDestroy: function () {}
		};

	/**
	 * Plugin
	 *
	 * @param element
	 * @param options
	 * @param metadata
	 * @constructor
	 */
	var Plugin = function (element, options, metadata) {
		// Exit if DOM element does not exist
		if (!element) {
			console.error('[' + PLUGIN_NAME + ']: DOM element is missing');
			return;
		}
		// Set the DOM element
		this.element = element;
		// Set options
		this.options = $.extend({}, DEFAULTS, options, metadata);
		// Initialization
		this.init();
	};

	/**
	 * Initialization
	 */
	Plugin.prototype.init = function () {
		// Cache elements
		this._cacheElements();
		// Bind events
		this._bindEvents();
		// Render the first action
		this.renderAction();
		// Hook event
		this._hook('onInit');
	};

	/**
	 * Caches DOM Elements.
	 *
	 * @private
	 */
	Plugin.prototype._cacheElements = function () {
		this.$document = $(document);
		this.$body = $(document.body);
		this.$element = $(this.element);
		this.steps = [];
	};

	/**
	 * Binds Events.
	 *
	 * @private
	 */
	Plugin.prototype._bindEvents = function () {
		this.$element.on('submit' + EVENT_NS, 'form', this._onFormSubmit.bind(this));
		this.$element.on('click' + EVENT_NS, '.mt-step-number', this._onStepClick.bind(this));
	};

	/**
	 * Renders a specific action.
	 *
	 * @param params
	 */
	Plugin.prototype.renderAction = function (params) {
		var me = this,
			url = this.options.url;

		if (params && params.url) {
			url = params.url;
		}

		this.$body.overlay();

		$.ajax({
			url: url,
			method: 'GET'
		}).done(function (response) {
			if (response.success) {
				me.$element.html(response.data);
				if (response.steps) {
					me.steps = response.steps;
				}
			}
		}).always(function () {
			me.$body.overlay('remove');
		});
	};

	/**
	 * Handles step form submit event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onFormSubmit = function (e) {
		var me = this,
			$target = $(e.target),
			formData = new FormData(e.target);

		this.$body.children('.tooltip').remove();
		this.$body.overlay();

		$.ajax({
			url: $target.attr('action'),
			method: $target.attr('method'),
			enctype: $target.attr('enctype'),
			data: formData,
			processData: false,
			contentType: false
		}).done(function (response) {
			// Show notification
			notify.show(response);
			// Check the response
			if (response.success) {
				// Render data
				me.$element.html(response.data);
				if (response.steps) {
					me.steps = response.steps;
				}
				// Redirect to a specific url
				if (response.returnUrl) {
					window.location.href = response.returnUrl;
				}
			}
		}).always(function () {
			me.$body.overlay('remove');
		});

		e.preventDefault();
	};

	/**
	 * Handles step click event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onStepClick = function (e) {
		var $target = $(e.currentTarget),
			targetData = $target.data(),
			step = this.steps[targetData.action];

		if (step) {
			this.renderAction(step);
		}
		e.preventDefault();
	};

	/**
	 * Hooks callbacks.
	 *
	 * @access private
	 * @param [arguments]
	 */
	Plugin.prototype._hook = function () {
		var args = Array.prototype.slice.call(arguments),
			hookName = args.shift(),
			eventName = hookName.split(/(?=[A-Z])/)[1].toLowerCase() + EVENT_NS;

		if (typeof this.options[hookName] !== 'undefined') {
			// Callback
			this.options[hookName].apply(this.element, args);
			// Create a new event
			var event = $.Event(eventName, {
				target: this.element
			});
			// Trigger the event
			this.$element.trigger(event, args);
		}
	};

	/**
	 * Gets or sets a property.
	 *
	 * @access public
	 * @param {String} key
	 * @param {String} val
	 */
	Plugin.prototype.option = function (key, val) {
		if (val) {
			this.options[key] = val;
		} else {
			return this.options[key];
		}
	};

	/**
	 * Destroys the plugin instance.
	 *
	 * @public
	 */
	Plugin.prototype.destroy = function () {
		this._hook('onDestroy');
		this.$element.off(EVENT_NS);
		this.$element.removeData(DATA_KEY);
	};

	/**
	 * Plugin definition
	 * @function external "jQuery.fn".spreadsheetImport
	 */
	$.fn[PLUGIN_NAME] = function (options) {
		var args = arguments;

		if (!options || typeof options === "object") {
			return this.each(function () {
				if (!$.data(this, DATA_KEY)) {
					var metadata = $(this).data();
					$.data(this, DATA_KEY, new Plugin(this, options, metadata));
				}
			});
		} else if (typeof args[0] === 'string') {
			var methodName = args[0].replace('_', ''),
				returnVal;

			this.each(function () {
				var instance = $.data(this, DATA_KEY);

				if (instance && typeof instance[methodName] === 'function') {
					returnVal = instance[methodName].apply(instance, Array.prototype.slice.call(args, 1));
				} else {
					throw new Error('Could not call method "' + methodName + '" on jQuery.fn.' + PLUGIN_NAME);
				}
			});

			return (typeof returnVal !== 'undefined') ? returnVal : this;
		}
	};

	/**
	 * Expose global
	 */
	this[PLUGIN_NAME] = Plugin;

})(jQuery, window, document);
