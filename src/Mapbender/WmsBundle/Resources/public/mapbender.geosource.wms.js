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
        this.customParams = {};
        if (definition.customParams) {
            $.extend(this.customParams, definition.customParams);
        }
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
            return [Mapbender.mapEngine.createWmsLayer(this)];
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
        hasVisibleLayers: function(srsName) {
            var activatedLayers = this.getActivatedLeaves();
            var nonEmptyLayerNames = activatedLayers.map(function(sourceLayer) {
                return sourceLayer.options.name;
            }).filter(function(layerName) {
                return !!layerName;
            });
            return !!nonEmptyLayerNames.length;
        },
        _isBboxFlipped: function(srsName) {
            if (this.configuration.options.version === '1.3.0') {
                return Mapbender.mapEngine.isProjectionAxisFlipped(srsName);
            } else {
                return false;
            }
        },
        _bboxArrayToBounds: function(bboxArray, projCode) {
            var bboxArray_;
            if (this._isBboxFlipped(projCode)) {
                // Seriously.
                // See http://portal.opengeospatial.org/files/?artifact_id=14416 page 18
                bboxArray_ = [bboxArray[1], bboxArray[0], bboxArray[3], bboxArray[2]];
            } else {
                bboxArray_ = bboxArray;
            }
            return Mapbender.Source.prototype._bboxArrayToBounds.call(this, bboxArray_, projCode);
        },
        checkLayerParameterChanges: function(layerParams) {
            var olLayer = this.getNativeLayer(0);
            return Mapbender.mapEngine.compareWmsParams(olLayer, layerParams.layers, layerParams.styles);
        },
        getMultiLayerPrintConfig: function(bounds, scale, projection) {
            var olLayer = this.getNativeLayer(0);
            var baseUrl = Mapbender.Util.removeProxy(olLayer.getURL(bounds));
            var baseParams = OpenLayers.Util.getParameters(baseUrl);
            var dataOut = [];
            var leafInfoMap = Mapbender.source.wms.getExtendedLeafInfo(this, scale, bounds);
            var units = projection.proj.units || 'degrees';
            var resFromScale = function(scale) {
                return scale && (OpenLayers.Util.getResolutionFromScale(scale, units)) || null;
            };
            var changeAxis = this._isBboxFlipped(projection.projCode);
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
                        changeAxis: changeAxis,
                        order: item.order
                    });
                }
            });
            return dataOut.sort(function(a, b) {
                return a.order - b.order;
            });
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

Mapbender.source['wms'] = Mapbender.Geo.SourceHandler;
