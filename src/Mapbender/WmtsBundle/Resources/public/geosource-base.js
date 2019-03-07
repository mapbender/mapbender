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
 * @property {Array<string>} supportedCrs
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
 * @property {WmtsLayerConfig|null} currentActiveLayer
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
        var matrixSet = this._getMatrixSet(sourceOpts, layer.options.tilematrixset);
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
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {OpenLayers.Projection} projection
     * @return {OpenLayers.Bounds|null}
     * @private
     */
    getMaxExtent: function(sourceDef, projection, layer) {
        var layer_ = layer || sourceDef.currentActiveLayer;
        var projCode = projection.projCode;
        if (!layer_) {
            console.warn("Didn't find layer to calulate max extent", sourceDef, projection);
            return null;
        }
        if (layer_.options.bbox[projCode]) {
            return OpenLayers.Bounds.fromArray(layer_.options.bbox[projCode]);
        } else {
            var bboxSrses = Object.keys(layer_.options.bbox);
            for (var i = 0; i < bboxSrses.length; ++i) {
                var bboxSrs = bboxSrses[i];
                var bboxArray = layer_.options.bbox[bboxSrs];
                var bboxProj = Mapbender.Model.getProj(bboxSrs);
                if (bboxProj) {
                    var newExtent = OpenLayers.Bounds.fromArray(bboxArray).transform(
                        bboxProj,
                        projection
                    );
                    if (newExtent.right > newExtent.left && newExtent.top > newExtent.bottom) {
                        return newExtent;
                    }
                }
            }
        }
        return null;
    },
    getLayerExtents: function(source, layerId) {
        if (source.currentActiveLayer) {
            return source.currentActiveLayer.options.bbox || null;
        }
        return null;
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
        var fakeRootLayer = source.configuration.children[0];
        if (!fakeRootLayer.state.visibility) {
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
    beforeSrsChange: function(source, olLayer, newSrsCode) {
        olLayer.removeBackBuffer();
        var layer = this.findLayerEpsg(source, newSrsCode);
        var matrixSet = layer && this._getMatrixSet(source, layer.options.tilematrixset);
        var fakeRootLayer = source.configuration.children[0];
        if (matrixSet) {
            source.currentActiveLayer = layer;
        } else {
            // disable layer before things can break
            Mapbender.Model.controlLayer(source.id, fakeRootLayer.options.id, false, false);
            source.currentActiveLayer = null;
        }
    },
    changeProjection: function(source, projection) {
        var layer = this.findLayerEpsg(source, projection.projCode);
        var matrixSet = layer && this._getMatrixSet(source, layer.options.tilematrixset);
        var olLayer = layer && Mapbender.Model.getNativeLayer(source);
        if (layer && olLayer && matrixSet) {
            var options = this._getMatrixOptions(layer, matrixSet, projection);
            options.projection = projection.projCode;
            olLayer.addOptions(options, false);
            return true;
        } else {
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
            var tileMatrixSetIdentifier = layers[i].options.tilematrixset;
            var tileMatrixSet = this._getMatrixSet(sourceDef, tileMatrixSetIdentifier);
            if (tileMatrixSet.supportedCrs.indexOf(epsg) !== -1) {
                return layers[i];
            }
        }
        return null;
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {string} identifier
     * @return {WmtsTileMatrixSet|null}
     */
    _getMatrixSet: function(sourceDef, identifier) {
        var matrixSets = sourceDef.configuration.tilematrixsets;
        for(var i = 0; i < matrixSets.length; i++){
            if (matrixSets[i].identifier === identifier) {
                return matrixSets[i];
            }
        }
        return null;
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
        var matrixSet = this._getMatrixSet(sourceDef, layer.options.tilematrixset);
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


