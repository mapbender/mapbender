window.Mapbender = Mapbender || {};
window.Mapbender.WmsSourceLayer = (function() {
    function WmsSourceLayer() {
        Mapbender.SourceLayer.apply(this, arguments);
    }
    WmsSourceLayer.prototype = Object.create(Mapbender.SourceLayer.prototype);
    WmsSourceLayer.prototype.constructor = WmsSourceLayer;
    Mapbender.SourceLayer.typeMap['wms'] = WmsSourceLayer;
    return WmsSourceLayer;
}());
window.Mapbender.WmsSource = (function() {
    function WmsSource(definition) {
        Mapbender.Source.apply(this, arguments);
        var customParams = {};
        if (definition.customParams) {
            $.extend(this.customParams, definition.customParams);
        }
        (definition.configuration.options.dimensions || []).map(function(dimensionConfig) {
            if (dimensionConfig.default) {
                customParams[dimensionConfig.__name] = dimensionConfig.default;
            }
        });
        this.customParams = customParams;
    }
    WmsSource.prototype = Object.create(Mapbender.Source.prototype);
    WmsSource.prototype.constructor = WmsSource;
    Mapbender.Source.typeMap['wms'] = WmsSource;
    $.extend(WmsSource.prototype, {
        // We must remember custom params for serialization in getMapState()...
        customParams: {},
        // ... but we will not remember the following ~standard WMS params the same way
        _runtimeParams: ['LAYERS', 'STYLES', 'EXCEPTIONS', 'QUERY_LAYERS', 'INFO_FORMAT', '_OLSALT'],
        createNativeLayers: function(srsName) {
            var options = this.getNativeLayerOptions();
            var params = this.getNativeLayerParams();
            var url = this.configuration.options.url;
            var name = this.title;
            var olLayer = new OpenLayers.Layer.WMS(name, url, params, options);
            this.nativeLayers = [olLayer];
            return this.nativeLayers;
        },
        getNativeLayerOptions: function() {
            var rootLayer = this.configuration.children[0];
            var bufferConfig = this.configuration.options.buffer;
            var ratioConfig = this.configuration.options.ratio;
            var opts = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                visibility: this.configuration.options.visible,
                singleTile: !this.configuration.options.tiled,
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
        },
        getNativeLayerParams: function() {
            var params = $.extend({}, this.customParams, {
                transparent: this.configuration.options.transparent,
                format: this.configuration.options.format,
                version: this.configuration.options.version
            });
            var exceptionFormatConfig = this.configuration.options.exception_format;
            if (exceptionFormatConfig) {
                params.exceptions = exceptionFormatConfig;
            }
            return params;
        },
        addParams: function(params) {
            for (var i = 0; i < this.nativeLayers.length; ++i) {
                this.nativeLayers[i].mergeNewParams(params);
            }
            var rtp = this._runtimeParams;
            $.extend(this.customParams, _.omit(params, function(value, key) {
                return -1 !== rtp.indexOf(('' + key).toUpperCase());
            }));
        },
        removeParams: function(names) {
            // setting a param to null effectively removes it from the generated URL
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Util.js#L514
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Layer/HTTPRequest.js#L197
            var nullParams = _.object(names, names.map(function() {
                return null;
            }));
            this.addParams(nullParams);
        },
        toJSON: function() {
            var s = Mapbender.Source.prototype.toJSON.apply(this, arguments);
            s.customParams = this.customParams;
            return s;
        },
        getLayerParameters: function(stateMap) {
            var result = {
                layers: [],
                styles: [],
                infolayers: []
            };
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function(layer) {
                // Layer names can be emptyish, most commonly on root layers
                // Suppress layers with empty names entirely
                if (layer.options.name) {
                    var layerState = stateMap[layer.options.id] || layer.state;
                    if (layerState.visibility) {
                        result.layers.push(layer.options.name);
                        result.styles.push(layer.options.style || '');
                    }
                    if (layerState.info) {
                        result.infolayers.push(layer.options.name);
                    }
                }
            });
            return result;
        },
        /**
         * Overview support hack: get names of all 'selected' leaf layers (c.f. instance backend),
         * disregarding 'allowed', disregarding 'state', not recalculating out of scale / out of bounds etc.
         */
        getActivatedLeaves: function() {
            var layers = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function(node, index, parents) {
                var selected = node.options.treeOptions.selected;
                for (var pi = 0; selected && pi < parents.length; ++pi) {
                    selected = selected && parents[pi].options.treeOptions.selected;
                }
                if (selected) {
                    layers.push(node);
                }
            });
            return layers;
        },
        _bboxArrayToBounds: function(bboxArray, projCode) {
            if (this.configuration.options.version === '1.3.0') {
                var projDefaults = OpenLayers.Projection.defaults[projCode];
                var yx = projDefaults && projDefaults.yx;
                if (yx) {
                    // Seriously.
                    // See http://portal.opengeospatial.org/files/?artifact_id=14416 page 18
                    bboxArray = [bboxArray[1], bboxArray[0], bboxArray[3], bboxArray[2]];
                }
            }
            return Mapbender.Source.prototype._bboxArrayToBounds.call(this, bboxArray, projCode);
        },
        checkLayerParameterChanges: function(layerParams) {
            var olLayer = this.getNativeLayer(0);
            var newLayers = (olLayer.params.LAYERS || '').toString() !== layerParams.layers.toString();
            var newStyles = (olLayer.params.STYLES || '').toString() !== layerParams.styles.toString();
            return newLayers || newStyles;
        },
        getPointFeatureInfoUrl: function(x, y, maxCount) {
            var olLayer = this.getNativeLayer(0);
            if (!(olLayer && olLayer.getVisibility())) {
                return false;
            }
            var queryLayers = this.getLayerParameters({}).infolayers;
            if (!queryLayers.length) {
                return false;
            }
            var wmsgfi = new OpenLayers.Control.WMSGetFeatureInfo({
                url: Mapbender.Util.removeProxy(olLayer.url),
                layers: [olLayer],
                queryVisible: true,
                maxFeatures: maxCount || 100
            });
            wmsgfi.map = olLayer.map;
            var reqObj = wmsgfi.buildWMSOptions(
                Mapbender.Util.removeProxy(olLayer.url),
                [olLayer],
                {x: x, y: y},
                olLayer.params.FORMAT
            );
            reqObj.params = $.extend({}, this.customParams, reqObj.params);
            reqObj.params['LAYERS'] = reqObj.params['QUERY_LAYERS'] = queryLayers;
            reqObj.params['STYLES'] = [];
            reqObj.params['EXCEPTIONS'] = this.configuration.options.exception_format;
            reqObj.params['INFO_FORMAT'] = this.configuration.options.info_format || 'text/html';
            var reqUrl = OpenLayers.Util.urlAppend(reqObj.url, OpenLayers.Util.getParameterString(reqObj.params || {}));
            return reqUrl;
        },
        getMultiLayerPrintConfig: function(bounds, scale, projection) {
            var baseUrl = this.getPrintConfigLegacy(bounds).url;
            var baseParams = OpenLayers.Util.getParameters(baseUrl);
            var dataOut = [];
            var leafInfoMap = Mapbender.source.wms.getExtendedLeafInfo(this, scale, bounds);
            var units = projection.proj.units || 'degrees';
            var resFromScale = function(scale) {
                return scale && (OpenLayers.Util.getResolutionFromScale(scale, units)) || null;
            };
            _.forEach(leafInfoMap, function(item) {
                if (item.state.visibility) {
                    var layerParams = $.extend(OpenLayers.Util.upperCaseObject(baseParams), {
                        LAYERS: item.layer.options.name,
                        STYLES: item.layer.options.style || ''
                    });
                    var layerUrl = [baseUrl.split('?')[0], OpenLayers.Util.getParameterString(layerParams)].join('?');
                    dataOut.push({
                        url: layerUrl,
                        minResolution: resFromScale(item.layer.options.minScale),
                        maxResolution: resFromScale(item.layer.options.maxScale),
                        order: item.order
                    });
                }
            });
            return dataOut.sort(function(a, b) {
                return a.order - b.order;
            });
        },
        getPrintConfigLegacy: function(bounds) {
            var olLayer = this.getNativeLayer(0);
            return {
                type: 'wms',
                url: Mapbender.Util.removeProxy(olLayer.getURL(bounds))
            };
        }
    });
    return WmsSource;
}());

