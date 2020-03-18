window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl2 = (function() {
    function VectorLayerBridgeOl2(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new OpenLayers.Layer.Vector();
    }
    VectorLayerBridgeOl2.prototype = Object.create(Mapbender.VectorLayerBridge.prototype);
    Object.assign(VectorLayerBridgeOl2.prototype, {
        constructor: VectorLayerBridgeOl2,
        clear: function() {
            this.wrappedLayer_.removeAllFeatures();
        },
        show: function() {
            this.wrappedLayer_.setVisibility(true);
        },
        hide: function() {
            this.wrappedLayer_.setVisibility(false);
        }
    });
    return VectorLayerBridgeOl2;
}());
