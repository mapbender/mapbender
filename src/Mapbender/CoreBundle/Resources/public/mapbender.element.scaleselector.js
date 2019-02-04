(function($) {

    $.widget('mapbender.mbScaleSelector', {

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
         * Constructor
         *
         * @private
         */
        _create: function() {
            var widget = this;
            var options = widget.options;
            var target = options.target;

            if(!Mapbender.checkTarget('mbScaleSelector', target)) {
                return;
            }

            var element = $(widget.element);

            widget.elementUrl = Mapbender.configuration.elementPath + element.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(target, $.proxy(widget._setup, widget));
        },

        _setup: function() {
            var widget = this;

            this.map = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            this.olMap = this.map.model.map.getView();

            var mbMap = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            var model = mbMap.model;
            var olMap = mbMap.model.map.getView();

            var opt = model.options;
            var scales = opt.scales;
            var dpi = opt.dpi;

            var scale = model.getScale(dpi, true, false);
            var select = $('select', $(widget.element));

            _.each(scales, function(scaleVal) {
                var option = $('<option/>');
                var formattedValue = Math.round(scaleVal);

                option.attr('value', formattedValue).html(formattedValue);
                select.append(option);
            });

            initDropdown.call(this.element.get(0));

            select.change($.proxy(widget._zoomToScale, widget));
            select.val(scale);

            widget._updateScale();

            model.setOnMoveendHandler($.proxy(widget._updateScale, widget), event);

            widget._trigger('ready');
        },

        /**
         * Zoom to scale event handler
         * @private
         */
        _zoomToScale: function() {
            var element = $(this.element);
            var scale = $('> select', element).val();

            this.map.model.setScale(scale);
        },

        /**
         * Update scale drop down view
         *
         * @private
         */
        _updateScale: function() {
            var widget = this;
            var model = widget.map.model;
            var dpi = model.options.dpi;

            var scale = Math.round(model.getScale(dpi));
            var element = $(widget.element);
            var select = $('> select', element);

            select
                .val(scale)
                .siblings('.dropdownValue')
                .text(scale);
        },

        _destroy: $.noop
    });

})(jQuery);

