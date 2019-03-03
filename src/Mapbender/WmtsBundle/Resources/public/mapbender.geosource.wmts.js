/**
 * @typedef {Object} WmtsTileMatrix
 * @property {string} identifier
 * @property {Number} scaleDenominator
 * @property {int} tileWidth
 * @property {int} tileHeight
 * @property {Array<float>} topLeftCorner
 * @property {Array<int>} matrixSize
 */

/**
 * @typedef {Object} WmtsTileMatrixSet
 * @property {string} id
 * @property {Array<Number>} tileSize
 * @property {string} identifier
 * @property {string} supportedCrs
 * @property {Array<Number>} origin
 * @property {WmtsTileMatrix[]} tilematrices
 */

/**
 * @typedef {Object} WmtsLayerConfig
 * @property {Object} options
 * @property {string} options.tilematrixset
 */

/**
 * @typedef {Object} WmtsSourceConfig
 * @property {string} type
 * @property {string} title
 * @property {Object} configuration
 * @property {string} configuration.type
 * @property {string} configuration.title
 * @property {boolean} configuration.isBaseSource
 * @property {Object} configuration.options
 * @property {boolean} configuration.options.proxy
 * @property {boolean} configuration.options.visible
 * @property {Number} configuration.options.opacity
 * @property {Array.<WmtsLayerConfig>} configuration.layers
 * @property {Array.<WmtsTileMatrixSet>} configuration.tilematrixsets
 */

