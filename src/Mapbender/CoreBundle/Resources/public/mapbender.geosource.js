var Mapbender = Mapbender || {};
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
Mapbender.Geo = {};

Mapbender.Geo.SourceHandler = {
    featureInfoUrl: function(source, x, y) {
        var source_;
        if (source.source) {
            // An actual MapQuery layer
            console.warn("Deprecated call to featureInfoUrl with a MapQuery layer, pass in the source object instead");
            source_ = source.source;
        } else {
            source_ = source;
        }
        return source_.getPointFeatureInfoUrl(x, y);
    },
    /**
     * Returns modernish print data.
     *
     * @param {*} source
     * @param {OpenLayers.Bounds} bounds
     * @param {Number} scale
     * @param {OpenLayers.Projection} projection
     * @return {Array<RasterPrintDataRecord>}
     */
    getPrintConfigEx: function(source, bounds, scale, projection) {
        return source.getMultiLayerPrintConfig(bounds, scale, projection);
    },
    getLayersList: function getLayersList(source) {
        if (arguments.length !== 1) {
            console.warn("Called getLayersList with extra arguments, ignoring");
        }
        var rootLayer = source.configuration.children[0];
        return {
            layers: (rootLayer.children || [])
        };
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
        return $.extend(result, source.getLayerParameters(newStates));
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
            var newTreeOptions = {
                selected: null
            };
            var changedTreeOptions;
            if (layer.children && layer.children.length) {
                for (var i = 0; i < layer.children.length; i++) {
                    if (setSelected(layer.children[i])) {
                        newTreeOptions.selected = true;
                    }
                }
                if (newTreeOptions.selected === null && layerOpts) {
                    newTreeOptions.selected = layerOpts.options.treeOptions.selected;
                }
                if (newTreeOptions.selected === null && defaultSelected !== null) {
                    newTreeOptions.selected = defaultSelected;
                }
            } else {
                newTreeOptions.selected = layerOpts ? layerOpts.options.treeOptions.selected : defaultSelected;
            }
            if (mergeSelected) {
                newTreeOptions.selected = newTreeOptions.selected || layer.options.treeOptions.selected;
            }
            if (newTreeOptions.selected === null) {
                return null;
            }

            if (newTreeOptions.selected !== layer.options.treeOptions.selected) {
                changedTreeOptions = {
                    selected: newTreeOptions.selected
                };
                if (mergeSelected && !newTreeOptions.selected) {
                    newTreeOptions.info = layer.options.treeOptions.info;
                } else {
                    newTreeOptions.info = newTreeOptions.selected;
                }
                newTreeOptions.info = newTreeOptions.info && layer.options.treeOptions.allow.info;
                if (newTreeOptions.info !== layer.options.treeOptions.info) {
                    changedTreeOptions.info = newTreeOptions.info;
                }
            }
            if (changedTreeOptions) {
                layerChanges[layer.options.id] = {
                    options: {
                        treeOptions: changedTreeOptions
                    }
                };
            }
            return newTreeOptions.selected;
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
    getLayerExtents: function (source, layerId) {
        return source.getLayerExtentConfigMap(layerId, true, true);
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

        var order = 0;
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
                order: order,
                parents: parents
            };
            ++order;
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
                        state: parentLayer.state,
                        options: {
                            treeOptions: parentLayer.options.treeOptions
                        }
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
     * @param {Object} layer
     * @param {*} extent
     * @return {boolean}
     */
    isLayerWithinBounds: function(layer, extent) {
        // HACK: disabled for now
        // WHen switching SRS this gets called multiple times, sometimes with an extent in the old SRS,
        // which effectively disables perfectly viable layers.
        return true;
        var projectionCode = Mapbender.Model.getCurrentProjectionCode();
        // let the source substitute the layer (c.f. WMTS fake root layer for layertree)
        var bounds = layer.source && layer.source.getLayerBounds(layer.options.id, projectionCode, true);
        if (layer.source && layer === layer.source.configuration.children[0]) {
            console.warn("Checking root layer in bounds", extent_, projectionCode);
        }
        if (!bounds) {
            if (bounds === null) {
                // layer is world wide
                return true;
            } else {
                // layer is kaputt
                return false;
            }
        }
        var extent_ = extent || Mapbender.Model.getCurrentExtent();
        return extent_.intersectsBounds(bounds);
    },
    setLayerOrder: function setLayerOrder(source, layerIdOrder) {
        var listsSorted = [];
        var _pickChildId = function(ids, layer) {
            if (!ids.length) {
                return null;
            } else {
                var ix = ids.indexOf(layer.options.id);
                if (ix !== -1) {
                    return ids[ix];
                }
            }
            if (layer.children && layer.children.length) {
                for (var ci = 0; ci < layer.children.length; ++ci) {
                    var ch = _pickChildId(ids, layer.children[ci]);
                    if (ch !== null) {
                        return ch;
                    }
                }
            }
            return null;
        };
        var _siblingSort = function(a, b) {
            var ixA = layerIdOrder.indexOf(_pickChildId(layerIdOrder, a));
            var ixB = layerIdOrder.indexOf(_pickChildId(layerIdOrder, b));
            return ixA - ixB;
        };
        for (var idIx = 0; idIx < layerIdOrder.length; ++idIx) {
            var layerId = layerIdOrder[idIx];
            var layerObj = this.findLayer(source, {id: layerId}).layer;
            if (listsSorted.indexOf(layerObj.siblings) === -1) {
                layerObj.siblings.sort(_siblingSort);
                listsSorted.push(layerObj.siblings);
            }
        }
    }
};

// old declaration
Mapbender['source'] = {};
