window.Mapbender = Mapbender || {};
window.Mapbender.WmtsSource = (function() {
    function WmtsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    WmtsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    $.extend(WmtsSource.prototype, {
        constructor: WmtsSource,
        _initializeSingleCompatibleLayer: function(compatibleLayer, proj) {
<<<<<<< HEAD
            var matrixSet = this._getMatrixSet(compatibleLayer.options.tilematrixset);
=======
            var matrixSet = this.getMatrixSetByIdent(compatibleLayer.options.tilematrixset);
>>>>>>> origin/master
            var options = $.extend(this._getNativeLayerOptions(matrixSet, compatibleLayer, proj), {
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
        /**
         * @param {WmtsLayerConfig} layerDef
         * @param {WmtsTileMatrixSet} matrixSet
         * @param {OpenLayers.Projection} projection
         * @return {{matrixSet: string, matrixIds: any[]}}
         * @private
         */
        _getMatrixOptions: function(layerDef, matrixSet, projection) {
            var matrixIds = matrixSet.tilematrices.map(function(matrix) {
                if (matrix.topLeftCorner) {
                    return $.extend({}, matrix, {
                        topLeftCorner: OpenLayers.LonLat.fromArray(matrix.topLeftCorner)
                    });
                } else {
                    return $.extend({}, matrix);
                }
            });
            var self = this;
            return {
                matrixSet: matrixSet.identifier,
                matrixIds: matrixIds,
                serverResolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, projection);
                })
            };
        },
        /**
         * @param {WmtsTileMatrix} tileMatrix
         * @param {OpenLayers.Projection} projection
         * @return {Number}
         * @private
         */
        _getMatrixResolution: function(tileMatrix, projection) {
            var projectionUnits = projection.proj.units;
            // OGC TileMatrix scaleDenom is calculated using meters, irrespective of projection units
            // OGC TileMatrix scaleDenom is also calculated assuming 0.28mm per pixel
            // Undo both these unproductive assumptions and calculate a proper resolutiion for the
            // current projection
            var metersPerUnit = OpenLayers.INCHES_PER_UNIT['mUnits'] * OpenLayers.METERS_PER_INCH;
            if (projectionUnits === 'm' || projectionUnits === 'Meter') {
                metersPerUnit = 1.0;
            }
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
