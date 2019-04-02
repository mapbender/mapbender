(function($) {

    $.widget("mapbender.mbScaleSelector", {

        options: {
            /**
             * Target widget id string
             */
            target: null
        },

        /**
         * Mapbender map widget
         */
        mbMap: null,
        $select: null,

        /**
         * Constructor
         *
         * @private
         */
        _create: function() {
            var self = this;
            this.$select = $("select", this.element);
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbScaleSelector", self.options.target)
            });
        },

        _setup: function() {
            var model = this.mbMap.getModel();
            var zoomLevels = model.getZoomLevels();
            var self = this;
            for (var i = 0; i < zoomLevels.length; ++i) {
                var $option = $("<option/>");
                $option
                    .attr('value', zoomLevels[i].scale)
                    .html(zoomLevels[i].scale)
                ;
                this.$select.append($option);
            }

            this.$select.change($.proxy(this._zoomToScale, this));
            this.$select.val(model.getCurrentScale());

            initDropdown.call(this.element.get(0));

            this._updateScale();
            $(document).on('mbmapzoomchanged', function(e, data) {
                if (data.mbMap === self.mbMap) {
                    self._updateScale();
                }
            });

            this._trigger('ready');
        },

        /**
         * Zoom to scale event handler
         * @private
         */
        _zoomToScale: function() {
            var scale = this.$select.val();
            var model = this.mbMap.getModel();
            var zoom = model.pickZoomForScale(scale);
            model.setZoomLevel(zoom, false);
        },

        /**
         * Update scale drop down view
         *
         * @private
         */
        _updateScale: function() {
            var scale = this.mbMap.getModel().getCurrentScale();

            this.$select
                .val(scale)
                .siblings(".dropdownValue")
                .text(scale)
            ;
        },

        _destroy: $.noop
    });

})(jQuery);

