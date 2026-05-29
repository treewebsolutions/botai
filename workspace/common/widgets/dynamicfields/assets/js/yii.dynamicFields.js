(function ($, window, document, undefined) {
	/**
	 * Constants
	 * @constant {String} PLUGIN_NAME
	 * @constant {String} PLUGIN_VERSION
	 * @constant {String} DATA_KEY
	 * @constant {Object} DEFAULTS
	 */
	var PLUGIN_NAME = 'yiiDynamicFields',
		PLUGIN_VERSION = '0.0.1',
		EVENT_NS = '.' + PLUGIN_NAME,
		DATA_KEY = 'plugin_' + PLUGIN_NAME,
		DEFAULTS = {
			min: 0,
			max: Infinity,
			insertType: 'append',
			insertContainer: null,
			keepValues: false,
			emptyMessage: 'No items found.',

			onInit: function () {},
			onDestroy: function () {},
			onChange: function (itemsCount, $item, operation) {}
		},
		PATTERN_ID = /^(.+?)([-\d-]{1,})(.+)$/i,
		PATTERN_NAME = /(^.+?)([\[\d{1,}\]]{1,})(\[.+\]$)/i;

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
		// Update the controls state
		this.updateControlsState();
		// Toggle empty item visibility
		this._toggleEmptyItem();
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
		this.$html = $('html');
		this.$body = this.$html.children('body');
		this.$element = $(this.element);
		this.$form = this.$element.closest('form');
		this.$insertContainer = this.options.insertContainer ?
			this.$body.find(this.options.insertContainer) :
			this.$element;
		// Cache the first item to make the add operation work if all items are deleted
		this._$firstItem = this.getItems().first();
	};

	/**
	 * Binds Events.
	 *
	 * @private
	 */
	Plugin.prototype._bindEvents = function () {
		this.$element.on('click' + EVENT_NS, '.dynamicfields-add', this._onAddFieldsClick.bind(this));
		this.$element.on('click' + EVENT_NS, '.dynamicfields-delete', this._onDeleteFieldsClick.bind(this));
	};

	/**
	 * Gets all items.
	 *
	 * @return {*}
	 */
	Plugin.prototype.getItems = function () {
		return this.$element.find('.dynamicfields-item');
	};

	/**
	 * Gets items count.
	 *
	 * @return number
	 */
	Plugin.prototype.getItemsCount = function () {
		return this.getItems().length;
	};

	/**
	 * Gets first item.
	 *
	 * @return {*}
	 */
	Plugin.prototype.getFirstItem = function () {
		if (!this._$firstItem) {
			this._$firstItem = this.getItems().first();
		}
		return this._$firstItem;
	};

	/**
	 * Handles DynamicFields add button click event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onAddFieldsClick = function (e) {
		var $item = this.getFirstItem().clone();
		// Exit if items reached the maximum allowed number
		if (this.getItemsCount() >= this.options.max) {
			return;
		}
		// Reset all item inputs values
		if (!this.options.keepValues) {
			$item.find(':input').val('');
			$item.find(':checkbox, :radio').prop('checked', false);
		} else if (this.options.keepValues instanceof Array) {
			var keepValuesFields = $.map(this.options.keepValues, function (item, index) {
				return '[name*="[' + item + ']"]';
			});
			$item.find(':input').not(keepValuesFields.join(',')).val('');
			$item.find(':checkbox, :radio').not(keepValuesFields.join(',')).prop('checked', false);
		}
		// Remove all select2 elements
		$item.find('.select2').remove();
		// Remove unneeded fields
		$item.find('.dynamicfields-ignore').remove();
		// Clear values for the specified elements
		$item.find('.dynamicfields-clear').each(function (index, tag) {
			var $tag = $(tag);
			if ($tag.is(':input')) {
				$tag.val('');
			} else {
				$tag.text('');
			}
		});
		// Add a new item
		if (this.options.insertType === 'prepend') {
			this.$insertContainer.prepend($item);
		} else if (this.options.insertType === 'append') {
			this.$insertContainer.append($item);
		}
		// Toggle empty item visibility
		this._toggleEmptyItem();
		// Update controls state
		this.updateControlsState();
		// Reindex items
		this.reindexItems();
		// Reinit plugins
		this.reinitPlugins($item);
		// Hook change event
		this._hook('onChange', this.getItemsCount(), $item, 'add');
	};

	/**
	 * Handles DynamicFields delete button click event.
	 *
	 * @param e
	 * @private
	 */
	Plugin.prototype._onDeleteFieldsClick = function (e) {
		var $target = $(e.currentTarget),
			targetData = $target.data();
		// Exit if items reached the minimum required number
		if (this.getItemsCount() <= this.options.min) {
			return;
		}
		// Confirm operation
		if (!confirm(targetData.deleteMessage)) {
			return;
		}
		// Destroy the Bootstrap tooltip if is attached to the current control
		if (targetData['bs.tooltip']) {
			$target.tooltip('destroy');
		}
		// Get the item
		var $item = $target.closest('.dynamicfields-item');
		// Remove the item
		$item.remove();
		// Toggle empty item visibility
		this._toggleEmptyItem();
		// Update controls state
		this.updateControlsState();
		// Reindex items
		this.reindexItems();
		// Hook change event
		this._hook('onChange', this.getItemsCount(), $item, 'remove');
	};

	/**
	 * Toggle empty item visibility.
	 */
	Plugin.prototype._toggleEmptyItem = function () {
		var itemsCount = this.getItemsCount(),
			$firstItem = this.getFirstItem();
		// Check the items count
		if (itemsCount === 0) {
			var $emptyItem = $('<div>', {
				'class': 'dynamicfields-empty-item',
				'text': this.options.emptyMessage
			});
			// Check the first item node type
			if ($firstItem.is('tr')) {
				// Create the item content
				var $td = $('<td>', {
					'colspan': $firstItem.children().length,
					'text': this.options.emptyMessage
				});
				// Create the item
				$emptyItem = $('<tr>', {
					'class': 'dynamicfields-empty-item',
					'html': $td.prop('outerHTML')
				});
			}
			// Append the empty item to the insert container
			this.$insertContainer.html($emptyItem);
		} else if (itemsCount === 1) {
			// Check if the first item is a new record
			if ($firstItem.hasClass('dynamicfields-first-item')) {
				// Remove if from the DOM
				$firstItem.removeClass('dynamicfields-first-item').remove();
				// Call again this method
				this._toggleEmptyItem();
			} else {
				// Remove the empty items message DOM element
				this.$element.find('.dynamicfields-empty-item').remove();
			}
		} else {
			// Remove the empty items message DOM element
			this.$element.find('.dynamicfields-empty-item').remove();
		}
	};

	// TODO: To review the _updateTagAttribute() and reindexItems() method to replace the indexes as for the class attribute

	/**
	 * Updates a tag attribute based on a specified index.
	 *
	 * @param $tag
	 * @param attribute
	 * @param index
	 * @private
	 */
	Plugin.prototype._updateTagAttribute = function ($tag, attribute, index) {
		var tagAttributte = $tag.attr(attribute),
			matches = tagAttributte.match(attribute === 'name' ? PATTERN_NAME : PATTERN_ID),
			getId = function () {
				return matches[1].toLowerCase() + '-' + index + '-' + matches[3].replace(/[\[\]']+/g, '');
			},
			getName = function () {
				return matches[1] + '[' + index + ']' + matches[3];
			},
			attributeValue;
		// Exit if there are no matches at all
		if (!matches || !matches.length) {
			return;
		}
		// Set the new attribute value using name or id format
		if (attribute === 'name') {
			// Set the attribute value
			attributeValue = getName();
			// Setup field validation
			this.setupFieldValidation($tag, attributeValue, getId());
		} else if (attribute === 'class') {
			attributeValue = $tag.attr('class').replace(/-\d-/gmi, ('-' + index + '-'));
		} else if (attribute === 'data-autofill-data') {
			attributeValue = tagAttributte.replace(/-\d-/gmi, ('-' + index + '-'));
		} else {
			attributeValue = getId();
		}
		// Set tag attribute
		$tag.attr(attribute, attributeValue);
	};

	/**
	 * Updates controls state.
	 */
	Plugin.prototype.updateControlsState = function () {
		var itemsCount = this.getItemsCount();
		// Handle delete control visibility
		if (this.options.min > 0) {
			this.$element.find('.dynamicfields-delete').toggleClass('hidden', (itemsCount <= this.options.min));
		}
		// Handle add control visibility
		if (this.options.max > 0) {
			this.$element.find('.dynamicfields-add').toggleClass('hidden', (itemsCount >= this.options.max));
		}
	};

	/**
	 * Sets the validation to a field.
	 *
	 * @param $field
	 * @param name
	 * @param id
	 */
	Plugin.prototype.setupFieldValidation = function ($field, name, id) {
		var firstItemFieldId = id.replace(/-\d-/gmi, ('-0-')),
			$firstItemField = this.getFirstItem().find('#' + firstItemFieldId),
			validationAttribute = this.$form.yiiActiveForm('find', $firstItemField.attr('id'));
		// Exit if the base validation attribute was not found
		if (typeof validationAttribute === 'undefined') {
			return;
		}
		// Set the properties for the validation attribute
		validationAttribute = $.extend(true, {}, validationAttribute);
		validationAttribute.id = id;
		validationAttribute.container = '.field-' + id;
		validationAttribute.input = '#' + id;
		validationAttribute.name = name;
		validationAttribute.value = $field.val();
		validationAttribute.status = 0;
		// Remove the old validation if exist
		if (typeof this.$form.yiiActiveForm('find', id) !== 'undefined') {
			this.$form.yiiActiveForm('remove', id);
		}
		// Add new attribute to yiiActiveForm
		this.$form.yiiActiveForm('add', validationAttribute);
	};

	/**
	 * Reindex all items.
	 */
	Plugin.prototype.reindexItems = function () {
		var me = this;
		// Loop through the items
		this.getItems().each(function (index, item) {
			var $item = $(item),
				$itemIndexLabel = $item.find('.dynamicfields-index');
			// Set the item index label
			$itemIndexLabel.text(index + 1);
			// Update tag attributes for the item itself
			me._updateTagAttribute($item, 'id', index);
			// Loop through the item tags
			$item.find('*').each(function (tagIndex, tag) {
				var $tag = $(tag);

				if ($tag.attr('id')) {
					me._updateTagAttribute($tag, 'id', index);
				}
				if ($tag.attr('for')) {
					me._updateTagAttribute($tag, 'for', index);
				}
				if ($tag.attr('name')) {
					me._updateTagAttribute($tag, 'name', index);
				}
				if ($tag.is('[class*="field-"]')) {
					me._updateTagAttribute($tag, 'class', index);
				}
				// Plugins specific
				if ($tag.attr('data-autofill-target')) {
					me._updateTagAttribute($tag, 'data-autofill-target', index);
				}
				if ($tag.attr('data-autofill-data')) {
					me._updateTagAttribute($tag, 'data-autofill-data', index);
				}
				if ($tag.attr('data-toggle-visibility')) {
					me._updateTagAttribute($tag, 'data-toggle-visibility', index);
				}
			});
		});
	};

	/**
	 * Reinitialize plugins.
	 *
	 * @param $container
	 */
	Plugin.prototype.reinitPlugins = function ($container) {
		// Fallback container to the current plugin element
		$container = $container || this.$element;
		var $selects2 = $container.find('[data-krajee-select2]'),
			$datetimepickers = $container.find('.datetimepicker');
		// Select2
		if ($.fn.select2 && $selects2.length) {
			$selects2.each(function (index, select2) {
				var $select2 = $(select2);
				if ($select2.data('select2')) {
					$select2.select2('destroy');
				}
				$.when($select2.select2(window[$select2.attr('data-krajee-select2')]))
					.done(initS2Loading($select2.attr('id'), $select2.attr('data-s2-options')));
			});
		}
		// DateTimePicker
		if ($.fn.datetimepicker && $datetimepickers.length) {
			$datetimepickers.each(function (index, datetimepicker) {
				var $datetimepicker = $(datetimepicker),
					$formControl = $datetimepicker.find('.datetimepicker-input');
				if ($datetimepicker.data('DateTimePicker')) {
					$datetimepicker.data('DateTimePicker').destroy();
				}
				$datetimepicker.datetimepicker(window[$formControl.attr('data-datetimepicker-options')]);
			});
		}
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
		this.$document.off(EVENT_NS);
		this.$element.off(EVENT_NS);
		this.$element.removeData(DATA_KEY);
	};

	/**
	 * Plugin definition
	 * @function external "jQuery.fn".timeslots
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
