Mapbender.Model = {
    mbMap: null,
    map: null,
    sourceTree: [],
    extent: null,
    resolution: null,
    units: null,
    proj: null,
    srsDefs: null,
    mapMaxExtent: null,
    mapStartExtent: null,
    layersMaxExtent: {},
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

        this.proj = this.getProj(this.mbMap.options.srs);
        this.units = this.mbMap.options.units; //TODO check if this.units === this.proj.proj.units

        this.mapMaxExtent = {
            projection: this.getProj(this.mbMap.options.srs),
            extent: this.mbMap.options.extents.max ?
                OpenLayers.Bounds.fromArray(this.mbMap.options.extents.max) : null
        };

        this.mapStartExtent = {
            projection: this.getProj(this.mbMap.options.srs),
            extent: this.mbMap.options.extents.start ?
                OpenLayers.Bounds.fromArray(this.mbMap.options.extents.start) : this.mbMap.options.extents.max
        };
        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent, this.proj).toArray(),
            zoomToMaxExtent: false,
            maxResolution: this.mbMap.options.maxResolution,
            numZoomLevels: this.mbMap.options.scales ? this.mbMap.options.scales.length : this.mbMap.options.numZoomLevels,
            projection: this.proj,
            displayProjection: this.proj,
            units: this.proj.proj.units,
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
        this._addLayerMaxExtent(this.map.layersList.mapquery0);
        this.map.olMap.tileManager = null; // fix WMS tiled setVisibility(false) for outer scale
        this.setView(true);
        this.parseURL();
        if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
            this.changeProjection({
                projection: this.getProj(
                    this.mbMap.options.targetsrs)
            });
        }
        if (this.mbMap.options.targetscale) {
            this.map.olMap.zoomToScale(this.mbMap.options.targetscale, true);
        }
    },
    /**
     * Set map view: extent from URL parameters or configuration and POIs
     */
    setView: function(addLayers) {
        var self = this;
        var start_extent = this.mapStartExtent;

        var pois = [],
            bbox = null;
        if (this.mbMap.options.extra && this.mbMap.options.extra['bbox']) {
            bbox = this.mbMap.options.extra['bbox'] ?
                OpenLayers.Bounds.fromArray(this.mbMap.options.extra['bbox']) :
                start_extent;
        }
        if (this.mbMap.options.extra && this.mbMap.options.extra['pois']) {
            $.each(this.mbMap.options.extra['pois'], function(idx, poi) {
                var coord = new OpenLayers.LonLat(poi.x, poi.y);
                if(poi.srs) {
                    coord = coord.transform(self.getProj(poi.srs), self.getCurrentProj());
                }
                pois.push({
                    position: coord,
                    label: poi.label,
                    scale: poi.scale
                });
            });
        }

        var poiBox = null,
            poiMarkerLayer = null,
            poiIcon = null,
            poiPopups = [];
        if (pois.length) {
            poiMarkerLayer = new OpenLayers.Layer.Markers();
            poiIcon = new OpenLayers.Icon(
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
        }
        $.each(pois, function(idx, poi) {
            if (!bbox) {
                if (!poiBox)
                    poiBox = new OpenLayers.Bounds();
                poiBox.extend(poi.position);
            }

            // Marker
            poiMarkerLayer.addMarker(new OpenLayers.Marker(
                poi.position,
                poiIcon.clone()));

            if (poi.label) {
                poiPopups.push(new OpenLayers.Popup.FramedCloud('chicken',
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
        var centered = false;
        if (poiBox) {
            if (pois.length == 1 && pois[0].scale) {
                this.map.olMap.setCenter(pois[0].position);
                this.map.olMap.zoomToScale(pois[0].scale, true);
            } else {
                this.map.olMap.zoomToExtent(poiBox.scale(1.5));
            }
            centered = true;
        }

        if (bbox) {
            if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
                bbox = bbox.transform(this.getProj(this.mbMap.options.targetsrs), this.getCurrentProj());
            }
            this.map.olMap.zoomToExtent(bbox, true);
        } else {
            if (!centered) {
                this.map.olMap.zoomToExtent(start_extent.extent ? start_extent.extent : start_extent, true);
            }
        }

        if (!centered && this.mbMap.options['center']) {
            var lonlat = new OpenLayers.LonLat(this.mbMap.options['center']);
            if (this.mbMap.options.targetsrs && this.getProj(this.mbMap.options.targetsrs)) {
                this.map.olMap.setCenter(lonlat.transform(this.getProj(this.mbMap.options.targetsrs), this.getCurrentProj()));
            } else {
                this.map.olMap.setCenter(lonlat);
            }
        }

        if (true === addLayers) {
            $(document).bind('mbsrsselectorsrsswitched', $.proxy(self._changeProjection, self));
            // this.map.olMap.events.register('zoomend', this, $.proxy(this._checkOutOfScale, this));
            // this.map.olMap.events.register('moveend', this, $.proxy(this._checkOutOfBounds, this));
            this.map.olMap.events.register('movestart', this, $.proxy(this._preCheckChanges, this));

            this.map.olMap.events.register('moveend', this, $.proxy(this._checkChanges, this));
            $.each(this.mbMap.options.layersets.reverse(), function(idx, layersetId) {
                if(!Mapbender.configuration.layersets[layersetId]) {
                    return;
                }
                $.each(Mapbender.configuration.layersets[layersetId].reverse(), function(lsidx, defArr) {
                    $.each(defArr, function(idx, sourceDef) {
                        self.addSourceFromConfig(sourceDef, false, false);
                    });
                });
            });
        }

        if (poiMarkerLayer) {
            this.map.olMap.addLayer(poiMarkerLayer);
        }

        // Popups have to be set after map extent initialization
        $.each(poiPopups, function(idx, popup) {
            self.map.olMap.addPopup(popup);
        });
    },
    getCurrentProj: function() {
        return this.map.olMap.getProjectionObject();
    },
    getProj: function(srscode) {
        var proj = null;
        for(var name in Proj4js.defs){
            if(srscode === name){
                proj = new OpenLayers.Projection(name);
                if (!proj.proj.units) {
                    proj.proj.units = 'degrees';
                }
                return proj;
            }
        }
        // Mapbender.error("CRS: " + srscode + " is not defined.");
        return proj;
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
    findLayerset: function(options) {
        for (var layersetId in Mapbender.configuration.layersets) {
            var layerset = Mapbender.configuration.layersets[layersetId];
            for (var i = 0; i < layerset.length; i++) {
                if (options.source && layerset[i][options.source.origId]) {
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
    getMqLayer: function(source) {
        return this.map.layersList[source.mqlid];
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
     *Creates a "tochange" object
     */
    createToChange: function(idxKey, idxValue) {
        var tochange = {
            sourceIdx: {}
        };
        tochange.sourceIdx[idxKey] = idxValue;
        if (this.getSource(tochange.sourceIdx))
            return tochange;
        else
            return null;
    },
    /**
     *Creates a "changed" object
     */
    createChangedObj: function(source) {
        if (!source || !source.id) {
            return null;
        }
        return {
            source: source,
            children: {}
        };
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
                sourceIdx: sourceIdx,
                options: {
                    children: {}
                }
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
     *
     */
    _sourceLoadStart: function(e) {
        var src = this.getSource({
            ollid: e.element.id
        });
        var mqLayer = this.map.layersList[src.mqlid];
//        Mapbender.source[src.type].onLoadStart(src);
        if (mqLayer.olLayer.getVisibility()) {
            this.mbMap.fireModelEvent({
                name: 'sourceloadstart',
                value: {
                    source: this.getSource(
                        {
                            ollid: e.element.id
                        })
                }
            });
        }
    },
    /**
     *
     */
    _sourceLoadeEnd: function(e) {
        this.mbMap.fireModelEvent({
            name: 'sourceloadend',
            value: {
                source: this.getSource({
                    ollid: e.element.id
                })
            }
        });
    },
    /**
     *
     */
    _sourceLoadError: function(e, imgEl) {
        var source = this.getSource({
            ollid: e.element.id
        });
        var mqLayer = this.map.layersList[source.mqlid];
        if (mqLayer.olLayer.getVisibility()) {
            Mapbender.source[source.type].onLoadError(imgEl, source.id, this.map.olMap.getProjectionObject(), $.proxy(
                this.sourceLoadErrorCallback, this));
        } else {
            this._sourceLoadeEnd(e);
        }
    },
    sourceLoadErrorCallback: function(loadError) {
        var source = this.getSource({
            'id': loadError.sourceId
        });
        this.mbMap.fireModelEvent({
            name: 'sourceloaderror',
            value: {
                source: source,
                    error: loadError
            }
        });
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
            if (extents && extents[proj.projCode]) {
                this.mbMap.zoomToExtent(OpenLayers.Bounds.fromArray(extents[proj.projCode]), true);
            } else {
                var ext = null;
                var extProj = null;
                for (var srs in extents) {
                    extProj = this.getProj(srs);
                    if (extProj !== null) {
                        ext = OpenLayers.Bounds.fromArray(extents[srs]);
                        var extObj = {
                            projection: extProj,
                            extent: OpenLayers.Bounds.fromArray(extents[srs])
                        };
                        var ext_new = this._transformExtent(extObj, proj);
                        this.mbMap.zoomToExtent(ext_new, true);
                        break;
                    }
                }
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
     * @deprecated, call addSourceFromConfig directly
     */
    addSource: function(addOptions) {
        if (addOptions.add && addOptions.add.sourceDef) {
            // because legacy behavior was to always mangle / destroy / rewrite all ids, we do the same here
            return this.addSourceFromConfig(addOptions.add.sourceDef, true, true);
        } else {
            console.error("Unuspported options, ignoring", addOptions);
        }
    },
    /**
     * @param {object} sourceDef
     * @param {boolean} [mangleSourceId] to rewrite sourceDef.id EVEN IF ITS ALREADY POPULATED
     * @param {boolean} [mangleLayerIds] to rewrite (recursively) all layer ids EVEN IF ALREADY POPULATED
     */
    addSourceFromConfig: function(sourceDef, mangleSourceId, mangleLayerIds) {
        var self = this;
        if (!sourceDef.origId) {
            sourceDef.origId = '' + sourceDef.id;
        }
        if (mangleSourceId) {
            sourceDef.id = this.generateSourceId();
            if (typeof sourceDef.origId === 'undefined') {
                sourceDef.origId = sourceDef.id;
            }
        }

        this.mbMap.fireModelEvent({
            name: 'beforeSourceAdded',
            value: {
                source: sourceDef,
                before: null,
                after: null
            }
        });
        if (!this.getSourcePos(sourceDef)) {
            this.sourceTree.push(sourceDef);
        }
        var source = sourceDef;
        var mapQueryLayer = this.map.layers(this._convertLayerDef(source, mangleLayerIds));
        if (mapQueryLayer) {
            source.mqlid = mapQueryLayer.id;
            source.ollid = mapQueryLayer.olLayer.id;
            mapQueryLayer.source = source;
            this._addLayerMaxExtent(mapQueryLayer);
            Mapbender.source[source.type.toLowerCase()].postCreate(source, mapQueryLayer);
            mapQueryLayer.olLayer.events.register("loadstart", mapQueryLayer.olLayer, function(e) {
                self._sourceLoadStart(e);
            });
            mapQueryLayer.olLayer.events.register("loadend", mapQueryLayer.olLayer, function(e) {
                var imgEl = $('div[id="' + e.element.id + '"]  .olImageLoadError');
                if (imgEl.length > 0) {
                    self._sourceLoadError(e, imgEl);
                } else {
                    self._sourceLoadeEnd(e);
                }
            });
            this.mbMap.fireModelEvent({
                name: 'sourceAdded',
                value: {
                    added: {
                        source: source,
                        // legacy: no known consumer evaluates these props,
                        // but even if, they've historically been wrong anyway
                        // was: "before": always last source previously in list, even though
                        // the new source was actually added *after* that
                        before: null,
                        after: null
                    }
                }
            });
            this._checkAndRedrawSource({
                sourceIdx: {
                    id: source.id
                },
                options: {
                    children: {}
                }
            });
        } else {
            this.sourceTree.splice(this.getSourcePos(sourceDef), 1);
        }
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
                        this._removeLayerMaxExtent(mqLayer);
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
                    var result = this._checkAndRedrawSource(changeOpts);
                    this.mbMap.fireModelEvent({
                        name: 'sourceChanged',
                        value: {
                            changed: {
                                children: result.children,
                                sourceIdx: result.sourceIdx
                            }
                        }
                    });
                }
                if (changeOpts.options.type === 'info') {
                    var result = {
                        infolayers: [
                        ],
                        changed: {
                            sourceIdx: {
                                id: sourceToChange.id
                            },
                            children: {}
                        }
                    };
                    result = Mapbender.source[sourceToChange.type].checkInfoLayers(sourceToChange,
                        this.getScale(), changeOpts, result);
                    this.map.layersList[sourceToChange.mqlid].olLayer.queryLayers = result.infolayers;
                    this.mbMap.fireModelEvent({
                        name: 'sourceChanged',
                        value: result
                    });//{options: result}});
                }
                if (changeOpts.options.type === 'toggle') {

                }
            }
            if (changeOpts.move) {
                console.error("mapbender.model:changeSource with 'move' is gone", changeOpts);
            }
            if (changeOpts.layerRemove) {
                var sourceToChange = this.getSource(changeOpts.layerRemove.sourceIdx);
                var layerToRemove = Mapbender.source[sourceToChange.type].findLayer(sourceToChange,
                    changeOpts.layerRemove.layer.options);
                var removedLayer = Mapbender.source[sourceToChange.type].removeLayer(sourceToChange,
                    layerToRemove.layer);
                var changed = {
                    changed: {
                        childRemoved: removedLayer,
                        sourceIdx: changeOpts.layerRemove.sourceIdx
                    }
                };
                this._checkAndRedrawSource({
                    sourceIdx: changeOpts.layerRemove.sourceIdx,
                    options: {
                        children: {}
                    }
                });
                this.mbMap.fireModelEvent({
                    name: 'sourceChanged',
                    value: changed
                });
            }
        } else {
            window.console && console.error("CHECK options at model.changeSource");
        }
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
        this._checkAndRedrawSource({
            sourceIdx: sourceIdx,
            options: {children: {}}
        });
    },
    /**
     * Bring the sources identified by the given ids into the given order.
     * All other sources will be left alone!
     *
     * @param {string[]} newIdOrder
     */
    reorderSources: function(newIdOrder) {
        var self = this;
        var sourceObjs = $.map(newIdOrder, function(sourceId) {
            return self.findSource({id: sourceId});
        });
        // Collect currently set positions and z indexes for given sources.
        // position := array index in this.sourceTree
        // z index := mapquery layer position = openlayers map layer index - 1
        // The collected values will be reused / redistributed to the affected
        // sources.
        var oldPositions = [];
        var zIndexes = [];
        var sourceIdToSource = {};
        _.forEach(sourceObjs, function(sourceObj) {
            oldPositions.push(self.getSourcePos(sourceObj));
            sourceIdToSource[sourceObj.id] = sourceObj;
            zIndexes.push(self.map.layersList[sourceObj.mqlid].position());
        });
        oldPositions.sort();
        zIndexes.sort();
        // rewrite sourceTree order and z indexes
        for (var i = 0; i < oldPositions.length; ++i) {
            var oldPos = oldPositions[i];
            var injectSourceId = newIdOrder[i];
            var injectSourceObj = sourceIdToSource[injectSourceId];
            var injectSourceZ = zIndexes[i];
            this.sourceTree[oldPos] = injectSourceObj;
            self.map.layersList[injectSourceObj.mqlid].position(injectSourceZ);
        }
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
        if (oldProj.projCode === srs.projection.projCode){
            return;
        }
        for(var i = 0; i < this.sourceTree.length; i++) {
            Mapbender.source[this.sourceTree[i].type].changeProjection(this.sourceTree[i], srs.projection);
        }
        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
        this.map.olMap.projection = srs.projection;
        this.map.olMap.displayProjection = srs.projection;
        this.map.olMap.units = srs.projection.proj.units;
        this.map.olMap.maxExtent = this._transformExtent(this.mapMaxExtent, srs.projection);
        $.each(self.map.olMap.layers, function(idx, layer) {
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if (!self.layersMaxExtent[layer.id])
                self._addLayerMaxExtent(layer);
            if (layer.maxExtent && layer.maxExtent != self.map.olMap.maxExtent)
                layer.maxExtent = self._transformExtent(self.layersMaxExtent[layer.id], srs.projection);
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
    /*
     * Transforms an extent into destProjection projection.
     */
    _transformExtent: function(extentObj, destProjection) {
        if (extentObj.extent != null) {
            if (extentObj.projection.projCode == destProjection.projCode) {
                return extentObj.extent.clone();
            } else {
                var newextent = extentObj.extent.clone();
                newextent.transform(extentObj.projection, destProjection);
                return newextent;
            }
        } else {
            return null;
        }
    },
    /**
     * Adds a layer's original extent into the widget layersOrigExtent.
     */
    _addLayerMaxExtent: function(layer) {
        if (layer.olLayer) {
            layer = layer.olLayer;
        }
        if (!this.layersMaxExtent[layer.id]) {
            var proj;
            var maxExt;
            if (layer.options.configuration) {
                var bboxes = layer.options.configuration.configuration.options.bbox;
                /* TODO? add "if" for source type 'wms' etc. */
                for (var srs in bboxes) {
                    if (this.getProj(srs)) {
                        proj = this.getProj(srs);
                        maxExt = OpenLayers.Bounds.fromArray(bboxes[srs]);
                        break;
                    }
                }
            }
            if (!proj || !maxExt) {
                proj = this.proj;
                maxExt = layer.maxExtent ? layer.maxExtent.clone() : null;
            }
            this.layersMaxExtent[layer.id] = {
                projection: proj,
                extent: maxExt
            };
        }
    },
    /**
     * Removes a layer's origin extent from the widget layersOrigExtent.
     */
    _removeLayerMaxExtent: function(layer) {
        if (layer.olLayer) {
            layer = layer.olLayer;
        }
        if (this.layersMaxExtent[layer.id]) {
            delete(this.layersMaxExtent[layer.id]);
        }
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
