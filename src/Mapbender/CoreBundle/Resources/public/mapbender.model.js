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
    /**
     * @typedef {Object} Model~BufferOptions
     * @property {Number} [buffer] in meters
     * @property {Number} [ratio] extension factor applied AFTER absolute buffer
     * @property {Number} [minScale]
     * @property {Number} [maxScale]
     * @property {Boolean} [ignorePadding]
     */

    map: null,
    _initMap: function _initMap() {
        var baseLayer = new OpenLayers.Layer('fake', {
            visibility: false,
            isBaseLayer: true,
            maxExtent: this._transformExtent(this.mapMaxExtent, this._configProj, this.initialViewParams.srsName).toArray(),
            projection: this.initialViewParams.srsName
        });
        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent, this._configProj, this.initialViewParams.srsName).toArray(),
            maxResolution: 'auto',
            numZoomLevels: this.mbMap.options.scales ? this.mbMap.options.scales.length : this.mbMap.options.numZoomLevels,
            projection: this.initialViewParams.srsName,
            displayProjection: this.initialViewParams.srsName,
            units: this.getProj(this.initialViewParams.srsName).proj.units || 'degrees',
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

        this._setInitialView(this.olMap, this.initialViewParams, this.mbMap.options);
        this.processUrlParams();
        this.initializeSourceLayers(this.sourceTree);
        this._initEvents(this.olMap, this.mbMap);
        this._startShare();
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
            mbMap.element.trigger('mbmapviewchanged', {
                mbMap: mbMap,
                params: self.getCurrentViewParams()
            });
        });
    },
    /**
     * @param {MouseEvent} event
     * @return {mmClickData}
     */
    locateClickEvent: function(event) {
        var mapRect = this.olMap.div.getBoundingClientRect();
        // on mobile devices, coordinates are returned as properties of the `xy`-Object only
        var x = (event.x || event.xy.x || 0) - mapRect.x - (window.scrollX || window.pageXOffset || 0);
        var y = (event.y || event.xy.y || 0) - mapRect.y - (window.scrollY || window.pageYOffset || 0);

        var clickLonLat = this.olMap.getLonLatFromViewPortPx({x: x, y: y});
        return {
            pixel: [x, y],
            coordinate: [clickLonLat.lon, clickLonLat.lat]
        };
    },
    _onMapClick: function(event) {
        var location = this.locateClickEvent(event);
        $(this.mbMap.element).trigger('mbmapclick', Object.assign(location, {
            mbMap: this.mbMap
        }));
    },
    /**
     * @param {OpenLayers.Map} olMap
     * @param {mmViewParams} viewParams
     * @param {Object} mapOptions
     * @private
     */
    _setInitialView: function(olMap, viewParams, mapOptions) {
        var zoom = this.pickZoomForScale(viewParams.scale, true);
        olMap.setCenter(viewParams.center, zoom);
    },
    /**
     * @return {String}
     */
    getCurrentProjectionCode: function() {
        if (this.olMap) {
            return this.olMap.getProjection();
        } else {
            return this.initialViewParams.srsName;
        }
    },
    getCurrentProjectionUnits: function() {
        var proj = this.getCurrentProj();
        return proj.proj.units || 'degrees';
    },
    getCurrentProj: function() {
        if (this.map && this.map.olMap) {
            return this.map.olMap.getProjectionObject();
        } else {
            return this.getProj(this.viewParams.srsName);
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
    /**
     * Get gedesic units per meter at given point. UPMs are returned separately
     * for vertical and horizontal axes.
     *
     * @param {Array<Number>} point
     * @param {String} [srsName]
     * @returns {{v: number, h: number}}
     */
    getUnitsPerMeterAt: function(point, srsName) {
        var xform84 = function(point) {
            var xy = Mapbender.mapEngine.transformCoordinate({x: point[0], y: point[1]}, srsName, 'EPSG:4326');
            return {lon: xy.x, lat: xy.y};
        };
        var left84 = xform84([point[0] - 0.5, point[1]]);
        var right84 = xform84([point[0] + 0.5, point[1]]);
        var bottom84 = xform84([point[0], point[1] - 0.5]);
        var top84 = xform84([point[0], point[1] + 0.5]);

        var distanceH = OpenLayers.Util.distVincenty(left84, right84) * 1000;
        var distanceV = OpenLayers.Util.distVincenty(bottom84, top84) * 1000;
        return {
            h: 1.0 / distanceH,
            v: 1.0 / distanceV
        };
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
        this.mbMap.element.trigger('mbmapviewchanged', {
            mbMap: this.mbMap,
            params: this.getCurrentViewParams()
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
     * @param {Model~BufferOptions} [options]
     * @param {boolean=} options.center to forcibly recenter map (default: true); otherwise
     *      just keeps feature in view
     */
    zoomToFeature: function(feature, options) {
        var center_ = !options || (options.center || typeof options.center === 'undefined');
        var bounds = this._getBufferedFeatureBounds(feature, options);

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
        return this.mbMap.options.scales;
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
        var oldProj = this.getProj(srsNameFrom);
        var newProj = this.getProj(srsNameTo);
        var newMaxExtent = this._transformExtent(this.mapMaxExtent, this._configProj, newProj);
        var i, j, olLayers, source;
        for (i = 0; i < this.sourceTree.length; ++i) {
            source = this.sourceTree[i];
            if (source.checkRecreateOnSrsSwitch(srsNameFrom, srsNameTo)) {
                source.destroyLayers(this.olMap);
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
    dumpGeoJsonFeatures: function(features, layer, resolution, includeStyle) {
        var self = this;
        var extract = this._geoJsonReader.extract.feature.bind(this._geoJsonReader);
        return features.map(function(feature) {
            var gj = extract(feature);
            if (includeStyle) {
                gj.style = self.extractSvgFeatureStyle(layer, feature);
            }
            return gj;
        });
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
        var styleRules;
        if (feature.style) {
            // stringify => decode: makes a deep copy of the style at the moment of capture
            styleRules = JSON.parse(JSON.stringify(feature.style));
        } else {
            styleRules = olLayer.styleMap.createSymbolizer(feature, feature.renderIntent);
        }
        Mapbender.StyleUtil.fixSvgStyleAssetUrls(styleRules);
        return styleRules;
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
     * @param {Number} x
     * @param {Number} y
     * @param {Element} content
     * @private
     * @return {Promise}
     */
    openPopupInternal_: function(x, y, content) {
        var olMap = this.olMap;
        // @todo: use native Promise (needs polyfill)
        var def = $.Deferred();
        var popup = new OpenLayers.Popup.FramedCloud(null,
            new OpenLayers.LonLat(x, y),
            null,
            content.innerHTML,
            null,
            true,
            function() {
                olMap.removePopup(this);
                this.destroy();
                def.resolve();
            }
        );
        // Fix close icon (with or without Openlayers 2 css)
        popup.closeDiv.className = '';
        $(popup.closeDiv)
            .css({
                width: '25px',
                height: 'auto',
                // right: '0',
                // top: '0',
                'padding-left': '8px',
                'font-size': '17px'
            })
            .attr('title', Mapbender.trans('mb.actions.close'))
            .append('<i class="fa fas fa-times"></i>')
        ;
        olMap.addPopup(popup);
        return def.promise();
    },
    _getConfiguredViewParams: function(mapOptions) {
        // No fractional scale support on Openlayers 2
        // => Limit scale to one of the exact configured scales
        var params = Mapbender.MapModelBase.prototype._getConfiguredViewParams.apply(this, arguments);
        var zoom = this.pickZoomForScale(params.scale);
        params.scale = (this._getScales())[zoom];
        return params;
    },
    /**
     * @param {Object} mapOptions
     * @return {mmViewParams}
     * @private
     */
    _getInitialViewParams: function(mapOptions) {
        // No fractional scale support on Openlayers 2
        // => Limit scale to one of the exact configured scales
        var params = Mapbender.MapModelBase.prototype._getInitialViewParams.apply(this, arguments);
        var zoom = this.pickZoomForScale(params.scale);
        params.scale = (this._getScales())[zoom];
        return params;
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
