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
        var layer = this.findLayerEpsg(sourceOpts, proj.projCode);
        if (!layer) { // find first layer with epsg from srs list to initialize.
            var allsrs = Mapbender.Model.getAllSrs();
            for (var i = 0; i < allsrs.length; i++) {
                layer = this.findLayerEpsg(sourceOpts, allsrs[i].name);
                if (layer) {
                    break;
                }
            }
        }
        rootLayer['children'] = [layer];
        var layerOptions = this._createLayerOptions(sourceOpts, layer, proj);
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
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @return {{matrixSet: string, matrixIds: any[]}}
     * @private
     */
    _getMatrixOptions: function(sourceDef, layer, projection) {
        var matrixSet = this._getLayerMatrixSet(sourceDef, layer);
        var matrixIds = matrixSet.tilematrices.map(function(matrix) {
            if (matrix.topLeftCorner) {
                return $.extend({}, matrix, {
                    topLeftCorner: OpenLayers.LonLat.fromArray(matrix.topLeftCorner)
                });
            } else {
                return $.extend({}, matrix);
            }
        });
        return {
            matrixSet: matrixSet.identifier,
            matrixIds: matrixIds,
            serverResolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                var projectionUnits = projection.proj.units;
                // OGC TileMatrix scaleDenom is calculated using meters, irrespective of projection units
                // OGC TileMatrix scaleDenom is also calculated assuming 0.28mm per pixel
                // Undo both these unproductive assumptions and calculate a proper resolutiion for the
                // current projection
                var metersPerUnit = OpenLayers.INCHES_PER_UNIT['mUnits'] * OpenLayers.METERS_PER_INCH;
                if (projectionUnits === 'm' || projectionUnits === 'Meter') {
                    metersPerUnit = 1.0;
                }
                var unitsPerPixel = 0.00028 / metersPerUnit;
                return tileMatrix.scaleDenominator * unitsPerPixel;
            })
        };
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {OpenLayers.Projection} projection
     * @return {*}
     */
    _createLayerOptions: function(sourceDef, layer, projection) {
        var layerOptions = $.extend(this._getMatrixOptions(sourceDef, layer, projection), {
            label: layer.options.title,
            layer: layer.options.identifier,
            format: layer.options.format,
            style: layer.options.style,
            url: layer.options.url,
            tileOriginCorner: 'tl',
            maxExtent: this._getMaxExtent(layer, projection)
        });
        return layerOptions;
    },
    /**
     * @param {WmtsLayerConfig} layer
     * @param {OpenLayers.Projection} projection
     * @return {OpenLayers.Bounds|null}
     * @private
     */
    _getMaxExtent: function(layer, projection) {
        var projCode = projection.projCode;
        if (layer.options.bbox[projCode]) {
            return OpenLayers.Bounds.fromArray(layer.options.bbox[projCode]);
        } else {
            var bboxSrses = Object.keys(layer.options.bbox);
            for (var i = 0 ; i < bboxSrses.length; ++i) {
                var bboxSrs = bboxSrses[i];
                var bboxArray = layer.options.bbox[bboxSrs];
                return OpenLayers.Bounds.fromArray(bboxArray).transform(
                    Mapbender.Model.getProj(bboxSrs),
                    projection
                );
            }
        }
        return null;
    },
    findLayerEpsg: function(sourceDef, epsg) {
        var layers = sourceDef.configuration.layers;
        for (var i = 0; i < layers.length; i++) {
            var tileMatrixSet = this._getLayerMatrixSet(sourceDef, layers[i]);
            if (epsg === this.urnToEpsg(tileMatrixSet.supportedCrs)) {
                return layers[i];
            }
        }
        return null;
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layerDef
     * @return {WmtsTileMatrixSet|null}
     */
    _getLayerMatrixSet: function(sourceDef, layerDef) {
        var matrixSets = sourceDef.configuration.tilematrixsets;
        for(var i = 0; i < matrixSets.length; i++){
            if (layerDef.options.tilematrixset === matrixSets[i].identifier){
                return matrixSets[i];
            }
        }
        return null;
    },
    /**
     * @param {string} urnOrEpsgIdentifier
     * @return {string}
     */
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
            matrixset: $.extend({}, this._getLayerMatrixSet(source[0], wmtslayer.layer)),
            zoom: Mapbender.Model.getZoomFromScale(scale)
        };
        return printConfig;
    },
    'public function changeProjection': function(source, projection) {
        var layer = this.findLayerEpsg(source, projection.projCode);
        if (layer) {
            var olLayer = Mapbender.Model.getNativeLayer(source);
            var matrixOptions = this._getMatrixOptions(source, layer, projection);
            var newLayerOptions = $.extend(matrixOptions, {
                maxExtent: this._getMaxExtent(layer, projection)
            });
            $.extend(olLayer, newLayerOptions);
            olLayer.updateMatrixProperties();
        }
    }
});
Mapbender.source['wmts'] = new Mapbender.Geo.WmtsSourceHandler();
