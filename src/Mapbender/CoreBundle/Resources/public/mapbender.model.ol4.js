/**
 *
 * @param domId
 * @param options
 * @returns {Mapbender.Model}
 * @constructor
 */
Mapbender.Model = function(domId, options) {
    'use strict';
    this.vectorLayer = {};
    if (!options || !options.srs || !options.maxExtent) {
        console.error("Options srs and maxExtent required");
        throw new Error("Can't initialize model");
    }
    this.options = options;
    this.viewOptions_ = this.initializeViewOptions(options);
    var view = new ol.View(this.viewOptions_);
    // remove zoom after creating view
    delete this.viewOptions_['zoom'];
    this.map = new ol.Map({
        view: view,
        target: domId
    });

    // ordered list of WMS / WMTS etc sources that provide pixel tiles
    /** @type {Array.<Mapbender.SourceModelOl4>} **/
    this.pixelSources = [];
    this.zoomToExtent(options.startExtent || options.maxExtent);
    // @todo: ???
    //var popupOverlay = new Mapbender.Model.MapPopup(undefined, this);
    /*this.map.on('singleclick', function(evt) {


        var coordinate = evt.coordinate;
        popupOverlay.openPopupOnXY(coordinate, function(coord){return '<span> X:  ' + coord[0] + '<span/><span> Y: '+ coord[1] + '</span>' });
    });*/
    return this;
};
Mapbender.Model.SourceModel = Mapbender.SourceModelOl4;
Mapbender.Model.prototype.SourceModel = Mapbender.SourceModelOl4;

Mapbender.Model.prototype.layerTypes = {
    vector: 'vectorLayer'
};


