Mapbender.Model = function(domId) {
    'use strict';
    this.map = new ol.Map({
        view:   new ol.View({
            center: [0, 0],
            zoom:   1
        }),
        target: domId
    });

    return this;
};

Mapbender.Model.prototype.map = null;
Mapbender.Model.prototype.vectorLayer = {};
Mapbender.Model.prototype.mapElement = null;
Mapbender.Model.prototype.currentProj = null;
Mapbender.Model.prototype.parseURL = function parseURL() {
};
Mapbender.Model.prototype.onMapClick = function onMapClick() {
    'use strict';
    return this;
};
Mapbender.Model.prototype.onFeatureClick = function onFeatureClick() {
    'use strict';
    return this;
};
Mapbender.Model.prototype.setLayerStyle = function setLayerStyle() {
};
Mapbender.Model.prototype.createStyle = function createStyle() {
};
Mapbender.Model.prototype.getActiveLayers = function getActiveLayers() {
};
Mapbender.Model.prototype.setRequestParameter = function setRequestParameter() {
};
Mapbender.Model.prototype.getCurrentProj = function getCurrentProj() {
    'use strict';
    return this.currentProj;
};
Mapbender.Model.prototype.getAllSrs = function getAllSrs() {
};
Mapbender.Model.prototype.getMapExtent = function getMapExtent() {
};
Mapbender.Model.prototype.getScale = function getScale() {
};

Mapbender.Model.prototype.center = function center() {
};

Mapbender.Model.prototype.addSource = function addSource() {
};
Mapbender.Model.prototype.removeSource = function removeSource() {
};
Mapbender.Model.prototype.setLayerOpacity = function setLayerOpacity() {
};
Mapbender.Model.prototype.changeProjection = function changeProjection() {
};

/**
 *
 * @param {object} config plain old data
 * @param {string} [id]
 * @returns {Mapbender.Model.Source}
 */
Mapbender.Model.prototype.sourceFromConfig = function sourceFromConfig(config, id) {
    'use strict';
    return Mapbender.Model.Source.fromConfig(this, config, id);
};

/**
 * Picks a (hopefully) unused source id based on the count of layers currently on the (engine-side) map.
 *
 * @returns {string}
 */
Mapbender.Model.prototype.generateSourceId = function generateSourceId() {
    'use strict';
    var layerCount = this.map.getLayers().length;
    return "autoSrc" + layerCount;
};

/**
 * @param {object} config
 * @return {Mapbender.Model.Source[]}
 */
Mapbender.Model.prototype.layerSetConfigToSources = function layerSetConfigToSources(config) {
    'use strict';
    var sources = [];
    _.forEach(config, function(sourceConfigWrapper) {
        _.forEach(sourceConfigWrapper, function(sourceConfig, sourceId) {
            var source = this.sourceFromConfig(sourceConfig, "" + sourceId);
            sources.push(source);
        }.bind(this));
    }.bind(this));
    return sources;
};

/**
 * @param {string[]} layerSetIds
 * @return {Mapbender.Model.Source[]}
 */
Mapbender.Model.prototype.sourcesFromLayerSetIds = function sourcesFromLayerSetIds(layerSetIds) {
    'use strict';
    var sources = [];
    _.forEach(layerSetIds, function(layerSetId) {
        var layerSetConfig = Mapbender.configuration.layersets["" + layerSetId];
        if (typeof layerSetConfig === 'undefined') {
            throw new Error("Unknown layerset '" + layerSetId + "'");
        }
        sources = sources.concat(this.layerSetConfigToSources(layerSetConfig));
    }.bind(this));

    return sources;
};

/**
 * Adapts a model source to an engine source.
 * TBD: This may only be required for Openlayers 4? Other engines may operate exclusively on displayable layers
 *      (not to be confused with WMS layers), and not model sources separately.
 *
 * @param {Mapbender.Model.Source} modelSource
 * @return {ol.source.Source}
 */
Mapbender.Model.prototype.modelSourceToEngineSource = function modelSourceToEngineSource(modelSource) {
    'use strict';
    var engineOpts;
    var sourceType = modelSource.getType();
    switch (sourceType.toLowerCase()) {
        case 'wms':
            engineOpts = {
                url: modelSource.getBaseUrl(),
                params: {
                    LAYERS: modelSource.activeLayerNames
                }
            };
            return new ol.source.TileWMS(engineOpts);
        default:
            throw new Error("Unhandled source type '" + sourceType + "'");
    }
};

/**
 *
 * @param {object} sourceConfig plain old data as seen in application config or WmsLoader/loadWms response
 * @param {string} [id]
 * @returns {Mapbender.Model.Source}
 */
Mapbender.Model.prototype.addSourceFromConfig = function addSourceFromConfig(sourceConfig, id) {
    'use strict';
    var id_;
    if (typeof id === 'undefined') {
        id_ = this.generateSourceId();
    } else {
        id_ = '' + id;
    }
    var source = this.sourceFromConfig(sourceConfig, id_);
    var olSource = this.modelSourceToEngineSource(source);
    var engineLayer = new ol.layer.Tile({source: olSource});
    this.map.addLayer(engineLayer);
    return source;
};

/**
 * @param {string[]} layerSetIds, in draw order
 * @param layerSetIds
 */
Mapbender.Model.prototype.addLayerSetsById = function addLayerSetsById(layerSetIds) {
    'use strict';
    var sources = this.sourcesFromLayerSetIds(layerSetIds);
    var engineLayers = _.map(sources.reverse(), function(source) {
        var olSource = this.modelSourceToEngineSource(source);
        return new ol.layer.Tile({source: olSource});
    }.bind(this));

    for (var i = 0; i < engineLayers.length; ++i) {
        this.map.addLayer(engineLayers[i]);
    }
};


Mapbender.Model.prototype.createVectorLayer = function(options, style, owner){
    'use strict';
    var uuid = Mapbender.UUID();
    this.vectorLayer[owner] = this.vectorLayer[owner] || {};
    options.map = this.map;
    var vectorLayer = new ol.layer.Vector(options,{
        style: style }
    );
    this.vectorLayer[owner][uuid] = vectorLayer;

    return uuid;
};

// /**
//  *
//  * @param options
//  * @returns {ol.Geolocation}
//  */
// Mapbender.Model.prototype.createGeolocation = function (options) {
//     return new ol.Geolocation(options)
// };
//
// /**
//  *
//  * @param options
//  * @returns {*}
//  */
// Mapbender.Model.prototype.createProjection = function (options) {
//     return new ol.proj.Projection(options)
// };

/**
 *
 * @param array
 * @returns {ol.Coordinate}
 */
Mapbender.Model.prototype.createCoordinate = function (array) {
    return new ol.Coordinate(array)
};

/**
 * // https://openlayers.org/en/latest/apidoc/ol.proj.html#.transform
 * @param coordinate
 * @param source
 * @param destination
 * @returns {ol.Coordinate}
 */
Mapbender.Model.prototype.transform = function transform(coordinate, source, destination) {
    return new ol.Coordinate(newCoordinate)
};