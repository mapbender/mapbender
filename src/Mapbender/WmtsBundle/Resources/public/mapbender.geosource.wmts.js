window.Mapbender = Mapbender || {};
window.Mapbender.WmtsSource = (function() {
    function WmtsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    Mapbender.Source.typeMap['wmts'] = WmtsSource;
    WmtsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    Object.assign(WmtsSource.prototype, {
        constructor: WmtsSource,
        _initializeSingleCompatibleLayer: function(compatibleLayer, srsName) {
            switch (Mapbender.mapEngine.code) {
                default:
                    return this._layerFactory(compatibleLayer, srsName);
                case 'ol2':
                    return this._layerFactoryOl2(compatibleLayer, srsName);
            }
        },
        _layerFactoryOl2: function(compatibleLayer, srsName) {
            var matrixSet = compatibleLayer.selectMatrixSet(srsName);
            var options = this._getNativeLayerBaseOptions(compatibleLayer, srsName);
            Object.assign(options, {
                requestEncoding: 'REST',
                style: false,
                layer: compatibleLayer.options.identifier,
                matrixSet: matrixSet.identifier,
                matrixIds: matrixSet.tilematrices.map(function(matrix) {
                    if (matrix.topLeftCorner) {
                        return $.extend({}, matrix, {
                            topLeftCorner: OpenLayers.LonLat.fromArray(matrix.topLeftCorner)
                        });
                    } else {
                        return $.extend({}, matrix);
                    }
                })
            });
            var olLayer = new OpenLayers.Layer.WMTS(options);
            return olLayer;
        },
        _layerFactory: function(layer, srsName) {
            var matrixSet = layer.selectMatrixSet(srsName);
            var self = this;
            var gridOpts = {
                origin: matrixSet.origin,
                resolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                }),
                matrixIds: matrixSet.tilematrices.map(function(matrix) {
                    return matrix.identifier;
                }),
                extent: layer.getBounds(srsName, true)
            };

            var sourceOpts = {
                version: this.configuration.version,
                requestEncoding: 'REST',
                urls: layer.options.tileUrls.map(function(tileUrlTemplate) {
                    return tileUrlTemplate.replace('{TileMatrixSet}', matrixSet.identifier);
                }),
                projection: srsName,
                tileGrid: new ol.tilegrid.WMTS(gridOpts)
            };
            var layerOpts = {
                opacity: this.configuration.options.opacity,
                source: new ol.source.WMTS(sourceOpts)
            };
            return new ol.layer.Tile(layerOpts);
        },
        /**
         * @param {WmtsTileMatrix} tileMatrix
         * @param {String} srsName
         * @return {Number}
         * @private
         */
        _getMatrixResolution: function(tileMatrix, srsName) {
            var engine = Mapbender.mapEngine;
            // OGC TileMatrix scaleDenom is calculated using meters, irrespective of projection units
            // OGC TileMatrix scaleDenom is also calculated assuming 0.28mm per pixel
            var metersPerUnit = 1.0 / engine.getProjectionUnitsPerMeter(srsName);
            var unitsPerPixel = 0.00028 / metersPerUnit;
            return tileMatrix.scaleDenominator * unitsPerPixel;
        },
        __dummy__: null
    });
    return WmtsSource;
}());
window.Mapbender.WmtsLayer = (function() {
    function WmtsLayer(definition) {
        Mapbender.WmtsTmsBaseSourceLayer.apply(this, arguments);
    }
    WmtsLayer.prototype = Object.create(Mapbender.WmtsTmsBaseSourceLayer.prototype);
    Object.assign(WmtsLayer.prototype, {
        constructor: WmtsLayer,
        /**
         * @param {String} srsName
         * @return {string}
         */
        getPrintBaseUrl: function(srsName) {
            var tileMatrixSet = this.selectMatrixSet(srsName);
            var template = this.options.tileUrls[0];
            return template
                .replace('{TileMatrixSet}', tileMatrixSet.identifier)
            ;
        },
        __dummy__: null
    });
    Mapbender.SourceLayer.typeMap['wmts'] = WmtsLayer;
    return WmtsLayer;
}());
