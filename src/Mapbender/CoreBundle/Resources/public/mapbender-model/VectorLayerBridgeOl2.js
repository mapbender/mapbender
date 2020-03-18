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
        },
        /**
         * @param {Array<OpenLayers.Feature>} features
         */
        addNativeFeatures: function(features) {
            this.wrappedLayer_.addFeatures(features);
        },
        getMarkerFeature_: function(lon, lat) {
            // @todo: marker styles with icons?
            var geometry = new OpenLayers.Geometry.Point(lon, lat);
            return new OpenLayers.Feature.Vector(geometry);
        }
    });
    return VectorLayerBridgeOl2;
}());
