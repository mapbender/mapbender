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

    // noinspection JSUnusedLocalSymbols
    /**
     * Callable type passed into iterateLayersets and iterateSources for both 'callback' and 'filter'. Receives
     * 1) an id -- this is stored one level up, outside the configuration, inside the config tree
     * 2) a data object with the full layerset or source configuration
     *
     * Callable may return boolean false at any node to immediately abort iteration.
     * Any other returned value is ignored.
     *
     * @callback Mapbender.Util.SourceTree~cbTypeNodeId
     * @param {number|string} id
     * @param {Object} def plain old data
     * @returns {boolean}
     */
    var _cbTypeNodeId;              // inconsequential, helps IDE separate callback type declaration from next real symbol

    // noinspection JSUnusedLocalSymbols
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

    // noinspection JSUnusedLocalSymbols
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
            var _ch = node.children;
            _ch = (reverse && _ch.slice().reverse()) || _ch;
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

    function getRootLayer(sourceDef) {
        return sourceDef.configuration.children[0];
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
     * @param {object} startLayerDef config node reference
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
        /**
         * Visit layerset config nodes matching the given ids, in the same order as the given ids, and invoke the given
         * callback with 1) the config node, 2) the id.
         *
         * NOTE: for layerset ids not matching any configured layerset, the callback (+optional filter callback) are still
         * invoked, but then receive undefined as the first argument.
         *
         * @param {Array.<string|number>} ids -
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} callback - receives def, id; can abort iteration by returning false
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} [filter] - also receives def, id
         */
        iterateLayerSetsById: function iterateLayersetsById(ids, callback, filter) {
            _r();
            for (var i = 0; i < ids.length; ++i) {
                var id = ids[i];
                var def = _lsroot[id];
                if ((!filter || filter(def, id)) && false === callback(def, id)) {
                    break;
                }
            }
        },
        /**
         * Find and return the configuration node for the given sourceId (strict) within the given layerset (flexible).
         * Null is only returned if no source with the given id is found in the layerset configuration.
         *
         * @param {string|number|object} layerset if Object, scanned as is; if string / id, looked up in global configuration
         * @param {string|number} sourceId
         * @returns {object|null}
         */
        getSourceDef: function getSourceDef(layerset, sourceId) {
            var match = null;
            // filter that only passes single, desired node
            var _f = function(def, id) {
                return id === sourceId;
            };
            var _cb = function(def) {
                // store match, abort iteration
                match = def;
                return false;
            };
            this.iterateSources(layerset, false, _cb, _f);
            return match;
        },
        /**
         * Iterate over all source configs in given layerset in desired order.
         *
         * @param {string|number|object} layerset if Object, scanned as is; if string / id, looked up in global configuration
         * @param {boolean} reverse false for configuration sort order; true for the other thing
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} callback - receives def, id; can abort iteration by returning false
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} [filter] - also receives def,id
         */
        iterateSources: function iterateSources(layerset, reverse, callback, filter) {
            var _ls = _anyToLsConf(layerset);
            _ls = (reverse && _ls.slice().reverse()) || _ls;
            for (var i = 0; i < _ls.length; ++i) {
                var srcDefWrap = _ls[i];
                var lsKeys = Object.keys(srcDefWrap);
                lsKeys = (reverse && lsKeys.reverse()) || lsKeys;
                for (var j = 0; j < lsKeys.length; ++j) {
                    var srcId = lsKeys[j];
                    var srcDef = srcDefWrap[srcId];
                    if ((!filter || filter(srcDef, srcId)) && false === callback(srcDef, srcId)) {
                        // multi-level break :)
                        return;
                    }
                }
            }
        },
        getLayersetDef: function(id) { return (_r())[id]; },
        getRootLayer: getRootLayer,

        /**
         * Recursively walks through all layers in the given source definition (depth last) and calls callback on any
         * visited node. Callback receives
         * 1) config node ref
         * 2) sibling offset
         * 3) list of parent config nodes (tree upwards / nearest first, root last)
         *
         * Optional second 'filter' callback has the same signature. If the filter callback is given, main callback is
         * only invoked after the filter, only if the filter returned a truthy value.
         *
         * @param {object} sourceDef
         * @param {boolean} reverse for child node visiting order; false follows config Array order; true reverses (all nodes)
         * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} callback
         * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} [filter]
         */
        iterateLayers: function(sourceDef, reverse, callback, filter) {
            var _r = getRootLayer(sourceDef);
            _itLayersRecursive(_r, reverse, callback, filter, []);
        },
        iterateSourceLeaves: iterateSourceLeaves,
        iterateChildlayers: iterateChildlayers,
        /**
         * Generates and assigns string ids for layers corresponding to source or parent node id + sibling index
         * within each level. Nodes deeper down the tree get longer ids as a result.
         *
         * The generated ids are stored in each layer node's options.id. Used only for dynamically added (WmsLoader)
         * layers which never have database-generated ids.
         *
         * @param {object} sourceDef
         * @param {Mapbender.Util.SourceTree~cbTypeNodeOffsetParents} [chainCallback] - called on each node
         *   after assignment of ids; receives layerDef, sibling index, list of parents (closest first, tree upwards)
         */
        generateLayerIds: function(sourceDef, chainCallback) {
            if (!sourceDef.id && sourceDef.id !== 0) {
                console.error("Empty source id in sourceDef", sourceDef);
                throw new Error("Empty source id");
            }
            this.iterateLayers(sourceDef, false, function(layer, index, parents) {
                // concat running sibling index either to parent's options.id or, if root layer, to source id
                var layerId = ((parents[0] || {}).options || sourceDef).id + '-' + index;
                layer.options.id = layerId;
                if (chainCallback) {
                    chainCallback(layer, index, parents);
                }
            });
        },
        /**
         * Iterate over all configured layersets in no specific order (ls config is an object, we can't guarantee anything).
         * Calls given callback on any located layerset configuration node, with 1) config node, 2) layerset id.
         *
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} callback - receives def, id; can abort iteration by returning false
         * @param {Mapbender.Util.SourceTree~cbTypeNodeId} [filter] - also receives def, id
         */
        iterateLayersets: function(callback, filter) {
            var lsKeys = Object.keys(_r());
            for (var i = 0; i < lsKeys.length; ++i) {
                var id = lsKeys[i];
                var def = _lsroot[id];
                if (!filter || filter(def, id)) {
                    if (false === callback(def, id)) {
                        break;
                    }
                }
            }
        }
    };
})();
