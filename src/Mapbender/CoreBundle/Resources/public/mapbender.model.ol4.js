window.Mapbender = Mapbender || {};
window.Mapbender.MapModelOl4 = (function() {
    'use strict';

    /**
     * @param {Object} mbMap
     * @constructor
     */
    function MapModelOl4(mbMap) {
        Mapbender.MapModelBase.apply(this, arguments);
        this._initMap();
    }

    MapModelOl4.prototype = Object.create(Mapbender.MapModelBase.prototype);
    Object.assign(MapModelOl4.prototype, {
        constructor: MapModelOl4,
        sourceTree: [],



    _initMap: function() {
        var options = {
            srs: this._startProj,
            maxExtent: Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, this._startProj),
            startExtent: Mapbender.mapEngine.transformBounds(this.mapStartExtent, this._configProj, this._startProj),
            scales : this.mbMap.options.scales,
            dpi: this.mbMap.options.dpi,
            tileSize: this.mbMap.options.tileSize
        };

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
        this.olMap = new ol.Map({
            view: view,
            target: this.mbMap.element.attr('id')
        });
        this.map = new Mapbender.NotMapQueryMap(this.mbMap.element, this.olMap);

        this._initEvents(this.olMap, this.mbMap);
        this.setExtent(options.startExtent || options.maxExtent);
        this.initializeSourceLayers();
        this.processUrlParams();
    },
    _initEvents: function(olMap, mbMap) {
        var self = this;
        olMap.on('moveend', function() {
            var scales = self._getScales();
            var zoom = self.getCurrentZoomLevel();
            self.sourceTree.map(function(source) {
                self._checkSource(source, true);
            });
            // @todo: figure out how to distinguish zoom change from panning
            mbMap.element.trigger('mbmapzoomchanged', {
                mbMap: mbMap,
                zoom: zoom,
                scale: scales[zoom]
            });
        });
        olMap.on("singleclick", function(data) {
            $(self.mbMap.element).trigger('mbmapclick', {
                mbMap: self.mbMap,
                pixel: data.pixel.slice(),
                coordinate: data.coordinate.slice(),
                event: data.originalEvent
            });
        });
    },
    /**
     * Injects native layers into the map at the "natural" position for the source.
     * This supports multiple layers for the same source.
     *
     * @param {Mapbender.Source} source
     * @param {Array<ol.Layer>} olLayers
     * @private
     */
    _spliceLayers: function(source, olLayers) {
        var sourceIndex = this.sourceTree.indexOf(source);
        if (sourceIndex === -1) {
            console.error("Can't splice layers for source with unknown position", source, olLayers);
            throw new Error("Can't splice layers for source with unknown position");
        }
        var olMap = this.olMap;
        var layerCollection = olMap.getLayers();
        var afterLayer = layerCollection[0]; // hopefully, that's a base layer
        for (var s = sourceIndex - 1; s >= 0; --s) {
            var previousSource = this.sourceTree[s];
            var previousLayer = (previousSource.nativeLayers.slice(-1))[0];
            if (previousLayer) {
                afterLayer = previousLayer;
                break;
            }
        }
        var baseIndex = layerCollection.getArray().indexOf(afterLayer) + 1;
        for (var i = 0; i < olLayers.length; ++i) {
            var olLayer = olLayers[i];
            layerCollection.insertAt(baseIndex + i, olLayer);
            olLayer.mbConfig = source;
            this._initLayerEvents(olLayer, source, i);
        }
    },
    _initLayerEvents: function(olLayer, source, sourceLayerIndex) {
        var mbMap = this.mbMap;
        var nativeSource = olLayer.getSource();
        var engine = Mapbender.mapEngine;
        var tmp = {
            pendingLoads: 0
        };
        nativeSource.on(["tileloadstart", "imageloadstart"], function() {
            if (!tmp.pendingLoads) {
                mbMap.element.trigger('mbmapsourceloadstart', {
                    mbMap: mbMap,
                    source: source
                });
            }
            ++tmp.pendingLoads;
        });
        nativeSource.on(["tileloaderror", "imageloaderror"], function(data) {
            tmp.pendingLoads = Math.max(0, tmp.pendingLoads - 1);
            if (engine.getLayerVisibility(olLayer)) {
                mbMap.element.trigger('mbmapsourceloaderror', {
                    mbMap: mbMap,
                    source: source
                });
            }
        });
        nativeSource.on(["tileloadend", "imageloadend"], function() {
            tmp.pendingLoads = Math.max(0, tmp.pendingLoads - 1);
            if (!tmp.pendingLoads) {
                mbMap.element.trigger('mbmapsourceloadend', {
                    mbMap: mbMap,
                    source: source
                });
            }
        });
    },
    zoomToFullExtent: function() {
        var currentSrsName = this.getCurrentProjectionCode();
        var extent = Mapbender.mapEngine.transformBounds(this.mapMaxExtent, this._configProj, currentSrsName);
        this.setExtent(extent);
    },
    /**
     * @param {Array<number>} boundsOrCoords
     */
    setExtent: function(boundsOrCoords) {
        var bounds;
        if ($.isArray(boundsOrCoords)) {
            bounds = boundsOrCoords;
        } else {
            bounds = [
                boundsOrCoords.left,
                boundsOrCoords.bottom,
                boundsOrCoords.right,
                boundsOrCoords.top
            ];
        }
        this.olMap.getView().fit(bounds);
    },
    /**
     * @param {*} anything
     * @return {OpenLayers.Layer|null}
     */
    getNativeLayer: function(anything) {
        if (anything.getNativeLayer) {
            return anything.getNativeLayer(0);
        }
        if (anything.olLayer) {
            // MapQuery layer
            return anything.olLayer;
        }
        if (anything.CLASS_NAME && anything.CLASS_NAME.search('OpenLayers.Layer') === 0) {
            // OpenLayers.Layer (child class) instance
            return anything;
        }
        if (anything.mqlid) {
            // sourceTreeish
            return (this.map.layersList[anything.mqlid] || {}).olLayer || null;
        }
        if (anything.ollid) {
            return _.find(this.map.olMap.layers, _.matches({id: anything.ollid})) || null;
        }
        console.error("Could not find native layer for given obect", anything);
        return null;
    },
    zoomToFeature: function(feature, options) {
        var geometry = feature && feature.getGeometry();
        if (!geometry) {
            console.error("Empty feature or empty feature geometry", feature);
            return;
        }
        var center_;
        if (options) {
            center_ = options.center || typeof options.center === 'undefined';
        } else {
            center_ = true;
        }
        var engine = Mapbender.mapEngine;
        var bounds = engine.getFeatureBounds(feature);
        if (options && options.buffer) {
            var unitsPerMeter = engine.getProjectionUnitsPerMeter(this.getCurrentProjectionCode());
            var bufferNative = options.buffer * unitsPerMeter;
            bounds.left -= bufferNative;
            bounds.right += bufferNative;
            bounds.top += bufferNative;
            bounds.bottom -= bufferNative;
        }
        var view = this.olMap.getView();
        var zoom0 = Math.floor(view.getZoomForResolution(view.getResolutionForExtent(bounds)));
        var zoom = this._adjustZoom(zoom0, options);
        var zoomNow = this.getCurrentZoomLevel();
        var viewExtent = view.calculateExtent();
        var featureInView = ol.extent.intersects(viewExtent, bounds);
        if (center_ || zoom !== zoomNow || !featureInView) {
            view.setCenter(ol.extent.getCenter(bounds));
            this.setZoomLevel(zoom, false);
        }
    },
    setZoomLevel: function(level, allowTransitionEffect) {
        var _level = this._clampZoomLevel(level);
        if (_level !== this.getCurrentZoomLevel()) {
            if (allowTransitionEffect) {
                this.olMap.getView().animate({zoom: _level, duration: 300});
            } else {
                this.olMap.getView().setZoom(_level);
            }
        }
    },
    getCurrentZoomLevel: function() {
        return this.olMap.getView().getZoom();
    },
    panByPixels: function(dx, dy) {
        var view = this.olMap.getView();
        var centerCoord = view.getCenter();
        var centerPixel = this.olMap.getPixelFromCoordinate(centerCoord);
        centerPixel[0] += dx;
        centerPixel[1] += dy;
        var targetCenterCoord = this.olMap.getCoordinateFromPixel(centerPixel);
        view.animate({
            center: view.constrainCenter(targetCenterCoord),
            duration: 300
        });
    },
    panByPercent: function(dx, dy) {
        var mapSize = this.olMap.getSize();
        var pixelDx = (dx / 100.0) * mapSize[0];
        var pixelDy = (dy / 100.0) * mapSize[1];
        this.panByPixels(pixelDx, pixelDy);
    },
    zoomIn: function() {
        this.setZoomLevel(this.getCurrentZoomLevel() + 1, true);
    },
    zoomOut: function() {
        this.setZoomLevel(this.getCurrentZoomLevel() - 1, true);
    },
    getCurrentProjectionUnits: function() {
        var proj;
        if (this.olMap) {
            proj = this.olMap.getView().getProjection();
        } else {
            proj = ol.proj.get(this._startProj);
        }
        return proj.getUnits() || 'degrees';
    },
    getCurrentProjectionCode: function() {
        if (this.olMap) {
            return this.olMap.getView().getProjection().getCode();
        } else {
            return this._startProj;
        }
    },
    _getScales: function() {
        // @todo: fractional zoom: method must not be called
        var view = this.olMap.getView();
        var dpi = parseFloat(this.mbMap.options.dpi) || 72;
        var self = this;
        return view.getResolutions().map(function(res) {
            var scale0 = self.resolutionToScale(res, dpi);
            return parseInt('' + Math.round(scale0));
        });
    },
    DRAWTYPES: ['Point', 'LineString', 'LinearRing', 'Polygon', 'MultiPoint', 'MultiLineString', 'MultiPolygon', 'GeometryCollection', 'Circle', 'Box'],

    /**
     * @todo is not complete yet
     *
     * @param {Object} options
     * @returns {ol.style.Style}
     */
    createStyle: function createStyle(options) {
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
            stroke: new ol.style.Stroke(options['text']['stroke']),
            offsetY: options['text']['offsetY']
        });
        style.setText(text);
    }

    return style;
},
/**
 *
 * @returns {*|OpenLayers.Bounds}
 */
