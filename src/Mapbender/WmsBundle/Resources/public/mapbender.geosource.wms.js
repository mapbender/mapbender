if(window.OpenLayers) {
    /**
     * This suppresses broken requests from MapQuery layers that get stuck with a
     * constantly empty LAYERS=... param.
     *
     * @return {boolean} Whether the layer is in range or not
     */
    OpenLayers.Layer.WMS.prototype.calculateInRange = function(){
        if(!this.params.LAYERS || !this.params.LAYERS.length) {
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
    create: function(sourceDef, mangleIds){
        var rootLayer = sourceDef.configuration.children[0];
        if(sourceDef.configuration.status !== 'ok'){ //deactivate corrupte or unreachable sources
            rootLayer.options.treeOptions.selected = false;
            rootLayer.options.treeOptions.allow.selected = false;
        }
        var layerNames = [];

        Mapbender.Util.SourceTree.iterateLayers(sourceDef, false, function(layer, index, parents) {
            /* set unic id for a layer */
            if (mangleIds) {
                layer.options.origId = layer.options.id;
                layer.options.id = (parents[0] && parents[0].options || sourceDef).id + "_" + index;
            } else {
                if (!layer.options.origId && layer.options.id) {
                    layer.options.origId = layer.options.id;
                }
            }
            if (!layer.children) {
                layerNames.push(layer.options.name);
            }
        });

        Mapbender.Geo.layerOrderMap["" + sourceDef.id] = layerNames;
        var finalUrl = sourceDef.configuration.options.url;
        
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
        if (sourceDef.configuration.options.exception_format) {
            mqLayerDef.wms_parameters.exceptions = sourceDef.configuration.options.exception_format;
        }
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
            queryVisible: true,
            maxFeatures: 1000
        });
        wmsgfi.map = mqLayer.map.olMap;
        var reqObj = wmsgfi.buildWMSOptions(
            Mapbender.Util.removeProxy(mqLayer.olLayer.url),
            [mqLayer.olLayer],
            {x: x, y: y},
            mqLayer.olLayer.params.FORMAT
        );
        reqObj.params['LAYERS'] = reqObj.params['QUERY_LAYERS'] = mqLayer.olLayer.queryLayers;
        reqObj.params['STYLES'] = [];
        reqObj.params['EXCEPTIONS'] = mqLayer.source.configuration.options.exception_format;
        reqObj.params['INFO_FORMAT'] = mqLayer.source.configuration.options.info_format || 'text/html';
        var reqUrl = OpenLayers.Util.urlAppend(reqObj.url, OpenLayers.Util.getParameterString(reqObj.params || {}));
        return reqUrl;
    },
    getPrintConfig: function(layer, bounds) {
        var printConfig = {
            type: 'wms',
            url: Mapbender.Util.removeProxy(layer.getURL(bounds))
        };
        return printConfig;
    },
    getSingleLayerUrl: function(olLayer, bounds, layerName, styleName) {
        var baseUrl = this.getPrintConfig(olLayer, bounds).url;
        var params = OpenLayers.Util.getParameters(baseUrl);
        params = $.extend(OpenLayers.Util.upperCaseObject(params), {
            LAYERS: layerName,
            STYLES: styleName || ''
        });
        var rebuiltUrl = [baseUrl.split('?')[0], OpenLayers.Util.getParameterString(params)].join('?');
        return rebuiltUrl;
    }
});
Mapbender.source['wms'] = new Mapbender.Geo.WmsSourceHandler();
