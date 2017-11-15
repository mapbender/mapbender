(function($) {

    $.widget("mapbender.mbScaleSelector", {

        options: {
            /**
             * Target widget id string
             */
            target: null
        },

        elementUrl: null,

        /**
         * Map target instance of OpenLayers.Map
         *
         * @var {OpenLayers.Map}
         */
        olMap: null,

        /**
         * Mapbender map widget
         */
        map: null,

        /**
         * Ready event listeners
         *
         * @var {Array<Function>}
         */
        readyCallbacks: [],

        /**
         * Constructor
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var options = widget.options;
            var target = options.target;

            if(!Mapbender.checkTarget("mbScaleSelector", target)) {
                return;
            }

            var element = $(widget.element);

            widget.elementUrl = Mapbender.configuration.elementPath + element.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(target, $.proxy(widget._setup, widget));
        },

        _setup: function() {
            var widget = this;
            var options = widget.options;
            var mbMap = widget.map = $('#' + options.target).data('mapbenderMbMap');
            var olMap = widget.olMap = mbMap.map.olMap;
            var scale = Math.round(olMap.getScale());
            var select = $("select", $(widget.element));

            _.each(mbMap.scales(), function(value) {
                var option = $("<option/>");
                var formattedValue = Math.round(value);

                option.attr('value', formattedValue).html(formattedValue);
                select.append(option);
            });

            select.change($.proxy(widget._zoomToScale, widget));
            select.val(scale);

            initDropdown.call(this.element.get(0));

            widget._updateScale();

            olMap.events.register('zoomend', widget, $.proxy(widget._updateScale, widget));

            widget._trigger('ready');
            widget._ready();
        },

        /**
         * Zoom to scale event handler
         * @private
         */
        _zoomToScale: function() {
            var widget = this;
            var element = $(widget.element);
            var scale = $("> select", element).val();
            var map = widget.map;

            map.zoomToScale(scale, true);
        },

        /**
         * Update scale drop down view
         *
         * @private
         */
        _updateScale: function() {
            var widget = this;
            var olMap = widget.olMap;
            var scale = Math.round(olMap.getScale());
            var element = $(widget.element);
            var select = $("> select", element);

            select
                .val(scale)
                .siblings(".dropdownValue")
                .text(scale);
        },

        /**
         * Adds event listener callback function
         * Called on widget is ready
         *
         * @param {Function} callback
         */
        ready: function(callback) {
            var widget = this;
            if(widget.readyState === true) {
                callback();
            } else {
                widget.readyCallbacks.push(callback);
            }
        },

        /**
         * On ready event handler
         *
         * @private
         */
        _ready: function() {
            var widget = this;
            var callbacks = widget.readyCallbacks;

            _.each(callbacks, function(callback) {

                // Call and change context to widget
                callback.call(widget);

                // Remove from array
                callbacks.splice(callbacks.indexOf(callback), 1);
            });

            widget.readyState = true;
        },

        _destroy: $.noop
    });

})(jQuery);