getMapExtent: function () {
    return this.olMap.getView().calculateExtent();
},


/**
 *
 * @param {float} resolution
 * @param {number} [dpi=72]
 * @param {string} unit "m" or "degrees"
 * @returns {number}
 */
resolutionToScale: function(resolution, dpi) {
    var currentUnit = this.getCurrentProjectionUnits();
    var mpu = this.getMetersPerUnit(currentUnit);
    var inchesPerMetre = 39.37;
    return resolution * mpu * inchesPerMetre * (dpi || this.options.dpi || 72);
},

/**
 * @param {Object} options (See https://openlayers.org/en/latest/apidoc/ol.layer.Vector.html)
 * @param {ol.style|function} options.style (See https://openlayers.org/en/latest/apidoc/ol.style.Style.html)
 * @param {string} owner
 * @returns {string}
 */
createVectorLayer: function(options, owner) {
    var uuid = Mapbender.UUID();
    this.vectorLayer[owner] = this.vectorLayer[owner] || {};
    options.map = this.olMap;
    options.style = options.style ? this.createVectorLayerStyle(options.style) : this.createVectorLayerStyle();
    this.vectorLayer[owner][uuid] = new ol.layer.Vector(options);

    return uuid;
},

/**
 *
 * @param array
 * @param deltaArray
 * @returns {ol.coordinate.add}
 */
