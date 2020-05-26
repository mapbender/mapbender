(function($) {

    $.widget("mapbender.mbScalebar", {
        options: {
        },
        scalebar: null,
        mbMap: null,

        /**
         * Creates the scale bar
         */
        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady(this.options.target).then(function(mbMap) {
                self.mbMap = mbMap;
                self._setup();
            }, function() {
                Mapbender.checkTarget("mbScalebar", self.options.target);
            });
        },

        /**
         * Initializes the scale bar
         */
        _setup: function() {
            $(this.element).addClass(this.options.anchor);
            switch (Mapbender.mapEngine.code) {
                case 'ol2':
                    this._setupOl2();
                    break;
                case 'ol4':
                    this._setupOl4();
                    break;
                default:
                    throw new Error("Unsupported map engine code " + Mapbender.mapEngine.code);
            }
            this._trigger('ready');
        },
        _setupOl4: function() {
            var controlOptions = {
                target: this.element.attr('id'),
                'minWidth': '64',
                geodesic: true,
                units: this.options.units === 'ml' ? 'imperial' : 'metric'
            };
            this.scalebar = new ol.control.ScaleLinePatched(controlOptions);
            // Todo: work around upstream bug in display calculations on non-metric SRS
            // This bug has been fixed only after v4 maintenance stopped
            // See https://github.com/openlayers/openlayers/pull/7700
            // See https://github.com/openlayers/openlayers/pull/7908
            this.mbMap.getModel().olMap.addControl(this.scalebar);
        },
        _setupOl2: function() {
            var controlOptions = {
                div: $(this.element).get(0),
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


            this.scalebar = new OpenLayers.Control.ScaleLine(controlOptions);
            this.mbMap.getModel().map.olMap.addControl(this.scalebar);
        }
    });

})(jQuery);
