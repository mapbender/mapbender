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
            if (Mapbender.mapEngine.code === 'ol4') {
                return this._ol4LayerFactory(compatibleLayer, srsName);
            }
            var matrixSet = compatibleLayer.getMatrixSet();
            var options = this._getNativeLayerBaseOptions(compatibleLayer, srsName);
            Object.assign(options, {
                requestEncoding: 'REST',
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
        _ol4LayerFactory: function(layer, srsName) {
            var matrixSet = layer.getMatrixSet();
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
                format: layer.options.format,
                style: layer.options.style,
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
    return WmtsSource;
}());
