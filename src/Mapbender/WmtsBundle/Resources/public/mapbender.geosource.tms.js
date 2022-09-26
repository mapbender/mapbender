window.Mapbender = Mapbender || {};
window.Mapbender.TmsSource = (function() {
    function TmsSource(definition) {
        Mapbender.WmtsTmsBaseSource.apply(this, arguments);
    }
    TmsSource.prototype = Object.create(Mapbender.WmtsTmsBaseSource.prototype);
    Mapbender.Source.typeMap['tms'] = TmsSource;
    Object.assign(TmsSource.prototype, {
        constructor: TmsSource,
        _initializeSingleCompatibleLayer: function(compatibleLayer, srsName) {
            switch (Mapbender.mapEngine.code) {
                default:
                    return this._layerFactory(compatibleLayer, srsName);
                case 'ol2':
                    return this._layerFactoryOl2(compatibleLayer, srsName);
            }
        },
        _layerFactoryOl2: function(compatibleLayer, srsName) {
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
        _layerFactory: function(layer, srsName) {
            var matrixSet = layer.getMatrixSet();
            var self = this;
            var gridOpts = {
                origin: matrixSet.origin,
                resolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                    return self._getMatrixResolution(tileMatrix, srsName);
                }),
                extent: layer.getBounds(srsName, true)
            };
            var imgExt = /jp(e)?g$/i.test(layer.options.format) && '.jpg' || '.png';
            var sourceOpts = {
                tileUrlFunction: function(coord, ratio, projection) {
                    return [
                        matrixSet.tilematrices[coord[0]].href.replace(/[/&?]*$/, ''),
                        '/', coord[1],
                        '/', -coord[2] - 1,
                        imgExt
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
        /**
         * @param {WmtsLayerConfig} layerDef
         * @return {string}
         */
        getPrintBaseUrl: function(layerDef) {
            return [layerDef.options.tileUrls[0], this.configuration.version, '/', layerDef.options.identifier].join('');
        }

    });
    return TmsSource;
}());
