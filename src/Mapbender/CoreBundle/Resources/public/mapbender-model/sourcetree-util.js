window.Mapbender = Mapbender || {};
window.Mapbender.Util = Mapbender.Util || {};
window.Mapbender.Util.SourceTree = (function() {
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
    return {
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
        }
    };
})();
