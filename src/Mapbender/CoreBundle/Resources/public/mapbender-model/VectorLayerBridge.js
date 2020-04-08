window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridge = (function() {
    function VectorLayerBridge(olMap) {
        this.olMap = olMap;
        this.drawControls_ = {};
    }
    Object.assign(VectorLayerBridge.prototype, {
        getNativeLayer: function() {
            return this.wrappedLayer_;
        },
        addMarker: function(lon, lat) {
            this.addNativeFeatures([this.getMarkerFeature_(lon, lat)]);
        },
        draw: function(type, featureCallback) {
            if (!this.drawControls_[type]) {
                this.drawControls_[type] = this.createDraw_(type);
            }
            var controlMap = this.drawControls_;
            var others = Object.keys(controlMap)
                .filter(function(key) {
                    return key !== type;
                })
                .map(function(key) {
                    return controlMap[key];
                })
            ;
            for (var i = 0; i < others.length; ++i) {
                this.endDraw_(others[i]);
            }
            this.activateDraw_(this.drawControls_[type], featureCallback);
            return this.drawControls_[type];
        },
        endDraw: function() {
            var controlMap = this.drawControls_;
            var tools = Object.keys(controlMap)
                .map(function(key) {
                    return controlMap[key];
                })
            ;
            for (var i = 0; i < tools.length; ++i) {
                this.endDraw_(tools[i]);
            }
        }
    });
    return VectorLayerBridge;
}());
