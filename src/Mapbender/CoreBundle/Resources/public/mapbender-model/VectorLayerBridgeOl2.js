window.Mapbender = Mapbender || {};
window.Mapbender.VectorLayerBridgeOl2 = (function() {
    function VectorLayerBridgeOl2(olMap) {
        window.Mapbender.VectorLayerBridge.call(this, olMap);
        this.wrappedLayer_ = new OpenLayers.Layer.Vector();
        this.markerStyle_ = null;
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
                    this.markerStyle_ = {
                        fillOpacity: 0.0,
                        graphicOpacity: 1.0,
                        externalGraphic: iconUrl,
                        graphicWidth: poiOptions.width,
                        graphicHeight: poiOptions.height,
                        graphicXOffset: poiOptions.xoffset,
                        graphicYOffset: poiOptions.yoffset
                    };
                    break;
            }
        },
        getMarkerFeature_: function(lon, lat) {
            var geometry = new OpenLayers.Geometry.Point(lon, lat);
            return new OpenLayers.Feature.Vector(geometry, null, this.markerStyle_ || null);
        },
        createDraw_: function(type) {
            var layer = this.wrappedLayer_;
            switch (type) {
                case 'point':
                    return new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Point);
                case 'line':
                    return  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Path);
                case 'polygon':
                    return new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.Polygon);
                case 'rectangle':
                    return  new OpenLayers.Control.DrawFeature(layer, OpenLayers.Handler.RegularPolygon, {
                        handlerOptions: {
                            sides: 4,
                            irregular: true
                        }
                    });
                default:
                    throw new Error("No such type " + type);
            }
        },
        activateDraw_: function(control, featureCallback) {
            if (-1 === this.wrappedLayer_.map.controls.indexOf(control)) {
                this.wrappedLayer_.map.addControl(control);
            }
            control.activate();
            control.featureAdded = featureCallback;
        },
        endDraw_: function(control) {
            control.deactivate();
        }
    });
    return VectorLayerBridgeOl2;
}());
