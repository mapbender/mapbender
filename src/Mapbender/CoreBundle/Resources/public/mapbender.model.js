Mapbender.Model = {
    mbMap: null,
    map: null,
    sourceTree: [],
    resolution: null,
    srsDefs: null,
    mapMaxExtent: null,
    mapStartExtent: null,
    highlightLayer: null,
    baseId: 0,
    // Hash map query layers settings
    _layersHash: {},
    init: function(mbMap) {
        var self = this;

        // need to monkey patch here in order to get next zoom in movestart event
        // prevents duplicate loads of WMS where a layer is going out of scale
        var zoomTo = OpenLayers.Map.prototype.zoomTo;
        OpenLayers.Map.prototype.zoomTo = function(zoom) {
            self.nextZoom = zoom;
            zoomTo.apply(this, arguments);
        };
        
        this.mbMap = mbMap;
        this.srsDefs = this.mbMap.options.srsDefs;
        Mapbender.Projection.extendSrsDefintions(this.srsDefs || []);

        if (typeof (this.mbMap.options.dpi) !== 'undefined') {
            this.resolution = OpenLayers.DOTS_PER_INCH = this.mbMap.options.dpi;
        }

        var tileSize = this.mbMap.options.tileSize;
        OpenLayers.Map.TILE_WIDTH = tileSize;
        OpenLayers.Map.TILE_HEIGHT = tileSize;

        OpenLayers.ImgPath = Mapbender.configuration.application.urls.asset + 'components/mapquery/lib/openlayers/img/';

        var proj = this.getProj(this.mbMap.options.srs);

        this.mapMaxExtent = {
            projection: proj,
            // using null or open bounds here causes failures in map, overview and other places
            // @todo: make applications work with open / undefined max extent
            extent: OpenLayers.Bounds.fromArray(this.mbMap.options.extents.max)
        };

        this.mapStartExtent = {
            projection: proj,
            extent: OpenLayers.Bounds.fromArray(this.mbMap.options.extents.start || this.mbMap.options.extents.max)
        };
        var mapOptions = {
            maxExtent: this.mapMaxExtent.extent.toArray(),
            zoomToMaxExtent: false,
            maxResolution: this.mbMap.options.maxResolution,
            numZoomLevels: this.mbMap.options.scales ? this.mbMap.options.scales.length : this.mbMap.options.numZoomLevels,
            projection: proj,
            displayProjection: proj,
            units: proj.proj.units,
            allOverlays: true,
            theme: null,
            transitionEffect: null,
            layers: [{
                    type: "wms",
                    name: "FAKE",
                    isBaseLayer: true,
                    url: "http://localhost",
                    visibility: false
                }],
            fallThrough: true
        };

        if (this.mbMap.options.scales) {
            $.extend(mapOptions, {
                scales: this.mbMap.options.scales
            });
        }

        $(this.mbMap.element).mapQuery(mapOptions);
        this.map = $(this.mbMap.element).data('mapQuery');
        this.map.layersList.mapquery0.olLayer.isBaseLayer = true;
        this.map.olMap.setBaseLayer(this.map.layersList.mapquery0);
        this.map.olMap.tileManager = null; // fix WMS tiled setVisibility(false) for outer scale
        this.setView(true);
        this.parseURL();
        if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
            this.changeProjection({
                projection: this.getProj(this.mbMap.options.targetsrs)
            });
        }
        if (this.mbMap.options.targetscale) {
            this.map.olMap.zoomToScale(this.mbMap.options.targetscale, true);
        }
    },
    /**
     * Set map view: extent from URL parameters or configuration and POIs
     * @deprecated, call individual methods
     */
    setView: function(addLayers) {
        var startExtent = this.getInitialExtent();

        if (this.mbMap.options['center']) {
            var lonlat = new OpenLayers.LonLat(this.mbMap.options['center']);
            if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
                this.map.olMap.setCenter(lonlat.transform(this.getProj(this.mbMap.options.targetsrs), this.getCurrentProj()));
            } else {
                this.map.olMap.setCenter(lonlat);
            }
        } else {
            this.map.olMap.zoomToExtent(startExtent, true);
        }
        if (addLayers) {
            this.initializeSourceLayers();
        }
        this.initializePois((this.mbMap.options && this.mbMap.options.extra && this.mbMap.options.extra['pois']) || []);
    },
    getInitialExtent: function() {
        var startExtent = this.mapStartExtent.extent;
        if (this.mbMap.options.extra && this.mbMap.options.extra['bbox']) {
            startExtent = OpenLayers.Bounds.fromArray(this.mbMap.options.extra['bbox']);
        }
        if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
            startExtent = startExtent.transform(this.getProj(this.mbMap.options.targetsrs), this.getCurrentProj());
        }

        return startExtent || null;
    },
    initializePois: function(poiOptionsList) {
        var self = this;
        if (!poiOptionsList.length) {
            return;
        }
        var pois = poiOptionsList.map(function(poi) {
            var coord = new OpenLayers.LonLat(poi.x, poi.y);
            if(poi.srs) {
                coord = coord.transform(self.getProj(poi.srs), self.getCurrentProj());
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
    initializeSourceLayers: function() {
        var self = this;
        // @todo 3.1.0: event binding is historically tied to initializing layers ... resolve / separate?
        $(document).bind('mbsrsselectorsrsswitched', $.proxy(self._changeProjection, self));
        // this.map.olMap.events.register('zoomend', this, $.proxy(this._checkOutOfScale, this));
        // this.map.olMap.events.register('moveend', this, $.proxy(this._checkOutOfBounds, this));
        this.map.olMap.events.register('movestart', this, $.proxy(this._preCheckChanges, this));

        this.map.olMap.events.register('moveend', this, $.proxy(this._checkChanges, this));
        // Array.protoype.reverse is in-place
        // see https://developer.mozilla.org/de/docs/Web/JavaScript/Reference/Global_Objects/Array/reverse
        // Do not use .reverse on centrally shared values without making your own copy
        $.each(this.mbMap.options.layersets.slice().reverse(), function(idx, layersetId) {
            if(!Mapbender.configuration.layersets[layersetId]) {
                return;
            }
            $.each(Mapbender.configuration.layersets[layersetId].slice().reverse(), function(lsidx, defArr) {
                $.each(defArr, function(idx, sourceDef) {
                    self.addSourceFromConfig(sourceDef, false);
                });
            });
        });
    },
    getCurrentProj: function() {
        return this.map.olMap.getProjectionObject();
    },
    getProj: function(srscode) {
        if (Proj4js.defs[srscode]) {
            var proj = new OpenLayers.Projection(srscode);
            if (!proj.proj.units) {
                proj.proj.units = 'degrees';
            }
            return proj;
        }
        // Mapbender.error("CRS: " + srscode + " is not defined.");
        return null;
    },
    getAllSrs: function() {
        return this.srsDefs;
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
    _convertLayerDef: function(layerDef, mangleIds) {
        var l = $.extend({}, Mapbender.source[layerDef.type.toLowerCase()].create(layerDef, mangleIds), {
            mapbenderId: layerDef.id
        });
        if(typeof this.mbMap.options.wmsTileDelay !== 'undefined') {
            l.removeBackBufferDelay = this.mbMap.options.wmsTileDelay;
        }
        return l;
    },
    generateSourceId: function() {
        this.baseId++;
        return this.baseId.toString();
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
            var source = $.extend(true, {}, sources[i]);
            source.layers = [];
            var root = source.configuration.children[0];
            var list = Mapbender.source[source.type].getLayersList(source, root, true);
            $.each(list.layers, function(idx, layer) {
                source.layers.push(layer.options.name);
            });
            state.sources.push(source);
        }
        return state;
    },
    getSources: function() {
        return this.sourceTree;
    },
    /**
     * Returns a source from a sourceTree
     * @param {Object} idObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @returns source from a sourceTree or null
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
    resetSourceUrl: function(source, options, reload) {
        var params = OpenLayers.Util.getParameters(source.configuration.options.url);
        var url;
        if (options.add) {
            for (var name in options.add) {
                params[name] = options.add[name];
            }
            url = OpenLayers.Util.urlAppend(
                source.configuration.options.url.split('?')[0], OpenLayers.Util.getParameterString(params));
        } else if (options.remove) {
            for (var name in options.remove) {
                if (params[name]) {
                    delete(params[name]);
                }
            }
            url = OpenLayers.Util.urlAppend(
                source.configuration.options.url.split('?')[0], OpenLayers.Util.getParameterString(params));
        }
        if (url) {
            source.configuration.options.url = url;
            var mqLayer = this.map.layersList[source.mqlid];
            if (mqLayer.olLayer.getVisibility()) {
                mqLayer.olLayer.url = url;
                if (reload) {
                    mqLayer.olLayer.redraw();
                }
            }
        }
    },
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
    findLayer: function(sourceOptions, layerOptions) {
        var source = this.findSource(sourceOptions);
        if (source.length === 1) {
            return Mapbender.source[source[0].type].findLayer(source[0], layerOptions);
        } else {
            return null;
        }
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
     * Returns the source's position
     */
    getSourcePos: function(source) {
        if (source) {
            for (var i = 0; i < this.sourceTree.length; i++) {
                if (this.sourceTree[i].id.toString() === source.id.toString()) {
                    return i;
                }
            }
        } else
            return null;
    },
    /**
     * Returns the source by id
     */
    getSourceLayerById: function(source, layerId) {
        if (source && layerId) {
            return Mapbender.source[source.type].findLayer(source, {
                id: layerId
            });
        } else {
            return null;
        }
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
     * Checks the source changes and returns the source changes.
     */
    _checkAndRedrawSource: function(toChangeOpts) {
        return this._checkSource(toChangeOpts, true, true);
    },
    _checkSource: function(toChangeOpts, reset, redraw) {
        var source = this.getSource(toChangeOpts.sourceIdx);
        var gsResult = Mapbender.source[source.type.toLowerCase()].changeOptions(source, this.getScale(), toChangeOpts);
        var mqLayer = this.map.layersList[source.mqlid];
        if (!mqLayer) {
            console.error("No mqLayer found", toChangeOpts);
        }
        if (mqLayer && reset) {
            redraw = redraw && this._resetSourceVisibility(mqLayer, gsResult.layers, gsResult.infolayers, gsResult.styles);
        }
        if (mqLayer && redraw) {
            mqLayer.olLayer.removeBackBuffer();
            mqLayer.olLayer.createBackBuffer();
            mqLayer.olLayer.redraw(true);
        }
        return gsResult.changed;
    },
    _preCheckChanges: function(e) {
        this._checkChanges(e, true);
    },
    
    _checkChanges: function(e, isPreEvent) {
        var self = this;
        $.each(self.sourceTree, function(idx, source) {
            var sourceIdx = {id: source.id};
            var changeOpts = {
                sourceIdx: sourceIdx
            };
            var changed = self._checkSource(changeOpts, true, !isPreEvent);
            for (var child in changed.children) {
                if (changed.children[child].state
                    && typeof changed.children[child].state.outOfScale !== 'undefined') {
                    self.mbMap.fireModelEvent({
                        name: 'sourceChanged',
                        value: {
                            changed: {
                                children: changed.children,
                                sourceIdx: sourceIdx
                            }
                        }
                    });
                    break;
                }
            }
        });
    },

    /**
     * Check if OpenLayer layer need to be redraw
     *
     * @TODO: infoLayers should be set outside of the function
     *
     * @param mqLayer map query layer
     * @param layers layer name string array
     * @param infolayers Various layers like: WMS layer; WFS layers; WFS Feature; WMTS Layers...
     *
     * @returns {boolean}
     * @private
     */
    _resetSourceVisibility: function(mqLayer, layers, infolayers, styles) {
        mqLayer.olLayer.queryLayers = infolayers;
        if(mqLayer.hasOwnProperty("id")) {
            if(this._layersHash.hasOwnProperty(mqLayer.id) && this._layersHash[mqLayer.id] == layers.toString()) {
                return false;
            }
            this._layersHash[mqLayer.id] = layers.toString();
        }
        if (this.map.olMap.tileManager) {
            this.map.olMap.tileManager.clearTileQueue({
                object: mqLayer.olLayer
            });
        }
        mqLayer.olLayer.params.STYLES = styles;
        if(layers.length === 0) {
            mqLayer.olLayer.setVisibility(false);
            mqLayer.visible(false);
            mqLayer.olLayer.params.LAYERS = layers;
            return false;
        } else {
            mqLayer.olLayer.params.LAYERS = layers;
            mqLayer.olLayer.setVisibility(true);
            mqLayer.visible(true);
            return true;
        }
    },
    /**
     *
     */
    center: function(options) {
        this.map.center(options);
    },
    /**
     * @param {Object} e
     * @param {OpenLayers.Layer} e.object
     */
    _sourceLoadStart: function(e) {
        var source = this.getSource({origId: e.object.mapbenderId});
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
        var source = this.getSource({origId: e.object.mapbenderId});
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
            var source = this.getSource({origId: e.tile.layer.mapbenderId});
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
     *
     */
    highlightOn: function(features, options) {
        var self = this;
        if (!this.highlightLayer) {
            this.highlightLayer = this.map.layers({
                type: 'vector',
                label: 'Highlight'
            });
            var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer.olLayer, {
                hover: true,
                onSelect: function(feature) {
                    self.mbMap._trigger('highlighthoverin', null, {
                        feature: feature
                    });
                },
                onUnselect: function(feature) {
                    self.mbMap._trigger('highlighthoverout', null, {
                        feature: feature
                    });
                }
            });
            selectControl.handlers.feature.stopDown = false;
            this.map.olMap.addControl(selectControl);
            selectControl.activate();
        }
        var o = $.extend({}, {
            clearFirst: true,
            "goto": true
        },
        options);
        // Remove existing features if requested
        if (o.clearFirst) {
            this.highlightLayer.olLayer.removeAllFeatures();
        }
        // Add new highlight features
        this.highlightLayer.olLayer.addFeatures(features);
        // Goto features if requested
        if (o['goto']) {
            var bounds = this.highlightLayer.olLayer.getDataExtent();
            this.map.center({
                box: bounds.toArray()
            });
        }
        this.highlightLayer.bind('featureselected', function() {
            self.mbMap._trigger('highlightselected', arguments);
        });
        this.highlightLayer.bind('featureunselected', function() {
            self.mbMap._trigger('highlightunselected', arguments);
        });
    },
    /**
     *
     */
    highlightOff: function(features) {
        if (!features && this.highlightLayer) {
            this.highlightLayer.remove();
        } else if (features && this.highlightLayer) {
            var a = 0;
            this.highlightLayer.olLayer.removeFeatures(features);
        }
    },
    setOpacity: function(source, opacity) {
        if (typeof opacity === 'number' && !isNaN(opacity) && opacity >= 0 && opacity <= 1 && source) {
            source.configuration.options.opacity = opacity;
            this.map.layersList[source.mqlid].opacity(opacity);
        }
    },
    /**
     * Zooms to layer
     * @param {object} options of form { sourceId: XXX, layerId: XXX, inherit: BOOL }
     */
    zoomToLayer: function(options) {
        var sources = this.findSource({
            id: options.sourceId
        });
        if (sources.length === 1) {
            var extents = Mapbender.source[sources[0].type].getLayerExtents(sources[0], options.layerId);
            var proj = this.map.olMap.getProjectionObject();
            var bounds;
            if (extents && extents[proj.projCode]) {
                bounds = OpenLayers.Bounds.fromArray(extents[proj.projCode]);
            } else {
                for (var srs in extents) {
                    var extProj = this.getProj(srs);
                    if (extProj !== null) {
                        var bounds0 = OpenLayers.Bounds.fromArray(extents[srs]);
                        // reproject to current system
                        bounds = this._transformExtent(bounds0, extProj, proj);
                        break;
                    }
                }
            }
            if (bounds) {
                this.mbMap.zoomToExtent(bounds, true);
            }
        }
    },
    getLayerExtents: function(options) {
        var sources = this.findSource({
            id: options.sourceId
        });
        if (sources.length === 1) {
            var extent = Mapbender.source[sources[0].type].getLayerExtents(sources[0], options.layerId);
            return extent ? extent : null;
        }
        return null;
    },
    /**
     * Old-style API to add a source. Source is a POD object that needs to be nested into an outer structure like:
     *  {add: {sourceDef: <x>}}
     *
     * @param {object} addOptions
     * @returns {object} source defnition (unraveled but same ref)
     * @deprecated, call addSourceFromConfig directly
     */
    addSource: function(addOptions) {
        if (addOptions.add && addOptions.add.sourceDef) {
            // because legacy behavior was to always mangle / destroy / rewrite all ids, we do the same here
            return this.addSourceFromConfig(addOptions.add.sourceDef, true);
        } else {
            console.error("Unuspported options, ignoring", addOptions);
        }
    },
    /**
     * @param {object} sourceDef
     * @param {boolean} [mangleIds] to rewrite sourceDef.id and all layer ids EVEN IF ALREADY POPULATED
     * @returns {object} sourceDef same ref, potentially modified
     */
    addSourceFromConfig: function(sourceDef, mangleIds) {
        if (!sourceDef.origId) {
            sourceDef.origId = '' + sourceDef.id;
        }
        if (mangleIds) {
            sourceDef.id = this.generateSourceId();
            if (typeof sourceDef.origId === 'undefined') {
                sourceDef.origId = sourceDef.id;
            }
        }

        if (!this.getSourcePos(sourceDef)) {
            this.sourceTree.push(sourceDef);
        }
        var mapQueryLayer = this.map.layers(this._convertLayerDef(sourceDef, mangleIds));
        sourceDef.mqlid = mapQueryLayer.id;
        sourceDef.ollid = mapQueryLayer.olLayer.id;
        mapQueryLayer.source = sourceDef;
        Mapbender.source[sourceDef.type.toLowerCase()].postCreate(sourceDef, mapQueryLayer);
        mapQueryLayer.olLayer.mbConfig = sourceDef;
        mapQueryLayer.olLayer.events.register("loadstart", this, this._sourceLoadStart);
        mapQueryLayer.olLayer.events.register("tileerror", this, this._sourceLoadError);
        mapQueryLayer.olLayer.events.register("loadend", this, this._sourceLoadeEnd);

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
        var checkOptions = {
            sourceIdx: {
                id: sourceDef.id
            }
        };
        this._checkSource(checkOptions, true, true);
        return sourceDef;
    },
    /**
     *
     */
    removeSource: function(options) {
        if (options.remove.sourceIdx) {
            var sourceToRemove = this.getSource(options.remove.sourceIdx);
            if (sourceToRemove) {
                this.mbMap.fireModelEvent({
                    name: 'beforeSourceRemoved',
                    value: {
                        source: sourceToRemove
                    }
                });
                var mqLayer = this.map.layersList[sourceToRemove.mqlid];
                if (mqLayer) {
                    if (mqLayer.olLayer instanceof OpenLayers.Layer.Grid) {
                        mqLayer.olLayer.clearGrid();
                    }
                    if (this.map.olMap.tileManager) {
                        this.map.olMap.tileManager.clearTileQueue({
                            object: mqLayer.olLayer
                        });
                    }
                    var removedMq = mqLayer.remove();
                    if (removedMq) {
                        for (var i = 0; i < this.sourceTree.length; i++) {
                            if (this.sourceTree[i].id.toString() === sourceToRemove.id.toString()) {
                                this.sourceTree.splice(i, 1);
                                break;
                            }
                        }
                        if (this.map.layersList[sourceToRemove.mqlid]) {
                            delete(this.map.layersList[sourceToRemove.mqlid]);
                        }
                        this.mbMap.fireModelEvent({
                            name: 'sourceRemoved',
                            value: {
                                source: sourceToRemove
                            }
                        });
                    }
                }
            }
        } else {
            window.console && console.error("CHECK options at model.addSource");
        }
    },
    /**
     *
     */
    removeSources: function(keepSources) {
        var toRemoveArr = [];
        for (var i = 0; i < this.sourceTree.length; i++) {
            var source = this.sourceTree[i];
            if (!keepSources[source.id]) {
                toRemoveArr.push({
                    remove: {
                        sourceIdx: {
                            id: source.id
                        }
                    }
                });
            }
        }
        for (var i = 0; i < toRemoveArr.length; i++) {
            this.removeSource(toRemoveArr[i]);
        }
    },
    _changeSourceSelected: function(sourceId, layerMap) {
        var options = {
            sourceIdx: {id: '' + sourceId},
            options: {
                children: layerMap
            }
        };
        var eventData = {
            changed: this._checkSource(options, true, true)
        };
        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: eventData
        });
    },
    _changeSourceInfo: function(sourceId, layerMap) {
        var source = this.getSource({id: sourceId});
        var options = {
            options: {
                children: layerMap
            }
        };
        var gs = this.getGeoSourceHandler(source, true);
        var result = gs.checkInfoLayers(source, this.getScale(), options);
        this.map.layersList[source.mqlid].olLayer.queryLayers = result.infolayers;
        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: result
        });
    },
    /**
     *
     */
    changeSource: function(options) {
        if (options.change) {
            var changeOpts = options.change;
            if (typeof changeOpts.options !== 'undefined') {
                var sourceToChange = this.getSource(changeOpts.sourceIdx);
                this.mbMap.fireModelEvent({
                    name: 'beforeSourceChanged',
                    value: {
                        source: sourceToChange,
                        changeOptions: changeOpts
                    }
                });
                if (changeOpts.options.type === 'selected') {
                    console.warn("Use controlLayer instead of changeSource with type selected");
                    this._changeSourceSelected(sourceToChange.id, changeOpts.options.children);
                }
                if (changeOpts.options.type === 'info') {
                    console.warn("Use controlLayer instead of changeSource with type info");
                    this._changeSourceInfo(sourceToChange.id, changeOpts.options.children);
                }
            }
            if (changeOpts.move) {
                console.error("mapbender.model:changeSource with 'move' is gone", changeOpts);
            }
            if (changeOpts.layerRemove) {
                console.warn("Use removeLayer instead of changeSource");
                this.removeLayer(changeOpts.layerRemove.sourceIdx.id, changeOpts.layerRemove.layer.options.id);
            }
        } else {
            window.console && console.error("CHECK options at model.changeSource");
        }
    },
    removeLayer: function(sourceId, layerId) {
        var source = this.getSource({id: sourceId});
        var gs = this.getGeoSourceHandler(source, true);
        gs.findLayer(source, {id: layerId});
        var eventData = {
            changed: {
                childRemoved: gs.removeLayer(source, {options: {id: layerId}}),
                sourceIdx: {id: sourceId}
            }
        };
        var checkOptions = {
            sourceIdx: eventData.changed.sourceIdx
        };
        this._checkSource(checkOptions, true, true);
        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: eventData
        });
    },
    setSourceVisibility: function(sourceId, state) {
        var checkOptions = {
            sourceIdx: {id: sourceId},
            options: {
                configuration: {
                    options: {visibility: state}
                },
                type: 'selected'
            }
        };
        var result = this._checkSource(checkOptions, true, true);
        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: {
                changed: {
                    children: result.children,
                    sourceIdx: {id: sourceId}
                }
            }
        });
    },
    /**
     *
     * @param {Object} sourceIdObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @param {Object} options in form of:
     * {layers:{'LAYERNAME': {options:{treeOptions:{selected: bool,info: bool}}}}}
     */
    changeLayerState: function(sourceIdObject, options, defaultSelected, mergeSelected) {
        if (typeof mergeSelected === 'undefined')
            mergeSelected = false;
        if (typeof defaultSelected === 'undefined')
            defaultSelected = false;
        var source = this.getSource(sourceIdObject);
        if (source !== null) {
            var toChangeOptions = Mapbender.source[source.type].createOptionsLayerState(source, options,
                defaultSelected, mergeSelected);
            this.changeSource(toChangeOptions);
        }

    },
    /**
     * Updates the source identified by given id with a new layer order.
     * This will pull styles and "state" (such as visibility) from values
     * currently stored in the "geosource".
     *
     * @param {string} sourceId
     * @param {string[]} newLayerIdOrder
     */
    setSourceLayerOrder: function(sourceId, newLayerIdOrder) {
        var sourceIdx = {id: sourceId};
        var sourceObj = this.getSource(sourceIdx);
        var geoSource = Mapbender.source[sourceObj.type];

        geoSource.setLayerOrder(sourceObj, newLayerIdOrder);

        this.mbMap.fireModelEvent({
            name: 'sourceMoved',
            // no receiver uses the bizarre "changeOptions" return value
            // on this event
            value: null
        });
        var checkOptions = {
            sourceIdx: sourceIdx,
            options: {children: {}}
        };
        this._checkSource(checkOptions, true, true);
    },
    /**
     * Bring the sources identified by the given ids into the given order.
     * All other sources will be left alone!
     *
     * @param {string[]} newIdOrder
     */
    reorderSources: function(newIdOrder) {
        var self = this;
        var olMap = self.map.olMap;
        var sourceObjs = $.map(newIdOrder, function(sourceId) {
            return self.findSource({id: sourceId});
        });
        // Collect current positions used by the layers to be reordered
        // position := array index in olMap.layers
        // The collected positions will be reused / redistributed to the affected
        // layers, while all other layers stay in their current slots.
        var layersToMove = [];
        var oldIndexes = [];
        var olLayerIdsToMove = {};
        _.forEach(sourceObjs, function(sourceObj) {
            var olLayer = self.map.layersList[sourceObj.mqlid].olLayer;
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
            var indexA = olMap.getLayerIndex(self.map.layersList[a.mqlid].olLayer);
            var indexB = olMap.getLayerIndex(self.map.layersList[b.mqlid].olLayer);
            return indexA - indexB;
        });
        this.mbMap.fireModelEvent({
            name: 'sourcesreordered'
        });
    },
    /*
     * Changes the map's projection.
     */
    _changeProjection: function(event, srs) {
        this.changeProjection(srs);
    },
    /*
     * Changes the map's projection.
     */
    changeProjection: function(srs) {
        var self = this;
        var oldProj = this.map.olMap.getProjectionObject();
        if (oldProj.projCode === srs.projection.projCode) {
            return;
        }
        for(var i = 0; i < this.sourceTree.length; i++) {
            Mapbender.source[this.sourceTree[i].type].changeProjection(this.sourceTree[i], srs.projection);
        }
        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
        this.map.olMap.projection = srs.projection;
        this.map.olMap.displayProjection = srs.projection;
        this.map.olMap.units = srs.projection.proj.units;
        this.map.olMap.maxExtent = this._transformExtent(this.mapMaxExtent.extent, oldProj, srs.projection);
        $.each(self.map.olMap.layers, function(idx, layer) {
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if (layer.maxExtent) {
                layer.maxExtent = self._transformExtent(layer.maxExtent, oldProj, srs.projection);
            }
            layer.initResolutions();
        });
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
        this.mbMap.fireModelEvent({
            name: 'srschanged',
            value: {
                projection: srs.projection
            }
        });
    },
    /**
     * Activate / deactivate a single layer's selection and / or FeatureInfo state states.
     *
     * @param {string|number} sourceId
     * @param {string|number} layerId
     * @param {bool|null} [selected]
     * @param {bool|null} [info]
     */
    controlLayer: function controlLayer(sourceId, layerId, selected, info) {
        var layerMap = {};
        // @todo: support flipping both values in a single interaction
        if (selected !== null && typeof selected !== 'undefined') {
            layerMap['' + layerId] = {
                options: {
                    treeOptions: {
                        selected: !!selected
                    }
                }
            };
            this._changeSourceSelected(sourceId, layerMap);
        }
        if (info !== null && typeof info !== 'undefined') {
            layerMap['' + layerId] = {
                options: {
                    treeOptions: {
                        info: !!info
                    }
                }
            };
            this._changeSourceInfo(sourceId, layerMap);
        }
    },
    /**
     * @param {OpenLayers.Layer.HTTPRequest|Object} source can be a sourceDef
     */
    getMbConfig: function(source) {
        if (source.mbConfig) {
            // monkey-patched OpenLayers.Layer
            return source.mbConfig;
        }
        if (source.source) {
            // MapQuery layer
            return source.source;
        }
        if (source.configuration) {
            // sourceTreeish
            return source.configuration;
        }
        console.error("Cannot find configuration in given source", source);
        throw new Error("Cannot find configuration in given source");
    },
    /**
     * Get the "geosource" object for given source from Mapbender.source
     * @param {OpenLayers.Layer|MapQuery.Layer|Object} source
     * @param {boolean} [strict] to throw on missing geosource object (default true)
     * @returns {*|null}
     */
    getGeoSourceHandler: function(source, strict) {
        var type = this.getMbConfig(source).type;
        var gs = Mapbender.source[type];
        if (!gs && (strict || typeof strict === 'undefined')) {
            throw new Error("No geosource for type " + type);
        }
        return gs || null;
    },
    _getActiveLayerInfo: function(olLayer, scale) {
        var mbConfig = this.getMbConfig(olLayer);
        var activeLayers = [];
        var resFromScale = function(scale) {
            return scale && (OpenLayers.Util.getResolutionFromScale(scale, olLayer.units)) || null;
        };
        var resolution = resFromScale(scale);

        Mapbender.Util.SourceTree.iterateSourceLeaves(mbConfig, false, function(node, offset, parents) {
            var skip = !node.state.visibility;
            parents.map(function(p) {
                skip = skip || !p.state.visibility;
            });
            var leafData = {
                name: node.options.name,
                maxResolution: resFromScale(node.options.maxScale),
                minResolution: resFromScale(node.options.minScale),
                config: node
            };
            if (leafData.maxResolution && resolution > leafData.maxResolution) {
                skip = true;
            }
            if (leafData.minResolution && resolution < leafData.minResolution) {
                skip = true;
            }

            if (!skip) {
                activeLayers.push(leafData);
            }
        });
        return activeLayers;
    },

    /**
     * @param {OpenLayers.Bounds|null} extent
     * @param {OpenLayers.Projection} fromProj
     * @param {OpenLayers.Projection} toProj
     * @returns {OpenLayers.Bounds|null}
     */
    _transformExtent: function(extent, fromProj, toProj) {
        var extentOut = (extent && extent.clone()) || null;
        if (extent && fromProj.projCode !== toProj. projCode) {
            extentOut.transform(fromProj, toProj);
        }
        return extentOut;
    },
    parseURL: function() {
        var self = this;
        var ids = new Mapbender.Util.Url(window.location.href).getParameter('visiblelayers');
        ids = ids ? decodeURIComponent(ids).split(',') : [];
        if (ids.length) {
            $.each(ids, function(idx, id) {
                var id = id.split('/');
                if (1 < id.length) {
                    var layer = self.findLayer({
                        origId: id[0]
                    },
                    {
                        origId: id[1]
                    });
                    if (layer) {
                        var options = {};
                        options.layers = {};
                        options.layers[layer.layer.options.id] = {
                            options: {
                                treeOptions: {
                                    selected: true
                                }
                            }
                        };
                        self.changeLayerState({
                            origId: id[0]
                        },
                        options,
                            false,
                            true);
                    }
                }
            });
        }
    }
};
