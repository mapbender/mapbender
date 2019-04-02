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
     * Check if source is active
     *
     * @returns {boolean}
     */
    Source.prototype.isActive = function isActive() {

        return this.options.visibility;
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

    /**
     * @returns {number|undefined}
     */
    Source.prototype.getZIndex = function getZIndex() {
        if (!this.engineLayer_) {
            throw new Error("Layer not initialized, z unknown");
        }
        return this.engineLayer_.getZIndex();
    };

    /**
     * @param {number|undefined} zIndex
     * @returns {void}
     */
    Source.prototype.setZIndex = function setZIndex(zIndex) {
        if (!this.engineLayer_) {
            throw new Error("Layer not initialized, z unknown");
        }
        return this.engineLayer_.setZIndex(zIndex);
    };

    return Source;
})();
