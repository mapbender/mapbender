var Mapbender = Mapbender || {};
Mapbender.SimpleHighlighting = Mapbender.SimpleHighlighting || function(map, defaultBuffer, defaultStyle) {
    var shl = this;
    this.mbMap = map;
    if(defaultBuffer) {
        this.defaultBuffer = defaultBuffer;
    } else {
        this.defaultBuffer = 1.0; // km
    }
    this.defaultStyle = defaultStyle ? defaultStyle : null;
    this.features = [];
    this.srs = null;
    var _transform = function(geometry, srsfrom, srsinto, clone){
        if(srsfrom.projCode !== srsinto.projCode) {
            return clone ? geometry.clone().transform(srsfrom, srsinto) : geometry.transform(srsfrom, srsinto);
        }
        return geometry;
    };
    var _add = function(geometry, attributes, style){
        shl.features.push(new OpenLayers.Feature.Vector(geometry, attributes, style));
    };
    this.add = function(features, srs) {
        this.srs = this.srs ? this.srs : this.mbMap.getModel().getCurrentProj();
        if(features.length) { // array of features
            for(var i = 0; i < features.length; i++) {
                _add(_transform(features[i].geometry, srs, this.srs, true),
                    features[i].attributes,
                    features[i].style ? features[i].style : this.defaultStyle);
            }
        } else { // single feature
            _add(_transform(features.geometry, srs, this.srs, true),
                features.attributes,
                features.style ? features.style : this.defaultStyle);
        }
        return this;
    };
    this.show = function(){
        if(this.features.length) {
            this.mbMap.highlightOn(this.features, {
                clearFirst: false,
                "goto":     false
            });
        }
        return this;
    };
    this.hide = function() {
        if(this.features.length) {
            this.mbMap.highlightOff(this.features);
        }
        return this;
    };
    this.remove = function() {
        if(this.features.length) {
            this.hide();
            this.features = [];
            this.srs = null;
        }
        return this;
    };
    this.transform = function(srs){
        for(var i = 0; i < this.features.length; i++) {
            this.features[i].geometry = _transform(this.srs, srs);
        }
        this.srs = srs;
    };
    this.zoom = function(geometry, srs, zoomOptions) {
        var geometry = _transform(geometry, srs, this.mbMap.getModel().getCurrentProj(), true);
        var zoomLevel = null;
        var centroid = null;
        if(zoomOptions.buffer){
            var geomExtent = this.mbMap.getModel().calculateExtent(geometry, {
                w: zoomOptions.buffer,
                h: zoomOptions.buffer
            });
            zoomLevel = this.mbMap.map.olMap.getZoomForExtent(geomExtent, false);
            centroid = geometry.getCentroid();
        } else if(zoomOptions.minScale || zoomOptions.maxScale){
            // TODO test start
            var geomExtent = geometry.getBounds() ? geometry.getBounds() : geometry.calculateBounds();
            var zoomLevel = this.mbMap.map.olMap.getZoomForExtent(geomExtent);
            var res = this.mbMap.map.olMap.getResolutionForZoom(zoomLevel);
            if(zoomOptions.maxScale){
                var maxRes = OpenLayers.Util.getResolutionFromScale(
                    zoomOptions.maxScale, this.mbMap.map.olMap.baseLayer.units);
                if(Math.round(res) < maxRes){
                    zoomLevel = map.getZoomForResolution(maxRes);
                }
            }
            if(zoomOptions.minScale){
                var minRes = OpenLayers.Util.getResolutionFromScale(
                    zoomOptions.minScale,this.mbMap.map.olMap.baseLayer.units);
                if(Math.round(res) > minRes){
                    zoomLevel = map.getZoomForResolution(minRes);
                }
            }
            // TODO test end
        } else {
            var geomExtent = this.mbMap.getModel().calculateExtent(geometry, {
                w: zoomOptions.buffer,
                h: zoomOptions.buffer
            });
            zoomLevel = this.mbMap.map.olMap.getZoomForExtent(geomExtent, false);
            centroid = geometry.getCentroid();
        }
        this.mbMap.map.olMap.setCenter(new OpenLayers.LonLat(centroid.x, centroid.y), zoomLevel);
    };
};