addCoordinate: function (array, deltaArray) {
    if (!deltaArray) {
        deltaArray = [0, 0];
    }

    return new ol.coordinate.add(array, deltaArray);
},

/**
 *
 * @param coordinate
 * @param source
 * @param destination
 * @returns {ol.Coordinate}
 */

transformCoordinate: function(coordinate, source, destination) {
    return ol.proj.transform(coordinate, source, destination);
},

/**
 *
 * @param coordinate
 * @param opt_projection
 * @returns {ol.Coordinate}
 */
toLonLat: function (coordinate, opt_projection) {
    return ol.proj.toLonLat(coordinate,opt_projection);
},

/**
 *
 * @param owner
 * @returns {*}
 */
getVectorLayerByNameId: function getVectorLayerByNameId(owner, id) {
    var vectorLayer = this.vectorLayer;
    return  vectorLayer[owner][id];
},

/**
 *
 * @param center
 * @returns {*|void}
 */
setCenter: function setCenter(center) {
    return this.olMap.getView().setCenter(center);
},

/**
 *
 * @param extent1
 * @param extent2
 * @returns {*|boolean}
 */
containsExtent: function(extent1, extent2) {
    return ol.extent.containsExtent(extent1, extent2);
},

/**
 *
 * @param extent
 * @param coordinate
 * @returns {*}
 */
