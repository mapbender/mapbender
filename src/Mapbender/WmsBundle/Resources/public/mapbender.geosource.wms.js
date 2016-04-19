if(window.OpenLayers) {
    /**
     * This prevents OpenLayers making GetMap requests when the LAYER parameter is empty.
     *
     * This is done by adding a test to the in-range calculation which tests the length of
     * the layers parameter.
     *
     * @return {Boolean} Whether the layer is in range or not
     */
    OpenLayers.Layer.WMS.prototype.calculateInRange = function(){
        if(!this.params.LAYERS || 0 === this.params.LAYERS.length) {
            // explicitely hide DOM element for this layer
            this.display(false);
            return false;
        }
        return OpenLayers.Layer.prototype.calculateInRange.apply(this, arguments);
    }
}

Mapbender.Geo.WmsSourceHandler = Class({'extends': Mapbender.Geo.SourceHandler },{
    'private object defaultOptions': {
        type: 'wms',
        noMagic: true,
        transitionEffect: 'resize'
    },
    create: function(sourceDef){
        var self = this;
        var rootLayer = sourceDef.configuration.children[0];
        if(sourceDef.configuration.status !== 'ok'){ //deactivate corrupte or unreachable sources
            rootLayer.options.treeOptions.selected = false;
            rootLayer.options.treeOptions.allow.selected = false;
        }

        function _setProperties(layer, parent, id, num, proxy){
            /* set unic id for a layer */
            layer.options.origId = layer.options.id;
            layer.options.id = parent ? parent.options.id + "_" + num : id + "_" + num;
            if(proxy && layer.options.legend) {
                if(layer.options.legend.graphic) {
                    layer.options.legend.graphic = Mapbender.Util.addProxy(layer.options.legend.graphic);
                } else if(layer.options.legend.url) {
                    layer.options.legend.url = Mapbender.Util.addProxy(layer.options.legend.url);
                }
            }
            if(layer.children) {
                for(var i = 0; i < layer.children.length; i++) {
                    _setProperties(layer.children[i], layer, id, i, proxy);
                }
            }
        }
        _setProperties(rootLayer, null, sourceDef.id, 0, sourceDef.configuration.options.proxy);

        var finalUrl = sourceDef.configuration.options.url;

        if(sourceDef.configuration.options.proxy === true) {
            finalUrl = Mapbender.Util.addProxy(finalUrl);
        }

        var mqLayerDef = {
            type: 'wms',
            wms_parameters: {
                version: sourceDef.configuration.options.version
            },
            label: sourceDef.title,
            url: finalUrl,
            transparent: sourceDef.configuration.options.transparent,
            format: sourceDef.configuration.options.format,
            isBaseLayer: sourceDef.configuration.options.baselayer,
            opacity: sourceDef.configuration.options.opacity,
            visibility: sourceDef.configuration.options.visible,
            singleTile: !sourceDef.configuration.options.tiled,
            attribution: sourceDef.configuration.options.attribution, // attribution add !!!
            minScale: rootLayer.minScale,
            maxScale: rootLayer.maxScale,
            transitionEffect: 'resize',
            buffer: sourceDef.configuration.options.buffer ? parseInt(sourceDef.configuration.options.buffer) : 0, // int only for gridded mode
            ratio: sourceDef.configuration.options.ratio ? parseFloat(sourceDef.configuration.options.ratio) : 1.0 // float only for single-tile mode
        };
        $.extend(mqLayerDef, this.defaultOptions);
        return mqLayerDef;
    },
    featureInfoUrl: function(mqLayer, x, y){
        if(!mqLayer.visible() || mqLayer.olLayer.queryLayers.length === 0) {
            return false;
        }
        var wmsgfi = new OpenLayers.Control.WMSGetFeatureInfo({
            url: Mapbender.Util.removeProxy(mqLayer.olLayer.url), 
            layers: [mqLayer.olLayer],
            queryVisible: true
        });
        wmsgfi.map = mqLayer.map.olMap;
        var reqObj = wmsgfi.buildWMSOptions(
            Mapbender.Util.removeProxy(mqLayer.olLayer.url),
            [mqLayer.olLayer],
            {x: x, y: y},
            mqLayer.olLayer.params.FORMAT
        );
        reqObj.params['LAYERS'] = reqObj.params['QUERY_LAYERS'] = mqLayer.olLayer.queryLayers;
        reqObj.params['EXCEPTIONS'] = mqLayer.source.configuration.options.exception_format;
        var reqUrl = OpenLayers.Util.urlAppend(reqObj.url, OpenLayers.Util.getParameterString(reqObj.params || {}));
        return reqUrl;
    },

    createSourceDefinitions: function(xml, options){
        if(!options.global.defFormat) {
            options.global.defFormat = "image/png";
        }
        if(!options.global.defInfoformat) {
            options.global.defInfoformat = "text/html";
        }
        var parser = new OpenLayers.Format.WMSCapabilities(),
                capabilities = parser.read(xml);

        if(typeof (capabilities.capability) !== 'undefined') {
            var rootlayer = capabilities.capability.nestedLayers[0];
            var bboxOb = {}, bboxSrs = null, bboxBounds = null;
            for(var bbox in rootlayer.bbox) {
                if(options.model.getProj(bbox) !== null) {
                    bboxOb[bbox] = rootlayer.bbox[bbox].bbox;
                    bboxSrs = bbox;
                    bboxBounds = OpenLayers.Bounds.fromArray(bboxOb[bbox]);
                }
            }
            for(var srs in rootlayer.srs) {
                if(rootlayer.srs[srs] === true && typeof bboxOb[srs] === 'undefined' && options.model.getProj(
                        srs) !== null && bboxBounds !== null) {
                    var oldProj = options.model.getProj(bboxSrs);
                    bboxOb[srs] = bboxBounds.transform(oldProj, options.model.getProj(srs)).toArray();
                }
            }
            var format;
            var formats = capabilities.capability.request.getmap.formats;
            for(var i = 0; i < formats.length; i++) {
                if(formats[i].toLowerCase() === options.global.defFormat.toLowerCase()) {
                    format = formats[i];
                    break;
                }
            }
            if(!format)
                format = formats[0];

            var infoformat;
            var gfi = capabilities.capability.request.getfeatureinfo;
            if(gfi && gfi.formats && gfi.formats.length > 0) {
                for(var i = 0; i < gfi.formats.length; i++) {
                    if(gfi.formats[i].toLowerCase() === options.global.defInfoformat.toLowerCase()) {
                        infoformat = gfi.formats[i];
                        break;
                    }
                }
                if(!infoformat)
                    infoformat = gfi.formats[0];
            } else {
                infoformat = options.global.defInfoformat;
            }
            //@TODO srs list, srs by layer -> parent layer srs + layer srs
            var getmap = new Mapbender.Util.Url(capabilities.capability.request.getmap.get.href);
            getmap.username = options.gcurl.username;
            getmap.password = options.gcurl.password;
            var def = {
                type: 'wms',
                title: capabilities.service.title,
                configuration: {
                    isBaseSource: false,
                    options: {
                        version: capabilities.version,
                        bbox: bboxOb,
                        format: format,
                        info_format: infoformat,
                        opacity: 1,
                        proxy: false,
                        tiled: false,
                        transparent: true,
                        url: getmap.asString(),
                        visible: true
                    }
                }
            };

            function readCapabilities(layer, parent, options){
                // @ TODO getLegendGraphic ?
                var legend = null, minScale_ = null, maxScale_ = null;
                if(layer.styles.length !== 0 && layer.styles[layer.styles.length - 1].legend) {
                    legend = {};
                    // get style  from self or parent (layer.styles.length - 1)
                    legend.url = layer.styles[layer.styles.length - 1].legend.href;
                    legend.width = layer.styles[layer.styles.length - 1].legend.width;
                    legend.height = layer.styles[layer.styles.length - 1].legend.height;
                }
                minScale_ = layer.minScale ? Math.round(layer.minScale) : parent && parent.options.minScale
                        ? parent.options.minScale : null;
                maxScale_ = layer.maxScale ? Math.round(layer.maxScale) : parent && parent.options.maxScale
                        ? parent.options.maxScale : null;
                var def = {
                    options: {
                        legend: legend,
                        maxScale: minScale_, // inheritance replace
                        minScale: maxScale_, // inheritance replace
                        name: layer.name, // inheritance
                        queryable: layer.queryable,
                        style: layer.styles.length === 0 ? null : layer.styles[0].name, // inheritance add
                        title: layer.title,
                        treeOptions: {
                            allow: {
                                info: layer.queryable ? true : false,
                                reorder: true,
                                selected: true,
                                toggle: layer.nestedLayers.length === 0 ? null : true
                            },
                            info: layer.queryable ? true : null,
                            selected: true,
                            toggle: layer.nestedLayers.length === 0 ? null : false
                        }
                    },
                    state: {
                        info: null,
                        outOfBounds: null,
                        outOfScale: false,
                        visibility: true
                    }
                };
                $.extend(true, def.options, options.global.options);
                if(options.layers[def.options.name])
                    $.extend(true, def.options, options.layers[def.options.name].options);
                if(layer.nestedLayers.length > 0) {
                    def.children = [];
                    for(var i = 0; i < layer.nestedLayers.length; i++) {
                        var child = readCapabilities(layer.nestedLayers[i], def, options);
                        if(child.options.treeOptions.selected)
                            def.options.treeOptions.selected = child.options.treeOptions.selected;
                        def.children.push(child);
                    }
                }
                return def;
            }
            function getSplitted(service, rootLayer, layer, result, num){

                if(num !== 0) {
                    var service_new = $.extend(true, {}, service);
                    var root_new = $.extend(true, {}, rootLayer);
                    var layer_new = $.extend(true, {}, layer);
                    root_new.options.title = layer_new.options.title + " (" + root_new.options.title + ")";
                    if(layer_new.children)
                        delete(layer_new.children);
                    root_new.children = [layer_new];
                    service_new.configuration.children = [root_new];
                    return service_new;
                }
                if(layer.children) {
                    for(var i = 0; i < layer.children.length; i++) {
                        num++;
                        result.push(getSplitted(service, rootLayer, layer.children[i], result, num));
                    }
                }
            }
            var layers = readCapabilities(capabilities.capability.nestedLayers[0], null, options);
            if(options.global.splitLayers) {
                var result = [];
                getSplitted(def, layers, layers, result, 0);
                return result;
            } else {
                def.configuration.children = [layers];
                return [def];
            }
        } else {
            return null;
        }
    },
    getPrintConfig: function(layer, bounds, isProxy){
        var printConfig = {
            type: 'wms',
            url: isProxy ? Mapbender.Util.removeProxy(layer.getURL(bounds)) : layer.getURL(bounds)
        };
        return printConfig;
    }
});
Mapbender.source['wms'] = new Mapbender.Geo.WmsSourceHandler();
