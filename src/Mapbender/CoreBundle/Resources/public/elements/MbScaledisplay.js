(function() {

    class MbScaledisplay extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            if (typeof this.options.unitPrefix === 'undefined') {
                this.options.unitPrefix = false;
            }
            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, function() {
                Mapbender.checkTarget('mbScaledisplay');
            });
        }

        /**
         * Initializes the scale display
         */
        _setup() {
            var self = this;
            this.$scaleSpan = $(this.$element).find('span');
            $(this.mbMap.element).on('mbmapzoomchanged', function(e, data) {
                self._updateDisplay(data.scaleExact);
            });
            $(this.mbMap.element).on('mbmapsrschanged', function() {
                self._autoUpdate();
            });
            this._autoUpdate();
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        _updateDisplay(scale) {
            if (!scale) {
                return;
            }
            var scaleText;

            if(this.options.unitPrefix){
                if (scale >= 9500 && scale < 950000) {
                    scaleText = Math.round(scale / 1000).toLocaleString() + "K";
                } else if (scale >= 950000) {
                    scaleText = Math.round(scale / 1000000).toLocaleString() + "M";
                } else {
                    scaleText = Math.round(scale).toLocaleString();
                }
            } else{
                scaleText = Math.round(scale).toLocaleString();
            }
            this.$scaleSpan.text('1 : ' + scaleText);
        }

        _autoUpdate() {
            this._updateDisplay(this.mbMap.getModel().getCurrentScale(false));
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbScaledisplay = MbScaledisplay;

})();
