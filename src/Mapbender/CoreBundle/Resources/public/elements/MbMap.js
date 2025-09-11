(function () {
    class MbMap extends MapbenderElement {

        elementUrl = false;
        model = null;
        map = null;

        constructor(configuration, $element) {
            super(configuration, $element);

            this.element = this.$element;
            this.options['poiIcon'] = {
                image: 'bundles/mapbendercore/image/pin_red.png',
                width: 32,
                height: 41,
                xoffset: -6,
                yoffset: -38
            }
            delete this.options.dpi;
            var self = this;
            Object.defineProperty(this.options, 'dpi', {
                get: function() {
                    return self.detectDpi_();
                }
            });
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.$element.attr('id') + '/';
            if (!this.options.extent_start && !this.options.extent_max) {
                throw new Error("Incomplete map configuration: no start extent");
            }
            if (!this.options.extent_start) {
                this.options.extent_start = this.options.extent_max.slice();
            }
            this.options.extent_start = this.options.extent_start.map(function(value) {
                return parseFloat(value);
            });
            this.options.extent_max = this.options.extent_max.map(function(value) {
                return parseFloat(value);
            });
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
            Mapbender.elementRegistry.markReady(this.$element.attr('id'));
        }

        getMapState() {
            return this.model.getMapState();
        }

        /**
         * Returns all defined srs
         */
        getAllSrs() {
            return this.options.srsDefs;
        }

        /**
         * Returns the model
         */
        getModel() {
            return this.model;
        }

        /*
         * Changes the map's projection.
         */
        changeProjection(srs) {
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
        }

        /**
         * Zooms the map in
         */
        zoomIn() {
            this.model.zoomIn();
        }

        /**
         * Zooms the map out
         */
        zoomOut() {
            this.model.zoomOut();
        }

        /**
         * Zooms the map to max extent
         */
        zoomToFullExtent() {
            this.model.zoomToFullExtent();
        }

        /**
         * @param {String} srsName
         * @return {boolean}
         */
        validateSrsOption(srsName) {
            return (typeof srsName === 'string') && /^EPSG:\d+$/.test(srsName);
        }

        detectDpi_() {
            // Auto-calculate dpi from device pixel ratio, to maintain reasonable canvas quality
            // see https://developer.mozilla.org/en-US/docs/Web/API/Window/devicePixelRatio
            // Avoid calculating dpi >= 1.5* (baseDpi) dpi to avoid pushing (Mapproxy) caches into a resolution
            // with too low label font size.
            // Also avoid calculating less than (baseDpi) dpi, to never perform client-side upscaling of Wms images
            const dpr = window.devicePixelRatio || 1;
            const baseDpi = this.options.base_dpi || 96;
            return baseDpi * Math.max(1, dpr / (1 +  Math.floor(dpr - 0.75)));
        }
    }
    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbMap = MbMap;
})();
