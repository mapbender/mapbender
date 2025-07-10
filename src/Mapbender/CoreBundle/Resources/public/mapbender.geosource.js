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
        var srsName_ = srsName || Mapbender.Model.getCurrentProjectionCode();
        // NOTE: ImageExport / Print pass a non-native data object with left / bottom / right / top properties
        // Adapt to internally useable format
        var extent_ = Mapbender.mapEngine.toExtent(extent || Mapbender.Model.getCurrentExtent());

        var order = 0;
        Mapbender.Util.SourceTree.iterateSourceLeaves(source, false, function(layer, offset, parents) {
            const layerId = layer.options.id;
            const outOfScale = !layer.isInScale(scale);
            const outOfBounds = !layer.intersectsExtent(extent_, srsName_);
            const unsupportedProjection = !layer.supportsProjection(srsName_);
            const enabled = layer.getActive();
            const visibility = enabled && !(outOfScale || outOfBounds || unsupportedProjection);
            // no feature info if layer turned off or out of scale
            const featureInfo = visibility && layer.options.treeOptions.info;
            infoMap[layerId] = {
                layer: layer,
                state: {
                    outOfScale: outOfScale,
                    outOfBounds: outOfBounds,
                    visibility: visibility,
                    unsupportedProjection: unsupportedProjection,
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
        let stateMap = {}
        const leafInfo = this.getExtendedLeafInfo(source, scale, extent, srsName);
        for (const [key, item] of Object.entries(leafInfo)) {
            stateMap[key] = item.state;
        }

        const stateNames = ['outOfScale', 'outOfBounds', 'unsupportedProjection', 'visibility', 'info'];
        let stateChanged = false;

        Mapbender.Util.SourceTree.iterateLayers(source, false, function(layer, offset, parents) {
            if (layer.children && layer.children.length) {
                // non-leaf layer visibility is a function of combined leaf layer visibility
                // start with false (recursion order is root first)
                layer.state.visibility = false;
            }
            const entry = stateMap[layer.options.id] || Object.assign({}, layer.state, {
                outOfScale: !layer.isInScale(scale),
                outOfBounds: !layer.intersectsExtent(extent, srsName),
                unsupportedProjection: !layer.supportsProjection(srsName),
            });

            for (let sni = 0; sni < stateNames.length; ++ sni) {
                const stateName = stateNames[sni];
                if (layer.state[stateName] !== entry[stateName]) {
                    layer.state = $.extend(layer.state || {}, entry);
                    stateChanged = true;
                    break;
                }
            }
            for (let p = 0; p < parents.length; ++p) {
                const parentLayer = parents[p];
                parentLayer.state.visibility = parentLayer.state.visibility || layer.state.visibility;
            }
        });
        return stateChanged;
    },
    setLayerOrder: function(source, layerIdOrder) {
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
            var childA = _pickChildId(layerIdOrder, a);
            var childB = _pickChildId(layerIdOrder, b);
            var ixA = layerIdOrder.includes(childA) ? layerIdOrder.indexOf(childA) : Number.MAX_SAFE_INTEGER;
            var ixB = layerIdOrder.includes(childB) ? layerIdOrder.indexOf(childB) : Number.MIN_SAFE_INTEGER;
            return parseInt(ixA, 10) - parseInt(ixB, 10);
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
