window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerPoolOl2 = (function() {
    function VectorLayerPoolOl2(olMap) {
        window.Mapbender.VectorLayerPoolOl2.apply(this, arguments);
    }
    window.Mapbender.VectorLayerPool.typeMap['ol2'] = VectorLayerPoolOl2;
    VectorLayerPoolOl2.prototype = Object.create(Mapbender.VectorLayerPool.prototype);
    Object.assign(VectorLayerPoolOl2.prototype, {
        constructor: VectorLayerPoolOl2,
        createBridgeLayer_: function(olMap) {
            return new window.Mapbender.VectorLayerBridgeOl2(olMap);
        },
        createOwnerGroup_: function(owner) {
            return {
                owner: owner,
                bridgeLayers: []
            };
        },
        spliceElementLayerDescriptor_: function(descriptor, index) {
            window.Mapbender.VectorLayerPool.prototype.addElementLayerDescriptor_.call(this, descriptor, index);
        }
    });
}());
