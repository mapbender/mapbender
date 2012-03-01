var Mapbender = Mapbender || {};
$.extend(true, Mapbender, { layer: {
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

            var finalUrl = layerDef.configuration.url;

            if(layerDef.configuration.proxy === true) {
                finalUrl = OpenLayers.ProxyHost + finalUrl;
            }

            mqLayerDef = {
                type:        'wms',
                label:       layerDef.configuration.title,
                url:         finalUrl,

                layers:      layers,
                queryLayers: queryLayers,
                allLayers:   layerDef.configuration.layers,

                transparent: layerDef.configuration.transparent,
                format:      layerDef.configuration.format,

                isBaseLayer: layerDef.configuration.baselayer,
                opacity:     layerDef.configuration.opacity,
                visibility:  layerDef.configuration.visible,
                singleTile:  !layerDef.configuration.tiled,
                attribution: layerDef.configuration.attribution
            };
            return mqLayerDef;
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
        },

        loadFromUrl: function(url) {
            var dlg = $('<div></div>').attr('id', 'loadfromurl-wms'),
                spinner = $('<img />')
                    .attr('src', Mapbender.configuration.assetPath + 'bundles/mapbenderwms/images/spinner.gif')
                    .appendTo(dlg);
            dlg.appendTo($('body'));

            $('<script></type')
                .attr('type', 'text/javascript')
                .attr('src', Mapbender.configuration.assetPath + 'bundles/mapbenderwms/mapbender.layer.wms.loadfromurl.js')
                .appendTo($('body'));
        },

        layersFromCapabilities: function(xml) {
            var parser = new OpenLayers.Format.WMSCapabilities(),
                capabilities = parser.read(xml);

            if(typeof(capabilities.capability) !== 'undefined') {
                var queryLayers = [];
                var def = {
                        type: 'wms',
                        configuration: {
                            title: capabilities.service.title,
                            url: capabilities.capability.request.getmap.get.href,

                            transparent: true,
                            format: capabilities.capability.request.getmap.formats[0],

                            baselayer: false,
                            opacity: 100,
                            tiled: false,

                            layers: []
                        }
                    };

                var layers = $.map(capabilities.capability.layers, function(layer, idx) {
                    var legend = null;
                    if(layer.styles && layer.styles.length > 0 && layer.styles[0].legend) {
                        legend = layer.styles[0].legend.href;
                    }

                    if(layer.queryable === true) {
                        queryLayers.push(layer.title);
                    }

                    def.configuration.layers.push({
                        name: layer.name,
                        title: layer.title,
                        maxScale: layer.maxScale,
                        minScale: layer.minScale,
                        visible: true,
                        bbox: layer.bbox,
                        srs: layer.srs,
                        legend: legend,
                        metadataUrls: layer.metadataURLs
                    });
                });

                def.queryLayers = queryLayers;
                return def;
            } else {
                return null;
            }
        }
    }
}});

