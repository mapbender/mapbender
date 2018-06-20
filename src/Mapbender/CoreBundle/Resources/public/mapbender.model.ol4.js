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
/**
 * @returns {string}
 */
Mapbender.Model.prototype.getCurrentProjectionCode = function getCurrentProj() {
    'use strict';
    return this.map.getView().getProjection().getCode();
};

/**
 * @returns {ol.proj.Projection}
 */
Mapbender.Model.prototype.getCurrentProjectionObject = function getCurrentProj() {
    'use strict';
    return this.map.getView().getProjection();
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
 * @param {string} layerSetId
 * @return {Mapbender.Model.Source[]}
 */
Mapbender.Model.prototype.sourcesFromLayerSetId = function sourcesFromLayerSetIds(layerSetId) {
    'use strict';
    var layerSetConfig = Mapbender.configuration.layersets["" + layerSetId];
    var sources = [];
    if (typeof layerSetConfig === 'undefined') {
        throw new Error("Unknown layerset '" + layerSetId + "'");
    }
    _.forEach(layerSetConfig, function(sourceConfigWrapper) {
        _.forEach(sourceConfigWrapper, function(sourceConfig, sourceId) {
            var source = this.sourceFromConfig(sourceConfig, "" + sourceId);
            sources.push(source);
        }.bind(this));
    }.bind(this));
    return sources;
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
    this.addSourceObject(source);
    return source;
};

/**
 * Adds a model source to the map.
 *
 * @param {Mapbender.Model.Source} sourceObj
 */
Mapbender.Model.prototype.addSourceObject = function addSourceObj(sourceObj) {
    var engineOpts;
    var sourceType = sourceObj.getType();
    var olSource;
    switch (sourceType.toLowerCase()) {
        case 'wms':
            engineOpts = {
                url: sourceObj.getBaseUrl(),
                params: {
                    LAYERS: sourceObj.activeLayerNames
                }
            };
            olSource = new ol.source.TileWMS(engineOpts);
            break;
        default:
            throw new Error("Unhandled source type '" + sourceType + "'");
    }

    var engineLayer = new ol.layer.Tile({source: olSource});
    this.map.addLayer(engineLayer);
};

/**
 * @param {string} layerSetId, in draw order
 */
Mapbender.Model.prototype.addLayerSetById = function addLayerSetsById(layerSetId) {
    'use strict';
    var sources = this.sourcesFromLayerSetId(layerSetId).reverse();
    for (var i = 0; i < sources.length; ++i) {
        this.addSourceObject(sources[i]);
    }
};

/**
 * Create draw control
 *
 * @param {ol.source.Vector} source
 * @param {ol.geom.GeometryType} type
 * @returns {*}
 * @constructor
 */
Mapbender.Model.prototype.DrawFeature = function DrawFeature(source, type) {
    'use strict';
    return ol.interaction.Draw({
        source: source,
        type: type
    });
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
 * @param array {lat,lon}
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
