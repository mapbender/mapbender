window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerPool = (function() {
    function VectorLayerPool(olMap) {
        this.olMap = olMap;
        this.elementLayerGroups_ = [];
    }
    VectorLayerPool.typeMap = {};
    /**
     * @param {MapEngine} engine
     * @param {(OpenLayers.Map|{ol.PluggableMap})} nativeMap
     * @return {(VectorLayerPoolOl2|VectorLayerPoolOl4)}
     */
    VectorLayerPool.factory = function(engine, nativeMap) {
        var typeMap = VectorLayerPool.typeMap;
        var constructor = typeMap[engine.code] || typeMap['current'];
        return new (constructor)(nativeMap);
    };
    Object.assign(VectorLayerPool.prototype, {
        /**
         * @param {*} owner
         * @param {Number} [index]
         * @return {Mapbender.VectorLayerBridge}
         */
        getElementLayer: function getElementLayer(owner, index) {
            var index_ = index || 0;
            var group = this.findElementLayerGroup_(owner);
            if (!group) {
                group = this.createOwnerGroup_(owner);
                this.elementLayerGroups_.push(group);
            }
            if (group.bridgeLayers.length < index_) {
                var max = group.bridgeLayers.length;
                throw new Error("Non-contiguous layer index " + (index_.toString()) + " / " + max + " for given owner.");
            }
            if (group.bridgeLayers.length === index_) {
                var layerBridge = this.createBridgeLayer_(this.olMap);
                this.addBridgeLayerToGroup_(group, layerBridge);
                return layerBridge;
            }
            return group.bridgeLayers[index_];
        },
        hideElementLayers: function hideElementLayers(owner) {
            var group = this.findElementLayerGroup_(owner);
            if (!group || !group.bridgeLayers.length) {
                console.error("Given owner has no assigned vector layers", owner, this.elementLayerGroups_);
                throw new Error("Given owner has no assigned vector layers");
            }
            this.hideGroup_(group);
        },
        /**
         * @param {*} owner
         * @param {Boolean} [raise]
         */
        showElementLayers: function showElementLayers(owner, raise) {
            if (raise) {
                this.raiseElementLayers(owner);
            }
            var group = this.findElementLayerGroup_(owner);
            if (!group || !group.bridgeLayers.length) {
                console.error("Given owner has no assigned vector layers", owner, this.elementLayerGroups_);
                throw new Error("Given owner has no assigned vector layers");
            }
            this.showGroup_(group);
        },
        findElementLayerGroup_: function(owner) {
            for (var i = 0; i < this.elementLayerGroups_.length; ++i) {
                var group = this.elementLayerGroups_[i];
                if (group.owner === owner) {
                    return group;
                }
            }
            return null;
        },
        addBridgeLayerToGroup_: function(group, layerBridge) {
            group.bridgeLayers.push(layerBridge);
        },
        hideGroup_: function(group) {
            for (var i = 0, max = group.bridgeLayers.length; i < max; ++i) {
                group.bridgeLayers[i].hide();
            }
        },
        showGroup_: function(group) {
            for (var i = 0, max = group.bridgeLayers.length; i < max; ++i) {
                group.bridgeLayers[i].show();
            }
        }
    });

    return VectorLayerPool;
}());
