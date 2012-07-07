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
                visibility:  layerDef.configuration.visible &&
                                (layers.length > 0),
                singleTile:  !layerDef.configuration.tiled,
                attribution: layerDef.configuration.attribution,

                minScale:    layerDef.configuration.minScale,
                maxScale:    layerDef.configuration.maxScale
            };
            return mqLayerDef;
        },

        featureInfo: function(layer, x, y, callback) {
            if(layer.options.queryLayers.length === 0) {
                return;
            }
            var param_tmp = {
                REQUEST: 'GetFeatureInfo',
                VERSION: layer.olLayer.params.VERSION,
                EXCEPTIONS: "application/vnd.ogc.se_xml",
                FORMAT: layer.options.configuration.configuration.format,
                SRS: layer.olLayer.params.SRS,
                BBOX: layer.map.center().box.join(','),
                WIDTH: $(layer.map.element).width(),
                HEIGHT: $(layer.map.element).height(),
                X: x,
                Y: y,
                LAYERS: layer.options.queryLayers.join(','),
                QUERY_LAYERS: layer.options.queryLayers.join(',')
            };
            var contentType_ = "";
            if(typeof(layer.options.configuration.configuration.info_format)
                !== 'undefined'){
                param_tmp["INFO_FORMAT"] =
                    layer.options.configuration.configuration.info_format;
//                contentType_ +=
//                    layer.options.configuration.configuration.info_format;
            }
            if(typeof(layer.options.configuration.configuration.feature_count)
                !== 'undefined'){
                param_tmp["FEATURE_COUNT"] =
                    layer.options.configuration.configuration.feature_count;
            }
            if(typeof(layer.options.configuration.configuration.info_charset)
                !== 'undefined'){
                contentType_ += contentType_.length > 0 ? ";" : "" +
                    layer.options.configuration.configuration.info_charset;
            }
            var params = $.param(param_tmp);


            // this clever shit was taken from $.ajax
            requestUrl = layer.options.url;
            requestUrl += (/\?/.test(layer.options.url) ? '&' : '?') + params;

            $.ajax({
                url: Mapbender.configuration.application.urls.proxy,
                contentType: contentType_,
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

                    def.configuration.layers.push({
                        name: layer.name,
                        title: layer.title,
                        maxScale: layer.maxScale,
                        minScale: layer.minScale,
                        visible: true,
                        bbox: layer.bbox,
                        srs: layer.srs,
                        legend: legend,
                        metadataUrls: layer.metadataURLs,
                        queryable: layer.queryable
                    });
                });

                return def;
            } else {
                return null;
            }
        },

        /**
         * The Mapbender map object calls this function when a new layer is
         * added to the map in the context of the layer, e.g. "this" is a
         * MapQuery layer object.
         */
        onLoadStart: function() {
            this.olLayer.events.on({
                scope: this,
                loadstart: function() {
                    var scale = this.olLayer.map.getScale();
                    var layers = [];
                    $.each(this.options.allLayers, function(idx, layer) {
                        var show = true;
                        if(!((typeof layer.minScale !== 'undefined' && layer.minScale < scale) ||
                           (typeof layer.minScale !== 'undefined' && layer.maxScale > scale))) {
                            layers.push(layer.name);
                        }
                    });
                    this.olLayer.layers = layers;

                    // Prevent loading without layers
                    if(this.olLayer.layers.length === 0) {
                        this.olLayer.setVisibility(false);
                    }
                }
            });
        }
    }
}});

