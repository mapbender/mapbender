window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl2 = (function() {
    function MapEngineOl2() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl2.prototype = Object.create(Mapbender.MapEngine.prototype);
    Object.assign(MapEngineOl2.prototype, {
        constructor: MapEngineOl2,
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
        createWmsLayer: function(source) {
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
                    visibility: source.configuration.options.visible,
                    singleTile: !source.configuration.options.tiled,
                    noMagic: true,
                    minScale: rootLayer.minScale,
                    maxScale: rootLayer.maxScale
                };
                if (opts.singleTile) {
                    opts.ratio = parseFloat(ratioConfig) || 1.0;
                } else {
                    opts.buffer = parseInt(bufferConfig) || 0;
                }
                return opts;
            }
            function getNativeLayerParams(source) {
                var params = $.extend({}, source.customParams, {
                    transparent: source.configuration.options.transparent,
                    format: source.configuration.options.format,
                    version: source.configuration.options.version
                });
                var exceptionFormatConfig = source.configuration.options.exception_format;
                if (exceptionFormatConfig) {
                    params.exceptions = exceptionFormatConfig;
                }
                var activatedLeaves = source.getActivatedLeaves();
                var nonEmptyLayerNames = activatedLeaves.map(function(sourceLayer) {
                    return sourceLayer.options.name;
                }).filter(function(layerName) {
                    return !!layerName;
                });
                params.LAYERS = nonEmptyLayerNames;
                // @todo: use configured styles
                var styles = nonEmptyLayerNames.map(function() {
                    return '';
                });
                params.STYLES = styles;
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
        boundsFromArray: function(values) {
            return OpenLayers.Bounds.fromArray(values);
        },
        transformBounds: function(bounds, fromProj, toProj) {
            var from = this._getProj(fromProj, true);
            var to = this._getProj(toProj, true);
            var boundsOut = (bounds && bounds.clone()) || null;
            if (!bounds || !boundsOut || !from.projCode || !to.projCode) {
                console.error("Empty extent or invalid projetcions", bounds, fromProj, toProj);
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
            var reqUrl = OpenLayers.Util.urlAppend(reqObj.url, OpenLayers.Util.getParameterString(params_));
            return reqUrl;
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
