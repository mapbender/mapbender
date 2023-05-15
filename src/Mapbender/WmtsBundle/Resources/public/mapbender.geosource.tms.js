window.Mapbender = Mapbender || {};
window.Mapbender.TmsSource = (function() {
    function TmsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    TmsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    Mapbender.Source.typeMap['tms'] = TmsSource;
    Object.assign(TmsSource.prototype, {
        constructor: TmsSource,
        _layerFactory: function(layer, srsName) {
            var matrixSet = layer.selectMatrixSet(srsName);
            var self = this;
            var gridOpts = {
                origin: matrixSet.origin,
                resolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                }),
                extent: layer.getBounds(srsName, true)
            };

            var sourceOpts = {
                tileUrlFunction: function(coord, ratio, projection) {
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
                opacity: this.configuration.options.opacity,
                source: new ol.source.XYZ(sourceOpts)
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
            // Yes, seriously, it's called scaleDenominator but it's the resolution
            // @todo: resolve backend config wording weirdness
            return tileMatrix.scaleDenominator;
        },
        __dummy__: null
    });
    return TmsSource;
}());

window.Mapbender.TmsLayer = (function() {
    function TmsLayer(definition) {
        Mapbender.WmtsTmsBaseSourceLayer.apply(this, arguments);
    }
    TmsLayer.prototype = Object.create(Mapbender.WmtsTmsBaseSourceLayer.prototype);
    Object.assign(TmsLayer.prototype, {
        constructor: TmsLayer,
        /**
         * @param {String} srsName
         * @return {string}
         */
        getPrintBaseUrl: function(srsName) {
            return [this.options.tileUrls[0], this.source.configuration.version, '/', this.options.identifier].join('');
        },
        __dummy__: null
    });
    Mapbender.SourceLayer.typeMap['tms'] = TmsLayer;
    return TmsLayer;
}());
