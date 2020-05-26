(function($) {
    'use strict';

    $.widget("mapbender.mbScaledisplay", {
        options: {
            scalePrefix: null,
            unitPrefix: false,
            target: null
        },
        mbMap: null,

        /**
         * Creates the scale display
         */
        _create: function() {
            var self = this;
            if (typeof this.options.unitPrefix === 'undefined') {
                this.options.unitPrefix = false;
            }
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbScaledisplay", self.options.target);
            });
        },

        /**
         * Initializes the scale display
         */
        _setup: function() {
            var self = this;
            switch (Mapbender.mapEngine.code) {
                case 'ol2':
                    // fall through
                case 'ol4':
                    $(this.mbMap.element).on('mbmapzoomchanged', function(e, data) {
                        self._updateDisplay(data.scale);
                    });
                    break;
                default:
                    throw new Error("Unsupported map engine code " + Mapbender.mapEngine.code);
            }

            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
            this._trigger('ready');
        },
        _updateDisplay: function(scale) {
            if (!scale) {
                return;
            }
            var scaleText;

            if(this.options.unitPrefix){
                if (scale >= 9500 && scale <= 950000) {
                    scaleText = Math.round(scale / 1000) + "K";
                } else if (scale >= 950000) {
                    scaleText = Math.round(scale / 1000000) + "M";
                } else {
                    scaleText = Math.round(scale);
                }
            } else{
                scaleText = Math.round(scale).toString();
            }
            var parts = ["1 : ", scaleText];
            if (this.options.scalePrefix) {
                parts.unshift(this.options.scalePrefix, ' ');
            }
            $(this.element).text(parts.join(''));
        },
        _changeSrs: function(event, srs){
            this._updateDisplay(this.mbMap.getModel().getCurrentScale());
        }
    });

})(jQuery);
