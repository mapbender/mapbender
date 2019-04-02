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
        var sourceArg = this;
        this.configuration.layers = (this.configuration.layers || []).map(function(layerDef) {
            return Mapbender.SourceLayer.factory(layerDef, sourceArg, null);
        });
    }
    WmtsTmsBaseSource.prototype = Object.create(Mapbender.Source.prototype);
    $.extend(WmtsTmsBaseSource.prototype, {
        constructor: WmtsTmsBaseSource,
        currentActiveLayer: null,
        autoDisabled: null,
        recreateOnSrsSwitch: true,
        destroyLayers: function() {
            Mapbender.Source.prototype.destroyLayers.call(this);
            this.currentActiveLayer = null;
        },
        checkRecreateOnSrsSwitch: function(oldProj, newProj) {
            return true;
        },
        createNativeLayers: function(srsName) {
            var compatibleLayer = this._selectCompatibleLayer(srsName);
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
            var olLayer = this._initializeSingleCompatibleLayer(compatibleLayer, srsName);
            return [olLayer];
        },
        _getNativeLayerOptions: function(matrixSet, layer, srsName) {
            var self = this;
            var baseOptions = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                visible: this.configuration.options.visible,
                label: layer.options.title,
                url: layer.options.tileUrls,
                format: layer.options.format,
                serverResolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                })
            };
            var bounds = layer.getBounds(srsName, true);
            if (bounds) {
                baseOptions.tileFullExtent = bounds;
            }
            return baseOptions;
        },
        _getEnabledLayers: function() {
            return this.configuration.layers.filter(function(l) {
                return l.options.treeOptions.allow.selected && l.options.treeOptions.selected;
            });
        },
        _selectCompatibleLayer: function(projectionCode) {
            var layers = this._getEnabledLayers();
            for (var i = 0; i < layers.length; i++) {
                var layer = layers[i];
                if (!layer.options.treeOptions.allow.selected) {
                    continue;
                }
                var tileMatrixSet = layer.getMatrixSet();
                if (tileMatrixSet.supportedCrs.indexOf(projectionCode) !== -1) {
                    return layers[i];
                }
            }
            return null;
        },
        /**
         * @param {string} identifier
         * @return {WmtsTileMatrixSet|null}
         */
        getMatrixSetByIdent: function(identifier) {
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
        getPointFeatureInfoUrl: function(x, y, maxCount) {
            // not implemented
            return null;
        },
        getMultiLayerPrintConfig: function(bounds, scale, projection) {
            var layerDef = this._selectCompatibleLayer(projection.projCode);
            var fakeRootLayer = this.configuration.children[0];
            if (!fakeRootLayer.state.visibility || !layerDef) {
                return [];
            }
            var matrix = this._getMatrix(layerDef, scale, projection);
            return [
                {
                    url: Mapbender.Util.removeProxy(this.getPrintBaseUrl(layerDef)),
                    matrix: $.extend({}, matrix),
                    resolution: this._getMatrixResolution(matrix, projection.projCode)
                }
            ];
        },
        getLayerById: function(id) {
            var foundLayer = Mapbender.Source.prototype.getLayerById.call(this, id);
            if (!foundLayer) {
                for (var i = 0; i < this.configuration.layers.length; ++i) {
                    var candidate = this.configuration.layers[i];
                    if (candidate.options.id === id) {
                        foundLayer = candidate;
                        break;
                    }
                }
            }
            return foundLayer;
        },
        supportsMetadata: function() {
            return false;
        },
        getLayerExtentConfigMap: function(layerId, inheritFromParent, inheritFromSource) {
            var bboxMap;
            var inheritParent_ = inheritFromParent || (typeof inheritFromParent === 'undefined');
            var inheritSource_ = inheritFromSource || (typeof inheritFromSource === 'undefined');
            if (this.currentActiveLayer && (inheritParent_ || inheritSource_)) {
                bboxMap = this._reduceBboxMap(this.currentActiveLayer.options.bbox);
                if (bboxMap) {
                    return bboxMap;
                }
            }
            var fakeRootLayerId = this.configuration.children[0].options.id;
            if ((!layerId || layerId === fakeRootLayerId) && (inheritParent_ || inheritSource_)) {
                // root layer doesn't have bbox config
                // just find something..
                for (var i = 0; i < this.configuration.layers.length; ++i) {
                    bboxMap = this._reduceBboxMap(this.configuration.layers[i].options.bbox);
                    if (bboxMap) {
                        return bboxMap;
                    }
                }
            }
            return Mapbender.Source.prototype.getLayerExtentConfigMap.apply(this, arguments);
        },
        getLayerBounds: function(layerId, projCode, inheritFromParent) {
            var layerId_;
            var fakeRootLayer = this.configuration.children[0];
            if (!layerId || layerId === fakeRootLayer.options.id) {
                var anyEnabledLayer = this._getEnabledLayers()[0];
                if (!anyEnabledLayer) {
                    return false;
                }
                layerId_ = anyEnabledLayer.options.id;
            } else {
                layerId_ = layerId;
            }
            return Mapbender.Source.prototype
                .getLayerBounds.call(this, layerId_, projCode, inheritFromParent);
        },
        /**
         * @param {WmtsTmsBaseSourceLayer} layer
         * @param {number} scale
         * @param {OpenLayers.Projection} projection
         * @return {WmtsTileMatrix}
         */
        _getMatrix: function(layer, scale, projection) {
            var resolution = OpenLayers.Util.getResolutionFromScale(scale, projection.proj.units);
            var matrixSet = layer.getMatrixSet();
            var scaleDelta = Number.POSITIVE_INFINITY;
            var closestMatrix = null;
            for (var i = 0; i < matrixSet.tilematrices.length; ++i) {
                var matrix = matrixSet.tilematrices[i];
                var matrixRes = this._getMatrixResolution(matrix, projection.projCode);
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

Mapbender.WmtsTmsBaseSourceLayer = (function() {
    function WmtsTmsBaseSourceLayer(definition, source, parent) {
        Mapbender.SourceLayer.apply(this, arguments);
    }
    WmtsTmsBaseSourceLayer.prototype = Object.create(Mapbender.SourceLayer.prototype);
    $.extend(WmtsTmsBaseSourceLayer.prototype, {
        constructor: WmtsTmsBaseSourceLayer,
        getMatrixSet: function() {
            return this.source.getMatrixSetByIdent(this.options.tilematrixset);
        }
    });
    Mapbender.SourceLayer.typeMap['wmts'] = WmtsTmsBaseSourceLayer;
    Mapbender.SourceLayer.typeMap['tms'] = WmtsTmsBaseSourceLayer;
}());



/**
 * Base class for TMS and WMTS geosources
 */
Mapbender.Geo.SourceTmsWmtsCommon = $.extend({}, Mapbender.Geo.SourceHandler, {
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


