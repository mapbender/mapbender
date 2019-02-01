var Mapbender = Mapbender || {};
/**
 * Simple event dispatcher
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 11.08.2014 by WhereGroup GmbH & Co. KG
 */
Mapbender.Event = {};
Mapbender.Event.Dispatcher = Class({
}, {
    'private object listeners': {},
    on: function(name, callback) {
        if (!this.listeners[name]) {
            this.listeners[name] = [];
        }
        this.listeners[name].push(callback);
        return this;
    },
    off: function(name, callback) {
        if (!this.listeners[name]) {
            return;
        }
        if (callback) {
            var listeners = this.listeners[name];
            for (var i in listeners) {
                if (callback == listeners[i]) {
                    listeners.splice(i, 1);
                    return;
                }
            }
        } else {
            delete this.listeners[name];
        }

        return this;
    },
    dispatch: function(name, data) {
        if (!this.listeners[name]) {
            return;
        }

        var listeners = this.listeners[name];
        for (var i in listeners) {
            listeners[i](data);
        }
        return this;
    }
});
/**
 * @typedef Model~ExtendedLayerInfo
 * @property {Object} layer
 * @property {Model~LayerState} state
 * @property {Array<Object>} parents
 */
/**
 * Abstract Geo Source Handler
 * @author Paul Schmidt
 */
