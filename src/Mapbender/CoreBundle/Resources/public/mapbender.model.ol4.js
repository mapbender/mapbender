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

    var proj = new ol.proj.Projection({
        code: options.srs,
        extent: options.maxExtent
    });
    var view = new ol.View({
        projection:  proj
    });
    this.map = new ol.Map({
        view: view,
        target: domId
    });

    // ordered list of WMS / WMTS etc sources that provide pixel tiles
    /** @type {Array.<Mapbender.SourceModelOl4>} **/
    this.pixelSources = [];
    this.zoomToExtent(options.startExtent || options.maxExtent);
    // @todo: ???
    /*var popupOverlay = new Mapbender.Model.MapPopup();
    this.map.on('singleclick', function(evt) {


        var coordinate = evt.coordinate;
        popupOverlay.openPopupOnXY(coordinate, function(){return '123'});
    }); */
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
    return this.map.getView().getProjection();
};

/**
 *
 * @returns {*|OpenLayers.Bounds}
 */
Mapbender.Model.prototype.getMapExtent = function getMapExtent() {
    'use strict';
    return this.map.getView().calculateExtent();
};

/**
 *
 * @param {number} dpi default 72 DPI
 * @param {boolean} opt_round Whether to round the scale or not.
 * @param {boolean} opt_scaleRating Whether to round the scale rating or not.
 * @returns {number}
 */
Mapbender.Model.prototype.getScale = function getScale(dpi, opt_round, opt_scaleRating) {
    var currentUnit = this.getUnitsOfCurrentProjection();
    var mpu = this.getMeterPersUnit(currentUnit);
    var resolution = this.map.getView().getResolution();
    var inchesPerMetre = 39.37;
    var dpi = dpi ? dpi : 72;
    var scaleCalc = resolution * mpu * inchesPerMetre * dpi;
    var scale = opt_round ? Math.round(scaleCalc) : scaleCalc;

    if (opt_scaleRating){
        if (scale >= 9500 && scale <= 950000) {
            scale = Math.round(scale/ 1000) + ".000";
        } else if (scale >= 950000) {
            scale = Math.round(scale / 1000000) + ".000.000";
        } else {
            scale = Math.round(scale);
        }
    }

    return scale;
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
        extent: extent || undefined
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
    if (! deltaArray){
        deltaArray = [0, 0]
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

    return   this.vectorLayer[owner][id].getSource().clear();

};

Mapbender.Model.prototype.getFeatureSize = function getFeatureSize(feature) {

    return   this.getLineStringLength(feature.getGeometry());

};

Mapbender.Model.prototype.getGeometryCoordinates = function getFeaureCoordinates(geom) {

    return   geom.getFlatCoordinates();

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
    var sourceObjParams = sourceObj.featureInfoParams;
    /** @var {ol.source.ImageWMS|ol.source.TileWMS} engineSource */
    var engineSource = sourceObj.getEngineSource();
    var projection = this.getCurrentProjectionCode();

    // @todo: pass / evaluate coordinate from feature click
    // @todo: figure out the purpose of 'resolution' param

    console.log(engineSource);
    return engineSource.getGetFeatureInfoUrl(coordinate || [0, 0], resolution || 5, projection, sourceObjParams);
};

/**
 * Collects feature info URLs from all active sources
 *
 * @todo: add coordinate / resolution params
 *
 * @returns {string[]}
 */
Mapbender.Model.prototype.collectFeatureInfoUrls = function collectFeatureInfoUrls() {
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

    if (options['text']) {
        var text = new ol.style.Text(options['text']);
        textStyle.setText(text);
    }

    if (options['fill']) {
        var fill = new ol.style.Fill(options['fill']);
        textStyle.setFill(fill);
    }

    if (options['stroke']) {
        var stroke = new ol.style.Stroke(options['stroke']);
        textStyle.setStroke(stroke);
    }
    return new ol.style.Text(options);
},


/**
 * Update map view according to selected projection
 *
 * @param {string} projectionCode
 */
Mapbender.Model.prototype.updateMapViewForProjection = function (projectionCode) {

    if (typeof projectionCode === 'undefined' || projectionCode === this.getCurrentProjectionCode()) {
        return;
    }

    var newProjection = ol.proj.get(projectionCode),
        currentCenter = this.map.getView().getCenter(),
        newCenter = ol.proj.transform(currentCenter, this.getCurrentProjectionCode(), projectionCode),
        zoom = this.map.getView().getZoom();

    var newView = new ol.View({
        projection: newProjection,
        center: newCenter,
        zoom: zoom
    });

    this.map.setView(newView);
};


/**
 * Set callback function for single click event
 *
 * @param callback
 * @returns {ol.EventsKey|Array.<ol.EventsKey>}
 */
Mapbender.Model.prototype.setOnSingleClickHandler = function (callback) {
    return this.map.on("singleclick", callback);
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
    return this.getCurrentProjectionObject().getUnits();
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
 * Center map to provided coordinates
 *
 * @param {string[]} coordinates
 * @returns {Mapbender.Model}
 */
Mapbender.Model.prototype.centerMapByCoordinates = function (coordinates) {
    this.map.getView().setCenter(coordinates);

    return this;

};

/**
 * Zoom map to provided zoom level
 *
 * @param {int} zoom
 * @returns {Mapbender.Model}
 */
Mapbender.Model.prototype.zoomToZoomLevel = function (zoom) {
    this.map.getView().setZoom(zoom);

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
 * @returns {number}
 */
Mapbender.Model.prototype.getMeterPersUnit = function getMeterPersUnit(currentUnit) {
    'use strict';
    return ol.proj.METERS_PER_UNIT[currentUnit];
};