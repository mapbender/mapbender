(function ($) {
    'use strict';

    $.widget("mapbender.mbCoordinatesDisplay", {
        options: {
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
            this.options.numDigits = Math.max(0, parseInt(this.options.numDigits) || 0);
            this.options.empty = this.options.empty || '';
            this.options.prefix = this.options.prefix || '';
            this.options.separator = this.options.separator || ' ';
            this.options.suffix = this.options.suffix || '';

            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbCoordinatesDisplay");
            });
        },
        _setup: function () {
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
            this._resetOl4(numDigits);
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
                className: 'inline',
                target: $('.display-area', this.element).get(0),
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
