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
window.Mapbender.WmtsTmsBaseSource = (function () {
    function WmtsTmsBaseSource(definition) {
        Mapbender.Source.apply(this, arguments);
        this.currentActiveLayer = null;
        var sourceArg = this;
        this.configuration.layers = (this.configuration.children[0].children || []).map(function (layerDef) {
            return Mapbender.SourceLayer.factory(layerDef, sourceArg, null);
        });
    }

    WmtsTmsBaseSource.prototype = Object.create(Mapbender.Source.prototype);
    Object.assign(WmtsTmsBaseSource.prototype, {
        constructor: WmtsTmsBaseSource,
        currentActiveLayer: null,
        recreateOnSrsSwitch: true,
        destroyLayers: function (olMap) {
            Mapbender.Source.prototype.destroyLayers.call(this, olMap);
            this.currentActiveLayer = null;
        },
        refresh: function () {
            this.nativeLayers.forEach(function (layer) {
                var source = layer.getSource();
                if (source.tileCache) {
                    source.tileCache.clear();
                    source.changed();
                }
            });
        },
        checkRecreateOnSrsSwitch: function (oldProj, newProj) {
            return true;
        },
        /**
         * @return {SourceSettings}
         */
        getSettings: function () {
            var diff = Object.assign(Mapbender.Source.prototype.getSettings.call(this), {
                selectedLayers: []
            });
            // Use a (single-item) layer id list
            if (this.getSelected()) {
                diff.selectedLayers.push(this);
            }
            return diff;
        },
        /**
         * @param {SourceSettingsDiff|null} diff
         */
        applySettingsDiff: function (diff) {
            console.log("diff", diff);
            const rootLayer = this.configuration.children[0];
            if (diff.activate || diff.deactivate) {
                rootLayer.options.treeOptions.selected = !!(diff.activate || []).length;
            }
        },
        getSelected: function () {
            const rootLayer = this.configuration.children[0];
            return rootLayer && rootLayer.options.treeOptions.selected || false;
        },
        /**
         * @param {String} srsName
         * @param {Object} [mapOptions]
         * @return {Array<Object>}
         */
        createNativeLayers: function (srsName, mapOptions) {
            const compatibleLayers = this._selectCompatibleLayers(srsName);
            const rootLayer = this.configuration.children[0];
            if (!compatibleLayers.length) {
                this.configuration.children[0].children = [];
                this.currentActiveLayer = null;
                return [];
            }

            rootLayer.children = compatibleLayers;
            this.currentActiveLayer = compatibleLayers[0];
            return compatibleLayers.map((layerDef) => {
                layerDef.state.visibility = rootLayer.options.treeOptions.selected;
                return this._layerFactory(layerDef, srsName);
            })
        },
        updateEngine: function () {
            const rootLayer = this.configuration.children[0];
            var layerIdent = this.currentActiveLayer && this.currentActiveLayer.options.identifier;
            var engine = Mapbender.mapEngine;
            var rootVisibility = rootLayer.state.visibility;
            var targetVisibility = !!layerIdent && rootVisibility && this.getActive();
            var olLayer = this.getNativeLayer(0);
            if (!olLayer) {
                return;
            }
            engine.setLayerVisibility(olLayer, targetVisibility);
        },
        _getEnabledLayers: function () {
            return this.configuration.layers.filter(function (l) {
                return l.options.treeOptions.allow.selected && l.options.treeOptions.selected;
            });
        },
        selectCompatibleMatrixSets: function (srsName) {
            return this.configuration.tilematrixsets.filter(function (matrixSet) {
                return -1 !== matrixSet.supportedCrs.indexOf(srsName);
            });
        },
        _selectCompatibleLayers: function (projectionCode) {
            const allLayers = this._getEnabledLayers();
            const compatibleLayers = [];

            for (var i = 0; i < allLayers.length; i++) {
                const layer = allLayers[i];
                if (!layer.options.treeOptions.allow.selected) {
                    continue;
                }
                if (layer.selectMatrixSet(projectionCode)) {
                    compatibleLayers.push(allLayers[i]);
                }
            }
            console.log(compatibleLayers.map(x => {return {title: x.title_, treeOptions: x.options.treeOptions}}));
            return compatibleLayers;
        },
        getFeatureInfoLayers: function () {
            console.warn("getFeatureInfoLayers not implemented for TMS / WMTS sources");
            return [];
        },
        /**
         * @param {*} bounds
         * @param {Number} scale
         * @param {String} srsName
         * @return {Array<Object>}
         */
        getPrintConfigs: function (bounds, scale, srsName) {
            var layerDef = this._selectCompatibleLayers(srsName)[0];
            const rootLayer = this.configuration.children[0];
            if (!rootLayer.state.visibility || !layerDef) {
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
        getLayerById: function (id) {
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
        getLayerBounds: function (layerId, projCode, inheritFromParent) {
            let layerId_;
            const rootLayer = this.configuration.children[0];
            if (!layerId || layerId === rootLayer.options.id) {
                const anyEnabledLayer = this._getEnabledLayers()[0];
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
        _getMatrix: function (layer, scale, srsName) {
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

Mapbender.WmtsTmsBaseSourceLayer = (function () {
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
        selectMatrixSet: function (srsName) {
            var matrixLinks = this.options.matrixLinks;
            var matches = this.source.selectCompatibleMatrixSets(srsName).filter(function (matrixSet) {
                return -1 !== matrixLinks.indexOf(matrixSet.identifier);
            });
            return matches[0] || null;
        },
        getSelected: function () {
            var rootLayer = this.source.getRootLayer();
            return rootLayer.options.treeOptions.selected;
        },
        hasBounds: function () {
            var currentActive = this.source.currentActiveLayer;
            return !!currentActive && Mapbender.SourceLayer.prototype.hasBounds.call(currentActive);
        },
        isInScale: function (scale) {
            // HACK: always return true
            // @todo: implement properly
            return true;
        },
        intersectsExtent: function (extent, srsName) {
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



