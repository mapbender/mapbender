window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl4 = (function() {
    function VectorLayerBridgeOl4(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new ol.layer.Vector({
            map: olMap,
            source: new ol.source.Vector({wrapX: false})
        });
        this.markerStyle_ = null;
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
        },
        /**
         * @param {Array<ol.Feature>} features
         */
        addNativeFeatures: function(features) {
            this.wrappedLayer_.getSource().addFeatures(features);
        },
        setBuiltinMarkerStyle: function(name) {
            switch (name) {
                default:
                    if (name === null) {
                        throw new Error("Unknown marker style " + name);
                    } else {
                        this.markerStyle_ = null;
                    }
                    break;
                case 'poiIcon':
                    // @todo: move poi icon options out of mbMap widget
                    var poiOptions = $['mapbender']['mbMap'].prototype.options.poiIcon;
                    var iconUrl = Mapbender.configuration.application.urls.asset + poiOptions.image;
                    this.markerStyle_ = new ol.style.Style({
                        image: new ol.style.Icon({
                            src: iconUrl,
                            imgSize: [poiOptions.width, poiOptions.height],
                            anchor: [-poiOptions.xoffset, -poiOptions.yoffset],
                            anchorOrigin: ol.style.IconOrigin.TOP_LEFT,
                            anchorXUnits: ol.style.IconAnchorUnits.PIXELS,
                            anchorYUnits: ol.style.IconAnchorUnits.PIXELS
                        })
                    });
                    break;
            }
        },
        getMarkerFeature_: function(lon, lat) {
            var feature = new ol.Feature({
                geometry: new ol.geom.Point([lon, lat])
            });
            if (this.markerStyle_) {
                feature.setStyle(this.markerStyle_);
            }
            return feature;
        }
    });
    return VectorLayerBridgeOl4;
}());
