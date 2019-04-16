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
     * @param {Mapbender.Source|Object} sourceOrSourceDef
     * @param {boolean} [mangleIds] to rewrite sourceDef.id and all layer ids EVEN IF ALREADY POPULATED
     * @returns {object} sourceDef same ref, potentially modified
     */
    addSourceFromConfig: function(sourceOrSourceDef, mangleIds) {
        var sourceDef;
        if (sourceOrSourceDef instanceof Mapbender.Source) {
            sourceDef = sourceOrSourceDef;
        } else {
            sourceDef = Mapbender.Source.factory(sourceOrSourceDef);
        }
        if (mangleIds) {
            sourceDef.id = this.generateSourceId();
            if (typeof sourceDef.origId === 'undefined' || sourceDef.origId === null) {
                sourceDef.origId = sourceDef.id;
            }
            sourceDef.rewriteLayerIds();
        }

        if (!this.getSourcePos(sourceDef)) {
            this.sourceTree.push(sourceDef);
        }
        var projCode = this.getCurrentProjectionCode();

        sourceDef.mqlid = this.map.trackSource(sourceDef).id;
        var olLayers = sourceDef.initializeLayers(projCode);
        for (var i = 0; i < olLayers.length; ++i) {
            var olLayer = olLayers[i];
            olLayer.setVisible(false);
        }

        this._spliceLayers(sourceDef, olLayers);

        this.mbMap.fireModelEvent({
            name: 'sourceAdded',
            value: {
                added: {
                    source: sourceDef,
                    // legacy: no known consumer evaluates these props,
                    // but even if, they've historically been wrong anyway
                    // was: "before": always last source previously in list, even though
                    // the new source was actually added *after* that
                    before: null,
                    after: null
                }
            }
        });
        this._checkSource(sourceDef, true, false);
        return sourceDef;
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
            // @todo; layer events
            //olLayer.events.register("loadstart", this, this._sourceLoadStart);
            //olLayer.events.register("tileerror", this, this._sourceLoadError);
            //olLayer.events.register("loadend", this, this._sourceLoadeEnd);
        }
    },
    /**
     * Check if OpenLayer layer need to be redraw
     *
     * @TODO: infoLayers should be set outside of the function
     *
     * @param {Object} source
     * @param {Object} layerParams
     * @param {Array<string>} layerParams.layers
     * @param {Array<string>} layerParams.styles
     *
     * @returns {boolean}
     * @private
     */
    _resetSourceVisibility: function(source, layerParams) {
        var olLayer = this.getNativeLayer(source);
        if (!olLayer || !olLayer.map) {
            // return false;
        }
        // @todo: this is almost entirely WMS specific
        // Clean up this mess. Move application of layer params into type-specific source classes
        var targetVisibility = !!layerParams.layers.length && source.configuration.children[0].options.treeOptions.selected;
        var visibilityChanged = targetVisibility !== olLayer.getVisible();
        var layersChanged = source.checkLayerParameterChanges(layerParams);

        if (!visibilityChanged && !layersChanged) {
            return false;
        }

        if (layersChanged && olLayer.map && olLayer.map.tileManager) {
            olLayer.map.tileManager.clearTileQueue({
                object: olLayer
            });
        }
        if (!targetVisibility) {
            olLayer.setVisible(false);
            return false;
        } else {
            var newParams = {
                LAYERS: layerParams.layers,
                STYLES: layerParams.styles
            };
            if (visibilityChanged) {
                // Prevent the browser from reusing the loaded image. This is almost equivalent
                // to a forced redraw (c.f. olLayer.redraw(true)), but without the undesirable
                // side effect of loading the layer twice on first activation.
                // @see https://github.com/openlayers/ol2/blob/master/lib/OpenLayers/Layer/HTTPRequest.js#L157
                newParams['_OLSALT'] = Math.random();
            }
            // Nuking the back buffer prevents the layer from going visible with old layer combination
            // before loading the new images.
            // @todo: backbuffer interaction?
            //olLayer.removeBackBuffer();
            //olLayer.createBackBuffer();
            olLayer.getSource().updateParams(newParams);
            olLayer.setVisible(true);
            return true;
        }
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
    /**
     * Bring the sources identified by the given ids into the given order.
     * All other sources will be left alone!
     *
     * @param {string[]} newIdOrder
     */
    reorderSources: function(newIdOrder) {
        var self = this;
        var olMap = this.olMap;

        var sourceObjs = (newIdOrder || []).map(function(id) {
            return self.getSourceById(id);
        });
        // Collect current positions used by the layers to be reordered
        // position := array index in olMap.layers
        // The collected positions will be reused / redistributed to the affected
        // layers, while all other layers stay in their current slots.
        var layersToMove = [];
        var currentLayerArray = this.olMap.getLayers().getArray();
        var oldIndexes = [];
        var olLayerIdsToMove = {};
        _.forEach(sourceObjs, function(sourceObj) {
            var olLayer = self.getNativeLayer(sourceObj);
            layersToMove.push(olLayer);
            oldIndexes.push(currentLayerArray.indexOf(olLayer));
            olLayerIdsToMove[olLayer.ol_uid] = true;
        });
        oldIndexes.sort(function(a, b) {
            // sort numerically (default sort performs string comparison)
            return a - b;
        });

        var unmovedLayers = currentLayerArray.filter(function(olLayer) {
            return !olLayerIdsToMove[olLayer.ol_uid];
        });

        // rebuild the layer list, mixing in unmoving layers with reordered layers
        var newLayers = [];
        var unmovedIndex = 0;
        for (var i = 0; i < oldIndexes.length; ++i) {
            var nextIndex = oldIndexes[i];
            while (nextIndex > newLayers.length) {
                newLayers.push(unmovedLayers[unmovedIndex]);
                ++unmovedIndex;
            }
            newLayers.push(layersToMove[i]);
        }
        while (unmovedIndex < unmovedLayers.length) {
            newLayers.push(unmovedLayers[unmovedIndex]);
            ++unmovedIndex;
        }
        // set new layer list, let OpenLayers reassign z indexes in list order
        olMap.getLayerGroup().setLayers(new ol.Collection(newLayers, {unique: true}));
        // Re-sort 'sourceTree' structure (inspected by legend etc for source order) according to actual, applied
        // layer order.
        this.sourceTree.sort(function(a, b) {
            var indexA = newLayers.indexOf(self.getNativeLayer(a));
            var indexB = newLayers.indexOf(self.getNativeLayer(b));
            return indexA - indexB;
        });
        this.mbMap.fireModelEvent({
            name: 'sourcesreordered'
        });
    },
    setZoomLevel: function(level, allowTransitionEffect) {
        var _level = this._clampZoomLevel(level);
        if (_level !== this.getCurrentZoomLevel()) {
            if (allowTransitionEffect) {
                this.olMap.getView().animate({zoom: _level});

            } else {
                this.olMap.getView().setZoom(_level);
            }
        }
    },
    getCurrentZoomLevel: function() {
        return this.olMap.getView().getZoom();
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
    getPointFeatureInfoUrl: function(source, x, y, maxCount) {
        var layerNames = source.getLayerParameters().infolayers;
        var engine = Mapbender.mapEngine;
        var olLayer = source.getNativeLayer(0);
        if (!(layerNames.length && olLayer && engine.getLayerVisibility(olLayer))) {
            return false;
        }
        /** @var {ol.source.ImageWMS|ol.source.TileWMS} nativeSource */
        var nativeSource = olLayer.getSource();
        if (!nativeSource.getGetFeatureInfoUrl) {
            return null;
        }
        var fiParams = {
            QUERY_LAYERS: layerNames,
            INFO_FORMAT: source.configuration.options.info_format || 'text/html',
            EXCEPTIONS: source.configuration.options.exception_format,
            FEATURE_COUNT: maxCount || 100
        };
        var res = this.olMap.getView().getResolution();
        var proj = this.getCurrentProjectionCode();
        var coord = this.olMap.getCoordinateFromPixel([x, y]);
        return nativeSource.getGetFeatureInfoUrl(coord, res, proj, fiParams);
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


/** @todo (following methods): put the "default" dpi in a common place? */
/**
 *
 * @param {number} dpi default this.map.options.dpi
 * @param {boolean} optRound Whether to round the scale or not.
 * @param {boolean} optScaleRating Whether to round the scale rating or not. K:X000 and M:X000000
 * @returns {number}
 */
getScale: function (dpi, optRound, optScaleRating) {
    var dpiNumber = dpi ? dpi : this.options.dpi;
    var resolution = this.olMap.getView().getResolution();
    var scaleCalc = this.resolutionToScale(resolution, dpiNumber);
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
 * @param owner
 * @param featuresArray
 */
addFeaturesVectorSource: function(owner,featuresArray) {
    var vectorLayer = this.vectorLayer[owner];
    var vectorSource = new ol.source.Vector({
        features: featuresArray
    });
    vectorLayer.setSource(vectorSource);
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

    /**
     * Update map view according to selected projection
     *
     * @param {string} srsCode
     */
    _changeProjectionInternal: function(srsNameFrom, srsNameTo) {
        var currentSrsCode = this.getCurrentProjectionCode();
        var currentView = this.olMap.getView();
        var fromProj = ol.proj.get(srsNameFrom);
        var toProj = ol.proj.get(srsNameTo);
        var i;
        if (!fromProj || !fromProj.getUnits() || !toProj || !toProj.getUnits()) {
            console.error("Missing / incomplete transformations (log order from / to)", [currentSrsCode, srsCode], [fromProj, toProj]);
            throw new Error("Missing / incomplete transformations");
        }
        for (i = 0; i < this.sourceTree.length; ++i) {
            var source = this.sourceTree[i];
            for (var j = 0; j < source.nativeLayers.length; ++j) {
                var olLayer = source.nativeLayers[j];
                var nativeSource = olLayer.getSource();
                if (nativeSource) {
                    nativeSource.projection_ = toProj;
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
    },

/**
 * Set callback for map moveend event
 * @param callback
 * @returns {ol.EventsKey|Array<ol.EventsKey>}
 */
setOnMoveendHandler: function(callback) {
    if (typeof callback === 'function') {
        return this.olMap.on("moveend", callback);
    }
},

/**
 * Remove event listener by event key
 *
 * @param key
 * @returns {Mapbender.Model}
 */
removeEventListenerByKey: function(key) {
    ol.Observable.unByKey(key);

    return this;
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

            // fill hover things.
            styleOptions['hoverFillColor'] = 'white';
            styleOptions['hoverFillOpacity'] = 0.8;

            // stroke things.
            var colorAndOpacityObjectStroke = this.getHexNormalColorAndOpacityObject(olStyle.getStroke().getColor());
            styleOptions['strokeColor'] = colorAndOpacityObjectStroke.color;
            styleOptions['strokeOpacity'] = colorAndOpacityObjectStroke.opacity;
            styleOptions['strokeWidth'] = olStyle.getStroke().getWidth();

            var strokeLinecap = olStyle.getStroke().getLineCap();
            styleOptions['strokeLinecap'] = strokeLinecap ? strokeLinecap : 'round';

            var strokeDashstyle = olStyle.getStroke().getLineDash();
            styleOptions['strokeDashstyle'] = strokeDashstyle ? strokeDashstyle : 'solid';


            // hover things.
            styleOptions['hoverStrokeColor'] = 'red';
            styleOptions['hoverStrokeOpacity'] = 1;
            styleOptions['hoverStrokeWidth'] = 0.2;
            styleOptions['pointRadius'] = 6;
            styleOptions['hoverPointRadius'] = 1;
            styleOptions['hoverPointUnit'] = '%';
            styleOptions['pointerEvents'] = 'visiblePainted';
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


            if (!allStyleOptions[owner]) {
                allStyleOptions[owner] = {};
            }

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
 * Get resolution for extent
 *
 * @param {Array} extent [minx, miny, maxx, maxy]
 * @param {Array} size [width, height]
 * @returns {number}
 */
getResolutionForExtent: function(extent, size) {
    return this.olMap.getView().getResolutionForExtent(extent, size);
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
