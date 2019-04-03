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
        },
        getLayerVisibility: function(olLayer) {
            return olLayer.getVisibility();
        },
        createWmsLayer: function(source) {
            var options = getNativeLayerOptions(source);
            var params = getNativeLayerParams(source);
            var url = source.configuration.options.url;
            var name = source.title;
            return new OpenLayers.Layer.WMS(name, url, params, options);

            function getNativeLayerOptions(source) {
                var rootLayer = source.configuration.children[0];
                var bufferConfig = source.configuration.options.buffer;
                var ratioConfig = source.configuration.options.ratio;
                var opts = {
                    isBaseLayer: false,
                    opacity: source.configuration.options.opacity,
                    visibility: source.configuration.options.visible,
                    singleTile: !source.configuration.options.tiled,
                    noMagic: true,
                    minScale: rootLayer.minScale,
                    maxScale: rootLayer.maxScale
                };
                if (opts.singleTile) {
                    opts.ratio = parseFloat(ratioConfig) || 1.0;
                } else {
                    opts.buffer = parseInt(bufferConfig) || 0;
                }
                return opts;
            }
            function getNativeLayerParams(source) {
                var params = $.extend({}, source.customParams, {
                    transparent: source.configuration.options.transparent,
                    format: source.configuration.options.format,
                    version: source.configuration.options.version
                });
                var exceptionFormatConfig = source.configuration.options.exception_format;
                if (exceptionFormatConfig) {
                    params.exceptions = exceptionFormatConfig;
                }
                var activatedLeaves = source.getActivatedLeaves();
                var nonEmptyLayerNames = activatedLeaves.map(function(sourceLayer) {
                    return sourceLayer.options.name;
                }).filter(function(layerName) {
                    return !!layerName;
                });
                params.LAYERS = nonEmptyLayerNames;
                // @todo: use configured styles
                var styles = nonEmptyLayerNames.map(function() {
                    return '';
                });
                params.STYLES = styles;
                return params;
            }
        },
        compareWmsParams: function (olLayer, layers, styles) {
            var newLayers = (olLayer.params.LAYERS || '').toString() !== layers.toString();
            var newStyles = (olLayer.params.STYLES || '').toString() !== styles.toString();
            return newLayers || newStyles;
        }
    });
    window.Mapbender.MapEngine.typeMap['ol2'] = MapEngineOl2;
    return MapEngineOl2;
}());
