(function ($) {

    $.widget("mapbender.mbScaleSelector", {

        options: {},

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
        _create: function () {
            var self = this;
            this.$select = $("select", this.element);
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function () {
                Mapbender.checkTarget('mbScaleSelector');
            });
        },

        _setup: function () {
            var self = this;
            this.$select.change($.proxy(this._zoomToScale, this));

            this._updateScale();
            initDropdown.call(this.$select.parent());
            // Do it again after initDropdown (which clears the value display if the current text is not also an option text)
            this._updateScale();

            $(document).on('mbmapzoomchanged', function (e, data) {
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
        _zoomToScale: function (e) {
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
        _updateScale: function () {
            var scale = this.mbMap.getModel().getCurrentScale(false);
            this.$select.val(scale);
            const $displayArea = $('.dropdownValue', this.$select.closest('.dropdown', this.element.get(0)));
            const scaleText = Math.round(scale).toLocaleString();
            $displayArea.text(scaleText);
        },

        _destroy: $.noop
    });

})(jQuery);
