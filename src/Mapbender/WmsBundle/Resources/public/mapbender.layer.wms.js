Mapbender.layer = $.extend(Mapbender.layer, {
    'wms': {
        create: function(layerDef) {
            var layers = [];
            var queryLayers = [];
            $.each(layerDef.configuration.layers, function(idx, layer) {
                var layerDef = $.extend({},
                    { visible: true, queryable: false }, layer );
                if(layerDef.visible) {
                    layers.push(layerDef.name);
                }
                if(layerDef.queryable) {
                    queryLayers.push(layerDef.name);
                }
            });

            mqLayerDef = {
                type:        'wms',
                label:       layerDef.configuration.title,
                url:         layerDef.configuration.url,

                layers:      layers,
                queryLayers: queryLayers,
                allLayers:   layerDef.configuration.layers,

                transparent: layerDef.configuration.transparent,
                format:      layerDef.configuration.format,

                isBaseLayer: layerDef.configuration.baselayer,
                opacity:     layerDef.configuration.opacity,
                visible:     layerDef.configuration.visible,
                singleTile:  !layerDef.configuration.tiled
            };
            return mqLayerDef
        },

        featureInfo: function(layer, x, y, callback) {
            var queryLayers = layer.options.queryLayers ?
                layer.options.queryLayers :
                layer.options.layers;

            var params = $.param({
                REQUEST: 'GetFeatureInfo',
                VERSION: layer.olLayer.params.VERSION,
                EXCEPTIONS: "application/vnd.ogc.se_xml",
                SRS: layer.olLayer.params.SRS,
                BBOX: layer.map.center().box.join(','),
                WIDTH: $(layer.map.element).width(),
                HEIGHT: $(layer.map.element).height(),
                X: x,
                Y: y,
                LAYERS: queryLayers.join(','),
                QUERY_LAYERS: queryLayers.join(',')
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

