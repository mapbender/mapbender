(function($){
    'use strict';

    /**
     * @typedef {{type:string, opacity:number, geometries: Array<Object>}} VectorLayerData~export
     * @typedef {{type:string, opacity:number, markers: Array<Object>}} MarkerLayerData~export
     */
    $.widget("mapbender.mbImageExport", {
        options: {},
        map: null,
        $form: null,

        _create: function(){
            this.$form = $('form', this.element);
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self.mbMap = mbMap;
                self.map = mbMap;   // legacy
                self._setup();
            }, function() {
                Mapbender.checkTarget(self.widgetName, self.options.target);
            });
        },
        _setup: function() {
            this.$form.on('submit', this._onSubmit.bind(this));
            this._trigger('ready');
        },
        defaultAction: function(callback){
            this.open(callback);
        },
        getPopupOptions: function() {
            return {
                title: this.element.attr('data-title'),
                draggable: true,
                modal: false,
                closeOnESC: false,
                content: this.element,
                width: 250,
                scrollable: false
            };
        },
        open: function(callback){
            this.callback = callback || null;
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup(this.getPopupOptions());
                this.popup.$element.one('close', $.proxy(this.close, this));
            }
        },
        close: function(){
            if (this.popup) {
                this.popup.close();
                this.popup = null;
            }
            if (this.callback) {
                (this.callback)();
                this.callback = null;
            }
        },
        /**
         * @returns {Array<Mapbender.Source>}
         * @private
         */
        _getRasterSources: function() {
            return this.map.getModel().getSources().filter(function(x) {
                return x.getActive();
            });
        },
        _getExportScale: function() {
            return this.mbMap.getModel().getCurrentScale(false);
        },
        _getExportExtent: function() {
            var lbrt = this.map.model.getCurrentExtentArray();
            return {
                left: lbrt[0],
                bottom: lbrt[1],
                right: lbrt[2],
                top: lbrt[3]
            };
        },
        _collectRasterLayerData: function() {
            var sources = this._getRasterSources();
            var scale = this._getExportScale();
            var extent = this._getExportExtent();
            var srsName = this.map.model.getCurrentProjectionCode();

            var dataOut = [];

            for (var i = 0; i < sources.length; i++) {
                var source = sources[i];
                var sourcePrintData = source.getPrintConfigs(extent, scale, srsName);
                dataOut.push.apply(dataOut, sourcePrintData);
            }
            return dataOut;
        },
        _collectJobData: function() {
            var mapExtent = this._getExportExtent();
            var imageSize = this.map.model.getCurrentViewportSize();
            var rasterLayers = this._collectRasterLayerData();
            var geometryLayers;
            switch (Mapbender.mapEngine.code) {
                default:
                    geometryLayers = this._collectGeometryLayers4();
                    break;
                case 'ol2':
                    geometryLayers = this._collectGeometryAndMarkerLayers();
                    break;
            }
            return {
                layers: rasterLayers.concat(geometryLayers),
                width: imageSize.width,
                height: imageSize.height,
                rotation: -this.mbMap.getModel().getViewRotation(),
                center: {
                    x: Math.min(mapExtent.left, mapExtent.right) + 0.5 * Math.abs(mapExtent.right - mapExtent.left),
                    y: Math.min(mapExtent.bottom, mapExtent.top) + 0.5 * Math.abs(mapExtent.top - mapExtent.bottom)
                },
                extent: {
                    width: Math.abs(mapExtent.right - mapExtent.left),
                    height: Math.abs(mapExtent.top - mapExtent.bottom)
                }
            };
        },
        _onSubmit: function(evt) {
            // add job data to hidden form fields
            var jobData;
            try {
                jobData = this._collectJobData();
                if (!jobData.layers.length) {
                    Mapbender.info(Mapbender.trans("mb.print.imageexport.info.noactivelayer"));
                    return false;
                }
                this._injectJobData(jobData);
            } catch (e) {
                evt.preventDefault();
                throw e;
            }
            return true;    // let the browser do the rest
        },
        _injectJobData: function(jobData) {
            var $hiddenArea = $('.-fn-hidden-fields', this.$form);
            $hiddenArea.empty();
            var submitValue = JSON.stringify(jobData);
            var $input = $('<input/>').attr('type', 'hidden').attr('name', 'data');
            $input.val(submitValue);
            $input.appendTo($hiddenArea);
        },
        /**
         * Injects data AND submits form
         * @param {Object} jobData
         * @private
         * @deprecated distinctly use _injectJobData and regular form submit events
         */
        _submitJob: function(jobData) {
            this._injectJobData(jobData);
            $('input[type="submit"]', this.$form).click();
        },
        /**
         * Should return true if the given layer needs to be included in export
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterGeometryLayer: function(layer) {
            if ('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || layer.visibility === false || this.layer === layer) {
                return false;
            }
            if (!(layer.features && layer.features.length)) {
                return false;
            }
            return true;
        },
        /**
         * Should return true if the given layer needs to be included in export
         *
         * @param {OpenLayers.Layer.Markers|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterMarkerLayer: function(layer) {
            if ('OpenLayers.Layer.Markers' !== layer.CLASS_NAME || layer.visibility === false || this.layer === layer) {
                return false;
            }
            if (!(layer.markers && layer.markers.length)) {
                return false;
            }
            return layer.opacity > 0;
        },
        /**
         * Should return true if the given feature should be included in export.
         *
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {boolean}
         * @private
         */
        _filterFeature: function(feature) {
            // onScreen throws an error if geometry is not populated, see
            // https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Feature/Vector.js#L198
            if (!feature.geometry || !feature.onScreen(true)) {
                return false;
            }
            return true;
        },
        /**
         * Extracts and preprocesses the geometry from a feature for export backend consumption.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer|ol.layer.Vector} layer
         * @param {OpenLayers.Feature.Vector|ol.Feature} feature
         * @returns {Object} geojsonish, with (non-conformant) "style" entry bolted on (Openlayers 2-native svg format!)
         * @private
         * engine-agnostic
         */
        _extractFeatureGeometry: function(layer, feature) {
            var geometry = this.map.model.featureToGeoJsonGeometry(feature);
            geometry.style = this.map.model.extractSvgFeatureStyle(layer, feature);
            if (geometry.style && geometry.style.externalGraphic) {
                geometry.style.externalGraphic = this._fixAssetPath(geometry.style.externalGraphic);
            }
            return geometry;
        },
        /**
         * Should return true if the given feature geometry should be included in export.
         *
         * @param geometry
         * @returns {boolean}
         * @private
         */
        _filterFeatureGeometry: function(geometry) {
            if (geometry.style.fillOpacity > 0 || geometry.style.strokeOpacity > 0) {
                return true;
            }
            if (geometry.style.externalGraphic) {
                return true;
            }
            if (geometry.style.label !== undefined) {
                return true;
            }
            return false;
        },
        _dumpFeatureGeometries: function(layer, features, resolution) {
            return this.map.model.dumpGeoJsonFeatures(features, layer, resolution, true)
                .map(function(gjFeature) {
                    // Legacy data format quirks (not actually GeoJson):
                    // 1) Strip "type: 'Feature'" outer container object
                    // 2) move style into geometry object
                    return Object.assign({}, gjFeature.geometry, {
                        style: gjFeature.style
                    });
                })
            ;
        },
        /**
         * Should return export data (sent to backend) for the given geometry layer. Given layer is guaranteed
         * to have passsed through the _filterGeometryLayer check positively.
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns VectorLayerData~export
         * @private
         */
        _extractGeometryLayerData: function(layer) {
            var postFilter = this._filterFeatureGeometry.bind(this);
            var features = layer.features.filter(this._filterFeature.bind(this))
            var geometries = this._dumpFeatureGeometries(layer, features);
            return {
                type: 'GeoJSON+Style',
                opacity: 1,
                geometries: geometries.filter(postFilter)
            };
        },
        _collectGeometryLayers4: function() {
            var vectorLayers = [];
            // For (nested) group layers, visibility must be checked at
            // each level.
            function processLayer(layer) {
                if (layer.getVisible() && layer.getOpacity()) {
                    if (layer instanceof ol.layer.Group) {
                        layer.getLayersArray().forEach(function(layer) {
                            processLayer(layer);
                        });
                    } else if (layer instanceof ol.layer.Vector) {
                        vectorLayers.push(layer);
                    }
                }
            }

            this.map.model.olMap.getLayers().getArray().forEach(processLayer);
            var dataOut = [];
            for (var li = 0; li < vectorLayers.length; ++li) {
                var layer = vectorLayers[li];
                var features = layer.getSource().getFeatures();
                if (!features.length) {
                    continue;
                }
                // printclient support HACK
                // @todo: implement filterFeature properly for dual-engine support
                if (this.feature) {
                    var skipFeature = this.feature;
                    features = features.filter(function(f) {
                        return f !== skipFeature;
                    });
                }
                var layerFeatureData = this._dumpFeatureGeometries(layer, features);
                dataOut.push({
                    "type": "GeoJSON+Style",
                    "opacity": layer.getOpacity(),
                    "geometries": layerFeatureData
                });
            }
            return dataOut;
        },
        /**
         * Should return export data (sent to backend) for the given geometry layer. Given layer is guaranteed
         * to have passsed through the _filterGeometryLayer check positively.
         *
         * @param {OpenLayers.Layer.Markers|OpenLayers.Layer} layer
         * @returns MarkerLayerData~export
         * @private
         */
        _extractMarkerLayerData: function(layer) {
            var markerData = [];
            for (var i = 0; i < layer.markers.length; ++i) {
                var marker = layer.markers[i];
                var originalUrl = marker.icon && marker.icon.url;
                var internalUrl = this._fixAssetPath(originalUrl);
                if (!internalUrl) {
                    continue;
                }
                markerData.push({
                    coordinates: {
                        x: marker.lonlat.lon,
                        y: marker.lonlat.lat
                    },
                    width: marker.icon.size.w,
                    height: marker.icon.size.h,
                    offset: {
                        x: marker.icon.offset.x,
                        y: marker.icon.offset.y
                    },
                    path: internalUrl
                });
            }
            return {
                type: 'markers',
                opacity: layer.opacity,
                markers: markerData
            };
        },
        _collectGeometryAndMarkerLayers: function() {
            // Iterating over all vector layers, not only the ones known to MapQuery
            var allOlLayers = this.map.map.olMap.layers;
            var layerDataOut = [];
            for (var i = 0; i < allOlLayers.length; ++i) {
                var olLayer = allOlLayers[i];
                if (this._filterGeometryLayer(olLayer)) {
                    layerDataOut.push(this._extractGeometryLayerData(olLayer));
                } else if (this._filterMarkerLayer(olLayer)) {
                    layerDataOut.push(this._extractMarkerLayerData(olLayer));
                }
            }
            return layerDataOut;
        },
        /**
         * Convert potentially absolute URL to web-local url pointing somewhere into bundles/
         * @param {String} url
         * @returns {String|boolean}
         * @private
         */
        _fixAssetPath: function(url) {
            // @todo: fold copy&paste vs Mapbender.StyleUtil
            var urlOut = url.replace(/^.*?(\/)(bundles\/.*)/, '$2');
            if (urlOut === url && (urlOut || '').indexOf('bundles/') !== 0) {
                console.warn("Asset path could not be resolved to local bundles reference", url);
                return false;
            } else {
                return urlOut;
            }
        },
        _noDanglingCommaDummy: null
    });

})(jQuery);
