window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl4 = (function() {
    function MapEngineOl4() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl4.prototype = Object.create(Mapbender.MapEngine.prototype);
    MapEngineOl4.fallbackProjection = 'EPSG:4326';

    Object.assign(MapEngineOl4.prototype, {
        constructor: MapEngineOl4,
        mapModelFactory: function(mbMap) {
            return new Mapbender.MapModelOl4(mbMap);
        },
        patchGlobals: function(mapOptions) {
            var _tileSize = mapOptions && mapOptions.tileSize && parseInt(mapOptions.tileSize);
            var _dpi = mapOptions && mapOptions.dpi && parseInt(mapOptions.dpi);
            if (_tileSize) {
                ol.DEFAULT_TILE_SIZE = _tileSize;
            }
            if (_dpi) {
                // todo: apply dpi globally?
            }
            // todo: fix drag pan
            // OpenLayers.Control.Navigation.prototype.documentDrag = true;
            Mapbender.MapEngine.prototype.patchGlobals.apply(this, arguments);
        },
        getLayerVisibility: function(olLayer) {
            return olLayer.getVisible();
        },
        setLayerVisibility: function(olLayer, state) {
            olLayer.setVisible(state);
        },
        createWmsTileSource_: function(sourceOptions, mapOptions) {
            var tileSource = new ol.source.TileWMS(sourceOptions);
            if ((mapOptions.scales || []).length) {
                /**
                 * Patch (caching) tile grid getter to ensure our precondigured scales can and will be queried directly from the
                 * WMS, with no scaling.
                 * @see https://github.com/openlayers/openlayers/blob/v6.5.0/src/ol/source/TileImage.js#L240
                 */
                var getTileGridForProjectionOriginal = tileSource.getTileGridForProjection;
                var scales = mapOptions.scales.map(parseFloat).filter(function(scale) {
                    return scale && !isNaN(scale);
                });
                tileSource.getTileGridForProjection = function(projection) {
                    var projKey = ol.getUid(projection);
                    var patched = !!this.tileGridForProjection[projKey];
                    var tileGrid = getTileGridForProjectionOriginal.apply(this, arguments);
                    if (!patched && tileGrid) {
                        var upm = 1.0 / projection.getMetersPerUnit();
                        var ipm = 39.37;
                        var resolutionFromScaleFactor = upm / (ipm * (mapOptions.dpi || 72));
                        var exactScaleResolutions = scales.map(function(scale) {
                            return scale * resolutionFromScaleFactor;
                        });
                        var defaultResolutions = tileGrid.getResolutions();
                        var mergedResolutions = exactScaleResolutions.slice();
                        // Combine with default resolutions to produce more upper / lower, and a few intermediate steps
                        for (var i = 0; i < defaultResolutions.length; ++i) {
                            var defaultResolution = defaultResolutions[i];
                            for (var j = 0; j < exactScaleResolutions.length; ++j) {
                                var exactResolution = exactScaleResolutions[j];
                                if ((exactResolution * 1.75 >= defaultResolution) && (defaultResolution * 1.75 >= exactResolution)) {
                                    mergedResolutions.push(defaultResolution);
                                    break;
                                }
                            }
                        }
                        mergedResolutions.sort(function(a, b) {
                            // numeric sort, descending
                            return b - a;
                        });
                        var tileSize = Math.max(128, parseInt(mapOptions.tileSize) || 512);

                        // Replace upstream-initialized TileGrid instance with a new one, replacing resolutions and tile size
                        tileGrid = new (tileGrid.constructor)({
                            resolutions: mergedResolutions,
                            origin: tileGrid.getOrigin(),
                            extent: tileGrid.getExtent(),
                            tileSize: [tileSize, tileSize]
                        });
                        this.tileGridForProjection[projKey] = tileGrid;
                    }
                    return tileGrid;
                };
            }
            return tileSource;
        },
        /**
         * @param {Mapbender.Source} source
         * @param {Object} [mapOptions]
         * @return {Object}
         */
        createWmsLayer: function(source, mapOptions) {
            var sourceOpts = {
                url: source.configuration.options.url,
                transition: 0,
                params: source.getGetMapRequestBaseParams()
            };

            var olSource;
            var olLayerClass;
            if (source.configuration.options.tiled) {
                olSource = this.createWmsTileSource_(sourceOpts, mapOptions || {});
                olLayerClass = ol.layer.Tile;
            } else {
                olSource = new ol.source.ImageWMS(sourceOpts);
                olLayerClass = ol.layer.Image;
            }

            var layerOptions = {
                opacity: source.configuration.options.opacity,
                source: olSource
            };
            // todo: transparent
            // todo: exception format
            return new (olLayerClass)(layerOptions);
        },
        /**
         * @param {ol.Layer} olLayer
         * @param {Object} params
         */
        applyWmsParams: function(olLayer, params) {
            // @todo: backbuffer interaction?
            olLayer.getSource().updateParams(params);
        },
        /**
         * @param olLayer
         * @param layers
         * @param styles
         * @return {boolean}
         */
        compareWmsParams: function (olLayer, layers, styles) {
            var paramsNow = olLayer.getSource().getParams();
            var layersNow = paramsNow.LAYERS;
            var stylesNow = paramsNow.STYLES;
            var newLayers = (layersNow || '').toString() !== layers.toString();
            var newStyles = (stylesNow || '').toString() !== styles.toString();
            return newLayers || newStyles;
        },
        isProjectionAxisFlipped: function(srsName) {
            var projection = ol.proj.get(srsName);
            var axisOrientation = projection && projection.getAxisOrientation();
            return !!(axisOrientation && axisOrientation.substr(0, 2) === 'ne');
        },
        getProjectionUnits: function(srsName) {
            var proj = ol.proj.get(srsName);
            return proj.getUnits() || 'degrees';
        },
        getProjectionUnitsPerMeter: function(srsName) {
            var proj = this._getProj(srsName);
            return 1.0 / proj.getMetersPerUnit();
        },
        /**
         * @param {Array<number>} values
         * @return {Array<Number> | {left: number, bottom: number, right: number, top: number}}
         */
        boundsFromArray: function(values) {
            var bounds = values.slice();
            Object.defineProperty(bounds, 'left', {
                get: function() { return this[0]; },
                set: function(v) { this[0] = v; }
            });
            Object.defineProperty(bounds, 'bottom', {
                get: function() { return this[1]; },
                set: function(v) { this[1] = v; }
            });
            Object.defineProperty(bounds, 'right', {
                get: function() { return this[2]; },
                set: function(v) { this[2] = v; }
            });
            Object.defineProperty(bounds, 'top', {
                get: function() { return this[3]; },
                set: function(v) { this[3] = v; }
            });
            return bounds;
        },
        /**
         * @param {Object} coordinate
         * @property {Number} coordinate.x
         * @property {Number} coordinate.y
         * @param {(String|Proj4js.Proj)} fromProj
         * @param {(String|Proj4js.Proj)} toProj
         * @return {Object}
         */
        transformCoordinate: function(coordinate, fromProj, toProj) {
            var from_ = proj4.Proj(fromProj);
            var to_ = proj4.Proj(toProj);
            // NOTE: proj4 modifies passed object in-place
            return proj4.transform(from_, to_, Object.assign({}, coordinate));
        },
        transformBounds: function(bounds, fromProj, toProj) {
            var from = this._getProj(fromProj, true);
            var to = this._getProj(toProj, true);
            var transformFn = ol.proj.getTransform(from, to);
            var transformed = ol.extent.applyTransform(bounds, transformFn);
            return this.boundsFromArray(transformed);
        },
        removeLayers: function(olMap, olLayers) {
            var layerCollection = olMap.getLayers();
            for (var i = 0; i < olLayers.length; ++i) {
                var olLayer = olLayers[i];
                layerCollection.remove(olLayer);
            }
        },
        /**
         * @param {ol.PluggableMap} olMap
         * @param {ol.layer.Layer} olLayer
         */
        destroyLayer: function(olMap, olLayer) {
            olMap.removeLayer(olLayer);
            olLayer.setMap(null);
            olLayer.dispose();
        },
        getPointFeatureInfoUrl: function(olMap, source, x, y, params) {
            var firstOlLayer = source.getNativeLayer(0);
            /** @var {ol.source.ImageWMS|ol.source.TileWMS} nativeSource */
            var nativeSource = firstOlLayer.getSource();
            if (!nativeSource.getFeatureInfoUrl) {
                return null;
            }
            var res = olMap.getView().getResolution();
            var proj = olMap.getView().getProjection().getCode();
            var coord = olMap.getCoordinateFromPixel([x, y]);
            return Mapbender.Util.removeProxy(nativeSource.getFeatureInfoUrl(coord, res, proj, params));
        },
        /**
         * @param {(ol.layer.Tile|ol.layer.Image)} olLayer
         * @param {String} srsName
         * @return {String}
         */
        getWmsBaseUrlInternal_: function(olLayer, srsName) {
            var source = olLayer.getSource();
            if (typeof source.tileUrlFunction === 'function') {
                /** @var {ol.source.TileWMS} source */
                return source.tileUrlFunction([0, 0, 0], 1, ol.proj.get(srsName));
            } else {
                /** @var {ol.source.ImageWMS} source */
                return source.getRequestUrl_([0,0,0,0], [0,0], 1, ol.proj.get(srsName), source.getParams());
            }
        },
        getLayerArray: function(olMap) {
            return olMap.getLayers().getArray();
        },
        getUniqueLayerId: function(olLayer) {
            return olLayer.ol_uid;
        },
        replaceLayers: function(olMap, nativeLayerArray) {
            olMap.getLayerGroup().setLayers(new ol.Collection(nativeLayerArray, {unique: true}));
        },
        /**
         * @param {ol.Feature} olFeature
         * @return {{left: number, bottom: number, right: number, top: number}}
         */
        getFeatureBounds: function(olFeature) {
            var geometry = olFeature && olFeature.getGeometry();
            if (!geometry) {
                console.error("Empty feature or empty feature geometry", olFeature);
                throw new Error("Empty feature or empty feature geometry");
            }
            return this.boundsFromArray(geometry.getExtent());
        },
        getFeatureProperties: function(olFeature) {
            return olFeature.getProperties();
        },
        supportsRotation: function() {
            return true;
        },
        _getProj: function(projOrSrsName, strict) {
            // ol.proj.get will happily accept an ol.proj instance :)
            var proj = ol.proj.get(projOrSrsName);
            if (!proj) {
                if (strict && !this.hasShownProjectionError) {
                    this.hasShownProjectionError = true;
                    Mapbender.error("Unsupported projection " + projOrSrsName.toString() + ", falling back to " + MapEngineOl4.fallbackProjection + ". Check configuration of map element.");
                }
                proj = ol.proj.get(MapEngineOl4.fallbackProjection);
            }
            if (proj && !proj.units_) {
                proj.units_ = 'degrees';
            }
            return proj || null;
        }
    });
    Object.assign(window.Mapbender.MapEngine.typeMap, {
        'current': MapEngineOl4,
        'ol4': MapEngineOl4     // legacy
    });
    return MapEngineOl4;
}());
