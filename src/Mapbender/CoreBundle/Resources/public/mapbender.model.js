((function($) {

window.Mapbender = Mapbender || {};
/**
 * @param {Object} mbMap
 * @constructor
 */
window.Mapbender.MapModelOl2 = function(mbMap) {
    Mapbender.MapModelBase.apply(this, arguments);
    this._geoJsonReader = new OpenLayers.Format.GeoJSON();
    this._wktReader = new OpenLayers.Format.WKT();
    this._initMap(mbMap);
    window.Mapbender.vectorLayerPool = window.Mapbender.VectorLayerPool.factory(Mapbender.mapEngine, this.olMap);
    this.displayPois(this._poiOptions);
};
Mapbender.MapModelOl2.prototype = Object.create(Mapbender.MapModelBase.prototype);
Object.assign(Mapbender.MapModelOl2.prototype, {
    constructor: Mapbender.MapModelOl2,
    /**
     * @typedef Model~LayerState
     * @property {boolean} visibility
     * @property {boolean} info
     * @property {boolean} outOfScale
     * @property {boolean} outOfBounds
     */
    /**
     * @typedef Model~TreeOptions
     * @property {boolean} selected
     * @property {boolean} info
     * @property {Object} allow
     */
    /**
     * @typedef Model~LayerChangeInfo
     * @property {Model~LayerState} state
     * @property {Object} options
     * @property {Model~TreeOptions} options.treeOptions
     */
    /**
     * @typedef Model~LayerTreeOptionWrapper
     * @property {Object} options
     * @property {Model~TreeOptions} options.treeOptions
     */
    /**
     * @typedef Model~LayerDef
     * @property {Object} options
     * @property {Model~TreeOptions} options.treeOptions
     * @property {Model~LayerState} state
     * @property {Array<Model~LayerDef>} children
     */
    /**
     * @typedef Model~SourceTreeish
     * @property {Object} configuration
     * @property {Array<Model~LayerDef>} configuration.children
     * @property {string} id
     * @property {string} type
     */
    /**
     * @typedef Model~SingleLayerPrintConfig
     * @property {string} type
     * @property {string} sourceId
     * @property {string} url
     * @property {number|null} minResolution
     * @property {number|null} maxResolution
     */
    /**
     * @typedef {Object} Model~CenterOptionsMapQueryish
     * @property {Array<Number>} [box]
     * @property {Array<Number>} [position]
     * @property {Array<Number>} [center] same as position! .position takes precedence if both are set
     * @property {Number} [zoom]
     */

    map: null,
    _initMap: function _initMap() {
        // dpi only used for scale to resolution / resolution to scale calculations
        this.options = {
            dpi: this.mbMap.options.dpi
        };
        var baseLayer = new OpenLayers.Layer('fake', {
            visibility: false,
            isBaseLayer: true,
            maxExtent: this._transformExtent(this.mapMaxExtent, this._configProj, this._startProj).toArray(),
            projection: this._startProj
        });
        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent, this._configProj, this._startProj).toArray(),
            maxResolution: 'auto',
            numZoomLevels: this.mbMap.options.scales ? this.mbMap.options.scales.length : this.mbMap.options.numZoomLevels,
            projection: this._startProj,
            displayProjection: this._startProj,
            units: this.getProj(this._startProj).proj.units || 'degrees',
            allOverlays: true,
            fallThrough: true,
            layers: [baseLayer],
            theme: null,
            // tile manager breaks tile WMS layers going out of scale as intended
            tileManager: null
        };
        if (this.mbMap.options.scales) {
            $.extend(mapOptions, {
                scales: this.mbMap.options.scales
            });
        }
        this.olMap = new OpenLayers.Map(this.mbMap.element.get(0), mapOptions);
        // Use a faked, somewhat compatible-ish surrogate for MapQuery Map
        this.map = new Mapbender.NotMapQueryMap(this.mbMap.element, this.olMap);

        // monkey-patch zoom interactions
        (function(olMap) {
            // need to monkey patch here in order to get next zoom in movestart event
            // prevents duplicate loads of WMS where a layer is going out of scale
            var setCenterOriginal = olMap.setCenter;
            var zoomToOriginal = olMap.zoomTo;
            olMap.setCenter = function(center, zoom) {
                if (zoom !== null && typeof zoom !== 'undefined') {
                    self.nextZoom = zoom;
                }
                setCenterOriginal.apply(this, arguments);
            };
            olMap.zoomTo = function(zoom, xy) {
                if (zoom !== null && typeof zoom !== 'undefined') {
                    self.nextZoom = zoom;
                }
                zoomToOriginal.apply(this, arguments);
            };
        })(this.olMap);
        this.olMap.addControl(new OpenLayers.Control.KeyboardDefaults());

        this.setView(true);
        this.processUrlParams();
        if (this.mbMap.options.targetscale) {
            var zoom = this.pickZoomForScale(this.mbMap.options.targetscale, true);
            this.setZoomLevel(zoom, false);
        }

        // Force-initialize map's layerContainerOrigin, minPx and maxPx properties. This avoids collateral errors
        // when converting between pixels and projected coordinates, e.g. implicitly in print.
        this.olMap.setCenter(this.olMap.getCenter(), this.olMap.getZoom(), false, true);

        this._setupHistoryControl();
        this._setupNavigationControl();
        this._initEvents(this.olMap, this.mbMap);
    },
    _initEvents: function(olMap, mbMap) {
        var self = this;
        olMap.events.register('zoomend', this, this._afterZoom);
        var clickHandlerOptions = {
            map: olMap
        };
        var handlerFn = function(event) {
            return self._onMapClick(event);
        };
        var clickHandler = new OpenLayers.Handler.Click({}, {click: handlerFn}, clickHandlerOptions);
        clickHandler.activate();
        olMap.events.register('moveend', null, function() {
            self.sourceTree.map(function(source) {
                self._checkSource(source, true);
            });
        });
    },
    _onMapClick: function(event) {
        var clickLonLat = this.olMap.getLonLatFromViewPortPx(event);
        $(this.mbMap.element).trigger('mbmapclick', {
            mbMap: this.mbMap,
            pixel: [event.x, event.y],
            coordinate: [clickLonLat.lon, clickLonLat.lat]
        });
    },
    _setupHistoryControl: function() {
        this.historyControl = new OpenLayers.Control.NavigationHistory();
        this.olMap.addControl(this.historyControl);
    },
    _setupNavigationControl: function() {
        this._navigationControl = this.map.olMap.getControlsByClass('OpenLayers.Control.Navigation')[0];
        this._navigationDragHandler = this._navigationControl.zoomBox.handler.dragHandler;
        this._initialDragHandlerKeyMask = this._navigationDragHandler.keyMask;
    },
    /**
     * Set map view: extent from URL parameters or configuration and POIs
     * @deprecated, call individual methods
     */
    setView: function(addLayers) {
        var mapOptions = this.mbMap.options;
        var lonlat;

        if (mapOptions.center) {
            lonlat = new OpenLayers.LonLat(mapOptions.center);
            this.map.olMap.setCenter(lonlat);
        } else if (this._poiOptions && this._poiOptions.length === 1) {
            var singlePoi = this._poiOptions[0];
            this.centerXy(singlePoi.x, singlePoi.y);
        } else {
            this.setExtent(this.mapStartExtent);
        }
        if (addLayers) {
            this.initializeSourceLayers();
        }
    },
    displayPoi: function(layer, poi) {
        var olMap = this.olMap;
        Mapbender.MapModelBase.prototype.displayPoi.call(this, layer, poi);
        if (poi.label) {
            olMap.addPopup(new OpenLayers.Popup.FramedCloud(null,
                new OpenLayers.LonLat(poi.x, poi.y),
                null,
                poi.label,
                null,
                true,
                function() {
                    olMap.removePopup(this);
                    this.destroy();
                }
            ));
        }
    },
    getCurrentProjectionCode: function() {
        if (this.olMap) {
            return this.olMap.getProjection();
        } else {
            return this._startProj;
        }
    },
    getCurrentProjectionUnits: function() {
        var proj;
        if (this.olMap) {
            proj = this.getProj(this.olMap.getProjection());
        } else {
            proj = this.getProj(this._startProj);
        }
        return proj.proj.units || 'degrees';
    },
    getCurrentProj: function() {
        if (this.map && this.map.olMap) {
            return this.map.olMap.getProjectionObject();
        } else {
            return this.getProj(this._startProj);
        }
    },
    /**
     * @param {string} srscode
     * @param {boolean} [strict] to throw errors (legacy default false)
     * @return {OpenLayers.Projection}
     */
    getProj: function(srscode, strict) {
        if (Proj4js.defs[srscode]) {
            var proj = new OpenLayers.Projection(srscode);
            if (!proj.proj.units) {
                proj.proj.units = 'degrees';
            }
            return proj;
        }
        if (strict) {
            throw new Error("Unsupported projection " + srscode.toString());
        }
        return null;
    },
    historyBack: function() {
        this.historyControl.previous.trigger();
    },
    historyForward: function() {
        this.historyControl.next.trigger();
    },
    /**
     * Calculates an extent from a geometry with buffer.
     * @param {OpenLayers.Geometry} geom geometry
     * @param {object} buffer {w: WWW,h: HHH}. WWW- buffer for x (kilometer), HHH- buffer for y (kilometer).
     * @returns {OpenLayers.Bounds}
     */
    calculateExtent: function(geom, buffer) {
        var proj = this.getCurrentProj();
        var centroid = geom.getCentroid();
        var bounds = geom.getBounds() ? geom.getBounds() : geom.calculateBounds();
        var buffer_bounds = {
            w: (bounds.right - bounds.left) / 2,
            h: (bounds.top - bounds.bottom) / 2
        };
        var k;
        var w;
        var h;
        if (proj.proj.units === 'degrees' || proj.proj.units === 'dd') {
            var pnt_ll = new OpenLayers.LonLat(centroid.x, centroid.y);
            var pnt_pxl = this.map.olMap.getViewPortPxFromLonLat(pnt_ll);
            var pnt_geodSz = this.map.olMap.getGeodesicPixelSize(pnt_pxl);
            var lb = new OpenLayers.Pixel(pnt_pxl.x - buffer.w / pnt_geodSz.w, pnt_pxl.y - buffer.h / pnt_geodSz.h);
            var rt = new OpenLayers.Pixel(pnt_pxl.x + buffer.w / pnt_geodSz.w, pnt_pxl.y + buffer.h / pnt_geodSz.h);
            var lb_lonlat = this.map.olMap.getLonLatFromLayerPx(lb);
            var rt_lonlat = this.map.olMap.getLonLatFromLayerPx(rt);
            return new OpenLayers.Bounds(
                lb_lonlat.lon - buffer_bounds.w,
                lb_lonlat.lat - buffer_bounds.h,
                rt_lonlat.lon + buffer_bounds.w,
                rt_lonlat.lat + buffer_bounds.h);
        } else if (proj.proj.units === 'm') {
            w = buffer.w;
            h = buffer.h;
        } else if (proj.proj.units === 'ft') {
            w = buffer.w / 0.3048;
            h = buffer.h / 0.3048;
        } else if (proj.proj.units === 'us-ft') {
            k = 0.3048 * 0.999998; // k === us-ft
            w = buffer.w / k;
            h = buffer.h / k;
        } else {
            w = 0;
            h = 0;
        }
        return new OpenLayers.Bounds(
            centroid.x - 0.5 * w - buffer_bounds.w,
            centroid.y - 0.5 * h - buffer_bounds.h,
            centroid.x + 0.5 * w + buffer_bounds.w,
            centroid.y + 0.5 * h + buffer_bounds.h);
    },
    getMapState: function() {
        var proj = this.map.olMap.getProjectionObject();
        var ext = this.map.olMap.getExtent();
        var maxExt = this.map.olMap.getMaxExtent();
        var size = this.map.olMap.getSize();
        var state = {
            window: {
                width: size.w,
                height: size.h
            },
            extent: {
                srs: proj.projCode,
                minx: ext.left,
                miny: ext.bottom,
                maxx: ext.right,
                maxy: ext.top
            },
            maxextent: {
                srs: proj.projCode,
                minx: maxExt.left,
                miny: maxExt.bottom,
                maxx: maxExt.right,
                maxy: maxExt.top
            },
            sources: []
        };
        var sources = this.getSources();
        for (var i = 0; i < sources.length; i++) {
            var source = sources[i];
            var sourceState = JSON.parse(JSON.stringify(source));
            state.sources.push(sourceState);
        }
        return state;
    },
    _afterZoom: function() {
        var scales = this._getScales();
        var zoom = this.getCurrentZoomLevel();
        $(this.mbMap.element).trigger('mbmapzoomchanged', {
            mbMap: this.mbMap,
            zoom: zoom,
            scale: scales[zoom],
            scaleExact: scales[zoom]
        });
    },
    /**
     * @param {Array|OpenLayers.Bounds|Object} boundsOrCoords
     */
    setExtent: function(boundsOrCoords) {
        var bounds;
        if ($.isArray(boundsOrCoords)) {
            bounds = OpenLayers.Bounds.fromArray(boundsOrCoords);
        } else {
            bounds = new OpenLayers.Bounds(
                boundsOrCoords.left,
                boundsOrCoords.bottom,
                boundsOrCoords.right,
                boundsOrCoords.top);
        }
        this.olMap.zoomToExtent(bounds);
    },
    getMaxExtentArray: function(srsName) {
        var targetSrs = srsName || this.getCurrentProjectionCode();
        var extentObj = this._transformExtent(this.mapMaxExtent, this._configProj, targetSrs);
        return extentObj.toArray();
    },
    zoomIn: function() {
        this.olMap.zoomIn();
    },
    zoomOut: function() {
        this.olMap.zoomOut();
    },
    zoomToFullExtent: function() {
        this.olMap.zoomToMaxExtent();
    },
    /**
     * @param {Number} x projected
     * @param {Number} y projected
     * @param {Object} [options]
     * @param {Number} [options.minScale]
     * @param {Number} [options.maxScale]
     * @param {Number|String} [options.zoom]
     */
    centerXy: function(x, y, options) {
        var centerLl = new OpenLayers.LonLat(x, y);
        var zoom = null;
        if (options && (options.zoom || parseInt(options.zoom) === 0)) {
            zoom = this._clampZoomLevel(parseInt(options.zoom));
        }
        zoom = this._adjustZoom(zoom, options);
        this.map.olMap.setCenter(centerLl, zoom);
    },
    /**
     * @param {OpenLayers.Feature.Vector} feature
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

        var zoom0 = this.map.olMap.getZoomForExtent(bounds, false);
        var zoom = this._adjustZoom(zoom0, options);
        var zoomNow = this.getCurrentZoomLevel();
        var featureInView = this.olMap.getExtent().containsBounds(bounds);
        if (center_ || zoom !== zoomNow || !featureInView) {
            var centerLl = bounds.getCenterLonLat();
            this.map.olMap.setCenter(centerLl, zoom);
        }
    },
    /**
     * @param {OpenLayers.Feature.Vector} feature
     * @param {Object} [options]
     * @param {number=} options.buffer in meters
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    panToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, (options && options.buffer) || 0);

        var featureInView = this.olMap.getExtent().containsBounds(bounds);
        if (center_ || !featureInView) {
            var centerLl = bounds.getCenterLonLat();
            this.map.olMap.setCenter(centerLl);
        }
    },
    setZoomLevel: function(level, allowTransitionEffect) {
        var _level = this._clampZoomLevel(level);
        if (_level !== this.getCurrentZoomLevel()) {
            if (allowTransitionEffect) {
                this.map.olMap.zoomTo(_level);
            } else {
                var centerPx = this.map.olMap.getViewPortPxFromLonLat(this.map.olMap.getCenter());
                var zoomCenter = this.map.olMap.getZoomTargetCenter(centerPx, _level);
                this.map.olMap.setCenter(zoomCenter, _level, false, true);
            }
        }
    },
    _getFractionalZoomLevel: function() {
        return this.map.olMap.getZoom();
    },
    getViewPort: function() {
        return this.map.olMap.viewPortDiv;
    },
    _getFractionalScale: function() {
        // no fractional zoom levels allowed in Openlayers 2
        return this.getCurrentScale(true);
    },
    _getScales: function() {
        // @todo: fractional zoom: method must not be called
        var baseLayer = this.map.olMap.baseLayer;
        if (!(baseLayer && baseLayer.scales && baseLayer.scales.length)) {
            console.error("No base layer, or scales not populated", baseLayer, this.map.olMap);
            throw new Error("No base layer, or scales not populated");
        }
        return baseLayer.scales.map(function(s) {
            return parseInt('' + Math.round(s));
        });
    },
    _countScales: function() {
        return this._getScales().length;
    },
    /**
     * @param {OpenLayers.Layer} olLayer
     * @param {OpenLayers.Projection} newProj
     * @param {OpenLayers.Bounds} [newMaxExtent]
     * @private
     */
    _changeLayerProjection: function(olLayer, newProj, newMaxExtent) {
        var layerOptions = {
            // passing projection as string is preferable to passing the object,
            // because it also auto-initializes units and projection-inherent maxExtent
            projection: newProj.projCode
        };
        if (olLayer.maxExtent) {
            layerOptions.maxExtent = newMaxExtent;
        }
        olLayer.addOptions(layerOptions);
    },
    /*
     * Changes the map's projection.
     */
    changeProjection: function(srsCode) {
        if (srsCode.projection) {
            console.warn("Legacy object-style argument passed to changeProjection");
            return this.changeProjection(srsCode.projection.projCode);
        }
        Mapbender.MapModelBase.prototype.changeProjection.call(this, srsCode);
    },
    _changeProjectionInternal: function(srsNameFrom, srsNameTo) {
        var engine = Mapbender.mapEngine;
        var oldProj = this.getProj(srsNameFrom);
        var newProj = this.getProj(srsNameTo);
        var newMaxExtent = this._transformExtent(this.mapMaxExtent, this._configProj, newProj);
        var i, j, olLayers, source;
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                source.destroyLayers();
            } else {
                olLayers = source.getNativeLayers();
                for (j = 0; j < olLayers.length; ++ j) {
                    this._changeLayerProjection(olLayers[j], newProj, newMaxExtent);
                }
            }
        }
        var center = this.map.olMap.getCenter().clone().transform(oldProj, newProj);
        var baseLayer = this.map.olMap.baseLayer || this.map.olMap.layers[0];
        if (baseLayer) {
            this._changeLayerProjection(baseLayer, newProj, newMaxExtent);
        }
        this.map.olMap.projection = newProj;
        this.map.olMap.displayProjection = newProj;
        this.map.olMap.units = newProj.proj.units;
        this.map.olMap.maxExtent = newMaxExtent;
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
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
     * Injects native layers into the map at the "natural" position for the source.
     * This supports multiple layers for the same source.
     *
     * @param {Mapbender.Source} source
     * @param {OpenLayers.Layer} olLayers
     * @private
     */
    _spliceLayers: function(source, olLayers) {
        var sourceIndex = this.sourceTree.indexOf(source);
        if (sourceIndex === -1) {
            console.error("Can't splice layers for source with unknown position", source, olLayers);
            throw new Error("Can't splice layers for source with unknown position");
        }
        var olMap = this.olMap;
        var afterLayer = olMap.baseLayer || olMap.layers[0];
        for (var s = sourceIndex - 1; s >= 0; --s) {
            var previousSource = this.sourceTree[s];
            var previousLayer = (previousSource.nativeLayers.slice(-1))[0];
            if (previousLayer && previousLayer.map === olMap) {
                afterLayer = previousLayer;
                break;
            }
        }
        var baseIndex = olMap.getLayerIndex(afterLayer) + 1;
        for (var i = 0; i < olLayers.length; ++i) {
            var olLayer = olLayers[i];
            olMap.addLayer(olLayer);
            olMap.setLayerIndex(olLayer, baseIndex + i);
            olLayer.mbConfig = source;
            this._initLayerEvents(olLayer, source, i);
        }
    },
    /**
     * Parses a single (E)WKT feature from text. Returns the engine-native feature.
     *
     * @param {String} text
     * @param {String} [sourceSrsName]
     * @return {OpenLayers.Feature.Vector}
     */
    parseWktFeature: function(text, sourceSrsName) {
        var ewktMatch = text.match(/^SRID=([^;]*);(.*)$/);
        if (ewktMatch) {
            return this.parseWktFeature(ewktMatch[2], ewktMatch[1]);
        }
        var targetSrsName = this.olMap.getProjection();
        this._wktReader.externalProjection = sourceSrsName || null;
        this._wktReader.internalProjection = targetSrsName;
        var feature = this._wktReader.read(text);
        if (Array.isArray(feature)) {
            // Fix geometrycollection parse result to maintain API expectations
            // OpenLayers does something similar internally in a different path,
            /** @see OpenLayers.Geometry.fromWKT */
            var geom = new OpenLayers.Geometry.Collection(feature.map(function(f) {
                return f.geometry;
            }));
            feature = new OpenLayers.Feature.Vector(geom);
        }
        return feature;
    },
    /**
     * @param {*} data
     * @param {String} [sourceSrsName]
     * @return {*}
     */
    parseGeoJsonFeature: function(data, sourceSrsName) {
        var feature = this._geoJsonReader.read(data)[0];

        if (feature && feature.geometry && sourceSrsName) {
            var targetSrsName = this.olMap.getProjection();
            if (targetSrsName !== sourceSrsName) {
                feature.geometry.transform(sourceSrsName, targetSrsName);
            }
        }
        return feature;
    },
    /**
     * @param {OpenLayers.Feature.Vector} feature
     */
    featureToGeoJsonGeometry: function(feature) {
        var gj = this._geoJsonReader.extract.feature.call(this._geoJsonReader, feature);
        return gj.geometry;
    },
    /**
     * Centered feature rotation (counter-clockwise)
     *
     * @param {OpenLayers.Feature.Vector} feature
     * @param {Number} degrees
     */
    rotateFeature: function(feature, degrees) {
        feature.geometry.rotate(degrees, feature.geometry.getCentroid(false));
    },
    /**
     * Returns the center coordinate of the given feature as an array, ordered x, y (aka lon, lat)
     * @param {OpenLayers.Feature.Vector} feature
     * @returns {Array<Number>}
     */
    getFeatureCenter: function(feature) {
        var center = feature.geometry.getBounds().getCenterLonLat();
        return [center.lon, center.lat];
    },
    /**
     * @param {OpenLayers.Layer.Vector} olLayer
     * @param {OpenLayers.Feature.Vector} feature
     * @return {Object}
     */
    extractSvgFeatureStyle: function(olLayer, feature) {
        if (feature.style) {
            // stringify => decode: makes a deep copy of the style at the moment of capture
            return JSON.parse(JSON.stringify(feature.style));
        } else {
            return olLayer.styleMap.createSymbolizer(feature, feature.renderIntent);
        }
    },
    _initLayerEvents: function(olLayer, source, sourceLayerIndex) {
        var mbMap = this.mbMap;
        var engine = Mapbender.mapEngine;
        olLayer.events.register("loadstart", null, function() {
            mbMap.element.trigger('mbmapsourceloadstart', {
                mbMap: mbMap,
                source: source
            });
        });
        olLayer.events.register("tileerror", null, function() {
            if (engine.getLayerVisibility(olLayer)) {
                mbMap.element.trigger('mbmapsourceloaderror', {
                    mbMap: mbMap,
                    source: source
                });
            }
        });
        olLayer.events.register("loadend", null, function() {
            mbMap.element.trigger('mbmapsourceloadend', {
                mbMap: mbMap,
                source: source
            });
        });
    },
    /**
     * Returns the center coordinate of the current map view as an array, ordered x, y (aka lon, lat)
     * @return {Array<Number>}
     */
    getCurrentMapCenter: function() {
        var centerNative = this.olMap.getCenter();
        return [centerNative.lon, centerNative.lat];
    },
    /**
     * @return {Array<Number>}
     */
    getCurrentExtentArray: function() {
        return this.olMap.getExtent().toArray();
    },
    getViewRotation: function() {
        // no rotation support => always 0
        return 0;
    },
    setViewRotation: function(degrees, animate) {
        throw new Error("Rotation not supported on current engine " + Mapbender.mapEngine.code);
    },
    /**
     * @param {OpenLayers.Bounds} extent
     * @param {string|OpenLayers.Projection} fromProj
     * @param {string|OpenLayers.Projection} toProj
     * @returns {OpenLayers.Bounds}
     */
    _transformExtent: function(extent, fromProj, toProj) {
        return Mapbender.mapEngine.transformBounds(extent, fromProj, toProj);
    }
});
})(jQuery));
