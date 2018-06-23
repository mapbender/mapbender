window.Mapbender = Mapbender || {};
window.Mapbender.Model = Mapbender.Model || {};
window.Mapbender.Model.Source = (function() {
    'use strict';

    /**
     * Instantiate a Mapbender.Model.Source from a given config + id
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

        this.options = {
            opacity: opacity,
            visibility: this.mergeParentTreeOptions_((config.configuration.children || [{}])[0]).selected,
            tiled: config.configuration.options.tiled || false
        };
        // console.log("Initial source options", config.title, this.options);
        this.getMapParams = {
            VERSION: config.configuration.options.version || "1.1.1",
            FORMAT: config.configuration.options.format || 'image/png',
            TRANSPARENT: (config.configuration.options.transparent || true) ? "TRUE" : "FALSE",
            LAYERS: ""
        };
        this.featureInfoParams = {
            MAX_FEATURE_COUNT: 1000,
            INFO_FORMAT: config.configuration.options.info_format || 'text/html',
            QUERY_LAYERS: ""
        };
        this.customRequestParams = {};

        var layerDefs = this.extractLeafLayerConfigs_(config.configuration.children[0], null);
        this.layerNameMap_ = {};
        this.layerOptionsMap_ = {};
        this.activeLayerMap_ = {};
        this.queryLayerMap_ = {};
        this.layerOrder_ = [];

        _.forEach(layerDefs, function(layerConfig) {
            this.processLayerConfig_(layerConfig);
        }.bind(this));
        // console.log("Initial active layers for source", config.title, this.getMapParams.LAYERS, this.featureInfoParams.QUERY_LAYERS);
    }

    /**
     * "Static" factory method.
     * @todo: different auto-selected classes for WMS vs WMTS?
     *
     * @param {Mapbender.Model} model
     * @param {object} config
     * @param {string} [id]
     * @returns {Source}
     */
    Source.fromConfig = function(model, config, id) {
        return new Source(model, config, id);
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

    Source.prototype.mergeParentTreeOptions_ = function mergeParentTreeOptions_(configNode, parentState) {
        var localState = $.extend({}, parentState || {
            info: true,
            selected: true
        });
        // gate off feature info if PARENT is not "selected" (i.e. visible)
        localState.info = localState.info && localState.selected;

        // merge parent state with values from config nodes
        var treeOptions = configNode.options && configNode.options.treeOptions || {
            info: true,
            selected: true
        };
        if (typeof treeOptions.info !== 'undefined') {
            localState.info = !!(localState.info && treeOptions.info);
        }
        if (typeof treeOptions.selected !== 'undefined') {
            localState.selected = !!(localState.selected && treeOptions.selected);
        }
        return localState;
    };

    /**
     * Traverses the (historically) nested layer configuration array and returns *only*
     * the configs for the leaves as one flat list.
     *
     * @param {object} layerConfig nested configuration array
     * @param {object} parentState to collect 'info' and 'selected' booleans tree-down
     * @return {object[]}
     *
     * @todo: Nested configuration is only needed for presentation (Layertree)
     *        => separate layertree configuration from Model configuration server-side
     */
    Source.prototype.extractLeafLayerConfigs_ = function extractLeafLayerConfigs_(layerConfig, parentState) {
        var localState;
        if (!parentState) {
            // Current node is a root layer
            // Visibility of root layer is NOT considered for any child layers
            // Instead, root layer visibility is treated as a separate "entire source" toggle
            localState = {
                selected: true,
                queryable: true
            };
        } else {
            localState = this.mergeParentTreeOptions_(layerConfig, parentState);
        }

        if (layerConfig.children) {
            var childConfigs = [];
            _.forEach(layerConfig.children, function(childConfig) {
                childConfigs = childConfigs.concat(this.extractLeafLayerConfigs_(childConfig, localState));
            }.bind(this));
            return childConfigs;
        } else {
            return [{options: layerConfig.options, state: localState}];
        }
    };

    /**
     * Inspects config for a single leaf layer and adds it to internal data structures.
     *
     * @param {object} layerConfigWrapper plain old data, should have 'options' and 'state' keys
     * @private
     */
    Source.prototype.processLayerConfig_ = function processLayerConfig_(layerConfigWrapper) {
        var layerConfig = layerConfigWrapper.options;
        var initialLayerState = layerConfigWrapper.state;
        if (!layerConfig.id) {
            console.error("Missing / empty 'id' in layer configuration", layerConfig);
            throw new Error("Can't initialize layer with empty id");
        }
        if (!layerConfig.name) {
            console.error("Missing / empty 'name' in layer configuration", layerConfig);
            throw new Error("Can't initialize layer with empty name");
        }
        var stringId = "" + layerConfig.id;
        var stringLayerName = "" + layerConfig.name;
        this.layerOptionsMap_[stringId] = layerConfig;
        this.layerNameMap_[stringLayerName] = layerConfig;
        this.layerOrder_.push(stringLayerName);

        var layerActive = initialLayerState.selected;
        var layerQueryable = initialLayerState.info;

        if (layerActive) {
            this.activeLayerMap_[stringLayerName] = true;
            if (!this.getMapParams.LAYERS) {
                this.getMapParams.LAYERS = stringLayerName;
            } else {
                this.getMapParams.LAYERS = [this.getMapParams.LAYERS, stringLayerName].join(',');
            }
        }
        if (layerQueryable) {
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
     * @returns {ol.source.ImageWMS|ol.source.TileWMS}
     */
    Source.prototype.getEngineSource = function() {
        return this.engineLayer_.getSource();
    };

    return Source;
})();
