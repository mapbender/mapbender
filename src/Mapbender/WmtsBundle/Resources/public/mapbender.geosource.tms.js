/**
 * Tms Source Handler
 * @author Paul Schmidt
 */
Mapbender.Geo.TmsSourceHandler = Class({
    'extends': Mapbender.Geo.SourceHandler
}, {
    'private string layerNameIdent': 'identifier',
    'public function create': function(sourceOpts) {
        var rootLayer = sourceOpts.configuration.children[0];
        function _setProperties(layer, parent, id, num, proxy) {
            if (proxy && layer.options.legend) {
                if (layer.options.legend.graphic) {
                    layer.options.legend.graphic = Mapbender.Util.addProxy(layer.options.legend.graphic);
                } else if (layer.options.legend.url) {
                    layer.options.legend.url = Mapbender.Util.addProxy(layer.options.legend.url);
                }
            }
            if (layer.children) {
                for (var i = 0; i < layer.children.length; i++) {
                    _setProperties(layer.children[i], layer, id, i, proxy);
                }
            }
        }
        _setProperties(rootLayer, null, sourceOpts.id, 0, sourceOpts.configuration.options.proxy);

        var proj = Mapbender.Model.getCurrentProj();
        var layer = this.findLayerEpsg(sourceOpts.configuration.layers, proj.projCode, true);
        if (!layer) { // find first layer with epsg from srs list to initialize.
            var allsrs = Mapbender.Model.getAllSrs();
            for (var i = 0; i < allsrs.length; i++) {
                layer = this.findLayerEpsg(sourceOpts.configuration.layers, allsrs[i].name, true);
                if (layer) {
                    break;
                }
            }
        }
        rootLayer['children'] = [layer];

        var layerOptions = this._createLayerOptions(layer, sourceOpts.configuration.options.proxy, null);
        // hide layer without start srs -> remove name
        var mqLayerDef = {
            type: 'tms',
            isBaseLayer: false,
            opacity: sourceOpts.configuration.options.opacity,
            visible: sourceOpts.configuration.options.visible,
            attribution: sourceOpts.configuration.options.attribution
        };
        $.extend(layerOptions, mqLayerDef);
        return layerOptions;
    },
    'public function postCreate': function(source, mqLayer) {
        this.changeProjection(source, Mapbender.Model.getCurrentProj());
    },
    _createLayerOptions: function(layer, proxy, olLayer) {
        var layerOptions = {
            label: layer.options.title,
            layer: layer.options.identifier,
            tileOrigin: OpenLayers.LonLat.fromArray(layer.options.tilematrixset.origin),
            tileSize:
                new OpenLayers.Size(layer.options.tilematrixset.tileSize[0], layer.options.tilematrixset.tileSize[1]),
            url: proxy ? Mapbender.Util.addProxy(layer.options.url) : layer.options.url
        };
        if (olLayer) {
            layerOptions['format'] = olLayer.format === layer.options.format ? olLayer.format : layer.options.format;
            layerOptions['formatSuffix'] = olLayer.format === layer.options.format ? olLayer.formatSuffix
                : layer.options.format.substring(layer.options.format.indexOf('/') + 1);
            layerOptions['params'] = {
                LAYERS: [layer.options.identifier]
            };
        }
        return layerOptions;
    },
    'private function findLayerEpsg': function(layers, epsg, clone) {
        for (var i = 0; i < layers.length; i++) {
            if (epsg === layers[i].options.tilematrixset.supportedCrs) {
                return clone ? $.extend(true, {}, layers[i]) : layers[i];
            }
        }
        return null;
    },
    'public function featureInfoUrl': function(mqLayer, x, y) {

    },
    'public function getPrintConfig': function(layer, bounds, scale, isProxy) {
        var source = Mapbender.Model.findSource({ollid: layer.id});
        var tmslayer = this.findLayer(source[0], {identifier:layer.layername});
        var url = layer.url + '1.0.0/' + layer.layername;
        var printConfig = {
            type: 'tms',
            url: isProxy ? Mapbender.Util.removeProxy(url) : url,
            options: tmslayer.layer.options,
            zoom: Mapbender.Model.getZoomFromScale(scale)
        };
        return printConfig;
    },
    'public function changeProjection': function(source, projection) {
        var layer = this.findLayerEpsg(source.configuration.layers, projection.projCode, true);
        if (layer) {
            var olLayer = Mapbender.Model.getNativeLayer(source);
            var layerOptions = this._createLayerOptions(layer, source.configuration.options.proxy, olLayer);
            $.extend(olLayer, layerOptions);
        }
    }
});
Mapbender.source['tms'] = new Mapbender.Geo.TmsSourceHandler();

if ($.MapQuery.Layer.types['tms']) {
    $.MapQuery.Layer.types['tms'] = function(options) {
        var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
            $.fn.mapQuery.defaults.layer.tms,
            options);
        var label = options.label || undefined;
        var url = options.url || undefined;
        var params = {
            layername: o.layer,
            type: o.format,
            tileOrigin: o.tileOrigin,
            isBaseLayer: o.isBaseLayer
        };
        return {
            layer: new OpenLayers.Layer.TMS(label, url, params),
            options: o
        };
    }
}