containsCoordinate: function(extent, coordinate) {
    return ol.extent.containsCoordinate(extent, coordinate);
},

/**
 *
 * @param owner
 * @param uuid
 * @param style
 * @param refresh
 */
setVectorLayerStyle: function(owner, uuid, style, refresh){
    this.setLayerStyle('vectorLayer', owner, uuid, style);
},

/**
 *
 * @param layerType
 * @param owner
 * @param uuid
 * @param style
 * @param refresh
 */
setLayerStyle: function(layerType, owner, uuid, style, refresh){
    this.vectorLayer[owner][uuid].setLayerStyle(new ol.style.Style(style));
    if(refresh){
        this.vectorLayer[owner][uuid].refresh();
    }

},
createDrawControl: function(type, owner, options){
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

    this.olMap.addInteraction(draw);

    return id;

},
createModifyInteraction: function(owner, style, vectorId, featureId, events) {
    var vectorLayer = this.vectorLayer[owner][vectorId];
    var features = vectorLayer.getSource().getFeatures();
    var selectInteraction = new ol.interaction.Select({
        layers: vectorLayer,
        style: style
    });
    selectInteraction.getFeatures().push(features[0]);

    this.vectorLayer[owner][vectorId].interactions = this.vectorLayer[owner][vectorId].interactions  || {};
    this.vectorLayer[owner][vectorId].interactions.select = this.vectorLayer[owner][vectorId].interactions.select  || {};
    this.vectorLayer[owner][vectorId].interactions.select[vectorId] = selectInteraction;

    var modify = new ol.interaction.Modify({
        features: selectInteraction.getFeatures()
    });

    this.vectorLayer[owner][vectorId].interactions = this.vectorLayer[owner][vectorId].interactions  || {};
    this.vectorLayer[owner][vectorId].interactions[vectorId] = modify;

    _.each(events, function(value, key) {
        modify.on(key, value);
    }.bind(this));

    this.olMap.getInteractions().extend([selectInteraction, modify]);

    return vectorId;
},

deselectFeatureById: function(owner, vectorId) {
    var vectorLayer = this.vectorLayer[owner][vectorId];
    if (!vectorLayer.interactions.select) {
        return;
    }
    var interaction = vectorLayer.interactions.select[vectorId];
    interaction.getFeatures().clear();
},
removeVectorLayer: function(owner,id){
    var vectorLayer = this.vectorLayer[owner][id];
    if(this.vectorLayer[owner][id].hasOwnProperty('interactions')){
        this.removeInteractions(this.vectorLayer[owner][id].interactions);
    }
    this.olMap.removeLayer(vectorLayer);
    delete this.vectorLayer[owner][id];
},
removeInteractions: function(controls){
    _.each(controls, function(control, index){
        this.olMap.removeInteraction(control);
    }.bind(this));
},
eventFeatureWrapper: function(event, callback, args){
    var args = [event.feature].concat(args)
    return callback.apply(this,args);

},



getLineStringLength: function(line){
    return  ol.Sphere.getLength(line);
},
onFeatureChange: function(feature, callback,obvservable, args){
    return feature.getGeometry().on('change', function(evt) {
        var geom = evt.target;
        args = [geom].concat(args);
        obvservable.value =  callback.apply(this,args);
    });


},


/**
 * Create olDefaultStyle or olCustomStyle
 * @param {array} optOptions params from ol.style.Style.
 * @returns {ol.style.Style}
 */
