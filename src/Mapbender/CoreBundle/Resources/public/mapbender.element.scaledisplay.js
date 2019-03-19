(function($) {
    'use strict';

    $.widget("mapbender.mbScaledisplay", {
        options: {
//            unitPrefix: false
            target: null
        },
        scaledisplay: null,
        preDisplay: null,

        /**
         * Creates the scale display
         */
        _create: function() {
            if(!Mapbender.checkTarget("mbScaledisplay", this.options.target)){
                return;
            }
            var self = this;
            this.preDisplay = document.createElement('div');
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the scale display
         */
        _setup: function() {
            if(typeof this.options.unitPrefix === 'undefined')
                this.options.unitPrefix = false;
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            this._init(mapEngineCode);
            $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
            this._trigger('ready');
        },
        _initOl2: function() {
            var mbMap = $('#' + this.options.target).data('mapbenderMbMap');

            this.scaledisplay = this._initializeControl();
            mbMap.map.olMap.addControl(this.scaledisplay);
        },
        _initializeControl: function() {
            var control = new OpenLayers.Control.Scale(this.preDisplay);
            var targetElement = $('>span', this.element).get(0);
            var updateText = this._cleanupDisplayText.bind(this);
            // Monkey-patch .updateScale method to massage the displayed text and forward it
            // to targetElement
            control.updateScale = function() {
                /** @type {OpenLayers.Control.Scale} this */
                OpenLayers.Control.Scale.prototype.updateScale.call(this);
                // noinspection JSPotentiallyInvalidUsageOfThis
                targetElement.innerHTML = updateText(this.element.innerHTML);
            };
            return control;
        },
        _initOl4: function(mbMap) {
            var model = mbMap.model;
            model.setOnMoveendHandler($.proxy(widget._updateScale, widget), event);
        },
        _updateScale: function() {
            var model = this.map.model;
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
        _changeSrs: function(event, srs){
            this.scaledisplay.updateScale();
        },
        _cleanupDisplayText: function(textIn) {
            // Openlayers 2.13.1 does not (yet) support a customizable template
            // strip off the hard-coded, untranslatable "Scale = " prefix
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Control/Scale.js#L95
            var textOut = textIn.replace(/^Scale\s*=\s*/, '');
            if (!this.options.unitPrefix) {
                // Convert unit suffixes back to digits
                // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Control/Scale.js#L87
                textOut = textOut.replace(/(\d*)([MK])/g, function(match, digits, suffix) {
                    if (suffix === 'M') {
                        return digits + '000000';
                    } else if (suffix === 'K') {
                        return digits + '000';
                    } else {
                        return digits;
                    }
                });
            }
            return textOut;
        }
    });

})(jQuery);
