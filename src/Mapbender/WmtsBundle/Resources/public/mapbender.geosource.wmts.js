(function () {
    window.Mapbender = Mapbender || {};
    window.Mapbender.WmtsSource = class WmtsSource extends Mapbender.WmtsTmsBaseSource {
        _layerFactory(layer, srsName) {
            var matrixSet = layer.selectMatrixSet(srsName);
            var self = this;
            var gridOpts = {
                origin: matrixSet.origin,
                resolutions: matrixSet.tilematrices.map(function (tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                }),
                matrixIds: matrixSet.tilematrices.map(function (matrix) {
                    return matrix.identifier;
                }),
                extent: layer.getBounds(srsName, true)
            };

            var sourceOpts = {
                version: this.configuration.version,
                requestEncoding: 'REST',
                urls: layer.options.tileUrls.map(function (tileUrlTemplate) {
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
        }

        /**
         * @param {WmtsTileMatrix} tileMatrix
         * @param {String} srsName
         * @return {Number}
         * @private
         */
        _getMatrixResolution(tileMatrix, srsName) {
            var engine = Mapbender.mapEngine;
            // OGC TileMatrix scaleDenom is calculated using meters, irrespective of projection units
            // OGC TileMatrix scaleDenom is also calculated assuming 0.28mm per pixel
            var metersPerUnit = 1.0 / engine.getProjectionUnitsPerMeter(srsName);
            var unitsPerPixel = 0.00028 / metersPerUnit;
            return tileMatrix.scaleDenominator * unitsPerPixel;
        }
    }

    window.Mapbender.WmtsLayer = class WmtsLayer extends Mapbender.WmtsTmsBaseSourceLayer {
        /**
         * @param {String} srsName
         * @return {string}
         */
        getPrintBaseUrl(srsName) {
            var tileMatrixSet = this.selectMatrixSet(srsName);
            var template = this.options.tileUrls[0];
            return template.replace('{TileMatrixSet}', tileMatrixSet.identifier);
        }
    }
    Mapbender.SourceLayer.typeMap['wmts'] = Mapbender.WmtsLayer;
    Mapbender.Source.typeMap['wmts'] = Mapbender.WmtsSource;
}());
