window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl2 = (function() {
    function MapEngineOl2() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl2.prototype = Object.create(Mapbender.MapEngine.prototype);
    Object.assign(MapEngineOl2.prototype, {
        constructor: MapEngineOl2,
        mapModelFactory: function(mbMap) {
            return new Mapbender.MapModelOl2(mbMap);
        },
        patchGlobals: function(mapOptions) {
            var _tileSize = mapOptions && mapOptions.tileSize && parseInt(mapOptions.tileSize);
            var _dpi = mapOptions && mapOptions.dpi && parseInt(mapOptions.dpi);
            if (_tileSize) {
                OpenLayers.Map.TILE_WIDTH = _tileSize;
                OpenLayers.Map.TILE_HEIGHT = _tileSize;
            }
            if (_dpi) {
                OpenLayers.DOTS_PER_INCH = mapOptions.dpi;
            }
            OpenLayers.ImgPath = Mapbender.configuration.application.urls.asset + 'components/mapquery/lib/openlayers/img/';
            OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';
            // Allow drag pan motion to continue outside of map div. Great for multi-monitor setups.
            OpenLayers.Control.Navigation.prototype.documentDrag = true;
            Mapbender.MapEngine.prototype.patchGlobals.apply(this, arguments);
        },
        getLayerVisibility: function(olLayer) {
            return olLayer.getVisibility();
        },
        setLayerVisibility: function(olLayer, state) {
            olLayer.setVisibility(state);
        },
        /**
         * @param {Mapbender.Source} source
         * @param {Object} [mapOptions]
         * @return {Object}
         */
        createWmsLayer: function(source, mapOptions) {
            var options = getNativeLayerOptions(source);
            var params = getNativeLayerParams(source);
            var url = source.configuration.options.url;
            var name = source.title;
            return new OpenLayers.Layer.WMS(name, url, params, options);

            function getNativeLayerOptions(source) {
                var rootLayer = source.configuration.children[0];
                var bufferConfig = source.configuration.options.buffer;
                var ratioConfig = source.configuration.options.ratio;
                var opts = {
                    isBaseLayer: false,
                    opacity: source.configuration.options.opacity,
                    singleTile: !source.configuration.options.tiled,
                    noMagic: true,
                    minScale: rootLayer.minScale,
                    maxScale: rootLayer.maxScale
                };
                if (!!((new Mapbender.Util.Url(source.configuration.options.url)).username)) {
                    opts.tileOptions = {
                        crossOriginKeyword: 'use-credentials'
                    };
                }

                if (opts.singleTile) {
                    opts.ratio = parseFloat(ratioConfig) || 1.0;
                } else {
                    opts.buffer = parseInt(bufferConfig) || 0;
                }
                return opts;
            }
            function getNativeLayerParams(source) {
                var params = Object.assign({}, source.getGetMapRequestBaseParams(), source.customParams, {
                    transparent: source.configuration.options.transparent
                });
                var exceptionFormatConfig = source.configuration.options.exception_format;
                if (exceptionFormatConfig) {
                    params.exceptions = exceptionFormatConfig;
                }
                return params;
            }
        },
        /**
         * @param {OpenLayers.Layer.WMS} olLayer
         * @param {Object} params
         */
        applyWmsParams: function(olLayer, params) {
            // Nuking the back buffer prevents the layer from going visible with old layer combination
            // before loading the new images.
            olLayer.removeBackBuffer();
            olLayer.createBackBuffer();
            olLayer.mergeNewParams(params);
        },
        compareWmsParams: function (olLayer, layers, styles) {
            var newLayers = (olLayer.params.LAYERS || '').toString() !== layers.toString();
            var newStyles = (olLayer.params.STYLES || '').toString() !== styles.toString();
            return newLayers || newStyles;
        },
        isProjectionAxisFlipped: function(srsName) {
            var projDefaults = OpenLayers.Projection.defaults[srsName];
            return !!(projDefaults && projDefaults.yx);
        },
        getProjectionUnits: function(srsName) {
            var proj = new OpenLayers.Projection(srsName);
            return proj.proj.units || 'dd';
        },
        getProjectionUnitsPerMeter: function(srsName) {
            var units = this.getProjectionUnits(srsName);
            if (units === 'm' || units === 'Meter') {
                return 1.0;
            } else {
                var metersPerUnit = OpenLayers.INCHES_PER_UNIT[units] * OpenLayers.METERS_PER_INCH;
                return 1.0 / metersPerUnit;
            }
        },
        boundsFromArray: function(values) {
            return OpenLayers.Bounds.fromArray(values);
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
            var from_ = this._getProj(fromProj).proj;
            var to_ = this._getProj(toProj).proj;
            // Proj4 modifies coordinate in place => make a copy
            var coordinate_ = (Array.isArray(coordinate) && coordinate.slice()) || Object.assign({}, coordinate);
            return Proj4js.transform(from_, to_, coordinate_);
        },
        transformBounds: function(bounds, fromProj, toProj) {
            var from = this._getProj(fromProj, true);
            var to = this._getProj(toProj, true);
            var boundsOut = (bounds && bounds.clone()) || null;
            if (!bounds || !boundsOut || !from.projCode || !to.projCode) {
                console.error("Empty extent or invalid projections", bounds, fromProj, toProj);
                throw new Error("Empty extent or invalid projections");
            }

            if (bounds && from.projCode !== to.projCode) {
                return boundsOut.transform(from, to);
            }
            return boundsOut;
        },
        removeLayers: function(olMap, olLayers) {
            for (var i = 0; i <olLayers.length; ++i) {
                var olLayer = olLayers[i];
                if (olLayer instanceof OpenLayers.Layer.Grid) {
                    olLayer.clearGrid();
                }
                if (olLayer.map && olLayer.map.tileManager) {
                    olLayer.map.tileManager.clearTileQueue({
                        object: olLayer
                    });
                }
                if (olLayer.map) {
                    olLayer.map.removeLayer(olLayer);
                }
            }
        },
        /**
         * @param {OpenLayers.Map} olMap
         * @param {OpenLayers.Layer} olLayer
         */
        destroyLayer: function(olMap, olLayer) {
            olLayer.destroy(false);
        },
        /**
         * @param {OpenLayers.Layer.WMS} olLayer
         * @return {String}
         */
        getWmsBaseUrlInternal_: function(olLayer) {
            var bounds = new OpenLayers.Bounds(0, 0, 0, 0);
            return olLayer.getURL(bounds);
        },
        getPointFeatureInfoUrl: function(olMap, source, x, y, params) {
            var firstOlLayer = source.getNativeLayer(0);
            var control = new OpenLayers.Control.WMSGetFeatureInfo({
                url: null,
                layers: [],
                queryVisible: true,
                maxFeatures: params.FEATURE_COUNT
            });
            control.map = olMap;
            var reqObj = control.buildWMSOptions(
                Mapbender.Util.removeProxy(source.configuration.options.url),
                [firstOlLayer],
                {x: x, y: y},
                source.configuration.options.format
            );
            var params_ = $.extend({}, reqObj.params, params);
            return Mapbender.Util.replaceUrlParams(reqObj.url, params_, false);
        },
        getLayerArray: function(olMap) {
            return olMap.layers;
        },
        getUniqueLayerId: function(olLayer) {
            return olLayer.id;
        },
        replaceLayers: function(olMap, nativeLayerArray) {
            olMap.layers = nativeLayerArray;
            olMap.resetLayersZIndex();
        },
        /**
         * @param {OpenLayers.Feature} olFeature
         * @return {{left: number, bottom: number, right: number, top: number}}
         */
        getFeatureBounds: function(olFeature) {
            if (!olFeature || !olFeature.geometry) {
                console.error("Empty feature or empty feature geometry", olFeature);
                throw new Error("Empty feature or empty feature geometry");
            }
            return this.boundsFromArray(olFeature.geometry.getBounds().toArray());
        },
        getFeatureProperties: function(olFeature) {
            return olFeature.data;
        },
        supportsRotation: function() {
            return false;
        },
        _getProj: function(projOrSrsName, strict) {
            var srsName;
            if (projOrSrsName && projOrSrsName.projCode) {
                srsName = projOrSrsName.projCode;
            } else {
                if (typeof projOrSrsName !== 'string') {
                    console.error("Invalid argument", projOrSrsName);
                    if (strict) {
                        throw new Error("Invalid argument");
                    } else {
                        return null;
                    }
                } else {
                    srsName = projOrSrsName;
                }
            }

            if (Proj4js.defs[srsName]) {
                var proj = new OpenLayers.Projection(srsName);
                if (!proj.proj.units) {
                    proj.proj.units = 'degrees';
                }
                return proj;
            }
            if (strict) {
                throw new Error("Unsupported projection " + srsName.toString());
            }
            return null;
        }
    });
    window.Mapbender.MapEngine.typeMap['ol2'] = MapEngineOl2;
    return MapEngineOl2;
}());
