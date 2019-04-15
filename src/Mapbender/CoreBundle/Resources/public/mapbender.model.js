((function($) {

window.Mapbender = Mapbender || {};
    /**
     * @param {Object} mbMap
     * @constructor
     */
    window.Mapbender.MapModelOl2 = function(mbMap) {
    Mapbender.MapModelBase.apply(this, arguments);
    this._initMap(mbMap);
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
    _initMap: function _initMap() {
        this._patchNavigationControl();
        var self = this;

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
        this.parseURL();
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
        olMap.events.register('movestart', this, $.proxy(this._checkChanges, this));
        olMap.events.register('moveend', this, $.proxy(this._checkChanges, this));
    },
    _onMapClick: function(event) {
        var clickLonLat = this.olMap.getLonLatFromViewPortPx(event);
        $(this.mbMap.element).trigger('mbmapclick', {
            mbMap: this.mbMap,
            pixel: [event.x, event.y],
            coordinate: [clickLonLat.lon, clickLonLat.lat],
            event: event
        });
    },
    _setupHistoryControl: function() {
        this.historyControl = new OpenLayers.Control.NavigationHistory();
        this.olMap.addControl(this.historyControl);
    },
    _patchNavigationControl: function() {
        var self = this;
        var zbZoomBoxMethodOriginal = OpenLayers.Control.ZoomBox.prototype.zoomBox;

        function zbZoomBoxMethodPatched(position) {
            console.warn("Monkey-patched zoomBoxControl.zoomBox", this, arguments);
            zbZoomBoxMethodOriginal.apply(this, arguments);
            self._afterZoomBox(this.map);
        }
        OpenLayers.Control.ZoomBox.prototype.zoomBox = zbZoomBoxMethodPatched;
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
        var pois = mapOptions.extra && mapOptions.extra.pois;
        var lonlat;

        if (mapOptions.center) {
            lonlat = new OpenLayers.LonLat(mapOptions.center);
            this.map.olMap.setCenter(lonlat);
        } else if (pois && pois.length === 1) {
            var singlePoi = pois[0];
            lonlat = new OpenLayers.LonLat(singlePoi.x, singlePoi.y);
            lonlat = lonlat.transform(this.getProj(singlePoi.srs), this.getProj(this._startProj));
            this.map.olMap.setCenter(lonlat);
        } else {
            this.setExtent(this.mapStartExtent);
        }
        if (addLayers) {
            this.initializeSourceLayers();
        }
        this.initializePois(pois || []);
    },
    initializePois: function(poiOptionsList) {
        var self = this;
        if (!poiOptionsList.length) {
            return;
        }
        var mapProj = this.getProj(this.mbMap.options.targetsrs || this.mbMap.options.srs);
        var pois = poiOptionsList.map(function(poi) {
            var coord = new OpenLayers.LonLat(poi.x, poi.y);
            if (poi.srs) {
                coord = coord.transform(self.getProj(poi.srs), mapProj);
            }
            return {
                position: coord,
                label: poi.label
            };
        });

        var poiMarkerLayer = new OpenLayers.Layer.Markers();
        var poiIcon = new OpenLayers.Icon(
            Mapbender.configuration.application.urls.asset + this.mbMap.options.poiIcon.image,
            {
                w: this.mbMap.options.poiIcon.width,
                h: this.mbMap.options.poiIcon.height
            },
            {
                x: this.mbMap.options.poiIcon.xoffset,
                y: this.mbMap.options.poiIcon.yoffset
            }
        );

        this.map.olMap.addLayer(poiMarkerLayer);

        $.each(pois, function(idx, poi) {
            // Marker
            poiMarkerLayer.addMarker(new OpenLayers.Marker(
                poi.position,
                poiIcon.clone()));

            if (poi.label) {
                self.map.olMap.addPopup(new OpenLayers.Popup.FramedCloud('chicken',
                    poi.position,
                    null,
                    poi.label,
                    null,
                    true,
                    function() {
                        self.mbMap.removePopup(this);
                        this.destroy();
                    }));
            }
        });
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
    getPointFeatureInfoUrl: function(source, x, y, maxCount) {
        var layerNames = source.getLayerParameters({}).infolayers;
        var engine = Mapbender.mapEngine;
        var olLayer = source.getNativeLayer(0);
        if (!(layerNames.length && olLayer && engine.getLayerVisibility(olLayer))) {
            return false;
        }
        var control = new OpenLayers.Control.WMSGetFeatureInfo({
            url: Mapbender.Util.removeProxy(olLayer.url),
            layers: [olLayer],
            queryVisible: true,
            maxFeatures: maxCount || 100
        });
        control.map = olLayer.map;
        var reqObj = control.buildWMSOptions(
            Mapbender.Util.removeProxy(olLayer.url),
            [olLayer],
            {x: x, y: y},
            olLayer.params.FORMAT
        );
        reqObj.params['LAYERS'] = layerNames;
        reqObj.params['QUERY_LAYERS'] = layerNames;
        reqObj.params['STYLES'] = [];
        reqObj.params['EXCEPTIONS'] = source.configuration.options.exception_format;
        reqObj.params['INFO_FORMAT'] = source.configuration.options.info_format || 'text/html';
        var reqUrl = OpenLayers.Util.urlAppend(reqObj.url, OpenLayers.Util.getParameterString(reqObj.params || {}));
        return reqUrl;
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
    getMapExtent: function() {
        return this.map.olMap.getExtent();
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
            // HACK amenity for completely unused XML representation
            // see src/Mapbender/WmcBundle/Resources/views/Wmc/wmc110_simple.xml.twig
            sourceState.layers = [];
            var list = Mapbender.source[source.type].getLayersList(source);
            $.each(list.layers, function(idx, layer) {
                sourceState.layers.push(layer.options.name);
            });
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
    resetSourceUrl: function(source, options) {
        if (options.add) {
            source.addParams(options.add);
        } else if (options.remove) {
            source.removeParams(Object.keys(options.remove));
        }
    },
    /**
     * @param options
     * @return {Array<Model~SourceTreeish>}
     */
    findSource: function(options) {
        var sources = [];
        var findSource = function(object, options) {
            var found = null;
            for (var key in options) {
                if (object[key]) {
                    if (typeof object[key] === 'object') {
                        var res = findSource(object[key], options[key]);
                        if (found === null)
                            found = res;
                        else
                            found = found && res;

                    } else {
                        return object[key] === options[key]
                    }
                }
            }
            return found;
        };
        for (var i = 0; i < this.sourceTree.length; i++) {
            var source = this.sourceTree[i];
            if (findSource(source, options))
                sources.push(source);
        }
        return sources;
    },
    /**
     * @deprecated used by layertree only, return type is presentationy, supports only a single search criterion
     * @param options
     * @returns {*}
     */
    findLayerset: function(options) {
        if (!(options.source && options.source.origId)) {
            console.error("Invalid layerset search parameters", options);
            throw new Error("Invalid layerset search parameters");
        }
        for (var layersetId in Mapbender.configuration.layersets) {
            var layerset = Mapbender.configuration.layersets[layersetId];
            for (var i = 0; i < layerset.length; i++) {
                if (layerset[i][options.source.origId]) {
                    return {
                        id: layersetId,
                        title: Mapbender.configuration.layersetmap[layersetId],
                        content: layerset
                    };
                }
            }
        }
        return null;
    },
    /**
     * Returns the current map's scale
     */
    getScale: function() {
        if (this.nextZoom) {
            return this.map.olMap.scales[this.nextZoom];
        }
        return Math.round(this.map.olMap.getScale());
    },
    /**
     * Enables the zoom selection box immediately without requiring a key combination (default: SHIFT)
     * to be held.
     */
    zoomBoxOn: function() {
        $(this.getViewPort()).css({cursor: 'crosshair'});
        this._navigationDragHandler.keyMask = OpenLayers.Handler.MOD_NONE;
    },
    /**
     * Reenables the keymask for drawing a zoom box
     */
    zoomBoxOff: function() {
        $(this.getViewPort()).css({cursor: ''});
        this._navigationDragHandler.keyMask = this._initialDragHandlerKeyMask;
    },
    _afterZoomBox: function(map) {
        if (map === this.map.olMap) {
            this.zoomBoxOff();
            $(this.mbMap.element).trigger('mbmapafterzoombox', {
                mbMap: this.mbMap
            });
        }
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
     * Calculates and applies layer state changes from accumulated treeOption changes in the source and (optionally)
     * 1) updates the engine layer parameters and redraws
     * 2) fires a mbmapsourcechanged event with the updated individual layer states
     * @param {Object} source
     * @param {boolean} redraw
     * @param {boolean} fireSourceChangedEvent
     */
    _checkSource: function(source, redraw, fireSourceChangedEvent) {
        var gsHandler = this.getGeoSourceHandler(source, true);
        var newStates = gsHandler.calculateLeafLayerStates(source, this.getScale());
        var changedStates = gsHandler.applyLayerStates(source, newStates);
        if (redraw) {
            var layerParams = source.getLayerParameters(newStates);
            this._resetSourceVisibility(source, layerParams);
        }
        if (fireSourceChangedEvent && Object.keys(changedStates).length) {
            this.mbMap.fireModelEvent({
                name: 'sourceChanged',
                value: {
                    changed: {
                        children: changedStates,
                        sourceIdx: {id: source.id}
                    }
                }
            });
        }
    },
    _checkChanges: function(e) {
        var isPreEvent = e.type === 'movestart';
        var self = this;
        $.each(self.sourceTree, function(idx, source) {
            self._checkSource(source, !isPreEvent, isPreEvent);
        });
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
            return false;
        }
        // @todo: this is almost entirely WMS specific
        // Clean up this mess. Move application of layer params into type-specific source classes
        var targetVisibility = !!layerParams.layers.length && source.configuration.children[0].options.treeOptions.selected;
        var visibilityChanged = targetVisibility !== olLayer.getVisibility();
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
            olLayer.setVisibility(false);
            olLayer.params.LAYERS = [];
            olLayer.params.STYLES = [];
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
            olLayer.removeBackBuffer();
            olLayer.createBackBuffer();
            olLayer.mergeNewParams(newParams);
            olLayer.setVisibility(true);
            return true;
        }
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
        if (!feature || !feature.geometry) {
            console.error("Empty feature or empty feature geometry", feature);
            return;
        }
        var center_;
        if (options) {
            center_ = options.center || typeof options.center === 'undefined';
        } else {
            center_ = true;
        }
        var bounds = feature.geometry.getBounds().clone();
        if (options && options.buffer) {
            var unitsPerMeter = this.getProjectionUnitsPerMeter(this.getCurrentProjectionCode());
            var bufferNative = options.buffer * unitsPerMeter;
            bounds.left -= bufferNative;
            bounds.right += bufferNative;
            bounds.top += bufferNative;
            bounds.bottom -= bufferNative;
        }
        var zoom0 = this.map.olMap.getZoomForExtent(bounds, false);
        var zoom = this._adjustZoom(zoom0, options);
        var zoomNow = this.getCurrentZoomLevel();
        var featureInView = this.olMap.getExtent().containsBounds(bounds);
        if (center_ || zoom !== zoomNow || !featureInView) {
            var centerLl = bounds.getCenterLonLat();
            this.map.olMap.setCenter(centerLl, zoom);
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
    panByPixels: function(dx, dy) {
        this.map.olMap.pan(dx, dy);
    },
    panByPercent: function(dx, dy) {
        var mapSize = this.map.olMap.getSize();
        var pixelDx = (dx / 100.0) * mapSize.w;
        var pixelDy = (dy / 100.0) * mapSize.h;
        this.panByPixels(pixelDx, pixelDy);
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
     * @param {number|null} targetZoom
     * @param {Object} scaleOptions
     * @param {number=} scaleOptions.minScale
     * @param {number=} scaleOptions.maxScale
     * @return {number|null}
     * @private
     */
    _adjustZoom: function(targetZoom, scaleOptions) {
        var zoom = targetZoom;
        var zoomNow = this.map.olMap.getZoom();
        if (scaleOptions && scaleOptions.minScale) {
            var maxZoom = this.pickZoomForScale(scaleOptions.minScale, true);
            if (zoom !== null) {
                zoom = Math.min(zoom, maxZoom);
            } else {
                zoom = Math.min(zoomNow, maxZoom);
            }
        }
        if (scaleOptions && scaleOptions.maxScale) {
            var minZoom = this.pickZoomForScale(scaleOptions.maxScale, false);
            if (zoom !== null) {
                zoom = Math.max(zoom, minZoom);
            } else {
                zoom = Math.max(zoomNow, minZoom);
            }
        }
        return zoom;
    },
    /**
     * @param {Object} e
     * @param {OpenLayers.Layer} e.object
     */
    _sourceLoadStart: function(e) {
        var source = this.getMbConfig(e.object);
        this.mbMap.fireModelEvent({
            name: 'sourceloadstart',
            value: {
                source: source
            }
        });
    },
    /**
     * @param {Object} e
     * @param {OpenLayers.Layer} e.object
     */
    _sourceLoadeEnd: function(e) {
        var source = this.getMbConfig(e.object);
        this.mbMap.fireModelEvent({
            name: 'sourceloadend',
            value: {
                source: source
            }
        });
    },
    /**
     * @param {Object} e
     * @param {OpenLayers.Tile} e.tile
     */
    _sourceLoadError: function(e) {
        if (e.tile.layer && e.tile.layer.getVisibility()) {
            var source = this.getMbConfig(e.tile.layer);
            if (!source) {
                console.error("Source load error, but source unknown", e);
                return;
            }
            this.mbMap.fireModelEvent({
                name: 'sourceloaderror',
                value: {
                    source: source,
                    error: {
                        sourceId: source.origId,
                        details: Mapbender.trans('mb.geosource.image_error.datails') // sic!
                    }
                }
            });
        }
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
            olLayer.setVisibility(false);
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
     * Performs bulk-updates on the targetted source's treeOptions to make
     * specific, or all, layers visible or invisible.
     * Individual layers can be targetted by the options.layers mapping, which
     * should map layer ids to {options: {treeOptions: {selected: <something>}}}
     * objects.
     *
     * All other layers use the defaultSelected value for their new 'selected' value,
     * which defaults to false (=off).
     * Explicitly pass null for defaultSelected to avoid this.
     *
     * If you pass mergeSelected=true, you will essentially be prevented from turning
     * any layer off that is currently on.
     *
     * NOTE: The resulting source tree change will also implicitly enable parent
     *       layers of the layers you asked to enable. This behaviour cannot be disabled.
     *       If you intend to just "tick a checkbox" without implicit side effects
     *       on parent layers, use controlLayers instead.
     *
     * NOTE: This method can operate on sources that are OUTSIDE the current 'sourceTree',
     *       i.e. plain-data source definition objects that have not yet been promoted
     *       to active map sources.
     *
     *
     * @param {Object} sourceIdObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @param {Object} options
     * @param {Object<string, Model~LayerTreeOptionWrapper>} options.layers
     * @param {boolean|null} [defaultSelected] defaults to false
     * @param {boolean} [mergeSelected] defaults to false
     *
     */
    changeLayerState: function(sourceIdObject, options, defaultSelected, mergeSelected) {
        if (typeof mergeSelected === 'undefined')
            mergeSelected = false;
        if (typeof defaultSelected === 'undefined')
            defaultSelected = false;
        var source;
        if (sourceIdObject.configuration && sourceIdObject.id && sourceIdObject.configuration.children && sourceIdObject.type) {
            // let's just assume this already is a full-fledged source definition
            source = sourceIdObject;
        } else {
            source = this.getSource(sourceIdObject);
        }
        if (source !== null) {
            if (!(source instanceof Mapbender.Source)) {
                source = Mapbender.Source.factory(source);
            }
            var toChangeOptions = Mapbender.source[source.type].createOptionsLayerState(source, options,
                defaultSelected, mergeSelected);
            this._updateSourceLayerTreeOptions(source, toChangeOptions.change.options.children);
        }
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
        var oldIndexes = [];
        var olLayerIdsToMove = {};
        _.forEach(sourceObjs, function(sourceObj) {
            var olLayer = self.getNativeLayer(sourceObj);
            layersToMove.push(olLayer);
            oldIndexes.push(olMap.getLayerIndex(olLayer));
            olLayerIdsToMove[olLayer.id] = true;
        });
        oldIndexes.sort(function(a, b) {
            // sort numerically (default sort performs string comparison)
            return a - b;
        });

        var unmovedLayers = olMap.layers.filter(function(olLayer) {
            return !olLayerIdsToMove[olLayer.id];
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
        olMap.layers = newLayers;
        olMap.resetLayersZIndex();
        // Re-sort 'sourceTree' structure (inspected by legend etc for source order) according to actual, applied
        // layer order.
        this.sourceTree.sort(function(a, b) {
            var indexA = olMap.getLayerIndex(self.getNativeLayer(a));
            var indexB = olMap.getLayerIndex(self.getNativeLayer(b));
            return indexA - indexB;
        });
        this.mbMap.fireModelEvent({
            name: 'sourcesreordered'
        });
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
        var oldProj = this.getProj(srsNameFrom);
        var newProj = this.getProj(srsNameTo);
        var newMaxExtent = this._transformExtent(this.mapMaxExtent, this._configProj, newProj);
        var i, j, olLayers, dynamicSources = [], source;
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(oldProj, newProj)) {
                dynamicSources.push(source);
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
        for (i = 0; i < dynamicSources.length; ++i) {
            source = dynamicSources[i];
            if (source.checkRecreateOnSrsSwitch(oldProj, newProj)) {
                olLayers = source.initializeLayers(newProj.projCode);
                for (j = 0; j < olLayers.length; ++j) {
                    var olLayer = olLayers[j];
                    olLayer.setVisibility(false);
                }
                this._spliceLayers(source, olLayers);
            }
        }
        this._checkChanges({type: 'not-really-an-event'});
    },
    getProjectionUnitsPerMeter: function(srsName) {
        var units = this.getProj(srsName).proj.units || 'dd';
        if (units === 'm' || units === 'Meter') {
            return 1.0;
        } else {
            var metersPerUnit = OpenLayers.INCHES_PER_UNIT[units] * OpenLayers.METERS_PER_INCH;
            return 1.0 / metersPerUnit;
        }
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
        var olMap = this.map.olMap;
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
            olLayer.events.register("loadstart", this, this._sourceLoadStart);
            olLayer.events.register("tileerror", this, this._sourceLoadError);
            olLayer.events.register("loadend", this, this._sourceLoadeEnd);
        }
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
     * Returns individual print / export instructions for each active layer in the source individually.
     * This allows image export / print to respect min / max scale hints on a per-layer basis and print
     * layers at varying resolutions.
     * The multitude of layers will be squashed on the PHP side while considering the actual print
     * resolution (which is not known here), to minimize the total amount of requests.
     *
     * @param sourceOrLayer
     * @param scale
     * @param extent
     * @return {Array<Model~SingleLayerPrintConfig>}
     */
    getPrintConfigEx: function(sourceOrLayer, scale, extent) {
        var source = this.getMbConfig(sourceOrLayer, true, true);
        var extent_ = extent || this.getMapExtent();
        var dataOut = [];
        var commonLayerData = {
            type: source.configuration.type,
            sourceId: source.id,
            opacity: source.configuration.options.opacity
        };
        if (typeof source.getMultiLayerPrintConfig === 'function') {
            var mlPrintConfigs = source.getMultiLayerPrintConfig(extent_, scale, this.getCurrentProj());
            mlPrintConfigs.map(function(pc) {
                dataOut.push($.extend({}, commonLayerData, pc));
            });
        } else {
            console.warn("Unprintable source", sourceOrLayer);
        }
        return dataOut;
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
    },
    parseURL: function() {
        var visibleLayersParam = new Mapbender.Util.Url(window.location.href).getParameter('visiblelayers');
        if (visibleLayersParam) {
            this.processVisibleLayersParam(visibleLayersParam);
        }
    },
    /**
     * Activate specific layers on specific sources by interpreting a (comma-separated list of)
     * "<sourceId>/<layerId>" parameter pair.
     * The indicated source and layer must already be part of the running configuration for this
     * to work.
     *
     * @param {string} paramValue
     */
    processVisibleLayersParam: function(paramValue) {
        var self = this;
        var specs = (paramValue || '').split(',');
        $.each(specs, function(idx, layerSpec) {
            var idParts = layerSpec.split('/');
            var clsOptions = {layers: {}};
            if (idParts.length >= 2) {
                var sourceId = idParts[0];
                var layerId = idParts[1];
                var source = self.getSource({origId: sourceId});
                if (source) {
                    clsOptions.layers[layerId] = {
                        options: {
                            treeOptions: {
                                selected: true
                            }
                        }
                    };
                    self.changeLayerState(source, clsOptions, false, true);
                }
            }
        });
    }
});
})(jQuery));
