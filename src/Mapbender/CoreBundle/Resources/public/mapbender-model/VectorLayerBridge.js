window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridge = (function() {
    function VectorLayerBridge(olMap) {
        this.olMap = olMap;
    }
    Object.assign(VectorLayerBridge.prototype, {
        getNativeLayer: function() {
            return this.wrappedLayer_;
        },
        addMarker: function(lon, lat) {
            this.addNativeFeatures([this.getMarkerFeature_(lon, lat)]);
        }
    });
    return VectorLayerBridge;
}());
