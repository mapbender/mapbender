window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridge = (function() {
    function VectorLayerBridge(olMap) {
        this.olMap = olMap;
    }
    Object.assign(VectorLayerBridge.prototype, {
        getNativeLayer: function() {
            return this.wrappedLayer_;
        }
    });
    return VectorLayerBridge;
}());
