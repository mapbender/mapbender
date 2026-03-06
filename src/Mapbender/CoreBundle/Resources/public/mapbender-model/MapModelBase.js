window.Mapbender = Mapbender || {};
window.Mapbender.MapModelBase = (function() {
    /**
     * @typedef {Object} mmFlexibleExtent
     * @property {number} left
     * @property {number} right
     * @property {number} bottom
     * @property {number} top
     */
    /**
     * @typedef {Object} mmClickData
     * @property {Array<number>} pixel
     * @property {Array<number>} coordinate
     * @property {*} event
     */
    /**
     * @typedef {Object} mmDimension
     * @property {Number} width
     * @propery {Number} height
     */
    /**
     * @typedef {Object} mmViewParams
     * @property {String} srsName
     * @property {Array<Number>} center
     * @property {Number} scale
     * @property {Number} rotation
     */
    /**
     * @typedef {Object} mmMapSourceSettings
     * @property {Array<{(SourceSettings|{id: String})}>} sources
     * @property {Array<{id: string, selected: boolean}>} layersets
     */
    /**
     * @typedef {mmMapSourceSettings} mmMapSettings
     * @property {mmViewParams} viewParams
     */
    /**
     * @typedef {Object} mmMapSettingsLayersetsDiff
     * @property {Array<String>} activate
     * @property {Array<String>} deactivate
     */
    /**
     * @typedef {Object} mmMapSettingsDiff
     * @property {mmViewParams} viewParams
     * @property {Array<mmMapSettingsLayersetsDiff>} layersets
     * @property {Array<SourceSettingsDiff>} sources
     */
    /**
     * @param {Object} mbMap
     * @constructor
     */
    function MapModelBase(mbMap) {
        Mapbender.mapEngine.patchGlobals(mbMap.options);
        Mapbender.Projection.extendSrsDefintions(mbMap.options.srsDefs || []);
        this.mbMap = mbMap;
        var mapOptions = mbMap.options;
        this._configProj = mapOptions.srs;
        try {
            this._poiOptions = this._parsePoiParameter(window.location.href);
        } catch (e) {
            console.warn("Ignoring poi param parsing error", e);
            this._poiOptions = [];
        }
        this.initialViewParams = this._getInitialViewParams(mapOptions);
        this.mapMaxExtent = Mapbender.mapEngine.boundsFromArray(mapOptions.extent_max);
        this.sourceTree = this.getConfiguredSources_();
        this.configuredSettings_ = Object.assign({}, this.getCurrentSourceSettings(), {
            viewParams: this._getConfiguredViewParams(mapOptions)
        });
        if (Mapbender.configuration.application.persistentView) {
            try {
                var settings = this.getLocalStorageSettings();
                if (settings) {
                    this.applySourceSettings(settings);
                }
            } catch (e) {
                console.error("Restoration of local storage source selection settings failed, ignoring");
                throw e;
            }
        }
    }

    MapModelBase.prototype = {
        constructor: MapModelBase,
        mbMap: null,
        sourceTree: [],
        mapMaxExtent: null,
        /** Backend-configured initial projection, used for start / max extents */
        _configProj: null,
        /**
         * @param {boolean} [closest] round to nearest configured map scale (default true if omitted)
         * @return {number}
         * engine-agnostic
         */
        getCurrentScale: function(closest) {
            if (closest || (typeof closest === 'undefined')) {
                var zoom = this.getCurrentZoomLevel(true);
                var scales = this._getScales();
                return scales[zoom];
            } else {
                return this._getFractionalScale();
            }
        },
        /**
         * @param {boolean} [closest] round to nearest configured map scale (default true if omitted)
         * @returns {number}
         */
        getCurrentZoomLevel: function(closest) {
            var zoom = this._getFractionalZoomLevel();
            if (closest || (typeof closest === 'undefined')) {
                zoom = Math.max(0, Math.min(this._countScales() - 1, Math.round(zoom)));
            }
            return zoom;
        },
        /**
         * engine-agnostic
         * @param targetScale {int} the scale (1:x) to pick the zoom level for
         * @param [pickHigh] {boolean} if true, picks the higher scale if targetScale is between two scales (default: lower scale)
         * @param [fractional] {boolean} if true, returns fractional zoom levels. (default: false) If set, pickHigh is ignored
         * @return {number} the calculated zoom level, integer unless fractional is set to true
         */
        pickZoomForScale: function(targetScale, pickHigh, fractional) {
            const scales = this._getScales();

            if (targetScale >= scales[0]) {
                return 0;
            }
            for (let i = 0; i < scales.length - 1; ++i) {
                const scaleHigh = scales[i];
                const scaleLow = scales[i + 1];
                if (targetScale <= scaleHigh && targetScale >= scaleLow) {
                    if (fractional) {
                        // the scale to zoom calculation in OpenLayers follows an exponential progression
                        // scale = c * (scaleLow / scaleHigh)^zoom
                        // the constant c can be determined using scaleHigh (or scaleLow)
                        const c = scaleHigh / Math.pow(scaleLow / scaleHigh, i);
                        return (Math.log(targetScale) - Math.log(c)) / Math.log(scaleLow / scaleHigh);
                    }
                    if (targetScale > scaleLow && pickHigh) {
                        return i;
                    }
                    return i+1;
                }
            }
            return scales.length - 1;
        },
        /**
         * BC convenience getter. Commonly used only to determine number of decimals for rounding
         * coordinates for display.
         *
         * @param {String} [srsName] defaults to current projection
         * @return {number}
         * engine-agnostic
         */
        getProjectionUnitsPerMeter: function(srsName) {
            return Mapbender.mapEngine.getProjectionUnitsPerMeter(srsName || this.getCurrentProjectionCode());
        },
        /**
         * @param {int} scale
         * @param {number} [dpi]
         * @param {String} [srsName] defaults to current projection
         * @return {number}
         * engine-agnostic
         */
        scaleToResolution: function (scale, dpi, srsName) {
            var upm = this.getProjectionUnitsPerMeter(srsName);
            var inchesPerMetre = 39.37;
            return (scale * upm) / (inchesPerMetre * (dpi || this.mbMap.options.dpi || 72));
        },

        /**
         * @param {number} resolution
         * @param {number} [dpi=72]
         * @param {String} [srsName] defaults to current projection
         * @returns {number}
         * engine-agnostic
         */
        resolutionToScale: function(resolution, dpi, srsName) {
            var upm = this.getProjectionUnitsPerMeter(srsName);
            var inchesPerMetre = 39.37;
            return resolution * inchesPerMetre * (dpi || this.mbMap.options.dpi || 72) / upm;
        },
        /**
         * @return {Array<Object>}
         * engine-agnostic
         */
        getZoomLevels: function() {
            return this._getScales().map(function(scale, index) {
                return {
                    scale: scale,
                    level: index
                };
            });
        },
        /**
         * Activate / deactivate a single layer's selection and / or FeatureInfo state states.
         *
         * @param {Mapbender.SourceLayer} layer
         * @param {boolean|null} [selected]
         * @param {boolean|null} [info]
         * @param {boolean|null} [ignoreAllowSelectedSetting] if set to true the setting allowSelected will be ignored
         * engine-agnostic
         */
        controlLayer: function controlLayer(layer, selected, info, ignoreAllowSelectedSetting) {
            var updated = false;
            if (layer && selected !== null && typeof selected !== 'undefined') {
                var selected0 = layer.options.treeOptions.selected;
                var selectedAfter = !!selected && (ignoreAllowSelectedSetting || layer.options.treeOptions.allow.selected);
                updated = updated || (selected0 !== selectedAfter);
                layer.options.treeOptions.selected = selectedAfter;
            }
            if (layer && info !== null && typeof info !== 'undefined') {
                var info0 = layer.options.treeOptions.info;
                var infoAfter = !!info && layer.options.treeOptions.allow.info;
                updated = updated || (info0 !== infoAfter);
                layer.options.treeOptions.info = infoAfter;
            }
            if (updated) {
                this.updateSource(layer.source);
            }
        },
        /**
         * Updates the source identified by given id with a new layer order.
         * This will pull styles and "state" (such as visibility) from values
         * currently stored in the "geosource".
         *
         * @param {string} sourceId
         * @param {string[]} newLayerIdOrder
         * engine-agnostic
         */
        setSourceLayerOrder: function(sourceId, newLayerIdOrder) {
            const source = this.getSourceById(sourceId);
            source.setLayerOrder(newLayerIdOrder);
            this._checkSource(source, false);
            $(this.mbMap.element).trigger('mbmapsourcelayersreordered', {
                mbMap: this.mbMap,
                source: source
            });
        },
        /**
         * Zooms to layer
         * @param {Object} options
         * @property {String} options.sourceId
         * @property {String} options.layerId
         */
        zoomToLayer: function(options) {
            var source = this.getSourceById(options.sourceId);
            var bounds = source && source.getLayerBounds(options.layerId, this.getCurrentProjectionCode(), true, true);
            if (bounds) {
                this.setExtent(bounds);
            }
        },
        /**
         * @param {OpenLayers.Feature.Vector|ol.Feature} feature
         * @param {Object} [options]
         * @param {number=} options.buffer in meters
         * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
         *      just keeps feature in view
         */
        panToFeature: function(feature, options) {
            var scale = this.getCurrentScale(false);
            // default to zero buffering
            var ztfOptions = Object.assign({buffer: 0}, options, {
                minScale: scale,
                maxScale: scale
            });
            this.zoomToFeature(feature, ztfOptions);
        },
        /**
         * @param {Number|String} id
         * @return {Mapbender.Source|null}
         * engine-agnostic
         */
        getSourceById: function(id) {
            return Mapbender.Util.findFirst(this.sourceTree, (value) => value.id === '' + id);
        },
        /**
         * @param {Number|String} id
         * @return {Mapbender.Layerset|null}
         */
        getLayersetById: function(id) {
            return Mapbender.Util.findFirst(Mapbender.layersets, (value) => value.id === '' + id);
        },
        /**
         * @param {Mapbender.Layerset} theme
         * @param {Boolean} state
         */
        controlTheme: function(theme, state) {
            if (theme.getSelected() !== state) {
                theme.setSelected(state);
                this.mbMap.element.trigger('mb.sourcenodeselectionchanged', {
                    node: theme,
                    selected: theme.getSelected()
                });
            }
            var instances = theme.children;
            for (var i = 0; i < instances.length; ++i) {
                var instance = instances[i];
                this.updateSource(instance);
            }
        },
        /**
         * @param {Mapbender.Source} source
         * @param {boolean} state layer source will be set to visible if true
         * @param {boolean} ignoreAllowSelectedSetting if set to true the setting allowSelected will be ignored
         * engine-agnostic
         */
        setSourceVisibility: function(source, state, ignoreAllowSelectedSetting) {
            this.controlLayer(source.getRootLayer(), state, null, ignoreAllowSelectedSetting);
        },
        setSourceOpacity: function(source, opacity) {
            source.setOpacity(opacity);
            this.triggerSourceChanged_(source);
        },
        /**
         * Reevaluates source's treeOptions and other settings and reapplies effective parameters.
         * This should be used if a sources internal configuration structure has been updated "manually".
         *
         * @param {Object} source
         */
        updateSource: function(source) {
            this._checkSource(source, false);
            this.triggerSourceChanged_(source);
        },
        triggerSourceChanged_: function(source) {
            $(this.mbMap.element).trigger('mbmapsourcechanged', {
                mbMap: this.mbMap,
                source: source
            });
        },
        /**
         * @return {Array<Mapbender.Source>}
         * engine-agnostic
         */
        getSources: function() {
            return this.sourceTree;
        },
        /**
         * @param {string} [srsName] default: current
         * @return {mmFlexibleExtent}
         */
        getCurrentExtent: function(srsName) {
            var srsNow = this.getCurrentProjectionCode();
            var srsName_ = srsName || srsNow;
            var extentArray = this.getCurrentExtentArray();
            var extentNative = Mapbender.mapEngine.boundsFromArray(extentArray);
            return Mapbender.mapEngine.transformBounds(extentNative, srsNow, srsName_);
        },
        /**
         * @param {string} [srsName] default: current
         * @return {mmFlexibleExtent}
         */
        getMaxExtent: function(srsName) {
            var srsName_ = srsName || this.getCurrentProjectionCode();
            return Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, srsName_);
        },
        /**
         * @param {string} [srsName] default: current
         * @return {Array<Number>}
         */
        getMaxExtentArray: function(srsName) {
            var x = this.getMaxExtent(srsName);
            return [x.left, x.bottom, x.right, x.top];
        },
        /**
         * engine-agnostic
         */
        _getMaxZoomLevel: function() {
            // @todo: fractional zoom: no discrete scale steps
            return this._getScales().length - 1;
        },
        /**
         * engine-agnostic
         */
        _clampZoomLevel: function(zoomIn) {
            return Math.max(0, Math.min(zoomIn, this._getMaxZoomLevel()));
        },
        /**
         * Calculates and applies layer state changes from accumulated treeOption changes in the source and (optionally)
         * 1) updates the engine layer parameters and redraws
         * 2) fires a mbmapsourcechanged event
         * @param {Object} source
         * @param {boolean} fireSourceChangedEvent
         */
        _checkSource: function(source, fireSourceChangedEvent) {
            var scale = this.getCurrentScale(false);
            var extent = this.getCurrentExtent();
            var srsName = this.getCurrentProjectionCode();
            var changedStates = Mapbender.Geo.SourceHandler.updateLayerStates(source, scale, extent, srsName);
            source.updateEngine();
            if (fireSourceChangedEvent && changedStates) {
                this.triggerSourceChanged_(source);
            }
        },
        /**
         * @return {Array<String>}
         * @static
         */
        getViewRelatedUrlParamNames: function() {
            return ['scale', 'center', 'srs', 'bbox'];
        },
        /**
         * @return {Array<String>}
         * @static
         */
        getHandledUrlParams: function() {
            return MapModelBase.prototype.getViewRelatedUrlParamNames.call().concat([
                'visiblelayers',
                'slon',
                'sloff',
                'lson',
                'lsoff',
                'slstyle',
                'sop',
                'wms_id',
                'wms_url'
            ]);
        },
        processUrlParams: function() {
            var params = Mapbender.Util.getUrlQueryParams(window.location.href, true);
            var visibleLayersParam = params['visiblelayers'];
            if (visibleLayersParam) {
                this.processVisibleLayersParam(visibleLayersParam);
            }
            try {
                var settingsDiff = this.decodeSettingsDiff(params);
                // @todo: extract / fold with applySettings
                var i, ls;
                for (i = 0; i < settingsDiff.layersets.activate.length; ++i) {
                    ls = this.getLayersetById(settingsDiff.layersets.activate[i]);
                    if (ls) {
                        ls.setSelected(true);
                    }
                }
                for (i = 0; i < settingsDiff.layersets.deactivate.length; ++i) {
                    ls = this.getLayersetById(settingsDiff.layersets.deactivate[i]);
                    if (ls) {
                        ls.setSelected(false);
                    }
                }
                for (i = 0; i < settingsDiff.sources.length; ++i) {
                    var sourceDiff = settingsDiff.sources[i];
                    var source = this.getSourceById(sourceDiff.id);
                    if (source) {
                        source.applySettingsDiff(sourceDiff);
                    }
                }

            } catch (e) {
                console.warn("Error applying extra url params, ignoring", params, e);
            }
        },
        /**
         * Activate specific layers on specific sources by interpreting a (comma-separated list of)
         * "<sourceId>/<layerId>" parameter pair.
         * The indicated source and layer must already be part of the running configuration for this
         * to work.
         *
         * @param {string} paramValue
         */
        processVisibleLayersParam: function(paramValue) {
            if (typeof paramValue !== 'string') return;
            var self = this;
            var specs = (paramValue || '').split(',');
            $.each(specs, function(idx, layerSpec) {
                const idParts = layerSpec.split('/');
                let sourceId = idParts[0];
                let layerId = idParts.length >= 2 ? idParts[1] : null;
                if ((sourceId && isNaN(sourceId)) || (layerId && isNaN(layerId))) {
                    const sourceAndLayerId = self.findSourceAndLayerIdByName(sourceId, layerId);
                    sourceId = sourceAndLayerId.sourceId;
                    layerId = sourceAndLayerId.layerId;
                }
                console.log("Activating", sourceId, layerId);
                const source = self.getSourceById(sourceId);
                if (!source) return;

                let layer = layerId ? source.getLayerById(layerId) : source.getRootLayer();
                if (layer) {
                    layer.options.treeOptions.info = layer.options.treeOptions.allow.info;
                }
                while (layer) {
                    layer.setSelected(true);
                    layer.source.layerset?.setSelected(true);
                    layer = layer.parent;
                }
            });
        },

        findSourceAndLayerIdByName: function (sourceName, layerName) {
            const sourceAndLayerId = {};
            this.getSources().forEach(function (source) {
                if (!source.children || !source.children.length) return;
                const config = source.children[0];
                if (config.options.name === sourceName || config.options.title === sourceName) {
                    sourceAndLayerId.sourceId = config.source.id;
                    if (!layerName) return sourceAndLayerId;
                    config.children.forEach(function (child) {
                        if (child.options.name === layerName || child.options.title === layerName) {
                            sourceAndLayerId.layerId = child.options.id;
                        }
                    });
                }
            });
            return sourceAndLayerId;
        }
    };

    // Deprecated old-style APIs
    Object.assign(MapModelBase.prototype, {
        removeLayer: function(layer) {
            if (!layer.parent) {
                this.removeSource(layer.source);
            } else {
                var topMostRemovedLayerId = layer.remove();
                var rootLayerId = layer.source.getRootLayer().options.id;
                if (topMostRemovedLayerId === rootLayerId) {
                    this.removeSource(layer.source);
                } else {
                    this._checkSource(layer.source, false);
                    this.mbMap.element.trigger('mbmapsourcelayerremoved', {
                        layer: layer,
                        source: layer.source,
                        mbMap: this.mbMap
                    });
                }
            }
        },
        removeSource: function(source) {
            var stIndex = this.sourceTree.indexOf(source);
            Mapbender.mapEngine.removeLayers(this.olMap, source.nativeLayers);
            if (stIndex !== -1) {
                this.sourceTree.splice(stIndex, 1);
            }
            if (source.layerset) {
                source.layerset.removeChild(source);
            }
            $(this.mbMap.element).trigger('mbmapsourceremoved', {
                source: source
            });
        },
        getConfiguredSources_: function() {
            // Array.protoype.reverse is in-place
            // see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Array/reverse
            // Do not use .reverse on centrally shared values without making your own copy
            var layersetNames = this.mbMap.options.layersets.slice().reverse();
            var sources = [];
            for (var i = 0; i < layersetNames.length; ++i) {
                const layersetId = layersetNames[i];
                var theme = this.getLayersetById(layersetId);
                if (!theme) {
                    console.log("No layerset with id " + layersetId + ". Edit and save the map element if you just removed a layerset or layer.");
                } else {
                    sources = sources.concat.apply(sources, theme.children.slice().reverse());
                }
            }
            return sources;
        },
        /**
         * @param {Array<Mapbender.Source>} sources
         */
        initializeSourceLayers: function(sources) {
            var projCode = this.getCurrentProjectionCode();
            for (var i = 0; i < sources.length; ++i) {
                var source = sources[i];
                this.mbMap.element.trigger('mbconfiguringsource', {
                    mbMap: this.mbMap,
                    source: source
                });
                var olLayers = source.createNativeLayers(projCode, this.mbMap.options);
                for (var j = 0; j < olLayers.length; ++j) {
                    var olLayer = olLayers[j];
                    Mapbender.mapEngine.setLayerVisibility(olLayer, false);
                }

                this._spliceLayers(source, olLayers);
                this._checkSource(source, false);
            }
        },
        /**
         * @param {Mapbender.Source} source
         */
        addSource: function(source) {
            this.sourceTree.push(source);

            this.initializeSourceLayers([source]);

            this.mbMap.element.trigger('mbmapsourceadded', {
                mbMap: this.mbMap,
                source: source
            });
        },
        /**
         * Creates a Mapbender.Source instance from given configuration, adds it,
         * and returns it.
         *
         * @param {Object} sourceDef
         * @returns {Mapbender.Source}
         */
        addSourceFromConfig: function(sourceDef) {
            var source = Mapbender.Source.factory(sourceDef);
            this.addSource(source);
            return source;
        },
        /**
         * Bring the sources identified by the given ids into the given order.
         * All other sources will be left alone!
         *
         * @param {string[]} newIdOrder
         */
        reorderSources: function(newIdOrder) {
            var self = this, olMap = this.olMap, engine = Mapbender.mapEngine;
            var i;

            // Collect current positions used by the layers to be reordered
            // position := array index in olMap.layers
            // The collected positions will be reused / redistributed to the affected
            // layers, while all other layers stay in their current slots.
            var layersToMove = [];
            var currentLayerArray = engine.getLayerArray(olMap);
            var oldIndexes = [];
            var olLayerIdsToMove = {};
            for (i = 0; i < newIdOrder.length; ++i) {
                var source = this.getSourceById(newIdOrder[i]);
                source && source.getNativeLayers().map(function(olLayer) {
                    layersToMove.push(olLayer);
                    oldIndexes.push(currentLayerArray.indexOf(olLayer));
                    var layerUid = engine.getUniqueLayerId(olLayer);
                    olLayerIdsToMove[layerUid] = true;
                });
            }
            oldIndexes.sort(function(a, b) {
                // sort numerically (default sort performs string comparison)
                return a - b;
            });

            var unmovedLayers = currentLayerArray.filter(function(olLayer) {
                var layerUid = engine.getUniqueLayerId(olLayer);
                return !olLayerIdsToMove[layerUid];
            });

            // rebuild the layer list, mixing in unmoving layers with reordered layers
            var newLayers = [];
            var unmovedIndex = 0;
            for (i = 0; i < oldIndexes.length; ++i) {
                var nextIndex = oldIndexes[i];
                while (nextIndex > newLayers.length) {
                    newLayers.push(unmovedLayers[unmovedIndex]);
                    ++unmovedIndex;
                }
                newLayers.push(layersToMove[i]);
            }
            while (unmovedIndex < unmovedLayers.length) {
                newLayers.push(unmovedLayers[unmovedIndex]);
                ++unmovedIndex;
            }
            // set new layer list, let OpenLayers reassign z indexes in list order
            engine.replaceLayers(olMap, newLayers);
            // Re-sort 'sourceTree' structure (inspected by legend etc for source order) according to actual, applied
            // layer order.
            this.sourceTree.sort(function(a, b) {
                var indexA = newLayers.indexOf(a.getNativeLayer(0));
                var indexB = newLayers.indexOf(b.getNativeLayer(0));
                return indexA - indexB;
            });
            this.mbMap.element.trigger('mbmapsourcesreordered', {
                mbMap: this.mbMap
            });
        },
        changeProjection: function(srsName) {
            var srsNameBefore = this.getCurrentProjectionCode();
            if (srsNameBefore === srsName) {
                return;
            }
            $(this.mbMap.element).trigger('mbmapbeforesrschange', {
                from: srsNameBefore,
                to: srsName,
                mbMap: this.mbMap
            });
            this._changeProjectionInternal(srsNameBefore, srsName);
            this._applyLayerSrsChange(srsNameBefore, srsName);
            this.mbMap.element.trigger('mbmapsrschanged', {
                from: srsNameBefore,
                to: srsName,
                mbMap: this.mbMap
            });
            for (var i = 0; i < this.sourceTree.length; ++i) {
                var source = this.sourceTree[i];
                if (source.checkRecreateOnSrsSwitch(srsNameBefore, srsName)) {
                    // WMTS / TMS special: send another change event for each root layer, which
                    // may potentially just have been disabled / reenabled. This will update the
                    // Layertree visual
                    this.triggerSourceChanged_(source);
                }
            }
            this.mbMap.element.trigger('mbmapviewchanged', {
                mbMap: this.mbMap,
                params: this.getCurrentViewParams()
            });
        },
        _applyLayerSrsChange: function(srsNameFrom, srsNameTo) {
            for (var i = 0; i < this.sourceTree.length; ++i) {
                var source = this.sourceTree[i];
                if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                    var olLayers = source.createNativeLayers(srsNameTo, this.mbMap.options);
                    for (var j = 0; j < olLayers.length; ++j) {
                        var olLayer = olLayers[j];
                        Mapbender.mapEngine.setLayerVisibility(olLayer, false);
                    }
                    this._spliceLayers(source, olLayers);
                }
            }
            var self = this;
            self.sourceTree.map(function(source) {
                self._checkSource(source, true);
            });
        },
        /**
         * @param {number|null} targetZoom
         * @param {Object} scaleOptions
         * @param {number=} scaleOptions.minScale
         * @param {number=} scaleOptions.maxScale
         * @return {number|null}
         * @private
         */
        _adjustZoom: function(targetZoom, scaleOptions) {
            var zoom = targetZoom;
            var zoomNow = this.getCurrentZoomLevel();
            if (scaleOptions && scaleOptions.minScale) {
                var maxZoom = this.pickZoomForScale(scaleOptions.minScale, true);
                if (zoom !== null) {
                    zoom = Math.min(zoom, maxZoom);
                } else {
                    zoom = Math.min(zoomNow, maxZoom);
                }
            }
            if (scaleOptions && scaleOptions.maxScale) {
                var minZoom = this.pickZoomForScale(scaleOptions.maxScale, false);
                if (zoom !== null) {
                    zoom = Math.max(zoom, minZoom);
                } else {
                    zoom = Math.max(zoomNow, minZoom);
                }
            }
            return Math.min(this._getMaxZoomLevel(), zoom);
        },
        /**
         * @return {mmDimension}
         */
        getCurrentViewportSize: function() {
            var node = this.mbMap.element.get(0);
            return {
                width: node.clientWidth || node.offsetWidth || parseInt(node.style.width),
                height: node.clientHeight || node.offsetHeight || parseInt(node.style.height)
            };
        },
        _parsePoiParameter: function(url) {
            var params = Mapbender.Util.unpackObjectParam(Mapbender.Util.getUrlQueryParams(url), 'poi');
            if (params && params.point) {
                if (params.srs && !Mapbender.Projection.isDefined(params.srs)) {
                    return [];
                }
                // Produce Same format as previously generated by server-side Map Element
                var coords = params.point.split(',').map(parseFloat);
                params.x = coords[0];
                params.y = coords[1];
                delete(params.point);
                if (params.scale) {
                    params.scale = parseInt(params.scale);
                }
                return [params];
            } else {
                return [];
            }
        },
        displayPois: function (poiOptions) {
            if (!poiOptions.length) {
                return;
            }
            var layer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            layer.setBuiltinMarkerStyle('poiIcon');
            for (var i = 0; i < poiOptions.length; ++i) {
                this.displayPoi(layer, poiOptions[i]);
            }

            layer.olMap.on('singleclick', (e) => {
                layer.olMap.forEachFeatureAtPixel(e.pixel, (f) => {
                    if (f.get('mbPoiLabel')) {
                        const coords = f.getGeometry().getCoordinates();
                        this.openPopup(coords[0], coords[1], f.get("mbPoiLabel")).then(() => layer.removeNativeFeatures([f]))
                    }
                }, {hitTolerance: this.hitTolerance});
            });
        },
        displayPoi: function(layer, poi) {
            const targetSrs = this.getCurrentProjectionCode();
            const coords = Mapbender.mapEngine.transformCoordinate({x: poi.x, y: poi.y}, poi.srs || targetSrs, targetSrs);

            const marker = layer.addMarker(coords.x, coords.y);
            marker.set('mbPoiLabel', poi.label);
            if (poi.label && typeof(poi.label) === 'string') {
                this.openPopup(coords.x, coords.y, poi.label).then(() => layer.removeNativeFeatures([marker]))
            }
        },
        /**
         * @param {Number} x projected
         * @param {Number} y projected
         * @param {String|Element|jQuery} [content]
         * @return {Promise}
         */
        openPopup: function(x, y, content) {
            var contentNode = document.createElement('div');
            if (content) {
                if (typeof content === 'string') {
                    contentNode.innerText = content;
                } else if (content instanceof jQuery) {
                    $(content).appendTo(contentNode);
                }
            }
            return this.openPopupInternal_(x, y, contentNode);
        },
        /**
         * @return {mmViewParams}
         */
        getCurrentViewParams: function() {
            return {
                scale: this.getCurrentScale(false),
                center: this.getCurrentMapCenter(),
                srsName: this.getCurrentProjectionCode(),
                rotation: this.getViewRotation()
            };
        },
        /**
         * @param {mmViewParams} options
         */
        applyViewParams: function(options) {
            if (options.srsName) {
                this.changeProjection(options.srsName);
            }
            // Apply rotation before center + scale
            // Rotation interacts with the resolution constraint, and setting it later would
            // break view parameter history.
            // @see https://github.com/openlayers/openlayers/blob/v6.3.1/src/ol/View.js#L1562
            if (typeof (options.rotation) !== 'undefined' && Mapbender.mapEngine.supportsRotation()) {
                this.setViewRotation(options.rotation);
            }
            var centerOptions = {
                ignorePadding: true
            };
            var center = options.center;
            if (options.scale) {
                centerOptions.minScale = options.scale;
                centerOptions.maxScale = options.scale;
                center = options.center || this.getCurrentMapCenter();
            }
            if (center) {
                // @todo: fix restore of fractional scale (currently snaps to a configured zoom level)
                this.centerXy(center[0], center[1], centerOptions);
            }
        },
        /**
         * @param {mmViewParams} params
         * @return {String}
         */
        encodeViewParams: function(params) {
            // @todo: resolve inconsistent data format getCurrentMapCenter (Array<number>) vs transformCoordinate ({x: number, y: number})
            var center0 = {
                x: params.center[0],
                y: params.center[1]
            };
            var center84 = Mapbender.mapEngine.transformCoordinate(center0, params.srsName, 'WGS84');
            var center = [center84.x, center84.y];

            // normalize center: keep positive, round to five digits (~meter resolution)
            for (var ci = 0; ci < 2; ++ci) {
                while (center[ci] < 0) center[ci] += 360;
                center[ci] = center[ci].toFixed(5);
            }
            // round scale to integer and stringify
            var parts = [
                params.scale.toFixed(0),
                '@',
                center[0],
                '/',
                center[1],
                'r',
                params.rotation.toFixed(0),
                '@',
                params.srsName
            ];
            return parts.join('');
        },
        /**
         * @param {String} value
         * @return {mmViewParams}
         */
        decodeViewParams: function(value) {
            var matches = /^(\d+)@([\d.]+)\/([\d.]+)r(-?\d+)@(\w+:\d+)$/.exec(value);
            if (!matches) {
                throw new Error("Unsupported view parameter encoding " + value);
            }
            var parts = /^(\d+)@([\d.]+)\/([\d.]+)r(-?\d+)@(\w+:\d+)$/.exec(value).slice(1);
            // @todo: resolve inconsistent data format getCurrentMapCenter (Array<number>) vs transformCoordinate ({x: number, y: number})
            var center84 = {
                x: parseFloat(parts[1]),
                y: parseFloat(parts[2])
            };
            var targetSrsName = parts[4];
            var centerTargetSrs = Mapbender.mapEngine.transformCoordinate(center84, 'WGS84', targetSrsName);
            var params = {
                scale: parseInt(parts[0]),
                center: [centerTargetSrs.x, centerTargetSrs.y],
                rotation: parseInt(parts[3]) || 0,
                srsName: targetSrsName
            };
            return params;
        },
        /**
         * @param feature
         * @param {Model~BufferOptions} [options]
         * @return {*}
         * @private
         */
        _getBufferedFeatureBounds: function(feature, options) {
            var engine = Mapbender.mapEngine;
            var bounds = engine.getFeatureBounds(feature);
            var bufferAbs = (options || {}).buffer;
            var bufferFactor = (options || {}).ratio;
            if (typeof bufferAbs === 'undefined'){
                bufferAbs = 120;
            }
            if (typeof bufferFactor === 'undefined') {
                if (bufferAbs !== 0) {
                    bufferFactor = 1.25;
                } else {
                    bufferFactor = 1.0;
                }
            }

            if (bufferAbs) {
                var unitsPerMeter = engine.getProjectionUnitsPerMeter(this.getCurrentProjectionCode());
                var bufferNative = bufferAbs * unitsPerMeter;
                bounds.left -= bufferNative;
                bounds.right += bufferNative;
                bounds.top += bufferNative;
                bounds.bottom -= bufferNative;
            }
            if (bufferFactor) {
                var centerX = 0.5 * (bounds.left + bounds.right);
                var centerY = 0.5 * (bounds.top + bounds.bottom);
                bounds.left = centerX + bufferFactor * (bounds.left - centerX);
                bounds.right = centerX + bufferFactor * (bounds.right - centerX);
                bounds.top = centerY + bufferFactor * (bounds.top - centerY);
                bounds.bottom = centerY + bufferFactor * (bounds.bottom - centerY);
            }
            return bounds;
        },
        /**
         * @param {Object} mapOptions
         * @return {mmViewParams}
         * @private
         */
        _getConfiguredViewParams: function(mapOptions) {
            var startExtent = Mapbender.mapEngine.boundsFromArray(mapOptions.extent_start);
            startExtent = Mapbender.mapEngine.transformBounds(startExtent, mapOptions.srs, mapOptions.srs);
            var viewportSize = this.getCurrentViewportSize();
            var resolution = this._getExtentResolution(startExtent, viewportSize.width, viewportSize.height);
            return {
                rotation: 0,
                srsName: mapOptions.srs,
                scale: this.resolutionToScale(resolution, mapOptions.dpi, mapOptions.srs),
                center: [
                    0.5 * (startExtent.left + startExtent.right),
                    0.5 * (startExtent.bottom + startExtent.top)
                ]
            };
        },
        /**
         * @param {Object} mapOptions
         * @return {mmViewParams}
         * @private
         */
        _getInitialViewParams: function(mapOptions) {
            try {
                return this._decodeViewparamFragment();
            } catch (e) {
                // fall through
            }

            var params;
            var lsPersisted = Mapbender.configuration.application.persistentView && this.getLocalStorageSettings();
            if (lsPersisted && lsPersisted.viewParams) {
                params = lsPersisted.viewParams;
            } else {
                params = this._getConfiguredViewParams(mapOptions);
            }
            var urlParams = (new Mapbender.Util.Url(window.location.href)).parameters || {};
            var srsOverride = this._filterSrsOverride(mapOptions, urlParams.srs);
            if (srsOverride) {
                var centerXyOriginal = {x: params.center[0], y: params.center[1]};
                var transformedCenterXy = Mapbender.mapEngine.transformCoordinate(centerXyOriginal, params.srsName, srsOverride);
                params.center = [transformedCenterXy.x, transformedCenterXy.y];
                params.srsName = srsOverride;
            }

            var bboxOverride = this._getStartingBboxFromUrl();
            bboxOverride = bboxOverride && Mapbender.mapEngine.boundsFromArray(bboxOverride);
            bboxOverride = bboxOverride && Mapbender.mapEngine.transformBounds(bboxOverride, urlParams.srs || mapOptions.srs, params.srsName);

            var centerOverride = (urlParams.center || '').split(',').map(parseFloat).filter(function(x) {
                return !isNaN(x);
            });
            var scaleOverride = parseInt(urlParams.scale);
            if (!scaleOverride && centerOverride.length !== 2 && this._poiOptions.length) {
                scaleOverride = parseInt(this._poiOptions[0].scale || '2500');
            }

            if (centerOverride.length === 2) {
                params.center = centerOverride;
            } else if (this._poiOptions && this._poiOptions.length === 1) {
                var singlePoi = this._poiOptions[0];
                var transformedPoi = Mapbender.mapEngine.transformCoordinate(singlePoi, singlePoi.srs || params.srsName, params.srsName);
                params.center = [transformedPoi.x, transformedPoi.y];
            } else if (bboxOverride) {
                params.center = [
                    0.5 * (bboxOverride.left + bboxOverride.right),
                    0.5 * (bboxOverride.bottom + bboxOverride.top)
                ];
            }
            if (scaleOverride) {
                params.scale = scaleOverride;
            } else if (bboxOverride) {
                var viewportSize = this.getCurrentViewportSize();
                var resolution = this._getExtentResolution(bboxOverride, viewportSize.width, viewportSize.height);
                params.scale = this.resolutionToScale(resolution, mapOptions.dpi, params.srsName);
            }

            return params;
        },
        /**
         * @return {mmMapSourceSettings}
         */
        getCurrentSourceSettings: function() {
            return {
                sources: this.sourceTree.map(function(source) {
                    return Object.assign({}, source.getSettings(), {
                        id: source.id
                    });
                }),
                layersets: Mapbender.layersets.map(function(layerset) {
                    return Object.assign({}, layerset.getSettings(), {
                        id: layerset.getId()
                    });
                })
            };
        },
        /**
         * @return {mmMapSettings}
         */
        getCurrentSettings: function() {
            return Object.assign(this.getCurrentSourceSettings(), {
                viewParams: this.getCurrentViewParams()
            });
        },
        /**
         * @return {mmMapSettings}
         */
        getConfiguredSettings: function() {
            return Object.assign({}, this.configuredSettings_);
        },
        /**
         * @param {mmMapSettings} from
         * @param {mmMapSettings} to
         * @return {mmMapSettingsDiff}
         */
        diffSettings: function(from, to) {
            // Always include viewParams fully (not worth the effort to diff them)
            var diff = {
                viewParams: to.viewParams,
                layersets: {
                    activate: [],
                    deactivate: []
                },
                sources: []
            };
            var i, toMatches;
            for (i = 0; i < from.layersets.length; ++i) {
                var fromLsSettings = from.layersets[i];
                toMatches = to.layersets.filter(function(toLayerset) {
                    return ('' + toLayerset.id) === ('' + fromLsSettings.id);
                });
                if (toMatches.length && fromLsSettings.selected !== toMatches[0].selected) {
                    var lsId = '' + toMatches[0].id;
                    if (toMatches[0].selected) {
                        diff.layersets.activate.push(lsId);
                    } else {
                        diff.layersets.deactivate.push(lsId);
                    }
                }
            }
            for (i = 0; i < from.sources.length; ++i) {
                var fromSourceSettings = from.sources[i];
                toMatches = to.sources.filter(function(toSource) {
                    return ('' + toSource.id) === ('' + fromSourceSettings.id);
                });
                if (toMatches.length) {
                    var baseDiff = Mapbender.Source.prototype.diffSettings.call(null, fromSourceSettings, toMatches[0]);
                    if (baseDiff) {
                        diff.sources.push(Object.assign({id: fromSourceSettings.id}, baseDiff));
                    }
                } else {
                    // Source not present in target settings => deactivate all layers
                    diff.sources.push({
                        id: fromSourceSettings.id,
                        deactivate: fromSourceSettings.selectedLayers.slice(),
                        activate: []
                    });
                }
            }
            return diff;
        },
        /**
         * Transforms a settings diff into a compact and url-transportable shallow object form.
         * NOTE: view param entry from diff is ignored (already transportable via fragment encoding)
         *
         * @param {mmMapSettingsDiff} diff
         * @return {Object.<String, String>}
         */
        encodeSettingsDiff: function(diff) {
            var paramParts = {
                lson: ((diff && diff.layersets || {}).activate || []).slice(),      // =Layersets on
                lsoff: ((diff && diff.layersets || {}).deactivate || []).slice(),   // =Layersets off
                slon: [],
                sloff: [],
                sop: [],
                slstyle: [],
            };
            var i, j;
            for (i = 0; i < (diff && diff.sources || []).length; ++i) {
                var sourceDiffEntry = diff.sources[i];
                for (j = 0; j < (sourceDiffEntry.activate || []).length; ++j) {
                    paramParts.slon.push([sourceDiffEntry.id, sourceDiffEntry.activate[j].id].join(':'));
                }
                for (j = 0; j < (sourceDiffEntry.deactivate || []).length; ++j) {
                    paramParts.sloff.push([sourceDiffEntry.id, sourceDiffEntry.deactivate[j].id].join(':'));
                }
                for (j = 0; j < (sourceDiffEntry.changeStyle || []).length; ++j) {
                    paramParts.slstyle.push([sourceDiffEntry.id, sourceDiffEntry.changeStyle[j].id, sourceDiffEntry.changeStyle[j].style].join(':'));
                }
                if (typeof (sourceDiffEntry.opacity) !== 'undefined') {
                    paramParts.sop.push([sourceDiffEntry.id, parseFloat(sourceDiffEntry.opacity).toFixed(2)].join(':'));
                }
            }
            // Collapse lists to comma-separated
            var params = {};
            var paramNames = Object.keys(paramParts);
            for (i = 0; i < paramNames.length; ++i) {
                var paramName = paramNames[i];
                if (paramParts[paramName].length) {
                    params[paramName] = paramParts[paramName].join(',');
                }
            }
            return params;
        },
        /**
         * Reverse of encodeSettingsDiff
         * @param {Object.<String, String>} params
         * @param {mmViewParams} [viewParams]
         * @return {mmMapSettingsDiff}
         */
        decodeSettingsDiff: function(params, viewParams) {
            var diff = {
                layersets: {
                    activate: params.lson && params.lson.split(',') || [],
                    deactivate: params.lsoff && params.lsoff.split(',') || [],
                },
                sources: []
            };
            var sourceDiffs = {};
            if (typeof viewParams !== 'undefined') {
                diff.viewParams = viewParams;
            }
            var i, parts;
            var sourceParamParts = {
                slon: params.slon && params.slon.split(',') || [],
                sloff: params.sloff && params.sloff.split(',') || [],
                sop: params.sop && params.sop.split(',') || [],
                slstyle: params.slstyle && params.slstyle.split(',') || [],
            };

            var _getSourceDiffRef = function(id) {
                if (!sourceDiffs[id]) {
                    sourceDiffs[id] = {id: id, activate: [], deactivate: [], changeStyle: []};
                    diff.sources.push(sourceDiffs[parts[0]]);
                }
                return sourceDiffs[id];
            };

            for (i = 0; i < sourceParamParts.slon.length; ++i) {
                parts = sourceParamParts.slon[i].split(':', 2);
                _getSourceDiffRef(parts[0]).activate.push(parts[1]);
            }
            for (i = 0; i < sourceParamParts.sloff.length; ++i) {
                parts = sourceParamParts.sloff[i].split(':', 2);
                _getSourceDiffRef(parts[0]).deactivate.push(parts[1]);
            }
            for (i = 0; i < sourceParamParts.slstyle.length; ++i) {
                parts = sourceParamParts.slstyle[i].split(':', 3);
                _getSourceDiffRef(parts[0]).changeStyle.push({id: parts[1], style: parts[2]});
            }
            for (i = 0; i < sourceParamParts.sop.length; ++i) {
                parts = sourceParamParts.sop[i].split(':', 2);
                _getSourceDiffRef(parts[0]).opacity = parseFloat(parts[1]);
            }
            return diff;
        },
        /**
         * @param {mmMapSettings} base
         * @param {mmMapSettingsDiff} diff
         * @return {mmMapSettings}
         */
        mergeSettings: function(base, diff) {
            const sources = base.sources.map(/** @param {SourceSettings} baseSettings */ function(baseSettings) {
                var diffMatch = diff.sources.find(function(diffEntry) {
                    return ('' + diffEntry.id) === ('' + baseSettings.id);
                });
                if (diffMatch) {
                    return Mapbender.Source.prototype.mergeSettings.call(null, baseSettings, diffMatch);
                }
                return baseSettings;
            });

            return Object.assign({}, base, {
                viewParams: diff.viewParams,
                sources: sources,
                layersets: base.layersets.map((layersetConfig) => {
                    if (layersetConfig.selected && diff.layersets.deactivate?.includes(layersetConfig.id) === true) {
                        layersetConfig.selected = false;
                    }
                    if (!layersetConfig.selected && diff.layersets.activate?.includes(layersetConfig.id) === true) {
                        layersetConfig.selected = true;
                    }
                    return layersetConfig;
                }),
            });
        },
        /**
         * @param {mmMapSettings} settings
         * @return {Array<Mapbender.Source>}
         * @todo: fold with applySourceSettingsDiff
         */
        applySourceSettings: function(settings) {
            // @todo: defensive checks if source was actually changed to reduce reloads...?
            var sources = [], i;
            for (i = 0; i < settings.layersets.length; ++i) {
                var ls = this.getLayersetById(settings.layersets[i].id);
                if (ls && ls.applySettings(settings.layersets[i])) {
                    this.mbMap.element.trigger('mb.sourcenodeselectionchanged', {
                        node: ls,
                        selected: ls.getSelected()
                    });
                }
            }
            for (i = 0; i < settings.sources.length; ++i) {
                var sourceEntry = settings.sources[i];
                var source = this.getSourceById(sourceEntry.id);
                if (source) {
                    sources.push(source);
                    // NOTE: this only restores settings properties. It does NOT yet apply them on the map view.
                    if (source.applySettings(sourceEntry)) {
                        this.triggerSourceChanged_(source);
                    }
                }
            }
            return sources;
        },
        /**
         * @param {mmMapSettings} settings
         */
        applySettings: function(settings) {
            var sources = this.applySourceSettings(settings);
            this.applyViewParams(settings.viewParams);
            // Perform map view updates for (updated) sources; we deliberately defer this until after the view param
            // update because out of bounds / scale / CRS applicability checks heavily depend on view params.
            for (var i = 0; i < sources.length; ++i) {
                this._checkSource(sources[i], true);
            }
        },
        /**
         * @return {Array<Number>|null}
         * @private
         */
        _getStartingBboxFromUrl: function() {
            var urlParams = (new Mapbender.Util.Url(window.location.href)).parameters;
            var parts = (urlParams.bbox || '').split(',').map(parseFloat);
            return parts.length === 4 && parts || null;
        },
        /**
         * @param {Object} mapOptions
         * @param {String} mapOptions.srs
         * @param {String} value
         * @private
         */
        _filterSrsOverride: function(mapOptions, value) {
            var srsOverride = (value || '').toUpperCase();
            var pattern = /^EPSG:\d+$/;
            if (srsOverride) {
                if (!pattern.test(srsOverride)) {
                    console.warn("Ingoring invalid srs code override; must use EPSG:<digits> form", srsOverride);
                    srsOverride = undefined;
                } else {
                    var matches = mapOptions.srsDefs.filter(function(srsDef) {
                        return srsDef.name === srsOverride;
                    });
                    if (!matches.length) {
                        console.warn("Ingoring srs code override not supported by map element configuration", srsOverride);
                        srsOverride = undefined;
                    }
                }
            }
            return srsOverride || null;
        },
        _canonicalizeUrl: function(url, viewParamHash) {
            var removeParams = this.getViewRelatedUrlParamNames();
            if (viewParamHash) {
                var newUrl = Mapbender.Util.removeUrlParams((url || '').replace(/#.*$/, ''), removeParams, true) || '?';
                return [newUrl, viewParamHash].join('#');
            } else {
                return url;
            }
        },
        /**
         * @param {mmViewParams} params
         * @private
         */
        _updateViewParamFragment: function(params) {
            var newHash = this.encodeViewParams(params);
            // NOTE: hash property getter will return a leading '#'. It doesn't matter if
            //       we include the '#' when setting a hash via location.hash or pushState / replaceState
            var currentHash = (window.location.hash || '').replace(/^#/, '');
            // Defer first hash amendment to the first significant map movement (NOT immediately on startup)
            // Afterwards, avoid creating a browser history entry if params are equal
            if (!currentHash) {
                if (this.encodeViewParams(this.initialViewParams) !== newHash) {
                    // Canonicalize url on first update, by stripping all view-related query params
                    var canonical = this._canonicalizeUrl(window.location.search, newHash);
                    window.history.pushState({}, '', canonical);
                }
            } else if (currentHash !== newHash) {
                window.history.pushState({}, '', '#' + newHash);
            }
        },
        _decodeViewparamFragment: function() {
            return this.decodeViewParams((window.location.hash || '').replace(/^#/, ''));
        },
        _applyViewParamFragment: function() {
            try {
                var params = this._decodeViewparamFragment();
                this.applyViewParams(params);
            } catch (e) {
                var hash = (window.location.hash || '').replace(/^#/, '');
                // Go back to initial view ONLY IF no hash detected
                if (!hash) {
                    this.applyViewParams(this.initialViewParams);
                }
            }
        },
        _startShare: function() {
            if (Mapbender.configuration.application.persistentView) {
                this.startLocalStorageSettingsPersistence();
            }
            var self = this;
            var currentHash = (window.location.hash || '').replace(/^#/, '');
            if (currentHash) {
                try {
                    this.decodeViewParams(currentHash);
                    // valid view param hash, canonicalize
                    var canonicalSearch = this._canonicalizeUrl(window.location.search, currentHash);
                    window.history.replaceState({}, '', canonicalSearch);
                } catch (e) {
                    // invalid view param hash, remove
                    window.history.replaceState({}, '', '#');
                }
            }
            var updateHandler = function(evt, data) {
                self._updateViewParamFragment(data.params);
            };
            this.mbMap.element.on('mbmapviewchanged', Mapbender.Util.debounce(updateHandler, 400));
            window.addEventListener('popstate', function() {
                self._applyViewParamFragment();
            });
        },
        getLocalStoragePersistenceKey_: function(entryName) {
            var parts = [
                Mapbender.configuration.application.slug,
                entryName
            ];
            return parts.join(':');
        },
        getLocalStorageSettings: function() {
            var key = this.getLocalStoragePersistenceKey_('settings');
            var settings = window.localStorage.getItem(key);
            return settings && JSON.parse(settings);
        },
        startLocalStorageSettingsPersistence: function() {
            var key = this.getLocalStoragePersistenceKey_('settings');
            var self = this;
            var updateHandler = function() {
                var settings = self.getCurrentSettings();
                var serialized = JSON.stringify(settings);
                window.localStorage.setItem(key, serialized);
            };
            var listened = [
                'mbmapviewchanged',
                'mb.sourcenodeselectionchanged',
                'mbmapsourcechanged'
            ];
            this.mbMap.element.on(listened.join(' '), Mapbender.Util.debounce(updateHandler, 1000));
        },
        /**
         * @param {{left: Number, right: Number, top: Number, bottom: Number}} extent
         * @param {Number} viewportWidth
         * @param {Number} viewportHeight
         * @return {Number}
         * @private
         */
        _getExtentResolution: function(extent, viewportWidth, viewportHeight) {
            return Math.max(
                Math.abs(extent.right - extent.left) / viewportWidth,
                Math.abs(extent.top - extent.bottom) / viewportHeight
            );
        },
        _comma_dangle_dummy: null
    });

    return MapModelBase;
}());
