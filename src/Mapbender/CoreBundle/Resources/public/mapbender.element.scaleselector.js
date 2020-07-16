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
            var self = this;
            this.$select.change($.proxy(this._zoomToScale, this));

            this._updateScale();
            initDropdown.call(this.$select.parent());

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
            var scale = this.mbMap.getModel().getCurrentScale(false);
            this.$select.val(scale).trigger('dropdown.changevisual');
            if (!this.$select.val()) {
                // unconfigured fractional scale
                var $displayArea = $('.dropdownValue', this.$select.closest('.dropdown', this.element.get(0)));
                $displayArea.text(Math.round(scale));
            }
        },

        _destroy: $.noop
    });

})(jQuery);

