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
            var control = new ol.control.ScaleLine({
                target: $('.control-container', this.element).get(0),
                minWidth: '' + Math.max(1, parseInt(this.options.maxWidth) / 3),
                geodesic: true,
                units: this.options.units === 'ml' ? 'imperial' : 'metric'
            });
            this.mbMap.getModel().olMap.addControl(control);
            this._trigger('ready');
        },
    });

})(jQuery);
