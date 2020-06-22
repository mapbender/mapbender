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
    Object.assign(WmtsTmsBaseSource.prototype, {
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
        getSelected: function() {
            var fakeRootLayer = this.configuration.children[0];
            return fakeRootLayer.options.treeOptions.selected;
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
        updateEngine: function() {
            var fakeRootLayer = this.configuration.children[0];
            var layerIdent = this.currentActiveLayer && this.currentActiveLayer.options.identifier;
            var engine = Mapbender.mapEngine;
            var targetVisibility = !!layerIdent && fakeRootLayer.state.visibility;
            var olLayer = this.getNativeLayer(0);
            if (!olLayer) {
                return;
            }
            engine.setLayerVisibility(olLayer, targetVisibility);
        },
        _getNativeLayerBaseOptions: function(layer, srsName) {
            var matrixSet = layer.getMatrixSet();
            var self = this;

            var baseOptions = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                name: layer.options.title,
                url: layer.options.tileUrls,
                format: layer.options.format,
                style: layer.options.style,
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
        getFeatureInfoLayers: function() {
            console.warn("getFeatureInfoLayers not implemented for TMS / WMTS sources");
            return [];
        },
        getMultiLayerPrintConfig: function(bounds, scale, srsName) {
            var layerDef = this._selectCompatibleLayer(srsName);
            var fakeRootLayer = this.configuration.children[0];
            if (!fakeRootLayer.state.visibility || !layerDef) {
                return [];
            }
            var matrix = this._getMatrix(layerDef, scale, srsName);
            return [
                {
                    url: Mapbender.Util.removeProxy(this.getPrintBaseUrl(layerDef)),
                    matrix: $.extend({}, matrix),
                    resolution: this._getMatrixResolution(matrix, srsName)
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
         * @param {string} srsName
         * @return {WmtsTileMatrix}
         */
        _getMatrix: function(layer, scale, srsName) {
            var units = Mapbender.mapEngine.getProjectionUnits(srsName);
            var resolution = OpenLayers.Util.getResolutionFromScale(scale, units);
            var matrixSet = layer.getMatrixSet();
            var scaleDelta = Number.POSITIVE_INFINITY;
            var closestMatrix = null;
            for (var i = 0; i < matrixSet.tilematrices.length; ++i) {
                var matrix = matrixSet.tilematrices[i];
                var matrixRes = this._getMatrixResolution(matrix, srsName);
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
    Object.assign(WmtsTmsBaseSourceLayer.prototype, {
        constructor: WmtsTmsBaseSourceLayer,
        getMatrixSet: function() {
            return this.source.getMatrixSetByIdent(this.options.tilematrixset);
        }
    });
    Mapbender.SourceLayer.typeMap['wmts'] = WmtsTmsBaseSourceLayer;
    Mapbender.SourceLayer.typeMap['tms'] = WmtsTmsBaseSourceLayer;
}());



