(function() {

    class MbScaleSelector extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            // Constructor code from _create function of legacy widget
            var self = this;
            this.$select = $("select", this.$element);
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function (mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function () {
                Mapbender.checkTarget('mbScaleSelector');
            });
        }

        _setup() {
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

            Mapbender.elementRegistry.markReady(this);
        }

        /**
         * Zoom to scale event handler
         * @private
         */
        _zoomToScale(e) {
            var scale = this.$select.val();
            var model = this.mbMap.getModel();
            var zoom = model.pickZoomForScale(scale);
            model.setZoomLevel(zoom, false);
        }

        /**
         * Update scale drop down view
         *
         * @private
         */
        _updateScale() {
            var scale = this.mbMap.getModel().getCurrentScale(false);
            const roundedScale = Number.parseInt(scale);
            if (this.options.options.includes(roundedScale)) {
                this.$select.val(roundedScale);
            }
            const $displayArea = $('.dropdownValue', this.$select.closest('.dropdown', this.$element.get(0)));
            const scaleText = Math.round(scale).toLocaleString();
            $displayArea.text(scaleText);
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbScaleSelector = MbScaleSelector;
})();
