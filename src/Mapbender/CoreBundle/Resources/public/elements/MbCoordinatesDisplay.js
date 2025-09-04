(function() {
    class MbCoordinatesDisplay extends MapbenderElement {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.options.numDigits = Math.max(0, parseInt(this.options.numDigits) || 0);
            this.options.empty = this.options.empty || '';
            this.options.prefix = this.options.prefix || '';
            this.options.separator = this.options.separator || ' ';
            this.options.suffix = this.options.suffix || '';

            Mapbender.elementRegistry.waitReady('.mb-element-map').then((mbMap) => {
                this.mbMap = mbMap;
                this._setup();
            }, () => {
                Mapbender.checkTarget('mbCoordinatesDisplay');
            });
        }

        _setup() {
            $(document).on('mbmapsrschanged', this._reset.bind(this));
            this._reset();
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        _reset(event, data) {
            var units = this.mbMap.getModel().getCurrentProjectionUnits();
            var isDeg = !units || units === 'degrees' || units === 'dd';
            var numDigits = this.options.numDigits || 0;
            if (isDeg) {
                numDigits += 5;
            }
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
                coordinateFormat: (coordinate) => {
                    return ol.coordinate.format(coordinate, template, numDigits);
                },
                projection: model.getCurrentProjectionCode(),
                className: 'inline',
                target: $('.display-area', this.$element).get(0),
                placeholder: $('<span/>').text(this.options.empty).html()
            };
            if (this.control) {
                model.olMap.removeControl(this.control);
                this.control = null;
            }
            this.control = new ol.control.MousePosition(controlOptions);
            model.olMap.addControl(this.control);
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbCoordinatesDisplay = MbCoordinatesDisplay;
})();
