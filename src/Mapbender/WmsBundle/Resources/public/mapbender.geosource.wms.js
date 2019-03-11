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
    create: function(sourceDef) {
        var rootLayer = sourceDef.configuration.children[0];

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
    featureInfoUrl: function(source, x, y) {
        var source_, olLayer_;
        if (source.source) {
            // An actual MapQuery layer
            console.warn("Deprecated call to featureInfoUrl with a MapQuery layer, pass in the source object instead");
            source_ = source.source;
            olLayer_ = source.olLayer;
        } else {
            olLayer_ = Mapbender.Model.getNativeLayer(source);
            source_ = source;
        }
        if (!(olLayer_ && olLayer_.getVisibility() && source_)) {
            return false;
        }
        var queryLayers = this.getLayerParameters(source_, {}).infolayers;
        if (!queryLayers.length) {
            return false;
        }
        var wmsgfi = new OpenLayers.Control.WMSGetFeatureInfo({
            url: Mapbender.Util.removeProxy(olLayer_.url),
            layers: [olLayer_],
            queryVisible: true,
            maxFeatures: 1000
        });
        wmsgfi.map = olLayer_.map;
        var reqObj = wmsgfi.buildWMSOptions(
            Mapbender.Util.removeProxy(olLayer_.url),
            [olLayer_],
            {x: x, y: y},
            olLayer_.params.FORMAT
        );
        reqObj.params['LAYERS'] = reqObj.params['QUERY_LAYERS'] = queryLayers;
        reqObj.params['STYLES'] = [];
        reqObj.params['EXCEPTIONS'] = source_.configuration.options.exception_format;
        reqObj.params['INFO_FORMAT'] = source_.configuration.options.info_format || 'text/html';
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
