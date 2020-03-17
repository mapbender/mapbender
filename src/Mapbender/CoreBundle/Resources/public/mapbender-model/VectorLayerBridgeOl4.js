window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl4 = (function() {
    function VectorLayerBridgeOl4(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new ol.layer.Vector({
            map: olMap,
            source: new ol.source.Vector({wrapX: false})
        });
    }
    VectorLayerBridgeOl4.prototype = Object.create(Mapbender.VectorLayerBridge.prototype);
    Object.assign(VectorLayerBridgeOl4.prototype, {
        constructor: VectorLayerBridgeOl4,
        clear: function() {
            this.wrappedLayer_.getSource().clear();
        },
        show: function() {
            this.wrappedLayer_.setVisible(true);
        },
        hide: function() {
            this.wrappedLayer_.setVisible(false);
        }
    });
    return VectorLayerBridgeOl4;
}());