createVectorLayerStyle: function(optOptions){
    var olStyle = null;
    if (optOptions){
        olStyle = this.getCustomStyle(optOptions);
    }else{
        var fill = new ol.style.Fill({
            color: 'rgba(255,255,255,0.4)'
        });
        var stroke = new ol.style.Stroke({
            color: '#3399CC',
            width: 1.25
        });

        olStyle= new ol.style.Style({
            fill: fill,
            stroke: stroke
        });
    }

    return olStyle;
},

/**
 *
 * @param owner
 * @param vectorId
 * @param featureId
 * @returns {ol.Feature}
 */
getFeatureById: function(owner, vectorId, featureId) {
    var source = this.vectorLayer[owner][vectorId].getSource();
    return source.getFeatureById(featureId);
},

/**
 *
 * @param owner
 * @param vectorId
 * @param featureId
 */
removeFeatureById: function(owner, vectorId, featureId) {
    var source = this.vectorLayer[owner][vectorId].getSource();
    var feature = source.getFeatureById(featureId);
    source.removeFeature(feature);
},

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
mbExtent: function mbExtent(extent) {
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
},

/**
 *
 * @param mbExtent
 */
zoomToExtent: function(extent) {
    this.olMap.getView().fit(this.mbExtent(extent), this.olMap.getSize());
},

removeAllFeaturesFromLayer: function(owner, id) {
    return this.vectorLayer[owner][id].getSource().clear();

},

getFeatureSize: function(feature, type) {

    if(type === 'line'){
        return this.getLineStringLength(feature);
    }
    if(type === 'area'){
        return   this.getPolygonArea(feature);
    }





},

getGeometryCoordinates: function (geom) {

    return geom.getFlatCoordinates();

},





getPolygonArea: function (polygon){
    return  ol.Sphere.getArea(polygon);
},

getGeometryFromFeatureWrapper: function(feature, callback, args){
    args = [feature.getGeometry()].concat(args)
    return callback.apply(this,args);

},

createTextStyle: function(options) {
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
},

    _changeLayerProjection: function(olLayer, newProj) {
        var nativeSource = olLayer.getSource();
        if (nativeSource) {
            nativeSource.projection_ = newProj;
        }
    },
    /**
     * Update map view according to selected projection
     *
     * @param {string} srsNameFrom
     * @param {string} srsNameTo
     */
    _changeProjectionInternal: function(srsNameFrom, srsNameTo) {
        var engine = Mapbender.mapEngine;
        var currentView = this.olMap.getView();
        var fromProj = ol.proj.get(srsNameFrom);
        var toProj = ol.proj.get(srsNameTo);
        var i, j, source, olLayers;
        if (!fromProj || !fromProj.getUnits() || !toProj || !toProj.getUnits()) {
            console.error("Missing / incomplete transformations (log order from / to)", [srsNameFrom, srsNameTo], [fromProj, toProj]);
            throw new Error("Missing / incomplete transformations");
        }
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                Mapbender.mapEngine.removeLayers(this.olMap, source.getNativeLayers());
                source.destroyLayers();
            } else {
                olLayers = source.getNativeLayers();
                for (j = 0; j < olLayers.length; ++ j) {
                    this._changeLayerProjection(olLayers[j], toProj);
                }
            }
        }

        // viewProjection.getUnits() may return undefined, safer this way!
        var currentUnits = fromProj.getUnits() || "degrees";
        var newUnits = toProj.getUnits() || "degrees";
        // transform projection extent (=max extent)
        // DO NOT use currentView.getProjection().getExtent() here!
        // Going back and forth between SRSs, there is extreme drift in the
        // calculated values. Always start from the configured maxExtent.
        var newMaxExtent = Mapbender.mapEngine.transformBounds(this.options.maxExtent, this._configProj, srsNameTo);

        var viewPortSize = this.olMap.getSize();
        var currentCenter = currentView.getCenter();
        var newCenter = ol.proj.transform(currentCenter, fromProj, toProj);

        // Recalculate resolution and allowed resolution steps
        var _convertResolution = this.convertResolution_.bind(undefined, currentUnits, newUnits);
        var newResolution = _convertResolution(currentView.getResolution());
        var newResolutions = this.viewOptions_.resolutions.map(_convertResolution);
        // Amend this.viewOptions_, we need the applied values for the next SRS switch
        var newViewOptions = $.extend(this.viewOptions_, {
            projection: srsNameTo,
            resolutions: newResolutions,
            center: newCenter,
            size: viewPortSize,
            resolution: newResolution,
            extent: newMaxExtent
        });

        var newView = new ol.View(newViewOptions);
        this.olMap.setView(newView);
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                olLayers = source.initializeLayers(srsNameTo);
                for (j = 0; j < olLayers.length; ++j) {
                    var olLayer = olLayers[j];
                    engine.setLayerVisibility(olLayer, false);
                }
                this._spliceLayers(source, olLayers);
            }
        }
        var self = this;
        self.sourceTree.map(function(source) {
            self._checkSource(source, false);
        });
    },

