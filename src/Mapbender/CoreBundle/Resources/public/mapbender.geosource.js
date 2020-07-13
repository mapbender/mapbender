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
            // @todo: integrate OOS / OOB checks directly
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
     * @param [extent] currently not used; @todo: implement outOfBounds checking
     * @return {boolean}
     */
    updateLayerStates: function(source, scale, extent) {
        var stateMap = _.mapObject(this.getExtendedLeafInfo(source, scale, extent), function(item) {
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
            var entry = stateMap[layer.options.id];
            if (entry) {
                for (var sni = 0; sni < stateNames.length; ++ sni) {
                    var stateName = stateNames[sni];
                    if (layer.state[stateName] !== entry[stateName]) {
                        layer.state = $.extend(layer.state || {}, entry);
                        stateChanged = true;
                        break;
                    }
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
     */
    isLayerInScale: function(layer, scale) {
        return Mapbender.Util.isInScale(scale, layer.options.minScale, layer.options.maxScale);
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
