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
 * @property {Array<String>} options.tileUrls
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


/**
 * Base class for TMS and WMTS geosources
 */
Mapbender.Geo.SourceTmsWmtsCommon = Class({
    'extends': Mapbender.Geo.SourceHandler
}, {
    'private string layerNameIdent': 'identifier',
    create: function(sourceOpts) {
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
        var matrixSet = this._getLayerMatrixSet(sourceOpts, layer);
        var layerOptions = this._createLayerOptions(sourceOpts, layer, matrixSet, proj);
        var mqLayerDef = {
            type: sourceOpts.configuration.type,
            isBaseLayer: false,
            opacity: sourceOpts.configuration.options.opacity,
            visible: sourceOpts.configuration.options.visible,
            attribution: sourceOpts.configuration.options.attribution
        };
        $.extend(layerOptions, mqLayerDef);
        sourceOpts.currentActiveLayer = layer;
        return layerOptions;
    },
    _createLayerOptions: function(sourceDef, layerDef, matrixSet, projection) {
        var matrixOptions = this._getMatrixOptions(layerDef, matrixSet, projection);
        return $.extend(matrixOptions, {
            label: layerDef.options.title,
            url: layerDef.options.tileUrls,
            format: layerDef.options.format
        });
    },
    getPrintConfigEx: function(source, bounds, scale, projection) {
        var layerDef = this.findLayerEpsg(source, projection.projCode);
        if (!layerDef.state.visibility) {
            return [];
        }
        var matrix = this._getMatrix(source, layerDef, scale, projection);
        return [
            {
                url: Mapbender.Util.removeProxy(this._getPrintBaseUrl(source, layerDef)),
                matrix: $.extend({}, matrix),
                resolution: this._getMatrixResolution(matrix, projection)
            }
        ];
    },
    changeProjection: function(source, projection) {
        var layer = this.findLayerEpsg(source, projection.projCode);
        var matrixSet = layer && this._getLayerMatrixSet(source, layer);
        var olLayer = layer && Mapbender.Model.getNativeLayer(source);
        if (layer && olLayer && matrixSet) {
            var matrixOptions = this._getMatrixOptions(layer, matrixSet, projection);
            source.currentActiveLayer = layer;
            $.extend(olLayer, matrixOptions);
            return true;
        } else {
            source.currentActiveLayer = null;
            return false;
        }
    },
    'public function getPrintConfig': function(olLayer, bounds) {
        throw new Error("Unsafe printConfig with no scale information");
    },
    getLayerParameters: function(source, stateMap) {
        if (source.currentActiveLayer) {
            return {
                layers: [source.currentActiveLayer.options.identifier],
                infolayers: [],
                styles: []
            };
        } else {
            return {
                layers: [],
                infolayers: [],
                styles: []
            };
        }
    },
    checkLayerParameterChanges: function(source, layerParams) {
        if (source.currentActiveLayer) {
            var activeIdentifier = source.currentActiveLayer.options.identifier;
            return !(layerParams.layers && layerParams.layers.length && layerParams.layers[0] === activeIdentifier);
        } else {
            return !!(layerParams.layers && layerParams.layers.length);
        }
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
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {number} scale
     * @param {OpenLayers.Projection} projection
     * @return {WmtsTileMatrix}
     */
    _getMatrix: function(sourceDef, layer, scale, projection) {
        var resolution = OpenLayers.Util.getResolutionFromScale(scale, projection.proj.units);
        var matrixSet = this._getLayerMatrixSet(sourceDef, layer);
        var scaleDelta = Number.POSITIVE_INFINITY;
        var closestMatrix = null;
        for (var i = 0; i < matrixSet.tilematrices.length; ++i) {
            var matrix = matrixSet.tilematrices[i];
            var matrixRes = this._getMatrixResolution(matrix, projection);
            var resRatio = matrixRes / resolution;
            var matrixScaleDelta = Math.abs(resRatio - 1);
            if (matrixScaleDelta < scaleDelta) {
                scaleDelta = matrixScaleDelta;
                closestMatrix = matrix;
            }
        }
        return closestMatrix;
    },
    featureInfoUrl: function() {
        return null;
    }
});