/**
 * Set style of map cursor
 *
 * @param style
 * @returns {Mapbender.Model}
 */
setMapCursorStyle: function (style) {
    this.olMap.getTargetElement().style.cursor = style;

    return this;
},

/**
 * Set marker on a map by provided coordinates
 *
 * @param {string[]} coordinates
 * @param {string} owner Element id
 * @param {string} vectorLayerId
 * @param {ol.style} style
 * @returns {string} vectorLayerId
 */
setMarkerOnCoordinates: function(coordinates, owner, vectorLayerId, style) {

    if (typeof coordinates === 'undefined') {
        throw new Error("Coordinates are not defined!");
    }

    var point = new ol.geom.Point(coordinates);

    if (typeof vectorLayerId === 'undefined' || null === vectorLayerId) {

        vectorLayerId = this.createVectorLayer({
            source: new ol.source.Vector({wrapX: false}),
        }, owner);

        this.olMap.addLayer(this.vectorLayer[owner][vectorLayerId]);
    }

    this.drawFeatureOnVectorLayer(point, this.vectorLayer[owner][vectorLayerId], style);

    return vectorLayerId;
},

/**
 * Draw feature on a vector layer
 *
 * @param {ol.geom} geometry
 * @param {ol.layer.Vector} vectorLayer
 * @param {ol.style} style
 * @returns {Mapbender.Model}
 */
drawFeatureOnVectorLayer: function(geometry, vectorLayer, style) {
    var feature = new ol.Feature({
        geometry: geometry,
    });

    feature.setStyle(style);

    var source = vectorLayer.getSource();

    source.addFeature(feature);

    return this;
},
        /**
         * @return {Array<Number>}
         */
        getCurrentExtentArray: function() {
            return this.olMap.getView().calculateExtent();
        },

/**
 *
 * @param currentUnit
 * @static
 * @returns {number}
 */
getMetersPerUnit: function(currentUnit) {
    return ol.proj.METERS_PER_UNIT[currentUnit];
},


getGeomFromFeature: function(feature) {
    'use strict';
    return feature.getGeometry();
},

/**
 * Returns the size of the map in the DOM (in pixels):
 * An array of numbers representing a size: [width, height].
 * @returns {Array.<number>}
 */
getMapSize: function() {
    return this.olMap.getSize();
},

/**
 * Returns the view center of a map:
 * An array of numbers representing an xy coordinate. Example: [16, 48].
 * @returns {Array.<number>}
 */
getMapCenter: function() {
    return this.olMap.getView().getCenter();
},

/**
 * @returns {ol.format.GeoJSON}
 */
createOlFormatGeoJSON: function() {
    return new ol.format.GeoJSON;
},

/**
 * Returns the features of the vectorLayers hashed by owner and uuid.
 * @returns {object.<string>.<string>.<Array.<ol.Feature>>}
 */
getVectorLayerFeatures: function() {
    var features = {};
    for (var owner in this.vectorLayer) {
        for (var uuid in this.vectorLayer[owner]) {
            var vectorLayer = this.vectorLayer[owner][uuid];
            if (!vectorLayer instanceof ol.layer.Vector) {
                continue;
            }

            if (!features[owner]) {
                features[owner] = {};
            }

            features[owner][uuid] = vectorLayer.getSource().getFeatures();
        }
    }

    return features;
},