Mapbender.Model.prototype.DRAWTYPES = ['Point', 'LineString', 'LinearRing', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon', 'GeometryCollection', 'Circle', 'Box'];

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

/**
 * @todo is not complete yet
 *
 * @param {Object} options
 * @returns {ol.style.Style}
 */
Mapbender.Model.prototype.createStyle = function createStyle(options) {

    var style = new ol.style.Style();

    if (options['fill']) {
        var fill = new ol.style.Fill(options['fill']);
        style.setFill(fill);
    }

    if (options['stroke']) {
        var stroke = new ol.style.Stroke(options['stroke']);
        style.setStroke(stroke);
    }

    if (options['circle']) {
        var circle = new ol.style.Circle({
            radius: options['circle'].radius,
            fill: new ol.style.Fill({
                color: options['circle'].color
            }),
            stroke: new ol.style.Stroke(options['circle']['stroke'])
        });
        style.setImage(circle);
    }

    if (options['text']) {
        var text = new ol.style.Text({
            font: options['text']['font'],
            text: options['text']['text'],
            fill: new ol.style.Fill({
                color: options['text']['fill'].color
            }),
            stroke: new ol.style.Stroke(options['text']['stroke'])
        });
        style.setText(text);
    }

    return style;
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
    if(this.map){
        return this.map.getView().getProjection();
    } else {
      return new ol.proj.Projection({
          code: this.options.srs,
          extent: this.options.maxExtent
      });
    }

};

/**
 *
 * @returns {*|OpenLayers.Bounds}
 */
Mapbender.Model.prototype.getMapExtent = function getMapExtent() {
    'use strict';
    return this.map.getView().calculateExtent();
};

/** @todo (following methods): put the "default" dpi in a common place? */
/**
 *
 * @param {number} dpi default 72 DPI
 * @param {boolean} optRound Whether to round the scale or not.
 * @param {boolean} optScaleRating Whether to round the scale rating or not. K:X000 and M:X000000
 * @returns {number}
 */
Mapbender.Model.prototype.getScale = function getScale(dpi, optRound, optScaleRating) {
    var resolution = this.map.getView().getResolution();
    var scaleCalc = this.resolutionToScale(resolution, dpi);
    var scale = optRound ? Math.round(scaleCalc) : scaleCalc;

    if (optScaleRating){
        if (scale >= 10 && scale <= 1000) {
            scale = Math.round(scale/ 10) + "0";
        } else if (scale >= 1000 && scale <= 9500) {
            scale = Math.round(scale/ 100) + "00";
        } else if(scale >= 9500 && scale <= 950000) {
            scale = Math.round(scale/ 1000) + "000";
        } else if (scale >= 950000) {
            scale = Math.round(scale / 1000000) + "000000";
        } else {
            scale = Math.round(scale);
        }
    }

    scale = typeof scale ? parseFloat(scale) : scale;

    return scale;
};

/**
 *
 * @param {float} resolution
 * @param {number} [dpi=72]
 * @param {string} unit "m" or "degrees"
 * @returns {number}
 */
Mapbender.Model.prototype.resolutionToScale = function(resolution, dpi) {
    var currentUnit = this.getUnitsOfCurrentProjection();
    var mpu = this.getMetersPerUnit(currentUnit);
    var inchesPerMetre = 39.37;
    return resolution * mpu * inchesPerMetre * dpi;
};

/**
 * @param {float} scale
 * @param {number} dpi
 * @param {string} unit
 * @returns {number}
 */
Mapbender.Model.scaleToResolutionStatic = function(scale, dpi, unit) {
    if (!dpi || !unit) {
        console.error("Must supply dpi and unit", scale, dpi, unit);
        throw new Error("Must supply dpi and unit");
    }
    var mpu = this.getMetersPerUnit(unit);
    var inchesPerMetre = 39.37;
    return scale / (mpu * inchesPerMetre * dpi);
};
// make available on instance
Mapbender.Model.prototype.scaleToResolutionStatic = Mapbender.Model.scaleToResolutionStatic;

/**
 *
 * @param {float} scale
 * @param {number} [dpi]
 * @returns {number}
 */
Mapbender.Model.prototype.scaleToResolution = function(scale, dpi) {
    var currentUnit = this.getUnitsOfCurrentProjection();
    return this.scaleToResolutionStatic(scale, dpi || 72, currentUnit);
};

/**
 *
 * @param {float} scale
 * @param {number} [dpi]
 * @returns {number}
 */
Mapbender.Model.prototype.scaleToZoom = function(scale, dpi) {
    var resolution = this.scaleToResolution(scale, dpi);
    return this.map.getView().getZoomForResolution(resolution);
};

/**
 *
 * @param {float} scale
 * @param {number} [dpi]
 * @returns {number}
 */
Mapbender.Model.prototype.setScale = function(scale, dpi) {
    this.map.getView().setZoom(this.scaleToZoom(scale, dpi));
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
 * @type {number}
 * @static
 * @private
 */
Mapbender.Model.nextGeneratedSourceId_ = 1;

/**
 * Generate a string id for a source that doesn't have one. Preconfigured sources
 * (application backend: "Layersets") always have an id. Sources supplied dynamically
 * by WmsLoader and / or WmcHandler might not.
 *
 * @returns {string}
 * @static
 */
Mapbender.Model.generateSourceId = function generateSourceId() {
    return "src-autoid-" + (Mapbender.Model.nextGeneratedSourceId_++);
};
Mapbender.Model.prototype.generateSourceId = Mapbender.Model.generateSourceId;

/**
 *
 * @param {object} config plain old data
 * @param {string} [id]
 * @returns {Mapbender.SourceModelOl4}
 * @static
 */
Mapbender.Model.sourceFromConfig = function sourceFromConfig(config, id) {
    'use strict';
    return this.SourceModel.fromConfig(config, id || this.generateSourceId());
};
Mapbender.Model.prototype.sourceFromConfig = Mapbender.Model.sourceFromConfig;

/**
 * @param {string} layerSetId
 * @return {Array.<Mapbender.SourceModelOl4>}
 * @static
 */
Mapbender.Model.sourcesFromLayerSetId = function sourcesFromLayerSetIds(layerSetId) {
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
Mapbender.Model.prototype.sourcesFromLayerSetId = Mapbender.Model.sourcesFromLayerSetId;

/**
 *
 * @param {object} sourceConfig plain old data as seen in application config or WmsLoader/loadWms response
 * @param {string} [id]
 * @returns {Mapbender.SourceModelOl4}
 */
Mapbender.Model.prototype.addSourceFromConfig = function addSourceFromConfig(sourceConfig, mangleIds) {
    'use strict';
    var id_ = sourceConfig.id || sourceConfig.origId;
    // DO NOT check ids strictly for being undefined. We do not want to use
    // boolean false or empty strings as ids, ever
    if (!id_) {
        if (mangleIds) {
            id_ = this.generateSourceId();
            sourceConfig.origId = sourceConfig.id || id_;
            sourceConfig.id = id_;
        } else {
            console.error("Can't initialize source with emptyish id", id_, sourceConfig);
            throw new Error("Can't initialize source with emptyish id");
        }
    }
    var source = this.sourceFromConfig(sourceConfig, '' + id_);
    this.addSourceObject(source);
    return source;
};

/**
 * @param {Mapbender.SourceModelOl4} sourceObj
 * @param {ol.Extent} extent
 * @returns {(ol.layer.Tile|ol.layer.Image)}
 */
Mapbender.Model.layerFactoryStatic = function layerFactoryStatic(sourceObj, extent) {
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

    var layerOptions = {
        source: new (olSourceClass)(sourceOpts),

    };
    var layer = new (olLayerClass)(layerOptions);
    sourceObj.initializeEngineLayer(layer);
    return layer;
};
Mapbender.Model.prototype.layerFactoryStatic = Mapbender.Model.layerFactoryStatic;

/**
 * @param {Mapbender.SourceModelOl4} sourceObj
 * @returns {(ol.layer.Tile|ol.layer.Image)}
 */
Mapbender.Model.prototype.layerFactory = function layerFactory(sourceObj) {
    var extent = this.map.getView().getProjection().getExtent();
    return this.layerFactoryStatic(sourceObj, extent);
};

/**
 * @param {Mapbender.SourceModelOl4} sourceObj
 */
Mapbender.Model.prototype.addSourceObject = function addSourceObject(sourceObj) {
    var engineLayer = this.layerFactory(sourceObj);
    this.pixelSources.push(sourceObj);
    this.map.addLayer(engineLayer);
    sourceObj.updateEngine();
};

/**
 *
 * @param {string} sourceId
 * @returns {Mapbender.SourceModelOl4}
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
Mapbender.Model.prototype.createVectorLayer = function(options, owner) {
    'use strict';
    var uuid = Mapbender.UUID();
    this.vectorLayer[owner] = this.vectorLayer[owner] || {};
    options.map = this.map;
    this.vectorLayer[owner][uuid] = new ol.layer.Vector(options);

    return uuid;
};

/**
 *
 * @param array
 * @param deltaArray
 * @returns {ol.coordinate.add}
 */
Mapbender.Model.prototype.addCoordinate= function addCoordinate(array, deltaArray) {
    'use strict';

    if (!deltaArray) {
        deltaArray = [0, 0];
    }

    return new ol.coordinate.add(array, deltaArray);
};

/**
 *
 * @param coordinate
 * @param source
 * @param destination
 * @returns {ol.Coordinate}
 */

Mapbender.Model.prototype.transformCoordinate = function transformCoordinate(coordinate, source, destination) {
    'use strict';
    return ol.proj.transform(coordinate, source, destination);
};

/**
 *
 * @param coordinate
 * @param opt_projection
 * @returns {ol.Coordinate}
 */
Mapbender.Model.prototype.toLonLat = function toLonLat(coordinate, opt_projection) {
    'use strict';
    return ol.proj.toLonLat(coordinate,opt_projection);
};

/**
 *
 * @param owner
 * @returns {*}
 */
Mapbender.Model.prototype.getVectorLayerByNameId = function getVectorLayerByNameId(owner, id) {
    'use strict';
    var vectorLayer = this.vectorLayer;
    return  vectorLayer[owner][id];
};

/**
 *
 * @param owner
 * @param featuresArray
 */
Mapbender.Model.prototype.addFeaturesVectorSource = function addFeaturesVectorSource(owner,featuresArray) {
    'use strict';
    var vectorLayer = this.vectorLayer[owner];
    var vectorSource = new ol.source.Vector({
        features: featuresArray
    });
    vectorLayer.setSource(vectorSource);

};

/**
 *
 * @param center
 * @returns {*|void}
 */
Mapbender.Model.prototype.setCenter = function setCenter(center) {
    'use strict';
    return this.map.getView().setCenter(center);
};

/**
 *
 * @param zoom
 * @returns {*}
 */
Mapbender.Model.prototype.setZoom = function setZoom(zoom) {
    'use strict';
    return this.map.getView().setZoom(zoom);
};

/**
 *
 * @param geometryOrExtent
 * @param opt_options
 * @returns {*}
 */
Mapbender.Model.prototype.fit = function fit(geometryOrExtent, opt_options) {
    'use strict';
    return this.map.getView().fit(geometryOrExtent, opt_options);
};

/**
 *
 * @param extent1
 * @param extent2
 * @returns {*|boolean}
 */
Mapbender.Model.prototype.containsExtent = function containsExtent(extent1, extent2) {
    'use strict';
    return ol.extent.containsExtent(extent1, extent2);
};

/**
 *
 * @param extent
 * @param coordinate
 * @returns {*}
 */
Mapbender.Model.prototype.containsCoordinate = function containsCoordinate(extent, coordinate) {
    'use strict';
    return ol.extent.containsCoordinate(extent, coordinate);
};

/**
 *
 * @param extent
 * @param duration
 * @param maxZoom
 */
Mapbender.Model.prototype.panToExtent = function panToExtent(extent, duration, maxZoom) {
    'use strict';

    var view = this.map.getView();
    var maxZoomNum= view.getZoom();
    var durationNum = 2000;

    if (maxZoom){
        maxZoomNum = maxZoom;
    }

    if (duration){
        durationNum = duration;
    }

    view.fit(extent, {
        duration: durationNum,
        maxZoom: maxZoomNum
    });
};

/**
 *
 * @param mbExtent
 */
Mapbender.Model.prototype.zoomToExtent = function(mbExtent) {
    'use strict';
    var extent = [
        mbExtent.left,
        mbExtent.bottom,
        mbExtent.right,
        mbExtent.top
    ];
    this.map.getView().fit(extent, this.map.getSize());
};

/**
 *
 * @param coordinate
 * @returns {ol.Extent}
 */
Mapbender.Model.prototype.boundingExtentFromCoordinates = function boundingExtentFromCoordinates(coordinate) {
    'use strict';
    return ol.extent.boundingExtent(coordinate);
};

/**
 *
 * @returns {*}
 */
Mapbender.Model.prototype.getLayers = function getLayers() {
    'use strict';
    return this.map.getLayers();
};

/**
 *
 * @param owner
 * @param uuid
 * @param style
 * @param refresh
 */
Mapbender.Model.prototype.setVectorLayerStyle = function setVectorLayerStyle(owner, uuid, style, refresh){
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
Mapbender.Model.prototype.createDrawControl = function createDrawControl(type, owner, options){
    'use strict';

    if(!_.contains( this.DRAWTYPES,type )){
        throw new Error('Mapbender.Model.createDrawControl only supports the operations' + this.DRAWTYPES.toString()+ 'not' + type);
    }
    options = options || {};
    options.source = options.source ||  new ol.source.Vector({wrapX: false});

    var drawOptions = {
        type: type,
        source: options.source
    };
    var id = this.createVectorLayer(options, owner);

    if (type === 'Box') {
        drawOptions.geometryFunction = ol.interaction.Draw.createBox();
        drawOptions.type = 'Circle';
    }

    var draw = new ol.interaction.Draw(drawOptions);

    this.vectorLayer[owner][id].interactions = this.vectorLayer[owner][id].interactions || {};
    this.vectorLayer[owner][id].interactions[id] = draw;


    _.each(options.events, function(value, key) {
        draw.on(key, value);
    }.bind(this));

    this.map.addInteraction(draw);

    return id;

};

Mapbender.Model.prototype.createModifyInteraction = function createModifyInteraction(owner, style, vectorId, featureId, events) {
    'use strict';

    var vectorLayer = this.vectorLayer[owner][vectorId];
    var features = vectorLayer.getSource().getFeatures();
    var selectInteraction = new ol.interaction.Select({
        layers: vectorLayer,
        style: style
    });
    selectInteraction.getFeatures().push(features[0]);

    this.vectorLayer[owner][vectorId].interactions = this.vectorLayer[owner][vectorId].interactions  || {};
    this.vectorLayer[owner][vectorId].interactions[vectorId] = selectInteraction;

    var modify = new ol.interaction.Modify({
        features: selectInteraction.getFeatures()
    });

    this.vectorLayer[owner][vectorId].interactions = this.vectorLayer[owner][vectorId].interactions  || {};
    this.vectorLayer[owner][vectorId].interactions[vectorId] = modify;

    _.each(events, function(value, key) {
        modify.on(key, value);
    }.bind(this));

    // this.map.addInteraction(selectInteraction);
    // this.map.addInteraction(modify);
    this.map.getInteractions().extend([selectInteraction, modify]);
};

Mapbender.Model.prototype.removeVectorLayer = function removeVectorLayer(owner,id){
    var vectorLayer = this.vectorLayer[owner][id];
    if(this.vectorLayer[owner][id].hasOwnProperty('interactions')){
        this.removeInteractions(this.vectorLayer[owner][id].interactions);
    }
    this.map.removeLayer(vectorLayer);
    delete this.vectorLayer[owner][id];


};

Mapbender.Model.prototype.removeInteractions = function removeControls(control){
    //_.each(controls, function(control, index){
        this.map.removeInteraction(control);
    //}.bind(this));


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
    return new ol.style.Style();
};

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

/**
 *
 * @param owner
 * @param vectorId
 * @param featureId
 * @returns {ol.Feature}
 */
Mapbender.Model.prototype.getFeatureById = function(owner, vectorId, featureId) {
    'use strict';
    var source = this.vectorLayer[owner][vectorId].getSource();
    return source.getFeatureById(featureId);
};

/**
 *
 * @param owner
 * @param vectorId
 * @param featureId
 */
Mapbender.Model.prototype.removeFeatureById = function(owner, vectorId, featureId) {
    'use strict';
    var source = this.vectorLayer[owner][vectorId].getSource();
    var feature = source.getFeatureById(featureId);
    source.removeFeature(feature);
};

/**
 *
 * @param owner
 * @param vectorId
 */
Mapbender.Model.prototype.getLayerExtent = function(owner, vectorId) {
    'use strict';
    var vectorLayerExtent = this.vectorLayer[owner][vectorId].getSource().getExtent();
    return this.mbExtent(vectorLayerExtent);
};

/**
 *
 * @param owner
 * @param vectorId
 * @param featureId
 */
Mapbender.Model.prototype.getFeatureExtent = function(owner, vectorId, featureId) {
    'use strict';
    var feature = this.getFeatureById(owner, vectorId, featureId);
    var featureExtent = feature.getGeometry().getExtent();
    return this.mbExtent(featureExtent);
};

/**
 * Promote input extent into "universally understood" extent.
 *
 * Monkey-patch attributes 'left', 'bottom', 'right', 'top' onto
 * a coordinate array, or convert a pure object extent with those
 * attributes into a monkey-patched Array of numbers.
 *
 * Also force coordinate values to float.
 *
 * @param {(Array.<number>|Object.<string, number>)} extent
 * @returns {Array.<number>}
 * @static
 */
Mapbender.Model.mbExtent = function mbExtent(extent) {
    'use strict';
    if (Array.isArray(extent)) {
        if (extent.length !== 4) {
            console.error("Extent coordinate length mismatch", extent);
            throw new Error("Extent coordinate length mismatch");
        }
        if (typeof extent.left !== 'undefined') {
            // already patched, return same object (idempotence, no copy)
            return extent;
        }
        _.each(["left","bottom", "right","top"], function(value, index){
            extent[index] = parseFloat(extent[index]);
            extent[value] = extent[index];
        });
        return extent;
    } else if (typeof extent.left !== 'undefined') {
        return Mapbender.Model.mbExtent([
            extent.left,
            extent.bottom,
            extent.right,
            extent.top
            ]);
    } else {
        console.error("Unsupported extent format", extent);
        throw new Error("Unsupported extent format");
    }
};
Mapbender.Model.prototype.mbExtent = Mapbender.Model.mbExtent;

/**
 *
 * @param mbExtent
 */
Mapbender.Model.prototype.zoomToExtent = function(extent) {
    'use strict';
    this.map.getView().fit(this.mbExtent(extent), this.map.getSize());
};

Mapbender.Model.prototype.removeAllFeaturesFromLayer = function removeAllFeaturesFromLayer(owner, id) {

    return this.vectorLayer[owner][id].getSource().clear();

};

Mapbender.Model.prototype.getFeatureSize = function getFeatureSize(feature, type) {

    if(type === 'line'){
        return this.getLineStringLength(feature);
    }
    if(type === 'area'){
        return   this.getPolygonArea(feature);
    }





};

Mapbender.Model.prototype.getGeometryCoordinates = function getFeaureCoordinates(geom) {

    return geom.getFlatCoordinates();

};





Mapbender.Model.prototype.getPolygonArea = function getPolygonArea(polygon){
    'use strict';

    return  ol.Sphere.getArea(polygon);
};

Mapbender.Model.prototype.getGeometryFromFeatureWrapper = function getGeometryFromFeatureWrapper(feature, callback, args){
    'use strict';
    args = [feature.getGeometry()].concat(args)
    return callback.apply(this,args);

};

/**
 * Get feature info url; may return null if feature info is not available.
 *
 * @param {string} sourceId
 * @param {*} coordinate in current EPSG
 * @param {*} resolution purpose?
 * @returns {string|null}
 */
Mapbender.Model.prototype.getFeatureInfoUrl = function getFeatureInfoUrl(sourceId, coordinate, resolution) {
    var sourceObj = this.getSourceById(sourceId);

    if (!sourceObj.featureInfoParams.QUERY_LAYERS) {
        return null;
    }

    var sourceObjParams = sourceObj.featureInfoParams;
    /** @var {ol.source.ImageWMS|ol.source.TileWMS} engineSource */
    var engineSource = sourceObj.getEngineSource();
    var projection = this.getCurrentProjectionCode();

    // @todo: pass / evaluate coordinate from feature click
    // @todo: figure out the purpose of 'resolution' param

    console.log(engineSource);
    return engineSource.getGetFeatureInfoUrl(coordinate[0] || [0, 0], resolution || 5, projection, sourceObjParams);
};

/**
 * Collects feature info URLs from all active sources
 *
 * @todo: add coordinate / resolution params
 *
 * @returns {string[]}
 */
Mapbender.Model.prototype.collectFeatureInfoUrls = function collectFeatureInfoUrls(coordinate) {
    var urls = [];
    var sourceIds = this.getActiveSourceIds();
    for (var i = 0; i < sourceIds.length; ++i) {
        // pass sourceId, forward all remaining arguments
        // @todo: remove this argument-forwarding style once the API has settled
        urls.push(this.getFeatureInfoUrl.apply(this, [sourceIds[i]].concat(arguments)));
    }
    // strip nulls
    return _.filter(urls);
};

Mapbender.Model.prototype.createTextStyle = function createTextStyle(options) {
    'use strict';

    var textStyle = new ol.style.Text();

    if(options['text']) {
        var text = new ol.style.Text(options['text']);
        textStyle.setText(text);
    }

    if(options['fill']) {
        var fill = new ol.style.Fill(options['fill']);
        textStyle.setFill(fill);
    }

    if(options['stroke']) {
        var stroke = new ol.style.Stroke(options['stroke']);
        textStyle.setStroke(stroke);
    }
    return new ol.style.Text(options);
};

    /**
     * Update map view according to selected projection
     *
     * @param {string} projectionCode
     */
    Mapbender.Model.prototype.updateMapViewForProjection = function(projectionCode) {
        var currentSrsCode = this.getCurrentProjectionCode();

        if(typeof projectionCode === 'undefined' || projectionCode === currentSrsCode) {
            return;
        }
        var currentView = this.map.getView();
        var fromProj = ol.proj.get(currentSrsCode);
        var toProj = ol.proj.get(projectionCode);
        if (!fromProj || !fromProj.getUnits() || !toProj || !toProj.getUnits()) {
            console.error("Missing / incomplete transformations (log order from / to)", [currentSrsCode, projectionCode], [fromProj, toProj]);
            throw new Error("Missing / incomplete transformations");
        }
        for (var i = 0; i < this.pixelSources.length; ++i) {
            this.pixelSources[i].updateSrs(toProj);
        }
        // viewProjection.getUnits() may return undefined, safer this way!
        var currentUnits = fromProj.getUnits() || "degrees";
        var newUnits = toProj.getUnits() || "degrees";
        // transform projection extent (=max extent)
        // DO NOT use currentView.getProjection().getExtent() here!
        // Going back and forth between SRSs, there is extreme drift in the
        // calculated values. Always start from the configured maxExtent.
        var newMaxExtent = ol.proj.transformExtent(this.options.maxExtent, this.options.srs, toProj);

        var viewPortSize = this.map.getSize();
        var currentCenter = currentView.getCenter();
        var newCenter = ol.proj.transform(currentCenter, fromProj, toProj);

        var newProjection = ol.proj.get(projectionCode);
        newProjection.setExtent(newMaxExtent);

        // Recalculate resolution and allowed resolution steps
        var _convertResolution = this.convertResolution_.bind(undefined, currentUnits, newUnits);
        var newResolution = _convertResolution(currentView.getResolution());
        var newResolutions = this.viewOptions_.resolutions.map(_convertResolution);
        // Amend this.viewOptions_, we need the applied values for the next SRS switch
        var newViewOptions = $.extend(this.viewOptions_, {
            projection: newProjection,
            resolutions: newResolutions,
            center: newCenter,
            size: viewPortSize,
            resolution: newResolution
        });

        var newView = new ol.View(newViewOptions);
        this.map.setView(newView);
    };

/**
 * Set callback function for single click event
 *
 * @param callback
 * @returns {ol.EventsKey|Array.<ol.EventsKey>}
 */
Mapbender.Model.prototype.setOnSingleClickHandler = function (callback) {
    'use strict';
    return this.map.on("singleclick", callback);
};

/**
 * Set callback for map moveend event
 * @param callback
 * @returns {ol.EventsKey|Array<ol.EventsKey>}
 */
Mapbender.Model.prototype.setOnMoveendHandler = function (callback) {
    'use strict';

    if (typeof callback === 'function') {
        return this.map.on("moveend", callback);
    }
};

/**
 * Remove event listener by event key
 *
 * @param key
 * @returns {Mapbender.Model}
 */
Mapbender.Model.prototype.removeEventListenerByKey = function (key) {
    ol.Observable.unByKey(key);

    return this;
};

/**
 * Get coordinates from map click event and wrap them in {x,y} object
 *
 * @param event
 * @returns undefined | {{x}, {y}}
 */
Mapbender.Model.prototype.getCoordinatesXYObjectFromMapClickEvent = function (event) {

    var coordinates = undefined;

    if (typeof event.coordinate !== 'undefined') {
        coordinates = {
            x: event.coordinate[0],
            y: event.coordinate[1],
        };
    }

    return coordinates;
};

/**
 * Get units of current map projection
 *
 * @returns {ol.proj.Units}
 */
Mapbender.Model.prototype.getUnitsOfCurrentProjection = function () {
    var proj = this.getCurrentProjectionObject();
    var units = proj.getUnits();
    if (!units) {
        console.warn("Projection object has undefined units! Defaulting to degrees", proj);
        units = "degrees";
    }
    return units;
};

/**
 * Set style of map cursor
 *
 * @param style
 * @returns {Mapbender.Model}
 */
Mapbender.Model.prototype.setMapCursorStyle = function (style) {
    this.map.getTargetElement().style.cursor = style;

    return this;
};

/**
 * Set marker on a map by provided coordinates
 *
 * @param {string[]} coordinates
 * @param {string} owner Element id
 * @param {string} vectorLayerId
 * @returns {string} vectorLayerId
 */
Mapbender.Model.prototype.setMarkerOnCoordinates = function (coordinates, owner, vectorLayerId) {

    if (typeof coordinates === 'undefined') {
        throw new Error("Coordinates are not defined!");
    }

    var point = new ol.geom.Point(coordinates);

    if (typeof vectorLayerId === 'undefined') {

        vectorLayerId = this.createVectorLayer({
            source: new ol.source.Vector({wrapX: false}),
        }, owner);

        this.map.addLayer(this.vectorLayer[owner][vectorLayerId]);
    }

    this.drawFeatureOnVectorLayer(point, this.vectorLayer[owner][vectorLayerId]);

    return vectorLayerId;
};

/**
 * Draw feature on a vector layer
 *
 * @param {ol.geom} geometry
 * @param {ol.layer.Vector} vectorLayer
 * @returns {Mapbender.Model}
 */
Mapbender.Model.prototype.drawFeatureOnVectorLayer = function (geometry, vectorLayer) {
    var feature = new ol.Feature({
        geometry: geometry,
    });

    var source = vectorLayer.getSource();

    source.addFeature(feature);

    return this;
};

/**
 * Valdiates and fixes an incoming extent. Coordinate values will
 * be cast to float. Inverted coordinates are flipped.
 *
 * @param extent
 * @returns {Array<number>} monkey-patched mbExtent with .left etc
 * @static
 */
Mapbender.Model.sanitizeExtent = function(extent) {
    var mbExtent = this.mbExtent(extent);
    var warnings = [];
    for (var i = 0; i < mbExtent.length; ++i) {
        if (isNaN(mbExtent[i])) {
            console.error("Extent contains NaNs", mbExtent);
            throw new Error("Extent contains NaNs");
        }
    }
    if (mbExtent[0] > mbExtent[2]) {
        warnings.push("left > right");
    }
    if (mbExtent[1] > mbExtent[3]) {
        warnings.push("bottom > top");
    }
    if (warnings.length) {
        console.warn("Fixing flipped extent coordinates " + warnings.join(","), mbExtent);
        var left = Math.min(mbExtent[0], mbExtent[2]);
        var right = Math.max(mbExtent[0], mbExtent[2]);
        var bottom = Math.min(mbExtent[1], mbExtent[3]);
        var top = Math.max(mbExtent[1], mbExtent[3]);
        return this.mbExtent([left, bottom, right, top]);
    } else {
        return mbExtent;
    }
};
Mapbender.Model.prototype.sanitizeExtent = Mapbender.Model.sanitizeExtent;

/**
 * Return current live extent in "universal extent" format
 * + monkey-patched attribute 'srs'
 *
 * @returns {Array<number>|*}
 */
Mapbender.Model.prototype.getCurrentExtent = function getCurrentExtent() {
    var extent = this.mbExtent(this.map.getView().calculateExtent());
    extent.srs = this.getCurrentProjectionCode();
    return extent;
};

/**
 * Return maximum extent in "universal extent" format
 * + monkey-patched attribute 'srs'
 *
 * @returns {Array<number>|*}
 */
Mapbender.Model.prototype.getMaxExtent = function getMaxExtent() {
    var extent = this.mbExtent(this.getCurrentProjectionObject().getExtent());
    extent.srs = this.getCurrentProjectionCode();
    return extent;
};

/**
 *
 * @param currentUnit
 * @static
 * @returns {number}
 */
Mapbender.Model.prototype.getMetersPerUnit = function getMetersPerUnit(currentUnit) {
    'use strict';
    return ol.proj.METERS_PER_UNIT[currentUnit];
};
Mapbender.Model.getMetersPerUnit = Mapbender.Model.prototype.getMetersPerUnit;

Mapbender.Model.prototype.getGeomFromFeature = function getGeomFromFeature(feature) {
    'use strict';
    return feature.getGeometry();
};

/**
 * Returns the size of the map in the DOM (in pixels):
 * An array of numbers representing a size: [width, height].
 * @returns {Array.<number>}
 */
Mapbender.Model.prototype.getMapSize = function getMapSize() {
    'use strict';
    return this.map.getSize();
};

/**
 * Returns the view center of a map:
 * An array of numbers representing an xy coordinate. Example: [16, 48].
 * @returns {Array.<number>}
 */
Mapbender.Model.prototype.getMapCenter = function getMapCenter() {
    'use strict';
    return this.map.getView().getCenter();
};

/**
 * Returns the width of an extent.
 * @param extent
 * @returns {number}
 */
Mapbender.Model.prototype.getWidthOfExtent = function getWidthOfExtent(extent) {
    'use strict';
    return ol.extent.getWidth(extent);
};

/**
 * Returns the height of an extent.
 * @param {Array} extent
 * @returns {number}
 */
Mapbender.Model.prototype.getHeigthOfExtent = function getHeigthOfExtent(extent) {
    'use strict';
    return ol.extent.getHeight(extent);
};

/**
 * @param {Object} params
 * @returns {string}
 */
Mapbender.Model.prototype.getUrlParametersAsString = function getUrlParametersAsString(params) {
    'use strict';
    var url = '';
    for (var key in params) {
        if (params.hasOwnProperty(key)) {
            url += '&' + key + '=' + params[key];
        }
    }

    return url;
};

/**
 * @param {string} sourceId
 * @param {Array} extent
 * @param {Array} size
 * @returns {{type: (string|null), url: string, opacity}}
 */
Mapbender.Model.prototype.getSourcePrintConfig = function(sourceId, extent, size) {
    var sourceObj = this.getSourceById(sourceId);

    // Contains VERSION, FORMAT, TRANSPARENT, LAYERS
    var params = sourceObj.getMapParams;  //engineSource.getParams();

    var v13 = false;
    if (params.VERSION.indexOf('1.3') !== -1) {
        v13 = true;
    }

    // To ensure that only active layers are considered.
    params.LAYERS = sourceObj.getActiveLayerNames().join(',');

    params.REQUEST = 'GetMap';
    params.SERVICE = sourceObj.getType().toUpperCase();
    params.STYLES = ''; //@todo always empty or should it be possible to assign them from a config?


    params[v13 ? 'CRS' : 'SRS'] = this.getCurrentProjectionCode();

    var bbox;
    var axisOrientation = this.getCurrentProjectionObject().getAxisOrientation();
    if (v13 && axisOrientation.substr(0, 2) === 'ne') {
        bbox = [extent[1], extent[0], extent[3], extent[2]];
    } else {
        bbox = extent;
    }
    params.BBOX = bbox.join(',');

    params.WIDTH = size[0];
    params.HEIGHT = size[1];

    // base url contains the ? sign already.
    var url = sourceObj.getBaseUrl();
    url += this.getUrlParametersAsString(params);

    return {
        type : sourceObj.getType(),
        url : url,
        opacity : sourceObj.options.opacity
    };
};

/**
 *
 * @param elementConfig
 */
Mapbender.Model.prototype.createMousePositionControl = function createMousePositionControl(elementConfig){
    'use strict';
    var template = elementConfig.prefix + '{x}' + elementConfig.separator + '{y}';
    var mousePositionControl = new ol.control.MousePosition({
        coordinateFormat: function(coordinate) {

            return ol.coordinate.format(coordinate, template, elementConfig.numDigits);
        },
        projection: elementConfig.displayProjection.projCode,
        className: 'custom-mouse-position',
        target: elementConfig.target,
        undefinedHTML: elementConfig.emptyString
    });
    this.map.addControl(mousePositionControl);
};

/**
 * @param {object} options
 * @returns {object}
 */
Mapbender.Model.prototype.initializeViewOptions = function initializeViewOptions(options) {
    'use strict';
    var proj = ol.proj.get(options.srs);
    if (options.maxExtent) {
        proj.setExtent(options.maxExtent);
    }
    var viewOptions = {
        projection:  proj
    };

    if (options.scales && options.scales.length) {
        // Sometimes, the units are empty -.-
        // this seems to happen predominantely with "degrees" SRSs, so...
        var units = ol.proj.get(options.srs).getUnits();
        viewOptions['resolutions'] = options.scales.map(function(scale) {
            return this.scaleToResolutionStatic(scale, 72, proj.getUnits() || "degrees");
        }.bind(this));
    } else {
        viewOptions.zoom = 7; // hope for the best
    }
    return viewOptions;
};

/**
 * Recalculate a resolution number valid for fromUnit to an equivalent valid
 * for toUnit.
 * This is technically sth like:
 *   newRes = scaleToRes(resToScale(oldScale, dpi, oldUnit), dpi, newUnit).
 * If you look at the resolutionToScale and scaleToResolution math,
 * you'll see that the result of the back-and-forth transformation ONLY
 * depends on the meters per unit, and on nothing else.
 *
 * This allows us to perform the calculation independent of dpi settings.
 *
 * @param {string} fromUnits "m", "degrees" etc
 * @param {string} toUnits "m", "degrees" etc
 * @param {number} resolution
 * @returns {number}
 * @private
 * @static
 */
Mapbender.Model.convertResolution_ = function convertResolution_(fromUnits, toUnits, resolution) {
    var resolutionFactor =
        ol.proj.METERS_PER_UNIT[fromUnits] /
        ol.proj.METERS_PER_UNIT[toUnits];
    return resolution * resolutionFactor;
};
// make available on instance
Mapbender.Model.prototype.convertResolution_ = Mapbender.Model.convertResolution_;
