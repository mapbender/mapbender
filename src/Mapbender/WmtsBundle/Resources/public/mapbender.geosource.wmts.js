Mapbender.Geo.WmtsSourceHandler = Class({'extends': Mapbender.Geo.SourceTmsWmtsCommon },{
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
            }),
            maxExtent: this._getMaxExtent(layerDef, projection)
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
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {WmtsTileMatrixSet} matrixSet
     * @param {OpenLayers.Projection} projection
     * @return {*}
     */
    _createLayerOptions: function(sourceDef, layer, matrixSet, projection) {
        return $.extend(this.super('_createLayerOptions', sourceDef, layer, matrixSet, projection), {
            layer: layer.options.identifier,
            style: layer.options.style
        });
        return layerOptions;
    },
    /**
     * @param {WmtsLayerConfig} layer
     * @param {OpenLayers.Projection} projection
     * @return {OpenLayers.Bounds|null}
     * @private
     */
    _getMaxExtent: function(layer, projection) {
        var projCode = projection.projCode;
        if (layer.options.bbox[projCode]) {
            return OpenLayers.Bounds.fromArray(layer.options.bbox[projCode]);
        } else {
            var bboxSrses = Object.keys(layer.options.bbox);
            for (var i = 0 ; i < bboxSrses.length; ++i) {
                var bboxSrs = bboxSrses[i];
                var bboxArray = layer.options.bbox[bboxSrs];
                return OpenLayers.Bounds.fromArray(bboxArray).transform(
                    Mapbender.Model.getProj(bboxSrs),
                    projection
                );
            }
        }
        return null;
    },
    /**
     * @param {Object} sourceDef
     * @param {WmtsLayerConfig} layerDef
     * @return {string}
     * @private
     */
    _getPrintBaseUrl: function(sourceDef, layerDef) {
        return layerDef.options.tileUrls[0];
    },
    changeProjection: function(source, projection) {
        if (this.super('changeProjection', source, projection)) {
            Mapbender.Model.getNativeLayer(source).updateMatrixProperties();
            return true;
        } else {
            return false;
        }
    }
});
$.MapQuery.Layer.types.wmts = function(options) {
    options.requestEncoding = 'REST';
    return {
        layer: new OpenLayers.Layer.WMTS(options),
        options: options
    };
};
Mapbender.source['wmts'] = new Mapbender.Geo.WmtsSourceHandler();
