window.Mapbender = Mapbender || {};
window.Mapbender.WmtsSource = (function() {
    function WmtsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    WmtsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    $.extend(WmtsSource.prototype, {
        constructor: WmtsSource,
        _initializeSingleCompatibleLayer: function(compatibleLayer, srsName) {
            var matrixSet = compatibleLayer.getMatrixSet();
            var options = $.extend(this._getNativeLayerOptions(matrixSet, compatibleLayer, srsName), {
                requestEncoding: 'REST',
                layer: compatibleLayer.options.identifier,
                style: compatibleLayer.options.style,
                name: compatibleLayer.options.title,
                url: compatibleLayer.options.tileUrls,
                format: compatibleLayer.options.format
            });
            var olLayer = new OpenLayers.Layer.WMTS(options);
            return olLayer;
        },
        _getNativeLayerOptions: function(matrixSet, compatibleLayer, srsName) {
            var parentValues = Mapbender.WmtsTmsBaseSource.prototype._getNativeLayerOptions.apply(this, arguments);
            var matrixOptions = this._getMatrixOptions(matrixSet);
            return $.extend(parentValues, matrixOptions);
        },
        /**
         * @param {WmtsTileMatrixSet} matrixSet
         * @return {{matrixSet: string, matrixIds: any[]}}
         * @private
         */
        _getMatrixOptions: function(matrixSet) {
            var matrixIds = matrixSet.tilematrices.map(function(matrix) {
                if (matrix.topLeftCorner) {
                    return $.extend({}, matrix, {
                        topLeftCorner: OpenLayers.LonLat.fromArray(matrix.topLeftCorner)
                    });
                } else {
                    return $.extend({}, matrix);
                }
            });
            return {
                matrixSet: matrixSet.identifier,
                matrixIds: matrixIds
            };
        },
        /**
         * @param {WmtsTileMatrix} tileMatrix
         * @param {String} srsName
         * @return {Number}
         * @private
         */
        _getMatrixResolution: function(tileMatrix, srsName) {
            // OGC TileMatrix scaleDenom is calculated using meters, irrespective of projection units
            // OGC TileMatrix scaleDenom is also calculated assuming 0.28mm per pixel
            var metersPerUnit = 1.0 / Mapbender.Model.getProjectionUnitsPerMeter(srsName);
            var unitsPerPixel = 0.00028 / metersPerUnit;
            return tileMatrix.scaleDenominator * unitsPerPixel;
        },
        /**
         * @param {WmtsLayerConfig} layerDef
         * @return {string}
         */
        getPrintBaseUrl: function(layerDef) {
            var template = layerDef.options.tileUrls[0];
            return template
                .replace('{Style}', layerDef.options.style)
                // NOTE: casing of '{Style}' placeholder unspecified, emulate OpenLayers dual-casing support quirk
                .replace('{style}', layerDef.options.style)
                .replace('{TileMatrixSet}', layerDef.options.tilematrixset)
            ;
        }
    });
    Mapbender.Source.typeMap['wmts'] = WmtsSource;
    return WmtsSource;
}());
