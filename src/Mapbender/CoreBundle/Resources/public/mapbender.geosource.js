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
    'public function getLayersList': function(source, offsetLayer, includeOffset) {
        var rootLayer,
            _source;
        _source = $.extend(true, {}, source);//.configuration.children[0];
        rootLayer = _source.configuration.children[0];
        var options = {
            layers: [
            ],
            found: false,
            includeOffset: includeOffset
        };
        if (rootLayer.options.id.toString() === offsetLayer.options.id.toString()) {
            options.found = true;
        }
        options = _findLayers(rootLayer, offsetLayer, options);
        return {
            source: _source,
            layers: options.layers
        };

        function _findLayers(layer, offsetLayer, options) {
            if (layer.children) {
                var i = 0;
                for (; i < layer.children.length; i++) {
                    if (layer.children[i].options.id.toString() === offsetLayer.options.id.toString()) {
                        options.found = true;
                    }
                    if (options.found) {
                        var matchOffset = i;
                        if (!options.includeOffset) {
                            matchOffset += 1;
                        }
                        var matchLength = layer.children.length - matchOffset;
                        // splice modifies the original Array => work with a shallow copy
                        var layersCopy = layer.children.slice();
                        var matchedLayers = layersCopy.splice(matchOffset, matchLength);
                        options.layers = options.layers.concat(matchedLayers);

                        break;
                    }
                    options = _findLayers(layer.children[i], offsetLayer, options);
                }
            }
            return options;
        }
    },
    'public function addLayer': function(source, layerToAdd, parentLayerToAdd, position) {
        var rootLayer = source.configuration.children[0];
        var options = {
            layer: null
        };
        options = _addLayer(rootLayer, layerToAdd, parentLayerToAdd, position, options);
        return options.layer;

        function _addLayer(layer, layerToAdd, parentLayerToAdd, position, options) {
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
                return options;
            }
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    options = _addLayer(layer.children[i], layerToAdd, parentLayerToAdd, position, options);
                }
            }
            return options;
        }
    },
    'public function removeLayer': function(source, layerToRemove) {
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
        };//, listToRemove: {}, addToList: false }
        options = _removeLayer(rootLayer, layerToRemove, options);
        return {
            layer: options.layerToRemove
        };

        function _removeLayer(layer, layerToRemove, options) {
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    options = _removeLayer(layer.children[i], layerToRemove, options);
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
                return options;
            } else {
                options.layer = null;
                return options;
            }
        }
    },
    'public function findLayer': function(source, optionToFind) {
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
    'public function checkInfoLayers': function(source, scale, tochange) {
        var result = {
            changed: {
                sourceIdx: {
                    id: source.id
                },
                children: {}
            }
        };

        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer) {
            if (typeof layer.options.treeOptions.info === 'undefined') {
                layer.options.treeOptions.info = false;
            }
            var layerChanged = tochange && tochange.options.children[layer.options.id];
            if (layerChanged && layer.options.name) {
                if (layerChanged.options.treeOptions.info !== layer.options.treeOptions.info) {
                    layer.options.treeOptions.info = layerChanged.options.treeOptions.info;
                    result.changed.children[layer.options.id] = layerChanged;
                }
                layer.state.info = layer.options.treeOptions.info && layer.options.treeOptions.allow.info && layer.state.visibility;
            }
        });
        return $.extend(result, this.getLayerParameters(source));
    },
    /**
     * Returns object's changes : { layers: [], infolayers: [], changed: changed };
     */
    'public function changeOptions': function(source, scale, toChangeOpts) {
        var self = this;
        if (toChangeOpts.options && toChangeOpts.options.configuration) {
            if (toChangeOpts.options.configuration) {
                var configuration = toChangeOpts.options.configuration;
                if (configuration.options) {
                    // translate to root layer tree options
                    var rootId = source.configuration.children[0].options.id;
                    if (!toChangeOpts.options.children)
                        toChangeOpts.options['children'] = {};
                    var rootLayerTreeOptions = $.extend({}, configuration.options);
                    if (typeof rootLayerTreeOptions.visibility !== 'undefined') {
                        // the equivalent tree option is named 'selected' instead of 'visibility'
                        rootLayerTreeOptions.selected = rootLayerTreeOptions.visibility;
                        delete rootLayerTreeOptions['visibility'];
                    }
                    toChangeOpts.options.children[rootId] = $.extend(true, toChangeOpts.options.children[rootId] || {}, {
                        options: {
                            treeOptions: rootLayerTreeOptions
                        }
                    });
                }
            }
        }

        var result = {
            changed: {
                sourceIdx: {
                    id: source.id
                },
                children: {}
            }
        };
        var rootLayer = source.configuration.children[0];
        _changeOptions(rootLayer, scale, true);

        return $.extend(result, this.getLayerParameters(source));
        function _changeOptions(layer, scale, parentVisibility) {
            var initialLayerState = $.extend({}, layer.state);
            var layerChanges,
                elchanged = false;
            layerChanges = ((toChangeOpts.options || {}).children || {})[layer.options.id];
            if (layerChanges) {
                var treeChanges = layerChanges.options.treeOptions;
                var treeOptions = layer.options.treeOptions;
                if (typeof treeChanges !== 'undefined') {
                    var optionsTasks = [['selected', true], ['info', true], ['toggle', false]];
                    for (var oti = 0; oti < optionsTasks.length; ++oti) {
                        var optionName = optionsTasks[oti][0];
                        var checkAllow = optionsTasks[oti][1];
                        if (typeof treeChanges[optionName] !== 'undefined' && (!checkAllow || treeOptions.allow[optionName])) {
                            if (treeOptions[optionName] !== treeChanges[optionName]) {
                                treeOptions[optionName] = treeChanges[optionName];
                                elchanged = true;
                            }
                        }
                    }
                }
            }
            layer.state.outOfScale = !Mapbender.Util.isInScale(scale, layer.options.minScale,
                layer.options.maxScale);
            /* @TODO outOfBounds for layers ?  */
            var newVisibilityState = parentVisibility
                    && layer.options.treeOptions.selected
                    && !layer.state.outOfScale
                    && !layer.state.outOfBounds;
            if (layer.children) {
                // Store preliminary visibility for the recursive check, where current layer state will be "parentState"
                layer.state.visibility = !!newVisibilityState;
                var atLeastOneChildVisible = false;
                for (var j = 0; j < layer.children.length; j++) {
                    var childLayer = layer.children[j];
                    _changeOptions(childLayer, scale, layer.state.visibility);
                    if (childLayer.state.visibility) {
                        atLeastOneChildVisible = true;
                    }
                }
                // NOTE: Children can only be visible if this layer was already set visible before we called the
                //       recursive check. Thus if you think this should be a &&, you're not wrong, but the result is
                //       the same anyway.
                layer.state.visibility = atLeastOneChildVisible;
            } else {
                layer.state.visibility = !!(newVisibilityState && layer.options[self.layerNameIdent].length);
                layer.state.info = layer.options.treeOptions.info && layer.options.treeOptions.allow.info && layer.state.visibility;
            }
            var stateNames = ['outOfScale', 'outOfBounds', 'visibility', 'info'];
            for (var sni = 0; sni < stateNames.length; ++ sni) {
                var stateName = stateNames[sni];
                if (layer.state[stateName] !== initialLayerState[stateName]) {
                    elchanged = true;
                    break;
                }
            }
            if (elchanged) {
                result.changed.children[layer.options.id] = {
                    state: layer.state,
                    options: {
                        treeOptions: layer.options.treeOptions
                    }
                };
            }
        }
    },
    'public function getLayerParameters': function(source) {
        var result = {
            layers: [],
            styles: [],
            infolayers: []
        };
        var layerParamName = this.layerNameIdent;
        var customLayerOrder = Mapbender.Geo.layerOrderMap["" + source.id];
        Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer, offset, parents) {
            // Layer names can be emptyish, most commonly on root layers
            // Suppress layers with empty names entirely
            if (layer.options[layerParamName]) {
                if (layer.state.visibility) {
                    result.layers.push(layer.options[layerParamName]);
                    result.styles.push(layer.options.style || '');
                }
                if (layer.state.info) {
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
    'public function createOptionsLayerState': function(source, changeOptions, defaultSelected, mergeSelected) {
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
    'public function getLayerExtents': function(source, layerId) {
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
    'public function setLayerOrder': function(source, layerIdOrder) {
        var newLayerNameOrder = $.map(layerIdOrder, function(layerId) {
            var layerObj = this.findLayer(source, {id: layerId});
            return layerObj.layer.options.name;
        }.bind(this));
        Mapbender.Geo.layerOrderMap["" + source.id] = newLayerNameOrder;
    }
});

// old declaration
Mapbender['source'] = {};
