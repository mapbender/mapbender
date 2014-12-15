(function($) {
var firstPosition = true;
$.widget("mapbender.mbGpsPosition", {
    options: {
        follow: false,
        average: 1,
        zoomToAccuracy: true,
        centerOnFirstPosition: true,
        zoomOnFirstPosition: true
    },

    map: null,
    interval : null,
    stack: [],

    _create: function() {
        var self = this;
        var me = $(this.element);

        if(!Mapbender.checkTarget("mbGpsPosition", this.options.target)){
                return;
        }
        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));

        if(!this.options.average) {
            this.options.average = 1;
        }

        me.click(function() {
            me.parent().addClass("toolBarItemActive");
            self._timerGeolocation.call(self);
        });
    },

    _setup: function() {
        this.map = $('#' + this.options.target).data('mapbenderMbMap');
        if (this.options.autoStart === true){
            this._getGeolocation();
            this._activateTimer();
        }
    },

    _timerGeolocation: function() {
        if (this.interval != null){
            this._deactivateTimer();
        } else {
            this._getGeolocation();
            this._activateTimer();
        }
    },

    _getGeolocation: function() {
        var self = this;
        var olmap = this.map.map.olMap;
        if (navigator.geolocation)
        {
            navigator.geolocation.getCurrentPosition(function success(position) {
                var proj = new OpenLayers.Projection("EPSG:4326");
                var newProj = olmap.getProjectionObject();
                var p = new OpenLayers.LonLat(position.coords.longitude,position.coords.latitude);
                p.transform(proj, newProj);

                // Averaging: Building a queue...
                self.stack.push(p);
                if(self.stack.length > self.options.average) {
                    self.stack.splice(0, 1);
                }

                // ...and reducing it.
                p = _.reduce(self.stack, function(memo, p) {
                    memo.lon += p.lon / self.stack.length;
                    memo.lat += p.lat / self.stack.length;
                    return memo;
                }, new OpenLayers.LonLat(0, 0));

                self._createMarker(p);
                self._centerMap(p);
                
                if(firstPosition) firstPosition = false;

            }, function error(msg) {}, { enableHighAccuracy: true, maximumAge: 0 });
        } else {
            Mapbender.error(Mapbender.trans("mb.core.gpsposition.error.notsupported"));
        }
    },

    _createMarker: function (position) {
        var olmap = this.map.map.olMap;
        var markers;
        var candidates = olmap.getLayersByName('Markers');
        if (candidates.length > 0){
            markers = candidates[0];
            olmap.removeLayer(markers);
            markers.destroy();
        }
        markers = new OpenLayers.Layer.Markers( "Markers" );
        olmap.addLayer(markers);
        var size = new OpenLayers.Size(20,20);
        var icon = new OpenLayers.Icon(Mapbender.configuration.application.urls.asset + 'bundles/mapbendercore/image/marker_fett.gif', size);
        markers.addMarker(new OpenLayers.Marker(position,icon));
    },

    _centerMap: function (point){
        var olmap = this.map.map.olMap;
        var extent = olmap.getExtent();
        if (extent.containsLonLat(point) === false || true === this.options.follow) {
            olmap.panTo(point);
        } else if(firstPosition && this.options.centerOnFirstPosition) {
            olmap.panTo(point);
        }
    },
    
    _zoomMap: function(accuracy){
        if(!accuracy) return; // no accurancy
        if(!zoomToAccuracy) return;
        if(!this.options.zoomToAccuracy || !(firstPosition && this.options.zoomOnFirstPosition)) return;
        
        var olmap = this.map.map.olMap;
        var mpis = [1764.218, 352.843, 176.422, 35.284, 17.642, 8.821, 3.528, 2.646, 1.764, 0.882, 0.353, 0.176]; // meters per pixel in corresponding zoomLevel
        var resolution = olmap.getSize();
        var zoomLevel = 11;
        while (zoomLevel >= 0 && mpis[zoomLevel] * resolution.w < accuracy && mpis[zoomLevel] * resolution.h < accuracy) zoomLevel--;
        olmap.zoomTo(zoomLevel);
    },

    _activateTimer: function (){
        var self = this;
        var interval = this.options.refreshinterval;
        this.interval = setInterval(function() { self._getGeolocation.call(self); },interval);
    },

    _deactivateTimer: function (){
        $(this.element).parent().removeClass("toolBarItemActive");
        this.interval = clearInterval(this.interval);

        var olmap = this.map.map.olMap;
        var markers;
        var candidates = olmap.getLayersByName('Markers');
        if (candidates.length > 0){
            markers = candidates[0];
            olmap.removeLayer(markers);
            markers.destroy();
        }
    },
    /**
     *
     */
    ready: function(callback) {
        if(this.readyState === true) {
            callback();
        } else {
            this.readyCallbacks.push(callback);
        }
    },
    /**
     *
     */
    _ready: function() {
        for(callback in this.readyCallbacks) {
            callback();
            delete(this.readyCallbacks[callback]);
        }
        this.readyState = true;
    }
});

})(jQuery);
