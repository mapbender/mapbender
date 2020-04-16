window.Mapbender = Mapbender || {};
window.Mapbender.MapModelOl4 = (function() {
    'use strict';

    /**
     * @param {Object} mbMap
     * @constructor
     */
    function MapModelOl4(mbMap) {
        Mapbender.MapModelBase.apply(this, arguments);
        this._geojsonFormat = new ol.format.GeoJSON();
        this._initMap();
        window.Mapbender.vectorLayerPool = window.Mapbender.VectorLayerPool.factory(Mapbender.mapEngine, this.olMap);
        this.displayPois(this._poiOptions);
    }

    MapModelOl4.prototype = Object.create(Mapbender.MapModelBase.prototype);
    Object.assign(MapModelOl4.prototype, {
        constructor: MapModelOl4,
        _geojsonFormat: null,
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
                coordinate: data.coordinate.slice()
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
     * @param {ol.Feature} feature
     * @param {Object} [options]
     * @param {number=} options.buffer in meters
     * @param {number=} options.minScale
     * @param {number=} options.maxScale
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    zoomToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, (options && options.buffer) || 0);

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
    parseGeoJsonFeature: function(data) {
        return this._geojsonFormat.readFeature(data);
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

createVectorLayer: function() {
    if (arguments.length) {
        console.error("Arguments passed to createVectorLayer", arguments);
        throw new Error("Arguments passed to createVectorLayer");
    }
    return new ol.layer.Vector({
        map: this.olMap,
        source: new ol.source.Vector({wrapX: false})
    });
},
destroyVectorLayer: function(olLayer) {
    this.olMap.removeLayer(olLayer);
},

clearVectorLayer: function(olLayer) {
    olLayer.getSource().clear();
},

addVectorFeatures: function(olLayer, features) {
    olLayer.getSource().addFeatures(features);
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
    var id = this.createVectorLayer();

    if (type === 'Box') {
        drawOptions.geometryFunction = ol.interaction.Draw.createBox();
        drawOptions.type = 'Circle';
    }

    var draw = new ol.interaction.Draw(drawOptions);

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
removeInteractions: function(controls){
    _.each(controls, function(control, index){
        this.olMap.removeInteraction(control);
    }.bind(this));
},
eventFeatureWrapper: function(event, callback, args){
    var args = [event.feature].concat(args)
    return callback.apply(this,args);

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

getFeatureSize: function(feature, type) {
    if(type === 'line') {
        return this.getLineStringLength(feature.getGeometry());
    }
    if(type === 'area') {
        return this.getPolygonArea(feature.getGeometry());
    }
},
getPolygonArea: function (polygonGeometry) {
    if (polygonGeometry.getFlatCoordinates().length < 3) {
        return null;
    } else {
        return ol.Sphere.getArea(polygonGeometry);
    }
},
getLineStringLength: function(lineGeometry){
    if (lineGeometry.getFlatCoordinates().length < 2) {
        return null;
    } else {
        return ol.Sphere.getLength(lineGeometry);
    }
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

        // transform projection extent (=max extent)
        // DO NOT use currentView.getProjection().getExtent() here!
        // Going back and forth between SRSs, there is extreme drift in the
        // calculated values. Always start from the configured maxExtent.
        var newMaxExtent = Mapbender.mapEngine.transformBounds(this.options.maxExtent, this._configProj, srsNameTo);

        var viewPortSize = this.olMap.getSize();
        var currentCenter = currentView.getCenter();
        var newCenter = ol.proj.transform(currentCenter, fromProj, toProj);

        // Recalculate resolution and allowed resolution steps
        var resolutionFactor =
            Mapbender.mapEngine.getProjectionUnitsPerMeter(srsNameTo)
            /
            Mapbender.mapEngine.getProjectionUnitsPerMeter(srsNameFrom)
        ;
        var newResolution = resolutionFactor * currentView.getResolution();
        var newResolutions = this.viewOptions_.resolutions.map(function(r) {
            return r*resolutionFactor;
        });
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

    var feature = new ol.Feature({
        geometry: new ol.geom.Point(coordinates)
    });
    if (style) {
        feature.setStyle(style);
    }

    if (typeof vectorLayerId === 'undefined' || null === vectorLayerId) {

        vectorLayerId = this.createVectorLayer({
            source: new ol.source.Vector({wrapX: false}),
        }, owner);

        this.olMap.addLayer(this.vectorLayer[owner][vectorLayerId]);
    }
    var layer = this.vectorLayer[owner][vectorLayerId];
    layer.getSource().addFeature(feature);
    return vectorLayerId;
},

        /**
         * @return {Array<Number>}
         */
        getCurrentExtentArray: function() {
            return this.olMap.getView().calculateExtent();
        },
        extractSvgFeatureStyle: function(olLayer, feature, resolution) {
            var styleOptions = {};
            var layerStyleFn = olLayer.getStyleFunction();
            /** @var {ol.style.Style} olStyle */
            var olStyle = layerStyleFn(feature, resolution)[0];
            Object.assign(styleOptions, this._extractSvgGeometryStyle(olStyle));
            var text = olStyle.getText();
            var label = text && text.getText();
            if (label) {
                Object.assign(styleOptions, this._extractSvgLabelStyle(text), {
                    label: label
                });
            }
            return styleOptions;
        },
        /**
         * @param {ol.style.Style} olStyle
         * @return {Object}
         * @private
         */
        _extractSvgGeometryStyle: function(olStyle) {
            var style = {};
            var fill = olStyle.getFill();
            var stroke = olStyle.getStroke();
            var image = olStyle.getImage();
            Object.assign(style,
                Mapbender.StyleUtil.cssColorToSvgRules(fill.getColor(), 'fillColor', 'fillOpacity'),
                Mapbender.StyleUtil.cssColorToSvgRules(stroke.getColor(), 'strokeColor', 'strokeOpacity')
            );
            style['strokeWidth'] = stroke.getWidth();

            style['strokeDashstyle'] = stroke.getLineDash() ||  'solid';
            if (image && (image instanceof ol.style.RegularShape)) {
                style['pointRadius'] = image.getRadius() || 6;
            }
            return style;
        },
        /**
         * @param {ol.style.Text} olTextStyle
         * @return {Object}
         * @private
         */
        _extractSvgLabelStyle: function(olTextStyle) {
            var style = {};
            var stroke = olTextStyle.getStroke();
            Object.assign(style,
                Mapbender.StyleUtil.cssColorToSvgRules(olTextStyle.getFill().getColor(), 'fontColor', 'fontOpacity'),
                Mapbender.StyleUtil.cssColorToSvgRules(stroke.getColor(), 'labelOutlineColor', 'labelOutlineOpacity')
            );
            style['labelOutlineWidth'] = stroke.getWidth();

            style['labelAlign'] = [olTextStyle.getTextAlign().slice(0, 1), olTextStyle.getTextBaseline().slice(0, 1)].join('');
            style['labelXOffset'] = olTextStyle.getOffsetX();
            style['labelYOffset'] = olTextStyle.getOffsetY();
            return style;
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
        // Sometimes, the units are empty -.-
        // this seems to happen predominantely with "degrees" SRSs, so...
        var upm = Mapbender.mapEngine.getProjectionUnitsPerMeter(options.srs);
        var dpi = options.dpi || 72;
        var inchesPerMetre = 39.37;
        viewOptions['resolutions'] = options.scales.map(function(scale) {
            return scale * upm / (inchesPerMetre * dpi);
        }.bind(this));
    } else {
        viewOptions.zoom = 7; // hope for the best
    }
    return viewOptions;
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
}

    });

    return MapModelOl4;
}());
