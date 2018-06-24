window.Mapbender = Mapbender || {};
window.Mapbender.Util = Mapbender.Util || {};
window.Mapbender.Util.SourceTree = (function() {
    // The Mapbender.configuration.layersets node ref is completely constant, unlike sources and layers
    // => "cache" it in a local symbol
    var _lsroot;
    function _r() {
        _lsroot = _lsroot || Mapbender.configuration.layersets || {};
        return _lsroot;
    }
    function _isObj(x) {
        return !Array.isArray(x) && (typeof x === 'object');
    }

    /**
     * Promotes a scalarish input x to a layerset config node ref with ~id x.
     * If input is already a data object, return it as is.
     *
     * @param x
     * @returns {*}
     * @private
     */
    function _anyToLsConf(x) {
        if (!_isObj(x)) {
            return (_r())[x];
        } else {
            return x;
        }
    }

    /**
     * Callable type passed into iterateLayersets and iterateSources for both 'callback' and 'filter'. Receives
     * 1) an id -- this is stored one level up, outside the configuration, inside the config tree
     * 2) a data object with the full layerset or source configuration
     *
     * Callable may return boolean false at any node to immediately abort iteration.
     * Any other returned value is ignored.
     *
     * @callback Mapbender.Util.SourceTree~cbTypeIdNode
     * @param {number|string} id
     * @param {Object} def plain old data
     * @returns {boolean}
     */
    var _cbTypeIdPlusConfig;        // inconsequential, helps IDE separate callback type declaration from next real symbol

    /**
     * Callable type passed into iterateLayers and friends for both 'callback' and 'filter'. Receives
     * 1) Ref to a data object with the full layer configuration (including children and everything)
     * 2) A running per-parent sibling index of the node (first child of a parent node receives 0, next child after that 1 etc)
     * 3) A list of refs to parent config nodes, ordered direct parent first, root node last.
     *    The root node receives an empty list. First child receives [parentNodeRef]. Grandchild receives
     *    [parentNodeRef, grandParentNodeRef] etc.
     *
     * Callable may return boolean false at any node to immediately abort iteration.
     * Any other returned value is ignored.
     *
     * @callback Mapbender.Util.SourceTree~cbTypeNodeOffsetParents
     * @param {Object} def plain old data
     * @param {number} siblingIndex
     * @param {Array.<Object>} parents
     * @returns {boolean}
     */
    var _cbTypeNodeOffsetParents;   // inconsequential, helps IDE separate callback type declaration from next real symbol

    /**
     * Callable type passed into iterateChildLayers. Receives
     * 1) Ref to a data object with the full layer configuration (including children and everything)
     * 2) A running per-parent sibling index of the node (first child of a parent node receives 0, next child after that 1 etc)
     *
     * Callable may return boolean false at any node to immediately abort iteration.
     * Any other returned value is ignored.
     *
     * @callback Mapbender.Util.SourceTree~cbTypeNodeOffset
     * @param {Object} def plain old data
     * @param {number} siblingIndex
     * @returns {boolean}
     */
    var _cbTypeNodeOffset;          // inconsequential, helps IDE separate callback type declaration from next real symbol


    var _itLayersRecursive = function(node, reverse, callback, filter, parents, index) {
        if ((!filter || filter(node, parents)) && false === callback(node, index || 0, parents.slice())) {
            return false;
        }
        if (node.children && node.children.length) {
            /**
             * NOTE: there are two alternative approaches to track and untrack parents
             * 1) unshift onto original Array before entering recursion, shift after recursion returns
             * 2) unshift on slice (=shallow copy) before recursion, discard shallow copy after recursion
             * Both are equivalent in defined function (=values passed to callbacks), but variant #2 was chosen
             * for better introspection.
             *
             * With variant #1, if you use console.log in your callback, you will frequently not get the Array
             * entries dumped properly. E.g. in Chrome console you may see an Array(1) logged, but if you try to expand
             * it, there's nothing inside. Chrome's console might defer the evaluation of Array contents, and they'll
             * be gc'ed before they can be dumped.
             */
            var _p = parents.slice();
            _p.unshift(node);
            var _ch = node.children[reverse ? 'reverse' : 'slice']();
            for (var i = 0; i < _ch.length; ++i) {
                if (false === _itLayersRecursive(_ch[i], reverse, callback, filter, _p, i)) {
                    return false;
                }
            }
        }
    };

    var _chainFilters = function(a, b) {
        if (!a || !b) {
            return a || b;
        }
        return function() {
            return a.apply(null, arguments) && b.apply(null, arguments);
        }
    };

    /**
     * Iterate over all layersets in no specific order (config storage is an object, we can't guarantee
     * anything).
     *
     * @param {Mapbender.Util.SourceTree~cbTypeIdNode} callback receives id, def; can abort iteration by returning false
     * @param {Mapbender.Util.SourceTree~cbTypeIdNode} [filter] also receives id, def
     */
    function iterateLayersets(callback, filter) {
        var lsKeys = Object.keys(_r());
        for (var i = 0; i < lsKeys.length; ++i) {
            var id = lsKeys[i];
            var def = _lsroot[id];
            if (!filter || filter(id, def)) {
                if (false === callback(id, def)) {
                    break;
                }
            }
        }
    }

    /**
     * Iterate over all source configs in given layerset in desired order.
     *
     * @param {string|number|object} layerset if Object, scanned as is; if string / id, looked up in global configuration
     * @param {boolean} reverse false for configuration sort order; true for the other thing
     * @param {Mapbender.Util.SourceTree~cbTypeIdNode} callback receives id, def; can abort iteration by returning false
     * @param {Mapbender.Util.SourceTree~cbTypeIdNode} [filter] also receives id, def
     */
    function iterateSources(layerset, reverse, callback, filter) {
        var _ls = _anyToLsConf(layerset)[reverse ? 'reverse' : 'slice']();
        for (var i = 0; i < _ls.length; ++i) {
            var srcDefWrap = _ls[i];
            var lsKeys = Object.keys(srcDefWrap)[reverse ? 'reverse' : 'slice']();
            for (var j = 0; j < lsKeys.length; ++j) {
                var srcId = lsKeys[j];
                var srcDef = srcDefWrap[srcId];
                if ((!filter || filter(srcId, srcDef)) && false === callback(srcId, srcDef)) {
                    // multi-level break :)
                    return;
                }
            }
        }
    }

    function iterateLayersetsById(ids, callback, filter) {
        _r();
        for (var i = 0; i < ids.length; ++i) {
            var id = ids[i];
            var def = _lsroot[id];
            if ((!filter || filter(id, def)) && false === callback(id, def)) {
                break;
            }
        }
    }
    function getSourceDef(layerset, sourceId) {
        var match = null;
        var _cb = function(id, def) {
            match = def;
            return false;
        };
        var _f = function(id) {
            return id === sourceId;
        };
        iterateSources(layerset, false, _cb, _f);
        return match;
    }
    function getRootLayer(sourceDef) {
        return sourceDef.configuration.children[0];
    }

    /**
     * Recursively walks through all layers in the given source definition (depth last) and calls callback on any
     * visited node. Callback receives
     * 1) config node ref
     * 2) sibling offset
     * 3) list of parent config nodes (tree upwards / nearest first, root last)
     *
     * Optional second 'filter' callback has the same signature. If the filter callback is given and returns false for
     * the current node, invocation of the main callback is skipped.
     *
     * @param {object} sourceDef
     * @param {boolean} reverse for child node visiting order; false follows config Array order; true reverses (all nodes)
     * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} callback
     * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} [filter]
     */
    function iterateLayers(sourceDef, reverse, callback, filter) {
        var _r = getRootLayer(sourceDef);
        _itLayersRecursive(_r, reverse, callback, filter, []);
    }
    function iterateSourceLeaves(sourceDef, reverse, callback, filter) {
        var _r = getRootLayer(sourceDef);
        var _f = _chainFilters(filter, function(node) {
            return !(node.children || []).length;
        });
        _itLayersRecursive(_r, reverse, callback, _f, []);
    }
    function iterateSourceGroupLayers(sourceDef, reverse, callback, filter) {
        var _r = getRootLayer(sourceDef);
        var _f = _chainFilters(filter, function(node) {
            return !!(node.children || []).length;
        });
        _itLayersRecursive(_r, reverse, callback, _f, []);
    }

    /**
     *
     * @aram {Object} startLayerDef config node reference
     * @param {boolean} reverse false for 'children' list order matching configuration order; true for the other thing
     * @param {Mapbender.Util.SourceTree~cbTypeNodeOffset} callback
     *      receives config node and sibling offset for anyvisited node
     * @param {Mapbender.Util.SourceTree~cbTypeNodeOffset} filter
     *      receives config node and sibling offset for any visited node
     * @param filter
     */
    function iterateChildlayers(startLayerDef, reverse, callback, filter) {
        // create a wrapped callback that suppressed the problematic second 'parents' argument
        var _cb = function(node, index) { return callback(node, index); };
        // create a chained filter that suppressed the starting layer node (as passed in)
        var _f = _chainFilters(filter, function(node) {
            return node !== startLayerDef;
        });
        _itLayersRecursive(startLayerDef, reverse, _cb, _f, []);
    }

    return {
        iterateLayerSetsById: iterateLayersetsById,
        iterateSources: iterateSources,
        getLayersetDef: function(id) { return (_r())[id]; },
        getRootLayer: getRootLayer,
        getSourceDef: getSourceDef,
        /**
         * Recursively walks through all layers in the given source definition (depth last) and calls callback on any
         * visited node. Callback receives
         * 1) config node ref
         * 2) sibling offset
         * 3) list of parent config nodes (tree upwards / nearest first, root last)
         *
         * Optional second 'filter' callback has the same signature. If the filter callback is given and returns false for
         * the current node, invocation of the main callback is skipped.
         *
         * @param {object} sourceDef
         * @param {boolean} reverse for child node visiting order; false follows config Array order; true reverses (all nodes)
         * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} callback
         * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} [filter]
         */
        iterateLayers: iterateLayers,
        iterateSourceLeaves: iterateSourceLeaves,
        iterateChildlayers: iterateChildlayers
    };
})();
