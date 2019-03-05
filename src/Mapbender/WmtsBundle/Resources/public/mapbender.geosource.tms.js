/**
 * Tms Source Handler
 * @author Paul Schmidt
 */
Mapbender.Geo.TmsSourceHandler = Class({
    'extends': Mapbender.Geo.SourceHandler
}, {
    'private string layerNameIdent': 'identifier',
    'public function create': function(sourceOpts) {
        var rootLayer = sourceOpts.configuration.children[0];
        var proj = Mapbender.Model.getCurrentProj();
        var layer = this.findLayerEpsg(sourceOpts, proj.projCode);
        if (!layer) { // find first layer with epsg from srs list to initialize.
            var allsrs = Mapbender.Model.getAllSrs();
            for (var i = 0; i < allsrs.length; i++) {
                layer = this.findLayerEpsg(sourceOpts, allsrs[i].name);
                if (layer) {
                    break;
                }
            }
        }
        rootLayer['children'] = [layer];

        var layerOptions = this._createLayerOptions(sourceOpts, layer);
        // hide layer without start srs -> remove name
        var mqLayerDef = {
            type: 'tms',
            isBaseLayer: false,
            opacity: sourceOpts.configuration.options.opacity,
            visible: sourceOpts.configuration.options.visible,
            attribution: sourceOpts.configuration.options.attribution
        };
        $.extend(layerOptions, mqLayerDef);
        return layerOptions;
    },
    'public function postCreate': function(source, mqLayer) {
        // this.changeProjection(source, Mapbender.Model.getCurrentProj());
    },
    /**
     * @param {WmtsLayerConfig} layer
     * @param {WmtsTileMatrixSet} matrixSet
     */
    _getMatrixOptions: function(layer, matrixSet) {
        var options = {
            layername: layer.options.identifier,
            tileSize: new OpenLayers.Size(matrixSet.tileSize[0], matrixSet.tileSize[1]),
            params: {
                LAYERS: [layer.options.identifier]
            },
            tileOriginCorner: 'bl',
            serverResolutions: matrixSet.tilematrices.map(function(tileMatrix) {
                return tileMatrix.scaleDenominator;
            })
        };
        if (matrixSet.origin && matrixSet.origin.length) {
            options.tileOrigin = new OpenLayers.LonLat(matrixSet.origin[0], matrixSet.origin[1]);
        }
        return options;
    },
    _createLayerOptions: function(sourceDef, layer) {
        var matrixSet = this._getLayerMatrixSet(sourceDef, layer);
        var layerOptions = $.extend(this._getMatrixOptions(layer, matrixSet), {
            label: layer.options.title,
            layername: layer.options.identifier,
            tileSize: new OpenLayers.Size(matrixSet.tileSize[0], matrixSet.tileSize[1]),
            url: layer.options.url,
            format: layer.options.format
        });
        return layerOptions;
    },
    findLayerEpsg: function(sourceDef, epsg) {
        var layers = sourceDef.configuration.layers;
        for (var i = 0; i < layers.length; i++) {
            var tileMatrixSet = this._getLayerMatrixSet(sourceDef, layers[i]);
            if (epsg === this.urnToEpsg(tileMatrixSet.supportedCrs)) {
                return layers[i];
            }
        }
        return null;
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layerDef
     * @return {WmtsTileMatrixSet|null}
     */
    _getLayerMatrixSet: function(sourceDef, layerDef) {
        var matrixSets = sourceDef.configuration.tilematrixsets;
        for(var i = 0; i < matrixSets.length; i++){
            if (layerDef.options.tilematrixset === matrixSets[i].identifier){
                return matrixSets[i];
            }
        }
        return null;
    },
    /**
     * @param {string} urnOrEpsgIdentifier
     * @return {string}
     */
    urnToEpsg: function(urnOrEpsgIdentifier) {
        // @todo: drop URNs server-side, they offer no benefit here
        return urnOrEpsgIdentifier.replace(/^urn:.*?(\d+)$/, 'EPSG:$1');
    },
    /**
     * @param {WmtsTileMatrix} tileMatrix
     * @param {OpenLayers.Projection} projection
     * @return {Number}
     * @private
     */
    _getMatrixResolution: function(tileMatrix, projection) {
        // Yes, seriously, it's called scaleDenominator but it's the resolution
        // @todo: resolve backend config wording weirdness
        return tileMatrix.scaleDenominator;
    },
    /**
     * @param {WmtsSourceConfig} sourceDef
     * @param {WmtsLayerConfig} layer
     * @param {number} scale
     * @param {OpenLayers.Projection} projection
     * @return {WmtsTileMatrix}
     */
    _getMatrix: function(sourceDef, layer, scale, projection) {
        var resolution = OpenLayers.Util.getResolutionFromScale(scale, projection.proj.units);
        var matrixSet = this._getLayerMatrixSet(sourceDef, layer);
        var scaleDelta = Number.POSITIVE_INFINITY;
        var closestMatrix = null;
        for (var i = 0; i < matrixSet.tilematrices.length; ++i) {
            var matrix = matrixSet.tilematrices[i];
            var matrixRes = this._getMatrixResolution(matrix, projection);
            var resRatio = matrixRes / resolution;
            var matrixScaleDelta = Math.abs(resRatio - 1);
            if (matrixScaleDelta < scaleDelta) {
                scaleDelta = matrixScaleDelta;
                closestMatrix = matrix;
            }
        }
        return closestMatrix;
    },
    'public function featureInfoUrl': function(mqLayer, x, y) {

    },
    getPrintConfigEx: function(source, bounds, scale, projection) {
        var layerDef = this.findLayerEpsg(source, projection.projCode);
        if (!layerDef.state.visibility) {
            return [];
        }
        var matrix = this._getMatrix(source, layerDef, scale, projection);
        var baseUrl = layerDef.options.url + '1.0.0/' + layerDef.options.identifier;
        return [
            {
                url: Mapbender.Util.removeProxy(baseUrl),
                matrix: $.extend({}, matrix),
                resolution: this._getMatrixResolution(matrix, projection)
            }
        ];
    },
    'public function getPrintConfig': function(olLayer, bounds) {
        var source = olLayer.mbConfig || (Mapbender.Model.findSource({ollid: olLayer.id}))[0];
        var layerDef = this.findLayer(source, {identifier: olLayer.layername}).layer;
        var url = layerDef.options.url + '1.0.0/' + layerDef.options.identifier;
        var matrixSet = this._getLayerMatrixSet(source, layerDef);
        var printConfig = {
            type: 'tms',
            url: Mapbender.Util.removeProxy(url),
            matrixset: $.extend({}, matrixSet)
        };
        return printConfig;
    },
    'public function changeProjection': function(source, projection) {
        var layer = this.findLayerEpsg(source, projection.projCode);
        var matrixSet = layer && this._getLayerMatrixSet(source, layer);
        var olLayer = layer && Mapbender.Model.getNativeLayer(source);
        if (layer && olLayer && matrixSet) {
            var matrixOptions = this._getMatrixOptions(layer, matrixSet);
            $.extend(olLayer, matrixOptions);
        }
    }
});
Mapbender.source['tms'] = new Mapbender.Geo.TmsSourceHandler();

if ($.MapQuery.Layer.types['tms']) {
    $.MapQuery.Layer.types['tms'] = function(options) {
        var o = $.extend(true, {}, $.fn.mapQuery.defaults.layer.all,
            $.fn.mapQuery.defaults.layer.tms,
            options);
        var label = options.label || undefined;
        var url = options.url || undefined;
        var params = {
            layername: o.layername,
            type: o.format.split('/').pop(),
            tileOrigin: o.tileOrigin,
            tileSize: o.tileSize,
            isBaseLayer: o.isBaseLayer,
            serverResolutions: o.serverResolutions,
            tileOriginCorner: o.tileOriginCorner
        };
        return {
            layer: new OpenLayers.Layer.TMS(label, url, params),
            options: o
        };
    }
}
