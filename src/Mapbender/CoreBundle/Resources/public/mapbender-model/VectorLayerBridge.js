window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridge = (function() {
    function VectorLayerBridge(olMap) {
        this.olMap = olMap;
        this.drawControls_ = {};
        this.customIconMarkerStyles_ = {};
    }
    Object.assign(VectorLayerBridge.prototype, {
        getNativeLayer: function() {
            return this.wrappedLayer_;
        },
        addMarker: function(lon, lat) {
            this.addNativeFeatures([this.getMarkerFeature_(lon, lat, this.markerStyle_ || null)]);
        },
        /**
         * @param {String} iconStyleName
         * @param {Number} lon
         * @param {Number} lat
         */
        addIconMarker: function(iconStyleName, lon, lat) {
            if (!(iconStyleName && this.customIconMarkerStyles_[iconStyleName])) {
                throw new Error("Undefined named icon marker style " + iconStyleName);
            }
            var self = this;
            return this.customIconMarkerStyles_[iconStyleName].then(function(nativeStyle) {
                var feature = self.getMarkerFeature_(lon, lat, nativeStyle);
                self.addNativeFeatures([feature]);
                return feature;
            });
        },
        addCustomIconMarkerStyle: function(name, iconUrl, offsetX, offsetY) {
            var self = this;
            this.customIconMarkerStyles_[name] = Mapbender.Util.preloadImageAsset(iconUrl).then(function(img) {
                return self.imageToMarkerStyle_(img, offsetX, offsetY);
            });
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