Mapbender.Geo = {
    'layerOrderMap': {}
};
Mapbender.Geo.SourceHandler = Class({
    'extends': Mapbender.Event.Dispatcher
}, {
    _layerOrderMap: {},
    'private string layerNameIdent': 'name',
    'private object defaultOptions': {},
    'abstract public function create': function(options) {
    },
    'abstract public function featureInfoUrl': function(layer, x, y) {
    },
    'abstract public function getPrintConfig': function(layer, bounds, isProxy) {
    },
    'public function postCreate': function(olLayer) {

    },
    'public function changeProjection': function(source, projection) {
    },
    getLayersList: function getLayersList(source, offsetLayer, includeOffset) {
        var _source = $.extend(true, {}, source);
        var rootLayer = _source.configuration.children[0];
        var layerFound = rootLayer.options.id.toString() === offsetLayer.options.id.toString();
        var layersOut = [];
        _findLayers(rootLayer);
        return {
            source: _source,
            layers: layersOut
        };

        function _findLayers(layer) {
            if (layer.children) {
                var i = 0;
                for (; i < layer.children.length; i++) {
                    if (layer.children[i].options.id.toString() === offsetLayer.options.id.toString()) {
                        layerFound = true;
                    }
                    if (layerFound) {
                        var matchOffset = i;
                        if (!includeOffset) {
                            matchOffset += 1;
                        }
                        var matchLength = layer.children.length - matchOffset;
                        // splice modifies the original Array => work with a shallow copy
                        var layersCopy = layer.children.slice();
                        var matchedLayers = layersCopy.splice(matchOffset, matchLength);
                        layersOut = layersOut.concat(matchedLayers);

                        break;
                    }
                    _findLayers(layer.children[i]);
                }
            }
        }
    },
    addLayer: function addLayer(source, layerToAdd, parentLayerToAdd, position) {
        var rootLayer = source.configuration.children[0];
        var options = {
            layer: null
        };
        _addLayer(rootLayer);
        return options.layer;

        function _addLayer(layer) {
            if (layer.options.id.toString() === parentLayerToAdd.options.id.toString()) {
                if (layer.children) {
                    layer.children.splice(position, 0, layerToAdd);
                    options.layer = layer.children[position];
                } else {
                    // ignore position
                    layer.children = [];
                    layer.children.push($.extend(true, layerToAdd));
                    options.layer = layer.children[0];
                }
            } else if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    _addLayer(layer.children[i]);
                }
            }
        }
    },
    removeLayer: function removeLayer(source, layerToRemove) {
        var rootLayer = source.configuration.children[0];
        if (layerToRemove.options.id.toString() === rootLayer.options.id.toString()) {
            source.configuration.children = [];
            return {
                layer: rootLayer
            };
        }
        var options = {
            layer: null,
            layerToRemove: null
        };
        _removeLayer(rootLayer, layerToRemove);
        return {
            layer: options.layerToRemove
        };

        function _removeLayer(layer) {
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    _removeLayer(layer.children[i]);
                    if (options.layer) {
                        if (options.layer.options.id.toString() === layerToRemove.options.id.toString()) {
                            var layerToRemArr = layer.children.splice(i, 1);
                            if (layerToRemArr[0]) {
                                options.layerToRemove = $.extend({}, layerToRemArr[0]);
                            }
                        }
                    }
                }
            }
            if (layer.options.id.toString() === layerToRemove.options.id.toString()) {
                options.layer = layer;
                options.layerToRemove = layer;
            } else {
                options.layer = null;
            }
        }
    },
    findLayer: function findLayer(source, optionToFind) {
        var options = {
            level: 0,
            idx: 0,
            layer: null,
            parent: null
        };
        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, i, parents) {
            for (var key in optionToFind) {
                if (layer.options[key].toString() === optionToFind[key].toString()) {
                    options.idx = i;
                    options.parent = parents[0] || null;
                    options.level = parents.length;
                    options.layer = layer;
                    // abort iteration
                    return false;
                }
            }
        });
        return options;
    },
    checkInfoLayers: function checkInfoLayers(source, scale, tochange) {
        console.warn("checkInfoLayers is equivalent to changeOptions");
        return this.changeOptions(source, scale, tochange);
    },
    applyTreeOptions: function applyTreeOptions(source, layerOptionsMap) {
        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer) {
            var layerId = layer.options.id;
            var newTreeOptions = ((layerOptionsMap[layerId] || {}).options || {}).treeOptions;
            if (newTreeOptions) {
                var currentTreeOptions = layer.options.treeOptions;
                var optionsTasks = [['selected', true], ['info', true], ['toggle', false]];
                for (var oti = 0; oti < optionsTasks.length; ++oti) {
                    var optionName = optionsTasks[oti][0];
                    var checkAllow = optionsTasks[oti][1];
                    if (typeof newTreeOptions[optionName] !== 'undefined' && (!checkAllow || currentTreeOptions.allow[optionName])) {
                        if (currentTreeOptions[optionName] !== newTreeOptions[optionName]) {
                            currentTreeOptions[optionName] = newTreeOptions[optionName];
                        }
                    }
                }
            }
        });
    },
    /**
     * Returns object's changes : { layers: [], infolayers: [], changed: changed };
     */
    changeOptions: function changeOptions(source, scale, toChangeOpts) {
        var newTreeOptions = ((toChangeOpts || {}).options || {}).children || {};
        // apply tree options
        this.applyTreeOptions(source, newTreeOptions);
        // recalculate state
        var newStates = this.calculateLeafLayerStates(source, scale);
        // apply states and calculate changeset
        var changedStates = this.applyLayerStates(source, newStates);
        // Copy state changeset extended with treeOptions changeset
        // (a layer's state may change without a treeOptions change and vice versa)
        var result = {
            changed: {
                sourceIdx: {
                    id: source.id
                },
                children: $.extend(true, {}, newTreeOptions, changedStates)
            }
        };
        return $.extend(result, this.getLayerParameters(source, newStates));
    },
    getLayerParameters: function getLayerParameters(source, stateMap) {
        var result = {
            layers: [],
            styles: [],
            infolayers: []
        };
        var layerParamName = this.layerNameIdent;
        var customLayerOrder = Mapbender.Geo.layerOrderMap["" + source.id];
        Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer) {
            // Layer names can be emptyish, most commonly on root layers
            // Suppress layers with empty names entirely
            if (layer.options[layerParamName]) {
                var layerState = stateMap[layer.options.id] || layer.state;
                if (layerState.visibility) {
                    result.layers.push(layer.options[layerParamName]);
                    result.styles.push(layer.options.style || '');
                }
                if (layerState.info) {
                    result.infolayers.push(layer.options[layerParamName]);
                }
            }
        });
        if (customLayerOrder) {
            result.layers = _.filter(customLayerOrder, function(layerName) {
                return result.layers.indexOf(layerName) !== -1;
            });
        }
        return result;
    },
    /**
     * @param {object} source wms source
     * @param {object} changeOptions options in form of:
     * {layers:{'LAYERNAME': {options:{treeOptions:{selected: bool,info: bool}}}}}
     * @param {boolean | null} defaultSelected 
     * @param {boolean} mergeSelected
     * @returns {object} changes
     */
    createOptionsLayerState: function createOptionsLayerState(source, changeOptions, defaultSelected, mergeSelected) {
        var layerChanges = {
        };
        function setSelected(layer) {
            var layerOpts = changeOptions.layers[layer.options.id];
            var childSelected = false;
            var newTreeOptions;
            var changedTreeOptions;
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    var child = layer.children[i];
                    setSelected(child);
                    if ((!layerChanges[child.options.id] && child.options.treeOptions.selected)
                        || (layerChanges[child.options.id] && layerChanges[child.options.id].options.treeOptions.selected)) {
                        childSelected = true;
                    }
                }
                if (layerOpts) {
                    newTreeOptions = $.extend({}, layerOpts);
                } else {
                    newTreeOptions = {
                        selected: childSelected,
                        info: childSelected
                    }
                }
            } else {
                if(!layerOpts && defaultSelected === null) {
                    return;
                }
                var sel = layerOpts ? layerOpts.options.treeOptions.selected : defaultSelected;
                if (mergeSelected) {
                    sel = sel || layer.options.treeOptions.selected;
                }
                newTreeOptions = {
                    selected: sel,
                    info: sel
                };
            }

            newTreeOptions.info = newTreeOptions.info && layer.options.treeOptions.allow.info;

            if (newTreeOptions.selected !== layer.options.treeOptions.selected) {
                changedTreeOptions = {
                    selected: newTreeOptions.selected
                };
            }
            if (newTreeOptions.info !== layer.options.treeOptions.info) {
                changedTreeOptions = $.extend(changedTreeOptions || {}, {
                    info: newTreeOptions.info
                });
            }
            if (changedTreeOptions) {
                layerChanges[layer.options.id] = {
                    options: {
                        treeOptions: changedTreeOptions
                    }
                };
            }
        }
        var changed = {
            sourceIdx: {
                id: source.id
            },
            options: {
                children: layerChanges,
                type: 'selected'
            }
        };
        setSelected(source.configuration.children[0]);
        return {
            change: changed
        };
    },
    /**
     * Gets a layer extent, or the source extent as a fallback
     *
     * @param {object} source config object
     * @param {string} layerId
     * @returns {Object.<string,Array.<float>>} mapping of EPSG code to BBOX coordinate pair
     */
    getLayerExtents: function getLayerExtents(source, layerId) {
        var extents = null;
        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layerDef) {
            if (layerDef.options.id === layerId) {
                extents = layerDef.options.bbox || null;
                // abort iteration
                return false;
            }
        });

        if (extents && Object.keys(extents).length) {
            return extents;
        }
        if (source.configuration.options.bbox && Object.keys(source.configuration.options.bbox).length) {
            return source.configuration.options.bbox;
        }
        return null;
    },
    /**
     * Returns a preview mapping of states of displayable (=leaf) layers as if the given scale + extent were applied
     * (but they are not!), together with references to the layer definition and its parents.
     *
     * @param {Object} source
     * @param {number} [scale] current value fetched from Mapbender.Model if omitted
     * @param {*} [extent]
     * @return {Object<string, Model~ExtendedLayerInfo>}
     */
    getExtendedLeafInfo: function(source, scale, extent) {
        var infoMap = {};
        var self = this;
        Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer, offset, parents) {
            var layerId = layer.options.id;
            var outOfScale = !self.isLayerInScale(layer, scale);
            var outOfBounds = !self.isLayerWithinBounds(layer, extent);
            var enabled = !!layer.options.treeOptions.selected;
            var featureInfo = !!(layer.options.treeOptions.info && layer.options.treeOptions.allow.info);
            parents.map(function(p) {
                var parentEnabled = p.options.treeOptions.selected;
                enabled = enabled && parentEnabled;
                featureInfo = featureInfo && parentEnabled;
            });
            // @todo TBD: disable featureInfo if layer visual is disabled?
            // featureInfo = featureInfo && enabled
            var visibility = enabled && !(outOfScale || outOfBounds);
            infoMap[layerId] = {
                layer: layer,
                state: {
                    outOfScale: outOfScale,
                    outOfBounds: outOfBounds,
                    visibility: visibility,
                    info: featureInfo
                },
                parents: parents
            };
        });
        return infoMap;
    },
    /**
     * Returns a preview mapping of states of displayable (=leaf) layers as if the given scale + extent were applied
     * (but they are not!).
     *
     * @param {*} source
     * @param {number} [scale] uses current map scale if not passed in
     * @param [extent] currently not used; @todo: implement outOfBounds checking
     * @returns {Object.<string, Model~LayerState>}
     */
    calculateLeafLayerStates: function calculateLeafLayerStates(source, scale, extent) {
        // delegate to getExtendedLeafInfo and reduce objects to just the 'state' entries
        return _.mapObject(this.getExtendedLeafInfo(source, scale, extent), function(item) {
            return item.state;
        });
    },
    /**
     * Punches (assumed) leaf layer states from stateMap into the source structure, and calculates
     * a (conservative, imprecise) layer changeset that can be supplied in mbmapsourcechanged event data.
     * This will also update states of parent layers appropriately, and include these in the changeset.
     *
     * @param source
     * @param {Object.<string, Model~LayerState>} stateMap
     * @return {Object.<string, Model~LayerChangeInfo>}
     */
    applyLayerStates: function applyLayerStates(source, stateMap) {
        var stateNames = ['outOfScale', 'outOfBounds', 'visibility', 'info'];
        var changeMap = {};

        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, offset, parents) {
            if (layer.children && layer.children.length) {
                // non-leaf layer visibility is a function of combined leaf layer visibility
                // start with false (recursion order is root first)
                layer.state.visibility = false;
            }
            var entry = stateMap[layer.options.id];
            var stateChanged = false;
            if (entry) {
                for (var sni = 0; sni < stateNames.length; ++ sni) {
                    var stateName = stateNames[sni];
                    if (layer.state[stateName] !== entry[stateName]) {
                        layer.state = $.extend(layer.state || {}, entry);
                        changeMap[layer.options.id] = {
                            state: layer.state,
                            options: {
                                treeOptions: layer.options.treeOptions
                            }
                        };
                        stateChanged = true;
                        break;
                    }
                }
            }
            for (var p = 0; p < parents.length; ++p) {
                var parentLayer = parents[p];
                var parentId = parentLayer.options.id;
                parentLayer.state.visibility = parentLayer.state.visibility || layer.state.visibility;
                if (stateChanged) {
                    changeMap[parentId] = $.extend(changeMap[parentId] || {}, {
                        state: parentLayer.state
                    });
                }
            }
        });
        return changeMap;
    },
    /**
     * @param {Object} layer
     * @param {number} [scale] current value fetched from Mapbender.Model if omitted
     * @return {boolean}
     */
    isLayerInScale: function(layer, scale) {
        var scale_ = scale || Mapbender.Model.getScale();
        return Mapbender.Util.isInScale(scale_, layer.options.minScale, layer.options.maxScale);
    },
    /**
     * @todo: Implement this. Requires detection of applicable SRS, lookup in bbox list by SRS and potentially
     *        retransformation.
     *
     * @param {Object} layer
     * @param {*} extent
     * @return {boolean}
     */
    isLayerWithinBounds: function(layer, extent) {
        // HACK: out of bounds calculation broken since introduction of SRS switcher
        return true;
    },
    setLayerOrder: function setLayerOrder(source, layerIdOrder) {
        var newLayerNameOrder = $.map(layerIdOrder, function(layerId) {
            var layerObj = this.findLayer(source, {id: layerId});
            return layerObj.layer.options.name;
        }.bind(this));
        Mapbender.Geo.layerOrderMap["" + source.id] = newLayerNameOrder;
    }
});

// old declaration
Mapbender['source'] = {};
