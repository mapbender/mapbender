var Mapbender = Mapbender || {};
Mapbender.Highlighting = Mapbender.Highlighting || function(map, buffer) {
    this.mbMap = map;
    if(buffer) {
        this.buffer = buffer;
    } else {
        this.buffer = 1.0;
    }
    this.features = [];
    this.on = function(geometries) {
        for(var i = 0; i < geometries.length; i++) {
            var mapProj = this.mbMap.getModel().getCurrentProj();
            var geometry = geometries[i].geometry;
            if(geometries[i].srs.projCode !== mapProj.projCode) {
                geometry = geometry.transform(geometries[i].srs, mapProj);
            }
            this.features.push(new OpenLayers.Feature.Vector(geometry));
        }
        if(this.features.length) {
            this.mbMap.highlightOn(this.features, {
                clearFirst: false,
                "goto":     false
            });
        }
    };
    this.off = function() {
        if(this.features.length) {
            this.mbMap.highlightOff(this.features);
            this.features = [];
        }
    };
    this.offAll = function() {
        if(this.features.length) {
            this.mbMap.highlightOff(this.features);
            this.features = [];
            this.foundedFeature = null;
        }
    };
    this.zoom = function(geometry, srs) {
        if(srs.projCode !== this.mbMap.getModel().getCurrentProj().projCode) {
            geometry = geometry.transform(srs, this.mbMap.getModel().getCurrentProj());
        }
        var geomExtent = this.mbMap.getModel().calculateExtent(geometry, {
            w: this.buffer,
            h: this.buffer
        });
        var zoomLevel = this.mbMap.map.olMap.getZoomForExtent(geomExtent, false);
        var centroid = geometry.getCentroid();
        this.mbMap.map.olMap.setCenter(new OpenLayers.LonLat(centroid.x, centroid.y), zoomLevel);
        if(this.foundedFeature) {
            this.mbMap.highlightOff(this.foundedFeature);
            this.foundedFeature = null;
        }
        if(geometry.CLASS_NAME === "OpenLayers.Geometry.Point") {
            var centroid = geometry.getCentroid();
            var poi = {
                position: new OpenLayers.LonLat(centroid.x, centroid.y),
                label:    ""
            };
            this.foundedFeature = new OpenLayers.Feature.Vector(centroid, poi);
            this.mbMap.getModel().highlightOn(this.foundedFeature, {
                clearFirst: true,
                "goto":     false
            });
        } else {
            this.foundedFeature = new OpenLayers.Feature.Vector(geom);
            if(this.foundedFeature) {
                this.mbMap.highlightOn(this.foundedFeature, {
                    clearFirst: false,
                    "goto":     false
                });
            }
        }
    };
};