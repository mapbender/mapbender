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

window.Mapbender = Mapbender || {};
window.Mapbender.WmtsTmsBaseSource = (function() {
    function WmtsTmsBaseSource(definition) {
        Mapbender.Source.apply(this, arguments);
        this.currentActiveLayer = null;
        this.autoDisabled = false;
    }
    WmtsTmsBaseSource.prototype = Object.create(Mapbender.Source.prototype);
    $.extend(WmtsTmsBaseSource.prototype, {
        constructor: WmtsTmsBaseSource,
        currentActiveLayer: null,
        autoDisabled: null,
        recreateOnSrsSwitch: true,
        initializeLayers: function() {
            var proj = Mapbender.Model.getCurrentProj();
            this.nativeLayers = this._initializeLayersInternal(proj);
            return this.nativeLayers;
        },
        destroyLayers: function() {
            Mapbender.Source.prototype.destroyLayers.call(this);
            this.currentActiveLayer = null;
        },
        checkRecreateOnSrsSwitch: function(oldProj, newProj) {
            return true;
        },
        _initializeLayersInternal: function(proj) {
            var compatibleLayer = this._selectCompatibleLayer(proj);
            var fakeRootLayer = this.configuration.children[0];
            if (!compatibleLayer) {
                this.configuration.children[0].children = [];
                this.currentActiveLayer = null;

                // disable layer before things can break
                fakeRootLayer.options.treeOptions.allow.selected = false;
                if (fakeRootLayer.options.treeOptions.selected) {
                    this.autoDisabled = true;
                }
                fakeRootLayer.state.visibility = false;
                return [];
            }
            fakeRootLayer.options.treeOptions.allow.selected = true;
            this.autoDisabled = false;
            fakeRootLayer.children = [compatibleLayer];
            this.currentActiveLayer = compatibleLayer;
            // Make the uncontrollable layer active
            // @todo: we should either pre-initialize these values on the server, or evaluate server values
            //        when picking a compatible layer. The can already be edited and saved, but all they
            //        currently do is break print, unless we rewrite them again.
            compatibleLayer.options.treeOptions.selected = true;
            compatibleLayer.options.treeOptions.allow.selected = true;
            fakeRootLayer.state.visibility = fakeRootLayer.options.treeOptions.selected;
            compatibleLayer.state.visibility = fakeRootLayer.options.treeOptions.selected;
            var olLayer = this._initializeSingleCompatibleLayer(compatibleLayer, proj);
            return [olLayer];
        },
        _getNativeLayerOptions: function(matrixSet, layer, projection) {
            var matrixOptions = this._getMatrixOptions(layer, matrixSet, projection);
            var baseOptions = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                visible: this.configuration.options.visible,
                label: layer.options.title,
                url: layer.options.tileUrls,
                format: layer.options.format
            };
            return $.extend(baseOptions, matrixOptions);
        },
        _selectCompatibleLayer: function(proj) {
            var layer = this.findLayerEpsg(proj.projCode);
            if (false && !layer) { // find first layer with epsg from srs list to initialize.
                var allsrs = Mapbender.Model.getAllSrs();
                for (var i = 0; i < allsrs.length; i++) {
                    layer = this.findLayerEpsg(allsrs[i].name);
                    if (layer) {
                        break;
                    }
                }
            }
            return layer;
        },
        findLayerEpsg: function(epsg) {
            var layers = this.configuration.layers;
            for (var i = 0; i < layers.length; i++) {
                var tileMatrixSetIdentifier = layers[i].options.tilematrixset;
                var tileMatrixSet = this._getMatrixSet(tileMatrixSetIdentifier);
                if (tileMatrixSet.supportedCrs.indexOf(epsg) !== -1) {
                    return layers[i];
                }
            }
            return null;
        },
        /**
         * @param {string} identifier
         * @return {WmtsTileMatrixSet|null}
         */
        _getMatrixSet: function(identifier) {
            var matrixSets = this.configuration.tilematrixsets;
            for (var i = 0; i < matrixSets.length; i++){
                if (matrixSets[i].identifier === identifier) {
                    return matrixSets[i];
                }
            }
            return null;
        },
        getLayerParameters: function(stateMap) {
            if (this.currentActiveLayer) {
                return {
                    layers: [this.currentActiveLayer.options.identifier],
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
        checkLayerParameterChanges: function(layerParams) {
            if (this.currentActiveLayer) {
                var activeIdentifier = this.currentActiveLayer.options.identifier;
                return !(layerParams.layers && layerParams.layers.length && layerParams.layers[0] === activeIdentifier);
            } else {
                return !!(layerParams.layers && layerParams.layers.length);
            }
        },
        getPointFeatureInfoUrl: function(x, y) {
            // not implemented
            return null;
        },
        /**
         * @param {WmtsLayerConfig} layer
         * @param {number} scale
         * @param {OpenLayers.Projection} projection
         * @return {WmtsTileMatrix}
         */
        _getMatrix: function(layer, scale, projection) {
            var resolution = OpenLayers.Util.getResolutionFromScale(scale, projection.proj.units);
            var matrixSet = this._getMatrixSet(layer.options.tilematrixset);
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
        }
    });
    return WmtsTmsBaseSource;
}());



/**
 * Base class for TMS and WMTS geosources
 */
Mapbender.Geo.SourceTmsWmtsCommon = $.extend({}, Mapbender.Geo.SourceHandler, {
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
    getPrintConfigEx: function(source, bounds, scale, projection) {
        var layerDef = source.findLayerEpsg(projection.projCode);
        var fakeRootLayer = source.configuration.children[0];
        if (!fakeRootLayer.state.visibility) {
            return [];
        }
        var matrix = source._getMatrix(layerDef, scale, projection);
        return [
            {
                url: Mapbender.Util.removeProxy(source.getPrintBaseUrl(layerDef)),
                matrix: $.extend({}, matrix),
                resolution: source._getMatrixResolution(matrix, projection)
            }
        ];
    },
    applyTreeOptions: function(source, layerOptionsMap) {
        var layerKeys = Object.keys(layerOptionsMap);
        for (var i = 0; i < layerKeys.length; ++i) {
            var layerId = layerKeys[i];
            if (source.configuration.children[0].options.id === layerId) {
                var layerOptions = layerOptionsMap[layerId];
                var treeOptions = ((layerOptions.options || {}).treeOptions || {});
                if (treeOptions.selected === true && source.autoDisabled) {
                    delete treeOptions.selected;
                }
                break;
            }
        }
        Mapbender.Geo.SourceHandler.applyTreeOptions.call(this, source, layerOptionsMap);
    }
});
(function() {
    Mapbender.source['wmts'] = Mapbender.Geo.SourceTmsWmtsCommon;
    Mapbender.source['tms'] = Mapbender.Geo.SourceTmsWmtsCommon;
}());


