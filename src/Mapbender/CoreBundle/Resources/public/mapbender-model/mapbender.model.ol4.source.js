window.Mapbender = Mapbender || {};
/** @constructor */
window.Mapbender.SourceModelOl4 = (function() {
    'use strict';

    /**
     * Instantiate from a given config + id
     *
     * @param {object} config plain old data, generated server-side
     * @param {string} id
     * @constructor
     */
    function Source(config, id) {
        this.id = id;
        if (!id) {
            console.error("Instantiating source with empty id; this might cause problems later");
        }
        this.type = (config['type'] || 'wms').toLowerCase();
        // HACK: Store original config for old-style access.
        //       This attribute is not used internally in the source or in the model layer.
        this.configuration = config.configuration;
        this.baseUrl_ = config.configuration.options.url;
        var opacity;
        // handle / support literal 0 opacity (=falsy, needs extended check)
        if (typeof config.configuration.options.opacity !== undefined) {
            opacity = parseFloat(config.configuration.options.opacity);
            if (isNaN(opacity)) {
                opacity = 1.0;
            }
        } else {
            opacity = 1.0;
        }
        var rootLayerDef = (config.configuration.children || [{}])[0];
        this.options = {
            opacity: opacity,
            // determined entirely by single bool in root layer "treeOptions"
            visibility: rootLayerDef.options.treeOptions.selected,
            // determined by logical OR of queryability of all leaf layers
            // any layer queryable => source queryable
            // this property is modified by initLayers_
            // after initLayers, we logical-AND it with the root layer setting
            queryable: false,
            tiled: config.configuration.options.tiled || false,
            title: config.title || rootLayerDef.options.title || rootLayerDef.options.name
        };

        this.getMapParams = {
            // monkey-patching the projection DOES NOT apply reordered axes!
            VERSION: "1.1.1", //config.configuration.options.version || "1.1.1",
            FORMAT: config.configuration.options.format || 'image/png',
            TRANSPARENT: (config.configuration.options.transparent || true) ? "TRUE" : "FALSE",
            LAYERS: ""
        };
        if (config.configuration.options.version && config.configuration.options.version !== this.getMapParams['PARAMS']) {
            console.warn("VERSION parameter has been rewritten for compatibility", this.options.title, this.getMapParams['VERSION']);
        }
        this.featureInfoParams = {
            MAX_FEATURE_COUNT: 1000,
            INFO_FORMAT: config.configuration.options.info_format || 'text/html',
            QUERY_LAYERS: ""
        };
        this.customRequestParams = {};

        this.layerNameMap_ = {};
        this.layerOptionsMap_ = {};
        this.activeLayerMap_ = {};
        this.queryLayerMap_ = {};
        this.layerOrder_ = [];

        this.initLayers_(config);

        this.options.queryable = this.options.queryable && rootLayerDef.options.treeOptions.info;
    }

    /**
     * "Static" factory method.
     * @todo: different auto-selected classes for WMS vs WMTS?
     *
     * @param {object} config
     * @param {string} [id]
     * @returns {Source}
     */
    Source.fromConfig = function(config, id) {
        return new Source(config, id);
    };
    // convenience: make fromConfig accessible via instance as well
    Source.prototype.fromConfig = Source.fromConfig;

    /**
     * @param {(ol.layer.Tile|ol.layer.Image)} engineLayer
     */
    Source.prototype.initializeEngineLayer = function initializeEngineLayer(engineLayer) {
        if (this.engineLayer_) {
            throw new Error("Source: engine layer already assigned, runtime changes not allowed");
        }
        this.engineLayer_ = engineLayer;
    };

    Source.prototype.updateSrs = function(proj) {
        var oldProj = this.engineLayer_.getSource().projection_;
        console.warn("Replacing old proj with new proj", [oldProj, proj]);
        this.engineLayer_.getSource().projection_ = proj;
    };

    Source.prototype.getTitle = function getTitle() {
        return this.options.title;
    };

    /**
     * Add or remove param values to GetMap request URL.
     *
     * Map undefined to a key to remove that parameter.
     *
     * @param {Object} params plain old data
     * @param {boolean} caseSensitive for query param names (WMS style is CI)
     */
    Source.prototype.updateRequestParams = function updateRequestParams(params, caseSensitive) {
        var existingKeys = Object.keys(this.getMapParams);
        var passedKeys = Object.keys(params);
        var i, j;
        var keyTransform;
        if (!caseSensitive) {
            keyTransform = String.prototype.toLowerCase;
        } else {
            keyTransform = function identity() { return this; }
        }
        for (i = 0; i < passedKeys.length; ++i) {
            var passedKey = passedKeys[i];
            var passedValue = params[passedKey];
            var passedKeyTransformed = keyTransform.call(passedKey);
            // remove original value (might theoretically remove multiple values if not case sensitive!)
            for (j = 0; j < existingKeys.length; ++j) {
                var existingKey = existingKeys[j];
                var existingKeyTransformed = keyTransform.call(existingKey);
                if (existingKeyTransformed === passedKeyTransformed) {
                    delete this.getMapParams[existingKey];
                }
            }
            // warn if layers changed, any case
            if (passedKey.toLowerCase() === 'layers') {
                console.warn("Modifying layers parameter directly, you should use updateLayerState instead!", passedKey, passedValue, params);
            }
            if (typeof passedValue !== 'undefined') {
                // add value (use original, untransformed key)
                this.getMapParams[passedKey] = passedValue;
            }
            // NOTE: if passedValue === undefined, we specify that the original value is removed; we already did that
        }
        this.updateEngine();
    };

    /**
     *
     * @param {object} sourceConfig
     * @private
     */
    Source.prototype.initLayers_ = function initLayers_(sourceConfig) {
        Mapbender.Util.SourceTree.iterateSourceLeaves(sourceConfig, false, function(def, siblingIdx, parents) {
            var layerName = def.options.name;
            var queryable = !!def.options.queryable;
            var info = queryable && def.options.treeOptions.info;
            var selected = !!def.options.treeOptions.selected;
            // DO NOT evaluate root layer for visibility
            // Root layer "selected" controls the entire source and is evaluated separately
            // (see constructor, options.visibility)
            for (var i = 0; i < (parents.length - 1); ++i) {
                var parent = parents[i];
                var parentTreeOptions = parent.options.treeOptions;
                selected = selected && parentTreeOptions.selected;
                // info is disabled if any parent is not selected (=not visible in GetMap)
                // info is NOT disabled if the exact same layer is not selected
                info = info && selected;
            }
            this.options.queryable = this.options.queryable || queryable;
            this.initializeLayerState_(layerName, def, selected, info);
        }.bind(this));
    };

    /**
     *
     * @param {string} layerName
     * @param {object} layerConfig
     * @param {boolean} layerActive
     * @param {boolean} infoActive
     * @private
     */
    Source.prototype.initializeLayerState_ = function initializeLayerState_(layerName, layerConfig, layerActive, infoActive) {
        var stringId = "" + layerConfig.id;
        var stringLayerName = "" + layerName;

        this.layerOptionsMap_[stringId] = layerConfig;
        this.layerNameMap_[stringLayerName] = layerConfig;
        this.layerOrder_.push(stringLayerName);

        if (layerActive) {
            this.activeLayerMap_[stringLayerName] = true;
            if (!this.getMapParams.LAYERS) {
                this.getMapParams.LAYERS = stringLayerName;
            } else {
                this.getMapParams.LAYERS = [this.getMapParams.LAYERS, stringLayerName].join(',');
            }
        }
        if (infoActive) {
            this.queryLayerMap_[stringLayerName] = true;
            if (!this.featureInfoParams.QUERY_LAYERS) {
                this.featureInfoParams.QUERY_LAYERS = stringLayerName;
            } else {
                this.featureInfoParams.QUERY_LAYERS = [this.featureInfoParams.QUERY_LAYERS, stringLayerName].join(',');
            }
        }
    };

    Source.prototype.getOpacity = function getOpacity() {
        return this.options.opacity;
    };

    Source.prototype.setOpacity = function setOpacity(v) {
        this.options.opacity = v;
        this.updateEngine();
    };

    Source.prototype.updateEngine = function updateEngine() {
        var params = $.extend({}, this.getMapParams, this.customRequestParams);
        this.engineLayer_.setOpacity(this.options.opacity);
        this.engineLayer_.getSource().updateParams(params);
        // hide (engine-side) layer if no layers activated for display
        var visibility = this.options.visibility && !!this.getMapParams.LAYERS;
        this.engineLayer_.setVisible(visibility);
    };

    /**
     * Activate / deactivate the entire source
     *
     * @param {bool} active
     */
    Source.prototype.setState = function setState(active) {
        this.options.visibility = !!active;
        this.updateEngine();
    };

    /**
     * Activate / deactivate a single layer by name
     *
     * @param {string} layerName
     * @param {object} options should have string keys 'visible' and / or 'queryable' with booleans assigned
     * @todo: also support queryable
     */
    Source.prototype.updateLayerState = function updateLayerState(layerName, options) {
        if (!this.layerNameMap_[layerName]) {
            console.error("Unknown layer name", layerName, "known:", Object.keys(this.layerNameMap_));
            return;
        }
        this.updateStateMaps_();
        if (typeof options.visible !== 'undefined') {
            if (options.visible) {
                this.activeLayerMap_[layerName] = true;
            } else {
                delete this.activeLayerMap_[layerName];
            }
        }
        if (typeof options.queryable !== 'undefined') {
            if (options.queryable) {
                this.queryLayerMap_[layerName] = true;
            } else {
                delete this.queryLayerMap_[layerName];
            }
        }
        var visibleAfter = [];
        var queryableAfter = [];
        // loop through layerOrder_, use that to determine layer ordering and rebuild the LAYERS request parameter
        for (var i = 0; i < this.layerOrder_.length; ++i) {
            var nextLayerName = this.layerOrder_[i];
            if (!!this.activeLayerMap_[nextLayerName]) {
                visibleAfter.push(nextLayerName);
            }
            if (!!this.queryLayerMap_[nextLayerName]) {
                queryableAfter.push(nextLayerName);
            }
        }
        this.getMapParams.LAYERS = visibleAfter.join(',');
        this.featureInfoParams.QUERY_LAYERS = queryableAfter.join(',');
        this.updateEngine();
    };
    /**
     * @returns {string[]}
     */
    Source.prototype.getActiveLayerNames = function() {
        var effectiveQueryParams = this.engineLayer_.getSource().getParams();
        if (typeof effectiveQueryParams.LAYERS === 'undefined') {
            console.error("getParameters returned:", effectiveQueryParams);
            throw new Error("LAYERS parameter not populated");
        }
        return _.filter((effectiveQueryParams.LAYERS || "").split(','));
    };

    /**
     * @returns {string[]}
     */
    Source.prototype.getQueryableLayerNames = function() {
        return _.filter((this.featureInfoParams.QUERY_LAYERS || "").split(','));
    };

    /**
     * Check if source is active
     *
     * @returns {boolean}
     */
    Source.prototype.isActive = function isActive() {
        return this.options.visibility;
    };

    /**
     * Updates private internal tracking structures for active layer names (map + feature info)
     * @private
     */
    Source.prototype.updateStateMaps_ = function updateStateMaps_() {
        var visibleNames = this.getActiveLayerNames();
        var queryableNames = this.getQueryableLayerNames();
        // reset, restart from scratch
        this.activeLayerMap_ = {};
        this.queryLayerMap_ = {};
        var tasks = [
            {
                names: visibleNames,
                targetMap: this.activeLayerMap_
            },
            {
                names: queryableNames,
                targetMap: this.queryLayerMap_
            }
        ];
        for (var i = 0; i < tasks.length; ++i) {
            for (var j = 0; j < tasks[i].names.length; ++j) {
                tasks[i].targetMap[tasks[i].names[j]] = true;
            }
        }
    };


    /**
     * @returns {string|null}
     */
    Source.prototype.getType = function() {
        return this.type;
    };

    /**
     * @returns {string}
     */
    Source.prototype.getBaseUrl = function() {
        return this.baseUrl_;
    };

    /**
     * @returns {(ol.source.ImageWMS|ol.source.TileWMS)}
     */
    Source.prototype.getEngineSource = function() {
        return this.engineLayer_.getSource();
    };

    return Source;
})();
