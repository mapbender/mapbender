window.Mapbender = Mapbender || {};

(function () {
    Mapbender.WmsSourceLayer = class WmsSourceLayer extends Mapbender.SourceLayer {
        getId() {
            return this.options.id;
        }

        getName() {
            return this.options.name;
        }

        getLegend() {
            if (this.options.legend && this.options.legend.url) {
                return {
                    type: 'url',
                    url: this.options.legend.url
                };
            }
            return null;
        }

        setSelected(state) {
            this.options.treeOptions.selected = !!state;
        }

        getSelectedList() {
            var selectedLayers = [];
            if (this.getSelected()) {
                selectedLayers.push(this);
            }
            for (var i = 0; i < this.children.length; ++i) {
                selectedLayers = selectedLayers.concat(this.children[i].getSelectedList());
            }
            return selectedLayers;
        }

        isInScale(scale) {
            // NOTE: undefined / "open" limits are null, but it's safe to treat zero and null
            //       equivalently
            var min = this.options.minScale;
            var max = this.options.maxScale;
            if (min && min > scale) {
                return false;
            } else {
                return !(max && max < scale);
            }
        }

        intersectsExtent(extent, srsName) {
            var layerExtent = this.getBounds('EPSG:4326', false);
            if (layerExtent === null) {
                // unlimited layer extent
                return true;
            }
            var extent_;
            if (srsName !== 'EPSG:4326') {
                extent_ = Mapbender.mapEngine.transformBounds(extent, srsName, 'EPSG:4326');
            } else {
                extent_ = extent;
            }
            return Mapbender.Util.extentsIntersect(extent_, layerExtent);
        }
    }

    // @todo: add containing Layerset object to constructor (currently post-instantiation-patched in application setup)
    Mapbender.WmsSource = class WmsSource extends Mapbender.Source {
        constructor(definition) {
            super(definition);
            var customParams = {};
            if (definition.customParams) {
                $.extend(this.customParams, definition.customParams);
            }
            (definition.configuration.options.dimensions || []).map(function (dimensionConfig) {
                if (dimensionConfig.default) {
                    customParams[dimensionConfig.__name] = dimensionConfig.default;
                }
            });

            // We must remember custom params for serialization in getMapState()...
            this.customParams = customParams;

            // ... but we will not remember the following ~standard WMS params the same way
            this._runtimeParams = ['LAYERS', 'STYLES', 'EXCEPTIONS', 'QUERY_LAYERS', 'INFO_FORMAT', '_OLSALT'];
        }

        createNativeLayers(srsName, mapOptions) {
            this.nativeLayers = [Mapbender.mapEngine.createWmsLayer(this, mapOptions)];
            return this.nativeLayers;
        }

        /**
         * @return {SourceSettings}
         */
        getSettings() {
            const selectedLayers = this.getRootLayer().getSelectedList().map(function (layer) {
                return {
                    id: layer.getId(),
                    name: layer.getName(),
                };
            });
            return Object.assign(super.getSettings(this), {
                selectedLayers: selectedLayers
            });
        }

        /**
         * @param {SourceSettingsDiff|null} diff
         */
        applySettingsDiff(diff) {
            super.applySettingsDiff(diff);

            const isDiffValid = diff && ((diff.activate || []).length || (diff.deactivate || []).length);
            if (!isDiffValid) return;

            Mapbender.Util.SourceTree.iterateLayers(this, false, function (layer, index, parents) {
                for (let index in (diff.activate || [])) {
                    if (diff.activate[index].id === layer.getId()) layer.setSelected(true);
                }
                for (let index in (diff.deactivate || [])) {
                    if (diff.deactivate[index].id === layer.getId()) layer.setSelected(false);
                }
            }.bind(this));
        }

        getSelected() {
            return this.getRootLayer().getSelected();
        }

        refresh() {
            var cacheBreakParams = {
                _OLSALT: Math.random()
            };
            this.addParams(cacheBreakParams);
        }

        addParams(params) {
            for (let i = 0; i < this.nativeLayers.length; ++i) {
                Mapbender.mapEngine.applyWmsParams(this.nativeLayers[i], params);
            }
            const rtp = this._runtimeParams;
            $.extend(this.customParams, Mapbender.Util.filter(params, function (value, key) {
                return rtp.indexOf(('' + key).toUpperCase()) === -1;
            }));
        }

        removeParams(names) {
            // setting a param to null effectively removes it from the generated URL
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Util.js#L514
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Layer/HTTPRequest.js#L197
            // see https://github.com/openlayers/openlayers/blob/v4.6.5/src/ol/uri.js#L16
            let mapOfNamesToRemove = {}
            names.forEach((name) => {
                mapOfNamesToRemove[name] = null;
            })
            this.addParams(mapOfNamesToRemove);
        }

        toJSON() {
            var s = super.toJSON(arguments);
            s.customParams = this.customParams;
            return s;
        }

        updateEngine() {
            var layers = [], styles = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function (layer) {
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
                if (paramsChanged && visibilityChanged) {
                    // Prevent Openlayers 6 from reusing the previous image contents while
                    // still fetching from modified url
                    if (olLayer.getRenderer && olLayer.getRenderer().getImage()) {
                        olLayer.getRenderer().image_ = null;
                    }
                }
                engine.applyWmsParams(olLayer, newParams);
                engine.setLayerVisibility(olLayer, true);
            }
        }

        /**
         * @return {Array<WmsSourceLayer>}
         */
        getFeatureInfoLayers() {
            var layers = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function (layer) {
                // Layer names can be emptyish, most commonly on root layers
                // Suppress layers with empty names entirely
                if (layer.options.name && layer.state.info) {
                    layers.push(layer);
                }
            });
            return layers;
        }

        /**
         * Overview support hack: get names of all 'selected' leaf layers (c.f. instance backend),
         * disregarding 'allowed', disregarding 'state', not recalculating out of scale / out of bounds etc.
         */
        getActivatedLeaves() {
            var layers = [];
            Mapbender.Util.SourceTree.iterateSourceLeaves(this, false, function (node, index, parents) {
                var selected = node.options.treeOptions.selected;
                for (var pi = 0; selected && pi < parents.length; ++pi) {
                    selected = selected && parents[pi].options.treeOptions.selected;
                }
                if (selected) {
                    layers.push(node);
                }
            });
            return layers;
        }

        hasVisibleLayers(srsName) {
            var activatedLayers = this.getActivatedLeaves();
            var nonEmptyLayerNames = activatedLayers.map(function (sourceLayer) {
                return sourceLayer.options.name;
            }).filter(function (layerName) {
                return !!layerName;
            });
            return !!nonEmptyLayerNames.length;
        }

        /**
         * Build base params (no SRS / CRS / BBOX considerations) for GetMap
         * request.
         *
         * @return Object<String, (String | (Array<String>))
         */
        getGetMapRequestBaseParams() {
            var params = {
                LAYERS: [],
                STYLES: [],
                VERSION: this.configuration.options.version,
                TRANSPARENT: this.configuration.options.transparent && 'TRUE' || 'FALSE',
                FORMAT: this.configuration.options.format || null
            };
            var activatedLeaves = this.getActivatedLeaves();
            for (var i = 0; i < activatedLeaves.length; ++i) {
                var sourceLayer = activatedLeaves[i];
                // skip layers with empty name (valid for group container layers that cannot be requested directly)
                if (!!sourceLayer.options.name) {
                    params.LAYERS.push(sourceLayer.options.name);
                    // @todo: use configured style
                    params.STYLES.push('');
                }
            }
            return params;
        }

        _isBboxFlipped(srsName) {
            if (this.configuration.options.version === '1.3.0') {
                return Mapbender.mapEngine.isProjectionAxisFlipped(srsName);
            } else {
                return false;
            }
        }

        /**
         * @param {*} bounds
         * @param {Number} scale
         * @param {String} srsName
         * @return {Array<Object>}
         */
        getPrintConfigs(bounds, scale, srsName) {
            var baseUrl = Mapbender.mapEngine.getWmsBaseUrl(this.getNativeLayer(0), srsName, true);
            var extraParams = {
                REQUEST: 'GetMap',          // required for tunnel resolution
                VERSION: this.configuration.options.version,
                FORMAT: this.configuration.options.format || 'image/png'
            };
            var dataOut = [];
            var leafInfoMap = Mapbender.Geo.SourceHandler.getExtendedLeafInfo(this, scale, bounds);
            var resFromScale = function (scale) {
                return (scale && Mapbender.Model.scaleToResolution(scale, undefined, srsName)) || null;
            };
            var commonOptions = Object.assign({}, this._getPrintBaseOptions(), {
                changeAxis: this._isBboxFlipped(srsName)
            });
            for (const item of Object.values(leafInfoMap)) {
                if (item.state.visibility) {
                    var replaceParams = Object.assign({}, extraParams, {
                        LAYERS: item.layer.options.name,
                        STYLES: item.layer.options.style || ''
                    });
                    var layerUrl = Mapbender.Util.replaceUrlParams(baseUrl, replaceParams, false);
                    dataOut.push(Object.assign({}, commonOptions, {
                        url: layerUrl,
                        minResolution: resFromScale(item.layer.options.minScale),
                        maxResolution: resFromScale(item.layer.options.maxScale),
                        order: item.order
                    }));
                }
            }
            return dataOut.sort(function (a, b) {
                return a.order - b.order;
            });
        }
    }

    Mapbender.Source.typeMap['wms'] = Mapbender.WmsSource;
    Mapbender.SourceLayer.typeMap['wms'] = Mapbender.WmsSourceLayer;

}());