/**
 * Returns the styles of the vectorLayers hashed by owner and uuid.
 * @returns {object.<string>.<string>.<ol.style.Style>}
 */
getVectorLayerStyles: function() {
    var styles = {};
    for (var owner in this.vectorLayer) {
        for (var uuid in this.vectorLayer[owner]) {
            var vectorLayer = this.vectorLayer[owner][uuid];
            if (!vectorLayer instanceof ol.layer.Vector) {
                continue;
            }

            if (!styles[owner]) {
                styles[owner] = {};
            }

            styles[owner][uuid] = vectorLayer.getStyle();
        }
    }

    return styles;
},

/**
 * Returns the print style options of the vectorLayers hashed by owner and uuid.
 * @returns {Object.<string>.<string>.<object>}
 */
getVectorLayerPrintStyleOptions: function() {
    var olVectorLayerStyles = this.getVectorLayerStyles();

    var allStyleOptions = {};

    for (var owner in olVectorLayerStyles) {
        for (var uuid in olVectorLayerStyles[owner]) {
            var olStyle = olVectorLayerStyles[owner][uuid];

            if (!olStyle instanceof ol.style.Style) {
                continue;
            }

            var styleOptions = {};

            // fill things.
            var colorAndOpacityObjectFill = this.getHexNormalColorAndOpacityObject(olStyle.getFill().getColor());
            styleOptions['fillColor'] = colorAndOpacityObjectFill.color;
            styleOptions['fillOpacity'] = colorAndOpacityObjectFill.opacity;

            // stroke things.
            var colorAndOpacityObjectStroke = this.getHexNormalColorAndOpacityObject(olStyle.getStroke().getColor());
            styleOptions['strokeColor'] = colorAndOpacityObjectStroke.color;
            styleOptions['strokeOpacity'] = colorAndOpacityObjectStroke.opacity;
            styleOptions['strokeWidth'] = olStyle.getStroke().getWidth();

            var strokeLinecap = olStyle.getStroke().getLineCap();
            styleOptions['strokeLinecap'] = strokeLinecap ? strokeLinecap : 'round';

            var strokeDashstyle = olStyle.getStroke().getLineDash();
            styleOptions['strokeDashstyle'] = strokeDashstyle ? strokeDashstyle : 'solid';


            styleOptions['pointRadius'] = 6;
            styleOptions['cursor'] = 'inherit';

            // font/label things.
            var fontColor = olStyle.getText().getFill().getColor();
            if (fontColor) {
                var colorAndOpacityObjectFontColor = this.getHexNormalColorAndOpacityObject(olStyle.getText().getFill().getColor());
                styleOptions['fontColor'] = colorAndOpacityObjectFontColor.color;

            } else {
                styleOptions['fontColor'] = '#000000';
            }

            var labelAlign = olStyle.getText().getTextAlign();
            styleOptions['labelAlign'] = labelAlign ? labelAlign : 'cm';

            styleOptions['labelOutlineColor'] = 'white';
            styleOptions['labelOutlineWidth'] = 3;

            allStyleOptions[owner][uuid] = styleOptions;
        }
    }

    return allStyleOptions;
},

/**
 * Returns an object with color and opacity. If the color is in rgb or rgba form, it will be converted
 * into a hex string.
 * @param {string} color
 * @returns {object}
 */
getHexNormalColorAndOpacityObject: function (color) {
    var opacity = 1;
    if (color.indexOf('rgb') !== -1) {
        if (color.indexOf('rgba') !== -1) {
            opacity = color.replace(/^.*,(.+)\)/,'$1');
        }
        color = this.rgb2hex(color);
    }

    var hexColorAndOpacityObject = {};
    hexColorAndOpacityObject['color'] = color;
    hexColorAndOpacityObject['opacity'] = opacity;

    return hexColorAndOpacityObject;
},

/**
 * @param {string} orig
 * @returns {string}
 */
