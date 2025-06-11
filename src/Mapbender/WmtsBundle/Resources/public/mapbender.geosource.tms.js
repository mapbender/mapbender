window.Mapbender = Mapbender || {};

(function () {
    Mapbender.TmsSource = class TmsSource extends Mapbender.WmtsTmsBaseSource {
        _layerFactory(layer, srsName) {
            var matrixSet = layer.selectMatrixSet(srsName);
            var self = this;
            var gridOpts = {
                origin: matrixSet.origin,
                resolutions: matrixSet.tilematrices.map(function (tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                }),
                extent: layer.getBounds(srsName, true)
            };

            var sourceOpts = {
                tileUrlFunction: function (coord, ratio, projection) {
                    return [
                        matrixSet.tilematrices[coord[0]].href.replace(/[/&?]*$/, ''),
                        '/', coord[1],
                        '/', -coord[2] - 1,
                        '.', layer.options.extension
                    ].join('');
                },
                projection: srsName,
                tileGrid: new ol.tilegrid.WMTS(gridOpts)
            };
            var layerOpts = {
                opacity: this.options.opacity,
                source: new ol.source.XYZ(sourceOpts)
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
            // Yes, seriously, it's called scaleDenominator but it's the resolution
            // @todo: resolve backend config wording weirdness
            return tileMatrix.scaleDenominator;
        }
    }
    Mapbender.Source.typeMap['tms'] = Mapbender.TmsSource;

    Mapbender.TmsLayer = class TmsLayer extends Mapbender.WmtsTmsBaseSourceLayer {
        /* @param {String} srsName
        * @return {string}
        */
        getPrintBaseUrl(srsName) {
            return [this.options.tileUrls[0], this.source.version, '/', this.options.identifier].join('');
        }
    }
    Mapbender.SourceLayer.typeMap['tms'] = Mapbender.TmsLayer;
}());
