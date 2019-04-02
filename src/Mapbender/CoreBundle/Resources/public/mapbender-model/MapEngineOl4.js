window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl4 = (function() {
    function MapEngineOl4() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl4.prototype = Object.create(Mapbender.MapEngine.prototype);
    Object.assign(MapEngineOl4.prototype, {
        constructor: MapEngineOl4,
        patchGlobals: function(mapOptions) {
            var _tileSize = mapOptions && mapOptions.tileSize && parseInt(mapOptions.tileSize);
            var _dpi = mapOptions && mapOptions.dpi && parseInt(mapOptions.dpi);
            if (_tileSize) {
                // todo: apply tile size globally?
            }
            if (_dpi) {
                // todo: apply dpi globally?
            }
            // todo: image path?
             // something something Mapbender.configuration.application.urls.asset
            // todo: proxy host?
              // something something Mapbender.configuration.application.urls.proxy + '?url=';
            // Allow drag pan motion to continue outside of map div. Great for multi-monitor setups.
            // todo: fix drag pan
            // OpenLayers.Control.Navigation.prototype.documentDrag = true;
            Mapbender.MapEngine.prototype.patchGlobals.apply(this, arguments);
        }
    });
    window.Mapbender.MapEngine.typeMap['ol4'] = MapEngineOl4;
    return MapEngineOl4;
}());
