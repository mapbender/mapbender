(function($) {

$.widget("mapbender.mbGpsPosition", {
    options: {},
    
    map: null,
    interval : null,
    
    _create: function() {
        var self = this;
        var me = $(this.element);
        
        if(!Mapbender.checkTarget("mbGpsPosition", this.options.target)){
                return;
        }
        Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        
        me.click(function() { self._timerGeolocation.call(self); });
    },
    
    _setup: function() {    
        this.map = $('#' + this.options.target).data('mbMap');       
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
                self._createMarker(p);
                self._centerMap(p);
                
            }, function error(msg) {}, { enableHighAccuracy: true, maximumAge: 0 });
        } else {
            alert("I'm sorry, but geolocation services are not supported by your browser.");
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
        if (extent.containsLonLat(point) === false)
        {
            olmap.setCenter(point, olmap.getZoom(), false, true);
        }
    },
    
    _activateTimer: function (){			
        var self = this;
        var interval = this.options.refreshinterval;
        this.interval = setInterval(function() { self._getGeolocation.call(self); },interval);
    },	

    _deactivateTimer: function (){	
        this.interval = clearInterval(this.interval);
    }
});

})(jQuery);
