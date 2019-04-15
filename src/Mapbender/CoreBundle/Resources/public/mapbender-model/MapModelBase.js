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
     * @param {Object} mbMap
     * @constructor
     */
    function MapModelBase(mbMap) {
        Mapbender.mapEngine.patchGlobals(mbMap.options);
        Mapbender.Projection.extendSrsDefintions(mbMap.options.srsDefs || []);
        this.mbMap = mbMap;
        var mapOptions = mbMap.options;
        this.sourceBaseId_ = 0;
        this.sourceTree = [];
        this._configProj = mapOptions.srs;
        this._startProj = mapOptions.targetsrs || mapOptions.srs;
        this.mapMaxExtent = Mapbender.mapEngine.boundsFromArray(mapOptions.extents.max);
        var startExtentArray;
        if (mapOptions.extra && mapOptions.extra.bbox) {
            startExtentArray = mapOptions.extra.bbox;
        } else {
            startExtentArray = mapOptions.extents.start || mapOptions.extents.max;
        }
        this.mapStartExtent = Mapbender.mapEngine.boundsFromArray(startExtentArray);
    }

    MapModelBase.prototype = {
        constructor: MapModelBase,
        mbMap: null,
        sourceBaseId_: null,
        sourceTree: [],
        mapStartExtent: null,
        mapMaxExtent: null,
        /** Backend-configured initial projection, used for start / max extents */
        _configProj: null,
        /** Actual initial projection, determined by a combination of several URL parameters */
        _startProj: null,
        /**
         * @return {number}
         * engine-agnostic
         */
        getCurrentScale: function() {
            return (this._getScales())[this.getCurrentZoomLevel()];
        },
        /**
         * engine-agnostic
         */
        pickZoomForScale: function(targetScale, pickHigh) {
            // @todo: fractional zoom: use exact targetScale (TBD: method should not be called?)
            var scales = this._getScales();
            var scale = this._pickScale(scales, targetScale, pickHigh);
            return scales.indexOf(scale);
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
         * @param {Source} source
         * @param {number} opacity float in [0;1]
         * engine-agnostic
         */
        setOpacity: function(source, opacity) {
            // unchecked findSource in layertree may pass undefined for source
            if (source) {
                var opacity_ = parseFloat(opacity);
                if (isNaN(opacity_)) {
                    opacity_ = 1.0;
                }
                opacity_ = Math.max(0.0, Math.min(1.0, opacity_));
                if (opacity_ !== opacity) {
                    console.warn("Invalid-ish opacity, clipped to " + opacity_.toString(), opacity);
                }
                source.setOpacity(opacity_);
            }
        },
        /**
         * Activate / deactivate a single layer's selection and / or FeatureInfo state states.
         *
         * @param {string|number} sourceId
         * @param {string|number} layerId
         * @param {boolean|null} [selected]
         * @param {boolean|null} [info]
         * engine-agnostic
         */
        controlLayer: function controlLayer(sourceId, layerId, selected, info) {
            var layerMap = {};
            var treeOptions = {};
            if (selected !== null && typeof selected !== 'undefined') {
                treeOptions.selected = !!selected;
            }
            if (info !== null && typeof info !== 'undefined') {
                treeOptions.info = !!info;
            }
            if (Object.keys(treeOptions).length) {
                layerMap['' + layerId] = {
                    options: {
                        treeOptions: treeOptions
                    }
                };
            }
            if (Object.keys(layerMap).length) {
                var source = this.getSourceById(sourceId);
                this._updateSourceLayerTreeOptions(source, layerMap);
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
            var sourceObj = this.getSourceById(sourceId);
            var geoSource = Mapbender.source[sourceObj.type];

            geoSource.setLayerOrder(sourceObj, newLayerIdOrder);

            this.mbMap.fireModelEvent({
                name: 'sourceMoved',
                // no receiver uses the bizarre "changeOptions" return value
                // on this event
                value: null
            });
            this._checkSource(sourceObj, true, false);
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
         * Gets a mapping of all defined extents for a layer, keyed on SRS
         * @param {Object} options
         * @property {String} options.sourceId
         * @property {String} options.layerId
         * @return {Object<String, Array<Number>>}
         * engine-agnostic
         */
        getLayerExtents: function(options) {
            var source = this.getSourceById(options.sourceId);
            if (source) {
                return source.getLayerExtentConfigMap(options.layerId, true, true);
            } else {
                console.warn("Source not found", options);
                return null;
            }
        },
        /**
         * @param {Number|String} id
         * @return {Mapbender.Source|null}
         * engine-agnostic
         */
        getSourceById: function(id) {
            return _.findWhere(this.sourceTree, {id: '' + id}) || null;
        },
        generateSourceId: function() {
            var id = 'auto-src-' + (this.sourceBaseId_ + 1);
            ++this.sourceBaseId_;
            return id;
        },
        /**
         * @param {string|Object} sourceOrId
         * @property {string} sourceOrId.id
         * @param state
         * engine-agnostic
         */
        setSourceVisibility: function(sourceOrId, state) {
            var source;
            if (typeof sourceOrId === 'object') {
                if (sourceOrId instanceof Mapbender.Source) {
                    source = sourceOrId;
                } else {
                    source = this.getSourceById(sourceOrId.id);
                }
            } else {
                source = this.getSourceById(sourceOrId);
            }
            var newProps = {};
            var rootLayer = source.configuration.children[0];
            var state_ = state;
            if (state && !rootLayer.options.treeOptions.allow.selected) {
                state_ = false;
            }
            var rootLayerId = rootLayer.options.id;
            newProps[rootLayerId] = {
                options: {
                    treeOptions: {
                        selected: state_
                    }
                }
            };
            this._updateSourceLayerTreeOptions(source, newProps);
        },
        /**
         * @return {Array<Source>}
         * engine-agnostic
         */
        getSources: function() {
            return this.sourceTree;
        },
        /**
         * Returns the source's position
         * engine-agnostic
         */
        getSourcePos: function(source) {
            if (source) {
                for (var i = 0; i < this.sourceTree.length; i++) {
                    if (this.sourceTree[i].id.toString() === source.id.toString()) {
                        return i;
                    }
                }
            } else
                return null;
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
         *
         * @param scales
         * @param targetScale
         * @param pickHigh
         * @return {*}
         * @private
         * engine-agnostic
         */
        _pickScale: function(scales, targetScale, pickHigh) {
            if (targetScale >= scales[0]) {
                return scales[0];
            }
            for (var i = 0, nScales = scales.length; i < nScales - 1; ++i) {
                var scaleHigh = scales[i];
                var scaleLow = scales[i + 1];
                if (targetScale <= scaleHigh && targetScale >= scaleLow) {
                    if (targetScale > scaleLow && pickHigh) {
                        return scaleHigh;
                    } else {
                        return scaleLow;
                    }
                }
            }
            return scales[nScales - 1];
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
         * Updates the options.treeOptions within the source with new values from layerOptionsMap.
         * Always reapplies states to engine (i.e. affected layers are re-rendered).
         * Alawys fires an 'mbmapsourcechanged' event.
         *
         * @param {Object} source
         * @param {Object<string, Model~LayerTreeOptionWrapper>} layerOptionsMap
         * @private
         * engine-agnostic
         */
        _updateSourceLayerTreeOptions: function(source, layerOptionsMap) {
            var gsHandler = this.getGeoSourceHandler(source);
            gsHandler.applyTreeOptions(source, layerOptionsMap);
            var newStates = gsHandler.calculateLeafLayerStates(source, this.getScale());
            var changedStates = gsHandler.applyLayerStates(source, newStates);
            var layerParams = source.getLayerParameters(newStates);
            this._resetSourceVisibility(source, layerParams);

            this.mbMap.fireModelEvent({
                name: 'sourceChanged',
                value: {
                    changed: {
                        children: $.extend(true, {}, layerOptionsMap, changedStates)
                    },
                    sourceIdx: {id: source.id}
                }
            });
        }
    };

    // Deprecated old-style APIs
    Object.assign(MapModelBase.prototype, {
        /**
         * Old-style API to add a source. Source is a POD object that needs to be nested into an outer structure like:
         *  {add: {sourceDef: <x>}}
         *
         * @param {object} addOptions
         * @returns {object} source defnition (unraveled but same ref)
         * @deprecated, call addSourceFromConfig directly
         * engine-agnostic
         */
        addSource: function(addOptions) {
            if (addOptions.add && addOptions.add.sourceDef) {
                // because legacy behavior was to always mangle / destroy / rewrite all ids, we do the same here
                return this.addSourceFromConfig(addOptions.add.sourceDef, true);
            } else {
                console.error("Unuspported options, ignoring", addOptions);
            }
        },
        removeLayer: function(sourceId, layerId) {
            var source = this.getSourceById(sourceId);
            var layer = source && source.getLayerById(layerId);
            var removedLayerId = null;
            if (layer) {
                removedLayerId = layer.remove();
            }
            if (removedLayerId) {
                this._checkSource(source, true, false);
                $(this.mbMap.element).trigger('mbmapsourcelayerremoved', {
                    layerId: removedLayerId,
                    source: source,
                    mbMap: this.mbMap
                });
            }
        },
        /**
         * Get the "geosource" object for given source from Mapbender.source
         * @param {OpenLayers.Layer|MapQuery.Layer|Object} source
         * @param {boolean} [strict] to throw on missing geosource object (default true)
         * @returns {*|null}
         * @deprecated
         * engine-agnostic
         */
        getGeoSourceHandler: function(source, strict) {
            var type = this.getMbConfig(source).type;
            var gs = Mapbender.source[type];
            if (!gs && (strict || typeof strict === 'undefined')) {
                throw new Error("No geosource for type " + type);
            }
            return gs || null;
        },
        /**
         * @param {OpenLayers.Layer.HTTPRequest|Object} source
         * @param {boolean} [initializePod] to auto-instantiate a Mapbender.Source object from plain-old-data (default true)
         * @param {boolean} [initializeLayers] to also auto-instantiate layers after instantiating Mapbender.Source (default false)
         * @return {Mapbender.Source}
         * @deprecated should work with Source class instances directly
         * engine-agnostic
         */
        getMbConfig: function(source, initializePod, initializeLayers) {
            var _s;
            var projCode;
            if (source.mbConfig) {
                // monkey-patched OpenLayers.Layer
                _s =  source.mbConfig;
            } else if (source.source) {
                // MapQuery layer
                _s = source.source;
            } else if (source.configuration && source.configuration.children) {
                _s = source;
            }
            if (_s) {
                if (initializePod || typeof initializePod === 'undefined') {
                    if (!(_s instanceof Mapbender.Source)) {
                        var sourceObj = Mapbender.Source.factory(_s);
                        if (initializeLayers) {
                            projCode = projCode || this.getCurrentProjectionCode();
                            sourceObj.initializeLayers(projCode);
                        }
                        return sourceObj;
                    }
                }
                return _s;
            }
            console.error("Cannot infer source configuration from given input", source);
            throw new Error("Cannot infer source configuration from given input");
        },
        initializeSourceLayers: function() {
            var self = this;
            // Array.protoype.reverse is in-place
            // see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Array/reverse
            // Do not use .reverse on centrally shared values without making your own copy
            $.each(this.mbMap.options.layersets.slice().reverse(), function(idx, layersetId) {
                if(!Mapbender.configuration.layersets[layersetId]) {
                    return;
                }
                $.each(Mapbender.configuration.layersets[layersetId].slice().reverse(), function(lsidx, defArr) {
                    $.each(defArr, function(idx, sourceDef) {
                        self.addSourceFromConfig(sourceDef, false);
                    });
                });
            });
        },
        _comma_dangle_dummy: null
    });

    return MapModelBase;
}());
