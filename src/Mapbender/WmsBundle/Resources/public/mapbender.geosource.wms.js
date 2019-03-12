window.Mapbender = $.extend(Mapbender || {}, (function() {
    function WmsSource(definition) {
        Mapbender.Source.apply(this, arguments);
        this.customParams = {};
        if (definition.customParams) {
            $.extend(this.customParams, definition.customParams);
        }
    }
    WmsSource.prototype = Object.create(Mapbender.Source.prototype);
    WmsSource.prototype.constructor = WmsSource;
    function WmsSourceLayer() {
        Mapbender.SourceLayer.apply(this, arguments);
    }
    WmsSourceLayer.prototype = Object.create(Mapbender.SourceLayer.prototype);
    WmsSourceLayer.prototype.constructor = WmsSourceLayer;
    Mapbender.Source.typeMap['wms'] = WmsSource;
    Mapbender.SourceLayer.typeMap['wms'] = WmsSourceLayer;
    $.extend(WmsSource.prototype, {
        // We must remember custom params for serialization in getMapState()...
        customParams: {},
        // ... but we will not remember the following ~standard WMS params the same way
        _runtimeParams: ['LAYERS', 'STYLES', 'EXCEPTIONS', 'QUERY_LAYERS', 'INFO_FORMAT', '_OLSALT'],
        initializeLayers: function() {
            var options = this.getNativeLayerOptions();
            var params = this.getNativeLayerParams();
            var url = this.configuration.options.url;
            var name = this.title;
            var olLayer = new OpenLayers.Layer.WMS(name, url, params, options);
            this.nativeLayers = [olLayer];
            this.ollid = olLayer.id;
            return this.nativeLayers;
        },
        getNativeLayerOptions: function() {
            var rootLayer = this.configuration.children[0];
            var bufferConfig = this.configuration.options.buffer;
            var ratioConfig = this.configuration.options.ratio;
            var opts = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                visibility: this.configuration.options.visible,
                singleTile: !this.configuration.options.tiled,
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
        },
        getNativeLayerParams: function() {
            var params = $.extend({}, this.customParams, {
                transparent: this.configuration.options.transparent,
                format: this.configuration.options.format,
                version: this.configuration.options.version
            });
            var exceptionFormatConfig = this.configuration.options.exception_format;
            if (exceptionFormatConfig) {
                params.exceptions = exceptionFormatConfig;
            }
            return params;
        },
        addParams: function(params) {
            for (var i = 0; i < this.nativeLayers.length; ++i) {
                this.nativeLayers[i].mergeNewParams(params);
            }
            var rtp = this._runtimeParams;
            $.extend(this.customParams, _.omit(params, function(value, key) {
                return -1 !== rtp.indexOf(('' + key).toUpperCase());
            }));
        },
        removeParams: function(names) {
            // setting a param to null effectively removes it from the generated URL
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Util.js#L514
            // see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Layer/HTTPRequest.js#L197
            var nullParams = _.object(names, names.map(function() {
                return null;
            }));
            this.addParams(nullParams);
        },
        toJSON: function() {
            var s = Mapbender.Source.prototype.toJSON.apply(this, arguments);
            s.customParams = this.customParams;
            return s;
        }
    });
    return {
        WmsSource: WmsSource,
        WmsSourceLayer: WmsSourceLayer
    };
}()));

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
    changeProjection: function(source, projection) {
        // do not handle
        return undefined;
    },
    getMaxExtent: function(source, projection, layer) {
        var confSource;
        if (layer) {
            confSource = layer.options.bbox;
        } else {
            confSource = source.configuration.options.bbox;
        }
        var projCode = projection.projCode;
        if (confSource[projCode]) {
            return OpenLayers.Bounds.fromArray(confSource[projCode]);
        } else {
            var projKeys = Object.keys(confSource);
            for (var i = 0; i < projKeys.length; ++i) {
                var nextProj = Mapbender.Model.getProj(projKeys[i]);
                if (nextProj) {
                    var newExtent = OpenLayers.Bounds.fromArray(confSource[nextProj]);
                    newExtent = Mapbender.Model._transformExtent(newExtent, nextProj, projection);
                    // Reprojection wide EPSG:4326 range to local systems can produce completely
                    // invalid extents. Check for that and avoid returning them.
                    if (newExtent.right > newExtent.left && newExtent.top > newExtent.bottom) {
                        return newExtent;
                    }
                }
            }
        }
        return null;
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
