window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerPoolOl2 = (function() {
    function VectorLayerPoolOl2(olMap) {
        window.Mapbender.VectorLayerPool.apply(this, arguments);
    }
    window.Mapbender.VectorLayerPool.typeMap['ol2'] = VectorLayerPoolOl2;
    VectorLayerPoolOl2.prototype = Object.create(Mapbender.VectorLayerPool.prototype);
    Object.assign(VectorLayerPoolOl2.prototype, {
        constructor: VectorLayerPoolOl2,
        raiseElementLayers: function(owner) {
            var group = this.findElementLayerGroup_(owner);
            if (!group) {
                throw new Error("No such element layer group");
            }
            var nLayers = this.olMap.getNumLayers();
            var nativeLayers = group.bridgeLayers.map(function(bl) {
                return bl.getNativeLayer();
            });
            for (var i = 0; i < nativeLayers.length; ++i) {
                var nativeLayer = nativeLayers[i];
                this.olMap.raiseLayer(nativeLayer, nLayers);
            }
            this.olMap.resetLayersZIndex();
        },
        createBridgeLayer_: function(olMap) {
            return new window.Mapbender.VectorLayerBridgeOl2(olMap);
        },
        createOwnerGroup_: function(owner) {
            return {
                owner: owner,
                bridgeLayers: []
            };
        },
        addBridgeLayerToGroup_: function(group, layerBridge) {
            window.Mapbender.VectorLayerPool.prototype.addBridgeLayerToGroup_.call(this, group, layerBridge);
            this.olMap.addLayer(layerBridge.getNativeLayer());
        }
    });
    return VectorLayerPoolOl2;
}());
