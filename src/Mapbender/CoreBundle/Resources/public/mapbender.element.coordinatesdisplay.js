(function ($) {
    'use strict';

    $.widget("mapbender.mbCoordinatesDisplay", $.mapbender.mbBaseElement, {
        options: {
            target: null,
            empty: 'x= -<br>y= -',
            prefix: 'x= ',
            separator: '<br/>y= ',
            suffix: ''
        },
        RADIX: 10,

        _create: function () {
            if (!Mapbender.checkTarget("mbCoordinatesDisplay", this.options.target)) {
                return;
            }

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function () {
            var self = this,
                mbMap = $('#' + this.options.target).data('mapbenderMbMap'),
                layers = mbMap.map.layers();

            this.options.empty = this.options.empty || '';
            this.options.prefix = this.options.prefix || '';
            this.options.separator = this.options.separator || ' ';

            layers.map(function (layer) {
                if (layer.options.isBaseLayer) {
                    layer.olLayer.events.register('loadend', layer.olLayer, function () {
                        self.reset();
                    });
                }
            });

            $(document).on('mbmapsrschanged', $.proxy(this._reset, this));

            this.options.numDigits = isNaN(parseInt(this.options.numDigits, this.RADIX))
                ? 0
                : parseInt(this.options.numDigits, this.RADIX);
            this.options.numDigits = this.options.numDigits < 0 ? 0 : this.options.numDigits;

            this._reset();
        },

        _reset: function (event, srs) {
            var self = this,
                mbMap = $('#' + this.options.target).data('mapbenderMbMap'),
                isdeg = mbMap.map.olMap.units === 'degrees';

            srs = { projection: mbMap.map.olMap.getProjectionObject()};

            if (this.crs !== null && (this.crs === srs.projection.projCode)) {
                return;
            }

            if (typeof (this.options.formatoutput) !== 'undefined') {
                mbMap.map.olMap.addControl(
                    new OpenLayers.Control.MousePosition({
                        id: $(this.element).attr('id'),
                        element: $(this.element)[0],
                        emptyString: this.options.empty,
                        numDigits: isdeg ? 5 + this.options.numDigits : this.options.numDigits,
                        formatOutput: function (pos) {
                            var out = self.options.displaystring.replace("$lon$", pos.lon.toFixed(isdeg ? 5 : 0));
                            return out.replace("$lat$", pos.lat.toFixed(isdeg ? 5 : 0));
                        }
                    })
                );

                this.crs = srs.projection.projCode;
            } else {
                var mouseContr = mbMap.map.olMap.getControl($(this.element).attr('id'));

                if (mouseContr !== null) {
                    mbMap.map.olMap.removeControl(mouseContr);
                }

                mbMap.map.olMap.addControl(
                    new OpenLayers.Control.MousePosition({
                        id: $(this.element).attr('id'),
                        div: $($(this.element)[0]).find('#coordinatesdisplay')[0],
                        emptyString: this.options.empty || '',
                        prefix: this.options.prefix || '',
                        separator: this.options.separator || ' ',
                        suffix: this.options.suffix,
                        numDigits: isdeg ? 5 + this.options.numDigits : this.options.numDigits,
                        displayProjection: srs.projection
                    })
                );

                this.crs = srs.projection.projCode;
            }
        }
    });

})(jQuery);

