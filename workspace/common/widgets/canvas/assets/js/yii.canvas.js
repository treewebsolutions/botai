(function ($, window, document, undefined) {
	/**
	 * Constants
	 * @constant {String} PLUGIN_NAME
	 * @constant {String} PLUGIN_VERSION
	 * @constant {String} DATA_KEY
	 * @constant {Object} DEFAULTS
	 */
	var PLUGIN_NAME = 'yiiCanvas',
		PLUGIN_VERSION = '0.0.1',
		EVENT_NS = '.' + PLUGIN_NAME,
		DATA_KEY = 'plugin_' + PLUGIN_NAME,
		DEFAULTS = {
			responsive: true,
			aspectRatio: 16/10,
			resizeTimeout: 64
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
		// Init Canvas
		this.canvas = new fabric.Canvas(this.$canvas.get(0), this.options);
		// Bind events
		this._bindEvents();
		// Set the canvas size automatically if the responsive option is set
		if (this.options.responsive) {
			this.updateCanvasSize();
		}
		this.loadInputValue();
		// Hook event
		this._hook('onInit');
	};

	/**
	 * Caches DOM Elements.
	 *
	 * @private
	 */
	Plugin.prototype._cacheElements = function () {
		this.$window = $(window);
		this.$document = $(document);
		this.$body = $(document.body);
		this.$element = $(this.element);
		this.$container = this.$element.closest('.canvas-container');
		this.$canvas = this.$container.find('.canvas-element');
	};

	/**
	 * Binds Events.
	 *
	 * @private
	 */
	Plugin.prototype._bindEvents = function () {
		// DOM events
		this.$window.on('resize' + EVENT_NS, this._onWindowResize.bind(this));
		this.$document.on('click' + EVENT_NS, '[data-canvas-clear]', this._onClearCanvasClick.bind(this));

		// Canvas events
		this.canvas.on('mouse:up', this._onCanvasMouseUp.bind(this));
	};

	/**
	 * Handles window resize event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onWindowResize = function (e) {
		// Check if the canvas should be responsive
		if (this.options.responsive) {
			clearTimeout(this._resizeTimer);
			this._resizeTimer = setTimeout(this.updateCanvasSize.bind(this), this.options.resizeTimeout);
		}
	};

	/**
	 * Handles canvas button control click event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onClearCanvasClick = function (e) {
		var $target = $(e.currentTarget),
			targetData = $target.data(),
			$element = this.$body.find(targetData.canvasClear);
		// Clear the targeted canvas
		if ($element.is(this.element)) {
			this.canvas.clear();
			this.$element.val('').trigger('change');
		}
	};

	/**
	 * Handles canvas mouse up event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onCanvasMouseUp = function (e) {
		this.setInputValue();
	};

	/**
	 * Loads the data from the input and place it on the canvas.
	 */
	Plugin.prototype.loadInputValue = function (value) {
		var me = this;

		value = value || this.$element.val();

		fabric.Image.fromURL(value, function (img) {
			img.scaleToWidth(me.canvas.getWidth());
			img.scaleToHeight(me.canvas.getHeight());

			me.canvas.add(img).renderAll();

			me.setInputValue();
		});
	};

	/**
	 * Sets the input value as canvas base64 data.
	 *
	 * @param value
	 */
	Plugin.prototype.setInputValue = function (value) {
		value = value || this.canvas.toDataURL('png');

		this.$element.val(value).trigger('change');
	};

	/**
	 * Sets the canvas element width and height.
	 *
	 * @param w
	 * @param h
	 */
	Plugin.prototype.updateCanvasSize = function (w, h) {
		// Set the canvas size
		var containerWidth = w || this.$container.width(),
			containerHeight = h || this.$container.height(),
			width = containerWidth,
			height = containerHeight;

		// Compute width and height for a specific aspect ratio
		if (typeof this.options.aspectRatio === 'number') {
			height = this.computeHeight(containerWidth, this.options.aspectRatio);
			width = this.computeWidth(height, this.options.aspectRatio);
		}
		// Set canvas dimensions
		this.canvas.setDimensions({
			width: width,
			height: height
		});
		// Scale the canvas objects to the new canvas dimensions
		$.each(this.canvas.getObjects(), function (index, canvasObject) {
			canvasObject.scaleToWidth(width);
			canvasObject.scaleToHeight(height);
			canvasObject.center();
		});
	};

	/**
	 * Computes the height of a container based on a base size and a ratio value.
	 *
	 * @param width
	 * @param ratio
	 * @return {number}
	 */
	Plugin.prototype.computeWidth = function (width, ratio) {
		return Math.round(((width)/(Math.sqrt((1)/(Math.pow(ratio, 2)+1)))));
	};

	/**
	 * Computes the height of a container based on a base size and a ratio value.
	 *
	 * @param height
	 * @param ratio
	 * @return {number}
	 */
	Plugin.prototype.computeHeight = function (height, ratio) {
		return Math.round(((height)/(Math.sqrt((Math.pow(ratio, 2)+1)))));
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
		this.$window.off(EVENT_NS);
		this.$document.off(EVENT_NS);
		this.$element.off(EVENT_NS);
		this.$element.removeData(DATA_KEY);
	};

	/**
	 * Plugin definition
	 * @function external "jQuery.fn".yiiCanvas
	 */
	$.fn[PLUGIN_NAME] = function (options) {
		var args = arguments;

		if (!options || typeof options === 'object') {
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
