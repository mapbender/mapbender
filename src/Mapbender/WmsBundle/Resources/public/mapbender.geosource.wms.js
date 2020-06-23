window.Mapbender = Mapbender || {};
window.Mapbender.WmsSourceLayer = (function() {
    function WmsSourceLayer() {
        Mapbender.SourceLayer.apply(this, arguments);
    }
    Mapbender.SourceLayer.typeMap['wms'] = WmsSourceLayer;
    WmsSourceLayer.prototype = Object.create(Mapbender.SourceLayer.prototype);
    Object.assign(WmsSourceLayer.prototype, {
        constructor: WmsSourceLayer,
        getSelected: function() {
            return this.options.treeOptions.selected;
        }
    });
    return WmsSourceLayer;
}());
window.Mapbender.WmsSource = (function() {
    // @todo: add containing Layerset object to constructor (currently post-instantiation-patched in application setup)
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
    Object.assign(WmsSource.prototype, {
        // We must remember custom params for serialization in getMapState()...
        customParams: {},
        // ... but we will not remember the following ~standard WMS params the same way
        _runtimeParams: ['LAYERS', 'STYLES', 'EXCEPTIONS', 'QUERY_LAYERS', 'INFO_FORMAT', '_OLSALT'],
        createNativeLayers: function(srsName) {
            return [Mapbender.mapEngine.createWmsLayer(this)];
        },
        getSelected: function() {
            // delegate to root layer
            return this.configuration.children[0].getSelected();
        },
        addParams: function(params) {
            for (var i = 0; i < this.nativeLayers.length; ++i) {
                Mapbender.mapEngine.applyWmsParams(this.nativeLayers[i], params);
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
            // see https://github.com/openlayers/openlayers/blob/v4.6.5/src/ol/uri.js#L16
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
        updateEngine: function() {
            var layers = [], styles = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function(layer) {
                // Layer names can be emptyish, most commonly on root layers
                // Suppress layers with empty names entirely
                if (layer.options.name && layer.state.visibility) {
                    layers.push(layer.options.name);
                    styles.push(layer.options.style || '');
                }
            });
            var engine = Mapbender.mapEngine;
            var targetVisibility = !!layers.length && this.getActive();
            var olLayer = this.getNativeLayer(0);
            var visibilityChanged = targetVisibility !== engine.getLayerVisibility(olLayer);
            var paramsChanged = engine.compareWmsParams(olLayer, layers, styles);
            if (!visibilityChanged && !paramsChanged) {
                return;
            }

            if (paramsChanged && olLayer.map && olLayer.map.tileManager) {
                olLayer.map.tileManager.clearTileQueue({
                    object: olLayer
                });
            }
            if (!targetVisibility) {
                engine.setLayerVisibility(olLayer, false);
            } else {
                var newParams = {
                    LAYERS: layers,
                    STYLES: styles
                };
                if (visibilityChanged) {
                    // Prevent the browser from reusing the loaded image. This is almost equivalent
                    // to a forced redraw (c.f. olLayer.redraw(true)), but without the undesirable
                    // side effect of loading the layer twice on first activation.
                    // @see https://github.com/openlayers/ol2/blob/master/lib/OpenLayers/Layer/HTTPRequest.js#L157
                    newParams['_OLSALT'] = Math.random();
                }
                engine.applyWmsParams(olLayer, newParams);
                engine.setLayerVisibility(olLayer, true);
            }
        },
        /**
         * @return {Array<WmsSourceLayer>}
         */
        getFeatureInfoLayers: function() {
            var layers = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function(layer) {
                // Layer names can be emptyish, most commonly on root layers
                // Suppress layers with empty names entirely
                if (layer.options.name && layer.state.info) {
                    layers.push(layer);
                }
            });
            return layers;
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
        getMultiLayerPrintConfig: function(bounds, scale, srsName) {
            var baseUrl = Mapbender.mapEngine.getWmsBaseUrl(this.getNativeLayer(0), srsName);
            var extraParams = {
                SERVICE: 'WMS',
                REQUEST: 'GetMap',
                VERSION: this.configuration.options.version,
                FORMAT: this.configuration.options.format || 'image/png'
            };
            baseUrl = Mapbender.Util.removeProxy(baseUrl);
            var dataOut = [];
            var leafInfoMap = Mapbender.Geo.SourceHandler.getExtendedLeafInfo(this, scale, bounds);
            var resFromScale = function(scale) {
                return (scale && Mapbender.Model.scaleToResolution(scale, undefined, srsName)) || null;
            };
            var changeAxis = this._isBboxFlipped(srsName);
            _.forEach(leafInfoMap, function(item) {
                if (item.state.visibility) {
                    var replaceParams = Object.assign({}, extraParams, {
                        LAYERS: item.layer.options.name,
                        STYLES: item.layer.options.style || ''
                    });
                    var layerUrl = Mapbender.Util.replaceUrlParams(baseUrl, replaceParams, false);
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

