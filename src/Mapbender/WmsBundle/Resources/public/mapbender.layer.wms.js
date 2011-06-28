Mapbender.layer = $.extend(Mapbender.layer, {
    'wms': {
        featureInfo: function(layer, x, y, callback) {
            var queryLayers = layer.options.queryLayers ?
                layer.options.queryLayers :
                layer.options.layers;

            var params = $.param({
                REQUEST: 'GetFeatureInfo',
                VERSION: layer.olLayer.params.VERSION,
                EXCEPTIONS: "application/vnd.ogc.se_xml",
                SRS: layer.olLayer.params.SRS,
                BBOX: layer.map.goto().box.join(','),
                WIDTH: $(layer.map.element).width(),
                HEIGHT: $(layer.map.element).height(),
                X: x,
                Y: y,
                LAYERS: queryLayers,
                QUERY_LAYERS: queryLayers
            });
            // this clever shit was taken from $.ajax
            requestUrl = layer.options.url;
            requestUrl += (/\?/.test(layer.options.url) ? '&' : '?') + params;

            $.ajax({
                url: Mapbender.configuration.proxies.open,
                data: {
                    url: requestUrl
                },
                success: function(data) {
                    callback({
                        layerId: layer.id,
                        response: data
                    });
                },
                error: function(error) {
                    callback({
                        layerId: layer.id,
                        response: 'ERROR'
                    });
                }
            });
        }
    }
});