if(window.OpenLayers) {
    /**
     * This suppresses broken requests from MapQuery layers that get stuck with a
     * constantly empty LAYERS=... param.
     *
     * @return {boolean} Whether the layer is in range or not
     */
    OpenLayers.Layer.WMS.prototype.calculateInRange = function(){
        if(!this.params.LAYERS || !this.params.LAYERS.length) {
            return false;
        }
        return OpenLayers.Layer.prototype.calculateInRange.apply(this, arguments);
    }
}

Mapbender.source['wms'] = $.extend({}, Mapbender.Geo.SourceHandler, {
    /**
     * Returns legacy print object. Single object with 'type' and 'url'. Assumes all sources work with
     * a single layer. Does not check layer visibility. Does not provide opacity. Cannot reliably
     * indicate that nothing should be printed. Cannot respect print target scale. Expects
     * OpenLayers 2 objects as parameters. Source must be inferred from monkey-patched attribute
     * on OpenLayers layer.
     *
     * @param {OpenLayers.Layer} layer
     * @param {Mapbender.Source} layer.mbConfig
     * @param {OpenLayers.Bounds} bounds
     * @return {{type, url}|void}
     */
    getPrintConfig: function(layer, bounds) {
        console.warn("Calling legacy getPrintConfig. Please use Mapbender.Model.getPrintConfigEx instead, and pass in a Source");
        return layer.mbConfig.getPrintConfigLegacy(bounds);
    }
});
