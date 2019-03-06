/**
 * Tms Source Handler
 * @author Paul Schmidt
 */
Mapbender.Geo.TmsSourceHandler = Class({
    'extends': Mapbender.Geo.SourceTmsWmtsCommon
}, {
    /**
     * @param {WmtsLayerConfig} layer
     * @param {WmtsTileMatrixSet} matrixSet
     * @param {OpenLayers.Projection} projection
     */
    _getMatrixOptions: function(layer, matrixSet, projection) {
        var self = this;
        var options = {
            layername: layer.options.identifier,
            tileSize: new OpenLayers.Size(matrixSet.tileSize[0], matrixSet.tileSize[1]),
            serverResolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                return self._getMatrixResolution(tileMatrix, projection);
            })
        };
        if (matrixSet.origin && matrixSet.origin.length) {
            options.tileOrigin = new OpenLayers.LonLat(matrixSet.origin[0], matrixSet.origin[1]);
        }
        return options;
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {WmtsTileMatrixSet} matrixSet
     * @param {OpenLayers.Projection} projection
     * @return {*}
     */
    _createLayerOptions: function(sourceDef, layer, matrixSet, projection) {
        return $.extend(this.super('_createLayerOptions', sourceDef, layer, matrixSet, projection), {
            layername: layer.options.identifier,
            serviceVersion: sourceDef.configuration.version,
            tileSize: new OpenLayers.Size(matrixSet.tileSize[0], matrixSet.tileSize[1])
        });
        return layerOptions;
    },
    /**
     * @param {WmtsTileMatrix} tileMatrix
     * @param {OpenLayers.Projection} projection
     * @return {Number}
     * @private
     */
    _getMatrixResolution: function(tileMatrix, projection) {
        // Yes, seriously, it's called scaleDenominator but it's the resolution
        // @todo: resolve backend config wording weirdness
        return tileMatrix.scaleDenominator;
    },
    'public function featureInfoUrl': function(mqLayer, x, y) {
    },
    /**
     * @param {Object} sourceDef
     * @param {WmtsLayerConfig} layerDef
     * @return {string}
     * @private
     */
    _getPrintBaseUrl: function(sourceDef, layerDef) {
        return [layerDef.options.tileUrls[0], sourceDef.configuration.version, '/', layerDef.options.identifier].join('');
    }
});
Mapbender.source['tms'] = new Mapbender.Geo.TmsSourceHandler();

$.MapQuery.Layer.types['tms'] = function(options) {
    var label = options.label;
    var url = options.url;
    var params = {
        layername: options.layername,
        type: options.format.split('/').pop(),
        tileOrigin: options.tileOrigin,
        tileSize: options.tileSize,
        isBaseLayer: options.isBaseLayer,
        serviceVersion: options.serviceVersion,
        serverResolutions: options.serverResolutions
    };
    return {
        layer: new OpenLayers.Layer.TMS(label, url, params),
        options: options
    };
};
