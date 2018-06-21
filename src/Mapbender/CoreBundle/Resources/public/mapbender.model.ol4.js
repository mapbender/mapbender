Mapbender.Model = function(domId) {
    'use strict';
    this.vectorLayer = {};
    this.map = new ol.Map({
        view:   new ol.View({
            center: [0, 0],
            zoom:   1
        }),
        target: domId
    });
    // ordered list of WMS / WMTS etc sources that provide pixel tiles
    this.pixelSources = [];


    return this;
};

Mapbender.Model.prototype.layerTypes = {
    vector: 'vectorLayer'
};


Mapbender.Model.prototype.DRAWTYPES = ['Point', 'LineString', 'LinearRing', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon', 'GeometryCollection', 'Circle'];

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

// @todo is just a mockup now.
Mapbender.Model.prototype.createStyle = function createStyle() {
    return new ol.style.Style({
        fill: new ol.style.Fill({
            color: '#ffcc33'
        }),
        stroke: new ol.style.Stroke({
            color: '#ffcc33',
            width: 2
        }),
        image: new ol.style.Circle({
            radius: 7,
            fill: new ol.style.Fill({
                color: '#ffcc33'
            })
        })
    });
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
    var layerSetConfig = Mapbender.configuration.layersets['' + layerSetId];
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
    var sourceType = sourceObj.getType();
    var sourceOpts = {
        url: sourceObj.getBaseUrl(),
        transition: 0
    };

    var olSourceClass;
    var olLayerClass;
    switch (sourceType.toLowerCase()) {
        case 'wms':
            if (sourceObj.options.tiled) {
                olSourceClass = ol.source.TileWMS;
                olLayerClass = ol.layer.Tile;
            } else {
                olSourceClass = ol.source.ImageWMS;
                olLayerClass = ol.layer.Image;
            }
            break;
        default:
            throw new Error("Unhandled source type '" + sourceType + "'");
    }
    var olSource = new (olSourceClass)(sourceOpts);
    var engineLayer = new (olLayerClass)({source: olSource});
    this.pixelSources.push(sourceObj);
    this.map.addLayer(engineLayer);
    sourceObj.initializeEngineLayer(engineLayer);
    sourceObj.updateEngine();
};

/**
 *
 * @param {string} sourceId
 * @returns Mapbender.Model.Source
 * @internal
 */
Mapbender.Model.prototype.getSourceById = function getSourceById(sourceId) {
    var safeId = "" + sourceId;
    for (var i = 0; i < this.pixelSources.length; ++i) {
        var source = this.pixelSources[i];
        if (source.id === safeId) {
            return source;
        }
    }
    return null;
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
 *
 * @param  {Object} options (See https://openlayers.org/en/latest/apidoc/ol.layer.Vector.html)
 * @param {ol.style|function} style
 * @param {string} owner
 * @returns {string}
 */
Mapbender.Model.prototype.createVectorLayer = function(options, style, owner) {
    'use strict';
    var uuid = Mapbender.UUID();
    this.vectorLayer[owner] = this.vectorLayer[owner] || {};
    options.map = this.map;
    this.vectorLayer[owner][uuid] = new ol.layer.Vector(options, {
        style: style
    });

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
    return new ol.Coordinate(array);
};

/**
 * @see: https://openlayers.org/en/latest/apidoc/ol.proj.html#.transform
 * @param coordinate
 * @param source
 * @param destination
 * @returns {ol.Coordinate}
 */
Mapbender.Model.prototype.transform = function transform(coordinate, source, destination) {
    return new ol.Coordinate(newCoordinate);
};

/**
 *
 * @param owner
 * @param uuid
 * @param style
 * @param refresh
 */
Mapbender.Model.prototype.setVectorLayerStyle = function(owner, uuid, style, refresh){
    'use strict';
    this.setLayerStyle('vectorLayer', owner, uuid, style);
};

/**
 *
 * @param layerType
 * @param owner
 * @param uuid
 * @param style
 * @param refresh
 */
Mapbender.Model.prototype.setLayerStyle = function setLayerStyle(layerType, owner, uuid, style, refresh){
    'use strict';
    this.vectorLayer[owner][uuid].setLayerStyle(new ol.style.Style(style));
    if(refresh){
        this.vectorLayer[owner][uuid].refresh();
    }

};
Mapbender.Model.prototype.createDrawControl = function createDrawControl(type, owner, style, events){
    'use strict';

    if(!_.contains( this.DRAWTYPES,type )){
        throw new Error('Mapbender.Model.createDrawControl only supports the operations' + this.DRAWTYPES.toString()+ 'not' + type);
    }
    var vector = new ol.source.Vector({wrapX: false});
    var id = this.createVectorLayer({ source : vector, style : style}, {}, owner);

    var draw =  new ol.interaction.Draw({
        source: vector,
        type: type
    });


    this.vectorLayer[owner][id].interactions = this.vectorLayer[owner][id].interactions  || {};
    this.vectorLayer[owner][id].interactions[id] = draw;


    _.each(events, function(value, key) {
        draw.on(key, value);
    }.bind(this));

    this.map.addInteraction(draw);

    return id;

};

Mapbender.Model.prototype.removeVectorLayer = function removeVectorLayer(owner,id){
    var vectorLayer = this.vectorLayer[owner][id];
    if(this.vectorLayer[owner][id].hasOwnProperty('interactions')){
        this.removeInteractions(this.vectorLayer[owner][id].interactions);
    }
    this.map.removeLayer(vectorLayer);

};

Mapbender.Model.prototype.removeInteractions = function removeControls(controls){
    _.each(controls, function(control, index){
        this.map.removeInteraction(control);
    }.bind(this));


};

Mapbender.Model.prototype.eventFeatureWrapper = function eventFeatureWrapper(event, callback, args){
    'use strict';
    var args = [event.feature].concat(args)
    return callback.apply(this,args);

};



Mapbender.Model.prototype.getLineStringLength = function(line){
    'use strict';

    return  ol.Sphere.getLength(line);
};

Mapbender.Model.prototype.onFeatureChange = function(feature, callback,obvservable, args){
    'use strict';

    return feature.getGeometry().on('change', function(evt) {
        var geom = evt.target;
        args = [geom].concat(args);
        obvservable.value =  callback.apply(this,args);
    });


};



Mapbender.Model.prototype.createVectorLayerStyle = function createVectorLayerStyle(){
    return {};
}







/**
 * @returns {string[]}
 */
Mapbender.Model.prototype.getActiveSourceIds = function() {
    var ids = [];
    for (var i = 0; i < this.pixelSources.length; ++i) {
        var source = this.pixelSources[i];
        if (source.isActive()) {
            ids.push(source.id);
        }
    }
    return ids;
};

/**
 * @returns {string[]}
 * @param sourceId
 */
Mapbender.Model.prototype.getActiveLayerNames = function(sourceId) {
    return this.getSourceById(sourceId).getActiveLayerNames();
};
