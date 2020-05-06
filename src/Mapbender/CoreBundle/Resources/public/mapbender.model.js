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
     * @property {string} origId
     * @property {string} type
     * @property {string} ollid
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
    _highlightLayer: null,
    _geoJsonReader: null,
    _wktReader: null,
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
        // @todo: eliminate MapQuery method / property access completely
        // * accesses to 'layersList'
        // * layer lookup via 'mqlid' on source definitions

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

        this.setView(true);
        this.processUrlParams();
        if (this.mbMap.options.targetscale) {
            var zoom = this.pickZoomForScale(this.mbMap.options.targetscale, true);
            this.setZoomLevel(zoom, false);
        }
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
    /**
     * Returns a source from a sourceTree
     * @param {Object} idObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @returns {Model~SourceTreeish|null}
     */
    getSource: function(idObject) {
        var key;
        for (key in idObject) {
            break;
        }
        if (key) {
            for (var i = 0; i < this.sourceTree.length; i++) {
                if (this.sourceTree[i][key] && idObject[key]
                    && this.sourceTree[i][key].toString() === idObject[key].toString()) {
                    return this.sourceTree[i];
                }
            }
        }
        return null;
    },
    _afterZoom: function() {
        var scales = this._getScales();
        var zoom = this.getCurrentZoomLevel();
        $(this.mbMap.element).trigger('mbmapzoomchanged', {
            mbMap: this.mbMap,
            zoom: zoom,
            // scale: this.getCurrentScale()
            scale: scales[zoom]
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
        var extentObj = this._transformExtent(this.mapMaxExtent.extent, this._configProj, targetSrs);
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
     * Emulation shim for old-style MapQuery.Map.prototype.center.
     * See https://github.com/mapbender/mapquery/blob/1.0.2/src/jquery.mapquery.core.js#L298
     * @param {Model~CenterOptionsMapQueryish} options
     * @deprecated
     */
    setCenterMapqueryish: function(options) {
        if (!arguments.length) {
            throw new Error("Unsupported getter-style invocation");
        }
        if (options.projection) {
            throw new Error("Unsupported setCenterMapqueryish call with options.projection");
        }
        if (typeof options.box !== 'undefined') {
            console.warn("Deprecated setCenter call, please switch to Mapbender.Model.setExtent");
            this.setExtent(options.box);
        } else if (typeof options.position !== 'undefined' || typeof options.center !== 'undefined') {
            var _center = options.position || options.center;
            var x, y, zoom = options.zoom;
            if (typeof zoom === 'undefined') {
                zoom = null;
            }
            if ($.isArray(_center) && _center.length === 2) {
                x = _center[0];
                y = _center[1];
            } else {
                x = _center.lon;
                y = _center.lat;
            }
            if (typeof x === 'undefined' || typeof y == 'undefined' || x === null || y === null) {
                throw new Error("Invalid position / center option");
            }
            console.warn("Deprecated setCenter call, please switch to Mapbender.Model.centerXy");
            this.centerXy(x, y, {
                zoom: zoom
            });
        }
        throw new Error("Invalid setCenterMapqueryish options");
    },
    /**
     * @param {Model~CenterOptionsMapQueryish} options
     * @deprecated
     */
    center: function(options) {
        this.setCenterMapqueryish(options);
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
    getCurrentZoomLevel: function() {
        return this.map.olMap.getZoom();
    },
    getViewPort: function() {
        return this.map.olMap.viewPortDiv;
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

    /**
     * @param {Array<OpenLayers.Feature>} features
     * @param {Object} options
     * @property {boolean} [options.clearFirst]
     * @property {boolean} [options.goto]
     */
    highlightOn: function(features, options) {
        if (!this._highlightLayer) {
            this._highlightLayer = new OpenLayers.Layer.Vector('Highlight');
            var self = this;
            var selectControl = new OpenLayers.Control.SelectFeature(this._highlightLayer, {
                hover: true,
                onSelect: function(feature) {
                    // wrong event name, legacy
                    self.mbMap._trigger('highlighthoverin', null, {feature: feature});
                    // correct event name
                    self.mbMap._trigger('highlightselected', null, {feature: feature});
                },
                onUnselect: function(feature) {
                    // wrong event name, legacy
                    self.mbMap._trigger('highlighthoverout', null, {feature: feature});
                    // correct event name
                    self.mbMap._trigger('highlightunselected', null, {feature: feature});
                }
            });
            selectControl.handlers.feature.stopDown = false;
            this.map.olMap.addControl(selectControl);
            selectControl.activate();
        }
        if (!this._highlightLayer.map) {
            this.map.olMap.addLayer(this._highlightLayer);
        }

        // Remove existing features if requested
        if (!options || typeof options.clearFirst === 'undefined' || options.clearFirst) {
            this._highlightLayer.removeAllFeatures();
        }
        // Add new highlight features
        this._highlightLayer.addFeatures(features);
        // Goto features if requested
        if (!options || typeof options.goto === 'undefined' || options.goto) {
            var bounds = this._highlightLayer.getDataExtent();
            if (bounds !== null) {
                this.setExtent(bounds);
            }
        }
    },
    /**
     *
     */
    highlightOff: function(features) {
        if (!features && this._highlightLayer && this._highlightLayer.map) {
            this._highlightLayer.map.removeLayer(this._highlightLayer);
        } else if (features && this.highlightLayer) {
            this._highlightLayer.removeFeatures(features);
        }
    },
    /**
     *
     */
    removeSources: function(keepSources) {
        for (var i = 0; i < this.sourceTree.length; i++) {
            var source = this.sourceTree[i];
            if (!keepSources[source.id]) {
                this.removeSourceById(source.id);
            }
        }
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
    parseGeoJsonFeature: function(data) {
        return this._geoJsonReader.read(data)[0];
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
     * @return {Array<Number>}
     */
    getCurrentExtentArray: function() {
        return this.olMap.getExtent().toArray();
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
