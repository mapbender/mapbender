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
            var projection = (data && data.to) || this.mbMap.getModel().getCurrentProj();

            var numDigits = this.options.numDigits || 0;
            if (!projection.proj.units || projection.proj.units === 'degrees' || projection.proj.units === 'dd') {
                numDigits += 5;
            }
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
        }
    });

})(jQuery);
