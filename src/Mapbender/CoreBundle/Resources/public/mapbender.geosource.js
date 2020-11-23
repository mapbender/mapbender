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
    /**
     * Returns a preview mapping of states of displayable (=leaf) layers as if the given scale + extent were applied
     * (but they are not!), together with references to the layer definition and its parents.
     *
     * @param {Object} source
     * @param {number} [scale] current value fetched from Mapbender.Model if omitted
     * @param {*} [extent]
     * @param {String} [srsName]
     * @return {Object<string, Model~ExtendedLayerInfo>}
     */
    getExtendedLeafInfo: function(source, scale, extent, srsName) {
        var infoMap = {};
        // @todo: srsName should be a method argument to make extent well defined
        var srsName_ = srsName || Mapbender.Model.getCurrentProjectionCode();
        // @todo: callers should pass extent; this is required for working out-of-bounds checks
        // NOTE: ImageExport / Print pass a non-native data object with left / bottom / right / top properties
        // Adapt to internally useable format
        var extent_ = Mapbender.mapEngine.toExtent(extent || Mapbender.Model.getCurrentExtent());

        var order = 0;
        Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer, offset, parents) {
            var layerId = layer.options.id;
            var outOfScale = !layer.isInScale(scale);
            var outOfBounds = !layer.intersectsExtent(extent_, srsName_);
            var enabled = layer.getActive();
            var visibility = enabled && !(outOfScale || outOfBounds);
            // no feature info if layer turned off or out of scale
            var featureInfo = visibility && layer.options.treeOptions.info;
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
     * Updates layer states, considering current treeOptions and given scale / extent.
     *
     * @param {*} source
     * @param {number} [scale] uses current map scale if not passed in
     * @param {Object} [extent]
     * @param {String} [srsName]
     * @return {boolean}
     */
    updateLayerStates: function(source, scale, extent, srsName) {
        var stateMap = _.mapObject(this.getExtendedLeafInfo(source, scale, extent, srsName), function(item) {
            return item.state;
        });

        var stateNames = ['outOfScale', 'outOfBounds', 'visibility', 'info'];
        var stateChanged = false;

        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, offset, parents) {
            if (layer.children && layer.children.length) {
                // non-leaf layer visibility is a function of combined leaf layer visibility
                // start with false (recursion order is root first)
                layer.state.visibility = false;
            }
            var entry = stateMap[layer.options.id] || Object.assign({}, layer.state, {
                outOfScale: !layer.isInScale(scale),
                outOfBounds: !layer.intersectsExtent(extent, srsName)
            });

            for (var sni = 0; sni < stateNames.length; ++ sni) {
                var stateName = stateNames[sni];
                if (layer.state[stateName] !== entry[stateName]) {
                    layer.state = $.extend(layer.state || {}, entry);
                    stateChanged = true;
                    break;
                }
            }
            for (var p = 0; p < parents.length; ++p) {
                var parentLayer = parents[p];
                parentLayer.state.visibility = parentLayer.state.visibility || layer.state.visibility;
            }
        });
        return stateChanged;
    },
    /**
     * @param {Object} layer
     * @param {number} scale current value fetched from Mapbender.Model if omitted
     * @return {boolean}
     * @deprecated call layer.isInScale
     */
    isLayerInScale: function(layer, scale) {
        return layer.isInScale(scale);
    },
    /**
     * @param {Object} layer
     * @param {*} extent
     * @return {boolean}
     * @deprecated call layer.intersectsExtent
     */
    isLayerWithinBounds: function(layer, extent) {
        var srsName = Mapbender.Model.getCurrentProjectionCode();
        return layer.intersectsExtent(extent, srsName);
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
        var parentIdOrder = [];
        for (var idIx = 0; idIx < layerIdOrder.length; ++idIx) {
            var layerId = layerIdOrder[idIx];
            var layerObj = source.getLayerById(layerId);
            if (listsSorted.indexOf(layerObj.siblings) === -1) {
                layerObj.siblings.sort(_siblingSort);
                listsSorted.push(layerObj.siblings);
            }
            if (layerObj.parent) {
                var parentId = layerObj.parent.options.id;
                if (parentId && parentIdOrder.indexOf(parentId) === -1) {
                    parentIdOrder.push(parentId);
                }
            }
        }
        if (parentIdOrder.length) {
            this.setLayerOrder(source, parentIdOrder);
        }
    }
};