Mapbender.Geo.WmtsSourceHandler = Class({'extends': Mapbender.Geo.SourceHandler },{
    'private string layerNameIdent': 'identifier',
    'public function create': function(sourceOpts) {
        var rootLayer = sourceOpts.configuration.children[0];
        var proj = Mapbender.Model.getCurrentProj();
        var layer = this.findLayerEpsg(sourceOpts.configuration.layers,
            sourceOpts.configuration.tilematrixsets, proj.projCode, true);
        if (!layer) { // find first layer with epsg from srs list to initialize.
            var allsrs = Mapbender.Model.getAllSrs();
            for (var i = 0; i < allsrs.length; i++) {
                layer = this.findLayerEpsg(sourceOpts.configuration.layers,
                    sourceOpts.configuration.tilematrixsets, allsrs[i].name, true);
                if (layer) {
                    break;
                }
            }
        }
        rootLayer['children'] = [layer];
        var layerOptions = this._createLayerOptions(layer, sourceOpts.configuration.tilematrixsets);
        var mqLayerDef = {
            type: 'wmts',
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
    _getMatrixOptions: function(layer, matrixsets) {
        var matrixset = this.findMatrixSetIdent(matrixsets, layer.options.tilematrixset, null, true);
        var tileFullExtent = null;
        var supportedCrs = this.urnToEpsg(matrixset.supportedCrs);
        if(layer.options.bbox[supportedCrs]){
            tileFullExtent =
                OpenLayers.Bounds.fromArray(layer.options.bbox[supportedCrs]);
        } else {
            var bboxSrses = Object.keys(layer.options.bbox);
            for (var i = 0 ; i < bboxSrses.length; ++i) {
                var bboxSrs = bboxSrses[i];
                var bboxArray = layer.options.bbox[bboxSrs];
                tileFullExtent = OpenLayers.Bounds.fromArray(bboxArray).transform(
                    Mapbender.Model.getProj(bboxSrs),
                    Mapbender.Model.getProj(supportedCrs)
                );
                break;
            }
        }
        return {
            matrixSet: matrixset.identifier,
            matrixIds: matrixset.tilematrices,
            tileOrigin: OpenLayers.LonLat.fromArray(matrixset.origin),
            tileSize: new OpenLayers.Size(matrixset.tileSize[0], matrixset.tileSize[1]),
            tileFullExtent: tileFullExtent
        };
    },
    _createLayerOptions: function(layer, matrixsets) {
        var layerOptions = $.extend(this._getMatrixOptions(layer, matrixsets), {
            label: layer.options.title,
            layer: layer.options.identifier,
            format: layer.options.format,
            style: layer.options.style,
            url: layer.options.url
        });
        return layerOptions;
    },
    findLayerEpsg: function(layers, matrixSets, epsg, clone){
        var matrixSetMap = this.findMatrixSetEpsg(matrixSets, epsg, clone);
        for (var i = 0; i < layers.length; i++) {
            if(matrixSetMap[layers[i].options.tilematrixset]){
                return clone ? $.extend(true, {}, layers[i]) : layers[i];
            }
        }
        return null;
    },
    findMatrixSetEpsg: function(matrixSets, epsg, clone){
        var matrixsets = {};
        for(var i = 0; i < matrixSets.length; i++){
            var supportedCrs = this.urnToEpsg(matrixSets[i].supportedCrs);
            if(epsg === supportedCrs){
                matrixsets[matrixSets[i].identifier] = clone ? $.extend(true, {}, matrixSets[i]) : matrixSets[i];
            }
        }
        return matrixsets;
    },
    findMatrixSetIdent: function(matrixSets, identifier, clone){
        for(var i = 0; i < matrixSets.length; i++){
            if(identifier === matrixSets[i].identifier){
                return clone ? $.extend(true, {}, matrixSets[i]) : matrixSets[i];
            }
        }
        return null;
    },
    urnToEpsg: function(urnOrEpsgIdentifier) {
        // @todo: drop URNs server-side, they offer no benefit here
        return urnOrEpsgIdentifier.replace(/^urn:.*?(\d+)$/, 'EPSG:$1');
    },
    'public function featureInfoUrl': function(mqLayer, x, y) {
        if(!mqLayer.visible() || mqLayer.olLayer.queryLayers.length === 0) {
            return false;
        }
        var j = 0; // find Row index of a pixel in the tile -> from x
        var i = 0; // Column index of a pixel in the tile -> y
        var tilerow = 0; // find Row index of tile matrix
        var tilecol = 0; // find Column index of tile matrix
        Mapbender.error('GetFeatureInfo for WMTS is not yet implemented');
        return;
        var param_tmp = {
            SERVICE: 'WMTS',
            REQUEST: 'GetFeatureInfo',
            VERSION: '1.0.0',//
            LAYER: mqLayer.olLayer.layer, //
            STYLE: mqLayer.olLayer.style, // 
            FORMAT: mqLayer.olLayer.format,
            INFO_FORMAT: mqLayer.source.configuration.options.info_format || "application/gml+xml; version=3.1",
            TILEMATRIXSET: mqLayer.olLayer.matrixSet,
            TILEMATRIX: mqLayer.olLayer.getMatrix()['identigier'],
            TILEROW: tilerow,
            TILECOL: tilecol,
            J: j,
            I: i
        };
        var params = $.param(param_tmp);
        // this clever shit was taken from $.ajax
        var requestUrl = Mapbender.Util.removeProxy(mqLayer.olLayer.url);
        requestUrl += (/\?/.test(mqLayer.options.url) ? '&' : '?') + params;
        return requestUrl;
    },
    'public function getPrintConfig': function(layer, bounds, scale, isProxy) {
        var source = Mapbender.Model.findSource({ollid: layer.id});
        var wmtslayer = this.findLayer(source[0], {identifier:layer.layer});
        var url = wmtslayer.layer.options.url;
        var printConfig = {
            type: 'wmts',
            url: isProxy ? Mapbender.Util.removeProxy(url) : url,
            options: wmtslayer.layer.options,
            matrixset: this.findMatrixSetIdent(source[0].configuration.tilematrixsets, wmtslayer.layer.options.tilematrixset, true),
            zoom: Mapbender.Model.getZoomFromScale(scale)
        };
        return printConfig;
    },
    'public function changeProjection': function(source, projection) {
        var layer = this.findLayerEpsg(source.configuration.layers,
            source.configuration.tilematrixsets, projection.projCode, true);
        if(layer){
            var olLayer = Mapbender.Model.getNativeLayer(source);
            var layerOptions = this._createLayerOptions(layer, source.configuration.tilematrixsets);
            $.extend(olLayer, layerOptions);
            olLayer.updateMatrixProperties();
        }
    }
});
Mapbender.source['wmts'] = new Mapbender.Geo.WmtsSourceHandler();
