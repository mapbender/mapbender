(function ($) {
    'use strict';

    $.widget("mapbender.mbCoordinatesDisplay", {
        options: {
            target: null,
            empty: 'x= -<br>y= -',
            prefix: 'x= ',
            separator: '<br/>y= ',
            suffix: '',
            // Undocumented / unassignable legacy option combination doing ~freeform HTML output with template replacement
            formatoutput: null,     // some flag
            displaystring: null     // HTML with placeholders '$lon$' and '$lat$'
        },
        control: null,
        mbMap: null,

        _create: function () {
            if (!Mapbender.checkTarget("mbCoordinatesDisplay", this.options.target)) {
                return;
            }
            this.options.numDigits = Math.max(0, parseInt(this.options.numDigits) || 0);
            this.options.empty = this.options.empty || '';
            this.options.prefix = this.options.prefix || '';
            this.options.separator = this.options.separator || ' ';
            this.options.suffix = this.options.suffix || '';

            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(this._setup, this));
        },

        _setup: function () {
            this.mbMap = $('#' + this.options.target).data('mapbenderMbMap');
            $(document).on('mbmapsrschanged', $.proxy(this._reset, this));
            this._reset();
        },

        _reset: function(event, data) {
            var units = this.mbMap.getModel().getCurrentProjectionUnits();
            var isDeg = !units || units === 'degrees' || units === 'dd';
            var numDigits = this.options.numDigits || 0;
            if (isDeg) {
                numDigits += 5;
            }
            switch (Mapbender.mapEngine.code) {
                case 'ol2':
                    this._resetOl2(numDigits);
                    break;
                case 'ol4':
                    this._resetOl4(numDigits);
                    break;
                default:
                    throw new Error("Unuspported map engine code " + Mapbender.mapEngine.code);
            }
        },
        _resetOl2: function(numDigits) {
            var controlOptions = {
                emptyString: this.options.empty,
                numDigits: numDigits,
                prefix: this.options.prefix,
                separator: this.options.separator,
                suffix: this.options.suffix
            };
            if (this.options.formatoutput && this.options.displaystring) {
                // Undocumented / unassignable legacy option combination doing ~template replacement
                var template = this.options.displaystring;
                controlOptions.element = this.element.get(0);
                // Monkey-patch formatOutput
                // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Control/MousePosition.js#L208
                controlOptions.formatOutput = function (pos) {
                    return template
                        .replace("$lon$", pos.lon.toFixed(numDigits))
                        .replace("$lat$", pos.lat.toFixed(numDigits))
                    ;
                };
            } else {
                controlOptions.element = $('.display-area', this.element).get(0);
            }
            if (this.control) {
                this.mbMap.map.olMap.removeControl(this.control);
                this.control = null;
            }
            this.control = new OpenLayers.Control.MousePosition(controlOptions);
            this.mbMap.map.olMap.addControl(this.control);
        },
        _resetOl4: function(numDigits) {
            var model = this.mbMap.getModel();
            var template;
            if (this.options.formatoutput && this.options.displaystring) {
                // Undocumented / unassignable legacy option combination doing ~template replacement
                template = this.options.displaystring
                    .replace("$lon$", "{x}")
                    .replace("$lat$", "{y}")
                ;
            } else {
                template = [
                    this.options.prefix,
                    '{x}',
                    this.options.separator,
                    '{y}'
                    ].join('');
            }
            var controlOptions = {
                coordinateFormat: function(coordinate) {
                    return ol.coordinate.format(coordinate, template, numDigits);
                },
                projection: model.getCurrentProjectionCode(),
                className: 'custom-mouse-position',
                target: $(this.element).attr('id'),
                undefinedHTML: $('<span/>').text(this.options.empty).html()
            };
            if (this.control) {
                model.olMap.removeControl(this.control);
                this.control = null;
            }
            this.control = new ol.control.MousePosition(controlOptions);
            model.olMap.addControl(this.control);
        }
    });

})(jQuery);
