window.Mapbender = Mapbender || {};
window.Mapbender.TmsSource = (function() {
    function TmsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    TmsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    $.extend(TmsSource.prototype, {
        constructor: TmsSource,
        _initializeSingleCompatibleLayer: function(compatibleLayer, srsName) {
            var matrixSet = compatibleLayer.getMatrixSet();
            var options = this._getNativeLayerBaseOptions(compatibleLayer, srsName);
            Object.assign(options, {
                type: compatibleLayer.options.format.split('/').pop(),
                layername: compatibleLayer.options.identifier,
                serviceVersion: this.configuration.version,
                tileSize: new OpenLayers.Size(matrixSet.tileSize[0], matrixSet.tileSize[1])
            });
            if (matrixSet.origin && matrixSet.origin.length) {
                options.tileOrigin = new OpenLayers.LonLat(matrixSet.origin[0], matrixSet.origin[1]);
            }
            return new OpenLayers.Layer.TMS(compatibleLayer.options.title, compatibleLayer.options.tileUrls, options);
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
        /**
         * @param {WmtsLayerConfig} layerDef
         * @return {string}
         */
        getPrintBaseUrl: function(layerDef) {
            return [layerDef.options.tileUrls[0], this.configuration.version, '/', layerDef.options.identifier].join('');
        }

    });
    Mapbender.Source.typeMap['tms'] = TmsSource;
    return TmsSource;
}());
