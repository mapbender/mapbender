(function () {
    window.Mapbender = Mapbender || {};
    window.Mapbender.WmtsSource = class WmtsSource extends Mapbender.WmtsTmsBaseSource {
        _layerFactory(layer, srsName) {
            const matrixSet = layer.selectMatrixSet(srsName);
            const fallbackTileSize = Array.isArray(matrixSet.tileSize) ? matrixSet.tileSize[0] : 256;

            const gridOpts = {
                origins: matrixSet.tilematrices.map((matrix) => matrix.topLeftCorner ?? matrixSet.origin),
                resolutions: matrixSet.tilematrices.map((tileMatrix) => this._getMatrixResolution(tileMatrix, srsName)),
                matrixIds: matrixSet.tilematrices.map((matrix) => matrix.identifier),
                tileSizes: matrixSet.tilematrices.map((matrix) => matrix.tileWidth ?? fallbackTileSize),
                extent: layer.getBounds(srsName, true),
            };

            const sourceOpts = {
                version: this.version,
                requestEncoding: 'REST',
                urls: layer.options.tileUrls.map(function (tileUrlTemplate) {
                    return tileUrlTemplate.replace('{TileMatrixSet}', matrixSet.identifier);
                }),
                projection: srsName,
                tileGrid: new ol.tilegrid.WMTS(gridOpts)
            };
            const layerOpts = {
                opacity: this.options.opacity,
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

        supportsProjection(srsName) {
            if (!this.parent) {
                for (const children of this.children) {
                    if (children.supportsProjection(srsName)) {
                        return true;
                    }
                }
                return false;
            }

            const matrixSetIds = this.options.matrixLinks;
            const allMatrixSets = this.source.tilematrixsets;

            for (const matrixSetId of matrixSetIds) {
                for (const matrixSet of allMatrixSets) {
                    if (matrixSet.identifier !== matrixSetId) {
                        continue;
                    }
                    if (matrixSet.supportedCrs.includes(srsName)) {
                        return true;
                    }
                }
            }
            return false;
        }
    }
    Mapbender.SourceLayer.typeMap['wmts'] = Mapbender.WmtsLayer;
    Mapbender.Source.typeMap['wmts'] = Mapbender.WmtsSource;
}());
