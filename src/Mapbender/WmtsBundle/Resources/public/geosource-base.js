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
(function () {
    Mapbender.WmtsTmsBaseSource = class WmtsTmsBaseSource extends Mapbender.Source {
        constructor(definition) {
            super(definition);
            var sourceArg = this;
            this.configuration.layers = (this.getRootLayer().children || []).map((layerDef) => {
                return Mapbender.SourceLayer.factory(layerDef, sourceArg, this.getRootLayer());
            });
        }

        refresh() {
            this.nativeLayers.forEach(function (layer) {
                var source = layer.getSource();
                if (source.tileCache) {
                    source.tileCache.clear();
                    source.changed();
                }
            });
        }

        checkRecreateOnSrsSwitch(oldProj, newProj) {
            return true;
        }

        /**
         * @return {SourceSettings}
         */
        getSettings() {
            var diff = Object.assign(super.getSettings(), {
                selectedLayers: []
            });
            // Use a (single-item) layer id list
            if (this.getSelected()) {
                diff.selectedLayers.push(this);
            }
            return diff;
        }

        /**
         * @param {SourceSettingsDiff|null} diff
         */
        applySettingsDiff(diff) {
            if (diff.activate || diff.deactivate) {
                this.options.treeOptions.selected = !!(diff.activate || []).length;
            }
        }

        getSelected() {
            const rootLayer = this.getRootLayer();
            return rootLayer && rootLayer.options.treeOptions.selected || false;
        }

        createNativeLayers(srsName, mapOptions) {
            const allLayers = this._getAllLayers();
            const rootLayer = this.getRootLayer();
            rootLayer.children = allLayers;

            this.nativeLayers = allLayers.map((layerDef) => {
                layerDef.state.visibility = this._isCompatible(layerDef, srsName) && layerDef.options.treeOptions.selected;
                try {
                    return this._layerFactory(layerDef, srsName);
                } catch (e) {
                    return new ol.layer.Vector({
                        source: new ol.source.Vector({})
                    })
                }
            });
            return this.nativeLayers;
        }

        updateEngine() {
            const rootLayer = this.getRootLayer();
            const rootLayerVisibility = rootLayer.state.visibility;

            for (let i = 0; i < rootLayer.children.length; ++i) {
                const childSource = rootLayer.children[i];
                const olLayer = this.getNativeLayer(i);
                const targetVisibility = this.getActive() && rootLayerVisibility && childSource.state.visibility;
                if (olLayer) Mapbender.mapEngine.setLayerVisibility(olLayer, targetVisibility);
            }
        }

        _getAllLayers() {
            return this.getRootLayer().children.filter(function (l) {
                return l.options.treeOptions.allow.selected && l.options.treeOptions.selected;
            });
        }

        selectCompatibleMatrixSets(srsName) {
            return this.configuration.tilematrixsets.filter(function (matrixSet) {
                return -1 !== matrixSet.supportedCrs.indexOf(srsName);
            });
        }

        _selectCompatibleLayers(projectionCode) {
            const allLayers = this._getAllLayers();
            const compatibleLayers = [];

            for (var i = 0; i < allLayers.length; i++) {
                const layer = allLayers[i];
                if (this._isCompatible(layer, projectionCode)) {
                    compatibleLayers.push(allLayers[i]);
                }
            }
            return compatibleLayers;
        }

        _isCompatible(layer, projectionCode) {
            return (layer.options.treeOptions.allow.selected || layer.options.treeOptions.selected) && layer.selectMatrixSet(projectionCode);
        }

        /**
         * @param {*} bounds
         * @param {Number} scale
         * @param {String} srsName
         * @return {Array<Object>}
         */
        getPrintConfigs(bounds, scale, srsName) {
            var layerDef = this._selectCompatibleLayers(srsName)[0];
            const rootLayer = this.getRootLayer();
            if (!rootLayer.state.visibility || !layerDef) {
                return [];
            }
            var matrix = this._getMatrix(layerDef, scale, srsName);
            var commonOptions = this._getPrintBaseOptions();
            return [Object.assign({}, commonOptions, {
                url: Mapbender.Util.removeProxy(layerDef.getPrintBaseUrl(srsName)),
                matrix: Object.assign({}, matrix),
                resolution: this._getMatrixResolution(matrix, srsName),
                changeAxis: false,
            })];
        }

        getLayerById(id) {
            var foundLayer = super.getLayerById(id);
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
        }

        getLayerBounds(layerId, projCode, inheritFromParent) {
            let layerId_;
            const rootLayer = this.getRootLayer();
            if (!layerId || layerId === rootLayer.options.id) {
                const anyEnabledLayer = this._getAllLayers()[0];
                if (!anyEnabledLayer) {
                    return false;
                }
                layerId_ = anyEnabledLayer.options.id;
            } else {
                layerId_ = layerId;
            }
            return super.getLayerBounds(layerId_, projCode, inheritFromParent);
        }

        /**
         * @param {WmtsTmsBaseSourceLayer} layer
         * @param {number} scale
         * @param {string} srsName
         * @return {WmtsTileMatrix}
         */
        _getMatrix(layer, scale, srsName) {
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
    }

    Mapbender.WmtsTmsBaseSourceLayer = class WmtsTmsBaseSourceLayer extends Mapbender.SourceLayer {
        constructor(definition, source, parent) {
            super(definition, source, parent);
        }

        /**
         * @param {String} srsName
         * @return {WmtsTileMatrixSet|null}
         */
        selectMatrixSet(srsName) {
            var matrixLinks = this.options.matrixLinks;
            var matches = this.source.selectCompatibleMatrixSets(srsName).filter(function (matrixSet) {
                return -1 !== matrixLinks.indexOf(matrixSet.identifier);
            });
            return matches[0] || null;
        }

        isInScale(scale) {
            // HACK: always return true
            // @todo: implement properly
            return true;
        }

        intersectsExtent(extent, srsName) {
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

        getLegend() {
            if (this.options.legend && this.options.legend.url) {
                return {
                    type: 'url',
                    url: this.options.legend.url
                };
            }
            return null;
        }
    }

}());



