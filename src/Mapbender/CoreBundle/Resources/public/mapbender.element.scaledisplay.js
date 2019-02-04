(function($) {
    'use strict';

    $.widget('mapbender.mbScaledisplay', {
        options: {
            target: null
        },
        scaledisplay: null,

        map: null,

        /**
         * Creates the scale display
         */
        _create: function() {
            var self = this;

            if(!Mapbender.checkTarget('mbScaledisplay', this.options.target)){
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the scale display
         */
        _setup: function() {
            var widget = this;

            if(typeof this.options.unitPrefix === 'undefined') {
                this.options.unitPrefix = false;
            }

            var mbMap = Mapbender.elementRegistry.listWidgets().mapbenderMbMap;
            this.map = mbMap;
            var model = mbMap.model;
            var projection = model.getCurrentProjectionObject();

            model.setOnMoveendHandler($.proxy(widget._updateScale, widget), event);

            this._trigger('ready');
        },
        _updateScale: function() {
            var widget = this;
            var model = widget.map.model;
            var scale = model.getScale(model.options.dpi);

            if (!scale) {
                return;
            }

            if(this.options.unitPrefix){
                if (scale >= 9500 && scale <= 950000) {
                    scale = Math.round(scale / 1000) + "K";
                } else if (scale >= 950000) {
                    scale = Math.round(scale / 1000000) + "M";
                } else {
                    scale = Math.round(scale);
                }
            } else{
                scale = Math.round(scale);
            }

            $(this.element).text(this.options.scalePrefix + "1 : " + scale);
        },

        /**
         * Cahnges the scale bar srs
         */
        _changeSrs: function(event, srs){
            this.scaledisplay.updateScale();
        }

    });

})(jQuery);
