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
 * @property {Array<string>} options.matrixLinks
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
        destroyLayers: function(olMap) {
            Mapbender.Source.prototype.destroyLayers.call(this, olMap);
            this.currentActiveLayer = null;
        },
        refresh: function() {
            this.nativeLayers.forEach(function(layer) {
                if (typeof (layer.getSource) !== 'function') {
                    console.warn("No Openlayers 2 implementation for tile service refresh");
                    return;
                }
                var source = layer.getSource();
                if (source.tileCache) {
                    source.tileCache.clear();
                    source.changed();
                }
            });
        },
        checkRecreateOnSrsSwitch: function(oldProj, newProj) {
            return true;
        },
        /**
         * @return {SourceSettings}
         */
        getSettings: function() {
            var diff = Object.assign(Mapbender.Source.prototype.getSettings.call(this), {
                selectedIds: []
            });
            // Use a (single-item) layer id list
            if (this.getSelected()) {
                diff.selectedIds.push(this.id);
            }
            return diff;
        },
        /**
         * @param {SourceSettingsDiff|null} diff
         */
        applySettingsDiff: function(diff) {
            var fakeRootLayer = this.configuration.children[0];
            if (diff.activate || diff.deactivate) {
                fakeRootLayer.options.treeOptions.selected = !!(diff.activate || []).length;
            }
        },
        getSelected: function() {
            var fakeRootLayer = this.configuration.children[0];
            return fakeRootLayer && fakeRootLayer.options.treeOptions.selected || false;
        },
        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        createNativeLayers: function(srsName, mapOptions) {
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
            var rootVisibility = fakeRootLayer.state.visibility;
            var targetVisibility = !!layerIdent && rootVisibility && this.getActive();
            var olLayer = this.getNativeLayer(0);
            if (!olLayer) {
                return;
            }
            engine.setLayerVisibility(olLayer, targetVisibility);
        },
        _getNativeLayerBaseOptions: function(layer, srsName) {
            var matrixSet = layer.selectMatrixSet(srsName);
            var self = this;

            var baseOptions = {
                isBaseLayer: false,
                opacity: this.configuration.options.opacity,
                name: layer.options.title,
                url: layer.options.tileUrls,
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
        selectCompatibleMatrixSets: function(srsName) {
            return this.configuration.tilematrixsets.filter(function(matrixSet) {
                return -1 !== matrixSet.supportedCrs.indexOf(srsName);
            });
        },
        _selectCompatibleLayer: function(projectionCode) {
            var layers = this._getEnabledLayers();

            for (var i = 0; i < layers.length; i++) {
                var layer = layers[i];
                if (!layer.options.treeOptions.allow.selected) {
                    continue;
                }
                if (layer.selectMatrixSet(projectionCode)) {
                    return layers[i];
                }
            }
            return null;
        },
        getFeatureInfoLayers: function() {
            console.warn("getFeatureInfoLayers not implemented for TMS / WMTS sources");
            return [];
        },
        /**
         * @param {*} bounds
         * @param {Number} scale
         * @param {String} srsName
         * @return {Array<Object>}
         */
        getPrintConfigs: function(bounds, scale, srsName) {
            var layerDef = this._selectCompatibleLayer(srsName);
            var fakeRootLayer = this.configuration.children[0];
            if (!fakeRootLayer.state.visibility || !layerDef) {
                return [];
            }
            var matrix = this._getMatrix(layerDef, scale, srsName);
            var commonOptions = this._getPrintBaseOptions();
            return [
                Object.assign({}, commonOptions, {
                    url: Mapbender.Util.removeProxy(layerDef.getPrintBaseUrl(srsName)),
                    matrix: Object.assign({}, matrix),
                    resolution: this._getMatrixResolution(matrix, srsName)
                })
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
            var resolution = Mapbender.Model.scaleToResolution(scale, undefined, srsName);
            var matrixSet = layer.selectMatrixSet(srsName);
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
        /**
         * @param {String} srsName
         * @return {WmtsTileMatrixSet|null}
         */
        selectMatrixSet: function(srsName) {
            var matrixLinks = this.options.matrixLinks;
            var matches = this.source.selectCompatibleMatrixSets(srsName).filter(function(matrixSet) {
                return -1 !== matrixLinks.indexOf(matrixSet.identifier);
            });
            return matches[0] || null;
        },
        getSelected: function() {
            var rootLayer = this.source.getRootLayer();
            return rootLayer.options.treeOptions.selected;
        },
        hasBounds: function() {
            var currentActive = this.source.currentActiveLayer;
            return !!currentActive && Mapbender.SourceLayer.prototype.hasBounds.call(currentActive);
        },
        isInScale: function(scale) {
            // HACK: always return true
            // @todo: implement properly
            return true;
        },
        intersectsExtent: function(extent, srsName) {
            // Let the source substitute fake root layer for the right one
            var bounds = this.source && this.source.getLayerBounds(this.options.id, 'EPSG:4326', true);
            if (!bounds) {
                // unlimited extent
                return true;
            }
            var extent_;
            if (srsName !== 'EPSG:4326') {
                extent_ = Mapbender.mapEngine.transformBounds(extent, srsName, 'EPSG:4326');
            } else {
                extent_ = extent;
            }
            return Mapbender.Util.extentsIntersect(bounds, extent_);
        }
    });
    return WmtsTmsBaseSourceLayer;
}());



