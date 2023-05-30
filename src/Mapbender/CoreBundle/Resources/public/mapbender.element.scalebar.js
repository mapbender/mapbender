(function($) {

    $.widget("mapbender.mbScalebar", {
        options: {
        },
        mbMap: null,

        /**
         * Creates the scale bar
         */
        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbScalebar");
            });
        },

        /**
         * Initializes the scale bar
         */
        _setup: function() {
            switch (Mapbender.mapEngine.code) {
                default:
                    this._setupOl4();
                    break;
                case 'ol2':
                    this._setupOl2();
                    break;
            }
            this._trigger('ready');
        },
        _setupOl4: function() {
            var control = new ol.control.ScaleLine({
                target: $('.control-container', this.element).get(0),
                minWidth: '' + Math.max(1, parseInt(this.options.maxWidth) / 3),
                geodesic: true,
                units: this.options.units === 'ml' ? 'imperial' : 'metric'
            });
            this.mbMap.getModel().olMap.addControl(control);
        },
        _setupOl2: function() {
            var controlOptions = {
                div: $('.control-container', this.element).get(0),
                maxWidth: this.options.maxWidth,
                geodesic: true,
                // Disable simultaneous dual-display. Use only "bottom" units.
                topOutUnits: '',
                topInUnits: ''
            };
            switch (this.options.units) {
                default:
                case 'km':
                    controlOptions.bottomOutUnits = 'km';
                    controlOptions.bottomInUnits = 'm';
                    break;
                case 'ml':
                    controlOptions.bottomOutUnits = 'mi';
                    controlOptions.bottomInUnits = 'ft';
                    break;
            }

            var control = new OpenLayers.Control.ScaleLine(controlOptions);
            this.mbMap.getModel().map.olMap.addControl(control);
        }
    });

})(jQuery);