rgb2hex: function(orig) {
    var rgb = orig.replace(/\s/g,'').match(/^rgba?\((\d+),(\d+),(\d+)/i);
    return (rgb && rgb.length === 4) ? "#" +
        ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
        ("0" + parseInt(rgb[3],10).toString(16)).slice(-2) : orig;
},

/**
 * Get resolution for zoom level
 *
 * @param {number} zoom
 * @returns {number}
 */
getResolutionForZoom: function(zoom) {
    return this.olMap.getView().getResolutionForZoom(zoom);
},

/**
 * Get zoom for resolution
 *
 * @param {number} resolution
 * @returns {number|undefined}
 */
getZoomForResolution: function(resolution) {
    return this.olMap.getView().getZoomForResolution(resolution);
},


/**
 *
 * @param projection
 */
mousePositionControlUpdateProjection: function(projection) {
    this.olMap.getControls().forEach(function (control) {
        if (control instanceof ol.control.MousePosition) {
            control.setProjection(projection);
        }
    });
},

/**
 * @param {object} options
 * @returns {object}
 */
initializeViewOptions: function(options) {
    var viewOptions = {
        projection: options.srs
    };
    if (options.maxExtent) {
        viewOptions.extent = options.maxExtent;
    }

    if (options.scales && options.scales.length) {
        var proj = ol.proj.get(options.srs);
        // Sometimes, the units are empty -.-
        // this seems to happen predominantely with "degrees" SRSs, so...
        var units = proj.getUnits() || 'degrees';
        var mpu = this.getMetersPerUnit(units);
        var dpi = options.dpi || 72;
        var inchesPerMetre = 39.37;
        viewOptions['resolutions'] = options.scales.map(function(scale) {
            return scale / (mpu * inchesPerMetre * dpi);
        }.bind(this));
    } else {
        viewOptions.zoom = 7; // hope for the best
    }
    return viewOptions;
},

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
convertResolution_: function(fromUnits, toUnits, resolution) {
    var resolutionFactor =
        ol.proj.METERS_PER_UNIT[fromUnits] /
        ol.proj.METERS_PER_UNIT[toUnits];
    return resolution * resolutionFactor;
},

/**
 * Create style for icon
 *
 * @param {*} options
 * @return {ol.style.Style}
 */
createIconStyle: function(options) {
    var defaultOptions = {
        anchor: [0.5, 46],
        anchorXUnits: 'fraction',
        anchorYUnits: 'pixels',
    };

    var options_ = $.extend({}, options, defaultOptions);

    var iconStyle = new ol.style.Style({
        image: new ol.style.Icon(options_)
    });

    return iconStyle;
},

/**
 * create ol.style.Style
 * @param {array} customStyle only fill, stroke, zIndex
 * @returns {ol.style.Style}
 */
getCustomStyle: function(customStyle) {
    var geometry = undefined;
    var fill = undefined;
    var image = undefined;
    var renderer = undefined;
    var stroke = undefined;
    var text = undefined;
    var zIndex = undefined;
    var keys = Object.keys(customStyle);
    var options = null;

    if (keys.length){
        for (var i = 0; i < keys.length; i++) {
            var varName = keys[i];

            switch(varName) {
                case 'geometry':
                    options = customStyle[varName] || '';
                    fill = new ol.geom.Geometry(options);
                    break;
                case 'fill':
                    options = customStyle[varName] || {};
                    fill = new ol.style.Fill(options);
                    break;
                case 'image':
                    options = customStyle[varName] || {};
                    image = new ol.style.Image(options);
                    break;
                case 'renderer':
                    options = customStyle[varName] || {};
                    stroke = ol.StyleRenderFunction(options);
                    break;
                case 'stroke':
                    options = customStyle[varName] || {};
                    stroke = new ol.style.Stroke(options);
                    break;
                case 'text':
                    options = customStyle[varName] || {};
                    stroke = new ol.style.Text(options);
                    break;
                case 'zIndex':
                    zIndex = customStyle[varName] ? customStyle[varName] : undefined;
                    break;
            }

        }
    }

    return new ol.style.Style({
        geometry: geometry,
        fill: fill,
        image: image,
        renderer:renderer,
        stroke: stroke,
        text: text,
        zIndex: zIndex
    });
}

    });

    return MapModelOl4;
}());
