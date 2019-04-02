window.Mapbender = Mapbender || {};
window.Mapbender.MapEngineOl2 = (function() {
    function MapEngineOl2() {
        Mapbender.MapEngine.apply(this, arguments);
    }
    MapEngineOl2.prototype = Object.create(Mapbender.MapEngine.prototype);
    Object.assign(MapEngineOl2.prototype, {
        constructor: MapEngineOl2,
        patchGlobals: function(mapOptions) {
            var _tileSize = mapOptions && mapOptions.tileSize && parseInt(mapOptions.tileSize);
            var _dpi = mapOptions && mapOptions.dpi && parseInt(mapOptions.dpi);
            if (_tileSize) {
                OpenLayers.Map.TILE_WIDTH = _tileSize;
                OpenLayers.Map.TILE_HEIGHT = _tileSize;
            }
            if (_dpi) {
                OpenLayers.DOTS_PER_INCH = mapOptions.dpi;
            }
            OpenLayers.ImgPath = Mapbender.configuration.application.urls.asset + 'components/mapquery/lib/openlayers/img/';
            OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';
            // Allow drag pan motion to continue outside of map div. Great for multi-monitor setups.
            OpenLayers.Control.Navigation.prototype.documentDrag = true;
            Mapbender.MapEngine.prototype.patchGlobals.apply(this, arguments);
        }
    });
    window.Mapbender.MapEngine.typeMap['ol2'] = MapEngineOl2;
    return MapEngineOl2;
}());
