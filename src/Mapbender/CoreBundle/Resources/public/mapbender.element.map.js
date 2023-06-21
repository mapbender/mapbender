(function($){
    $.widget("mapbender.mbMap", {
        options: {
            poiIcon: {
                image: 'bundles/mapbendercore/image/pin_red.png',
                width: 32,
                height: 41,
                xoffset: -6,
                yoffset: -38
            },
            srsDefs: [],
            layersets: [],
            baseDpi: 96,
        },
        elementUrl: null,
        model: null,
        map: null,

        /**
         * Creates the map widget
         */
        _create: function() {
            delete this.options.dpi;
            var self = this;
            Object.defineProperty(this.options, 'dpi', {
                get: function() {
                    return self.detectDpi_();
                }
            });
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if (!this.options.extent_start && !this.options.extent_max) {
                throw new Error("Incomplete map configuration: no start extent");
            }
            if (!this.options.extent_start) {
                this.options.extent_start = this.options.extent_max.slice();
            }
            if (!this.options.srs) {
                throw new Error("Invalid map configuration: missing srs");
            }
            if (!this.validateSrsOption(this.options.srs)) {
                throw new Error("Invalid map configuration: srs must use EPSG:<digits> form, not " + this.options.srs);
            }

            this.model = Mapbender.mapEngine.mapModelFactory(this);
            // HACK: place the model instance globally at Mapbender.Model
            if (window.Mapbender.Model) {
                console.error("Mapbender.Model already set", window.Mapbender.Model);
                throw new Error("Can't globally reassing window.Mapbender.Model");
            }
            window.Mapbender.Model = this.model;
            this.map = this.model.map;
            self._trigger('ready');
        },
        getMapState: function(){
            return this.model.getMapState();
        },
        /**
         * Returns all defined srs
         */
        getAllSrs: function(){
            return this.options.srsDefs;
        },
        /**
         * Reterns the model
         */
        getModel: function(){
            return this.model;
        },
        /*
         * Changes the map's projection.
         */
        changeProjection: function(srs) {
            if (typeof srs === "string") {
                this.model.changeProjection(srs);
            } else {
                // legacy stuff
                var projCode = srs.projCode || (srs.proj && srs.proj.srsCode);
                if (!projCode) {
                    console.error("Invalid srs argument", srs);
                    throw new Error("Invalid srs argument");
                }
                this.model.changeProjection(projCode);
            }
        },
        /**
         * Zooms the map in
         */
        zoomIn: function() {
            this.model.zoomIn();
        },
        /**
         * Zooms the map out
         */
        zoomOut: function() {
            this.model.zoomOut();
        },
        /**
         * Zooms the map to max extent
         */
        zoomToFullExtent: function() {
            this.model.zoomToFullExtent();
        },
        /**
         * @param {String} srsName
         * @return {boolean}
         */
        validateSrsOption: function(srsName) {
            return (typeof srsName === 'string') && /^EPSG:\d+$/.test(srsName);
        },
        detectDpi_: function() {
            // Auto-calculate dpi from device pixel ratio, to maintain reasonable canvas quality
            // see https://developer.mozilla.org/en-US/docs/Web/API/Window/devicePixelRatio
            // Avoid calculating dpi >= 1.5* (baseDpi) dpi to avoid pushing (Mapproxy) caches into a resolution
            // with too low label font size.
            // Also avoid calculating less than (baseDpi) dpi, to never perform client-side upscaling of Wms images
            const dpr = window.devicePixelRatio || 1;
            const baseDpi = this.options.base_dpi || 96;
            return baseDpi * Math.max(1, dpr / (1 +  Math.floor(dpr - 0.75)));
        },
        _comma_dangle_dummy: null
    });

})(jQuery);

$('body').delegate(':input', 'keydown', function(event){
    event.stopPropagation();
});
