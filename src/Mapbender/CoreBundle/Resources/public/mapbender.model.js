((function($) {
    // NotMapQueryMap, unlike MapQuery.Map, doesn't try to know better how to initialize
    // an OpenLayers Map, doesn't pre-assume any map properties, doesn't mess with default
    // map controls, doesn't prevent us from passing in layers, doesn't inherently combine layer
    // creation and adding layers to the map into the same operation etc.
    // The OpenLayers Map is simply passed in.
    function NotMapQueryMap($element, olMap) {
        this.idCounter = 0;
        this.layersList = {};
        this.element = $element;
        $element.data('mapQuery', this);
        this.olMap = olMap;
    }
    NotMapQueryMap.prototype = {
        _fakeMqLayerFactory: function(id, olLayer) {
            return {
                id: id,
                olLayer: olLayer,
                // for older special snowflake versions of FeatureInfo
                label: olLayer.name || id,
                map: this
            };
        },
        trackMqLayer: function(mqLayer) {
            this.layersList[mqLayer.id] = mqLayer;
        },
        trackNativeLayer: function(olLayer) {
            var id = this._createId();
            var fakeLayer = this._fakeMqLayerFactory(id, olLayer);
            this.trackMqLayer(fakeLayer);
            return fakeLayer;
        },
        layers: function(layerOptions) {
            if (arguments.length !== 1 || Array.isArray(layerOptions) || layerOptions.type !== 'vector') {
                console.error("Unsupported MapQueryish layers call", arguments);
                throw new Error("Unsupported MapQueryish layers call");
            }
            console.warn("Engaging legacy emulation for MapQuery.Map.layers(), only allowed for 'vector' type. Please stop using this.", arguments);
            var fakeId = this._createId();
            var layerName = layerOptions.label || fakeId;
            var olLayer = new OpenLayers.Layer.Vector(layerName);
            var fakeMqLayer = this._fakeMqLayerFactory(fakeId, olLayer);
            this.trackMqLayer(fakeMqLayer);
            this.olMap.addLayer(olLayer);
            return fakeMqLayer;
        },
        _createId: function() {
            return 'certainly-not-mapquery-' + this.idCounter++;
        }
    };

window.Mapbender = Mapbender || {};
window.Mapbender.Model = $.extend(Mapbender && Mapbender.Model || {}, {
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

    mbMap: null,
    map: null,
    sourceTree: [],
    srsDefs: null,
    mapMaxExtent: null,
    mapStartExtent: null,
    _highlightLayer: null,
    /** Backend-configured initial projection, used for start / max extents */
    _configProj: null,
    /** Actual initial projection, determined by a combination of several URL parameters */
    _startProj: null,
    baseId: 0,
    init: function(mbMap) {
        var self = this;
        this.mbMap = mbMap;
        this.srsDefs = this.mbMap.options.srsDefs;
        Mapbender.Projection.extendSrsDefintions(this.srsDefs || []);

        if (typeof (this.mbMap.options.dpi) !== 'undefined') {
            OpenLayers.DOTS_PER_INCH = this.mbMap.options.dpi;
        }

        var tileSize = this.mbMap.options.tileSize;
        OpenLayers.Map.TILE_WIDTH = tileSize;
        OpenLayers.Map.TILE_HEIGHT = tileSize;

        OpenLayers.ImgPath = Mapbender.configuration.application.urls.asset + 'components/mapquery/lib/openlayers/img/';
        // Allow drag pan motion to continue outside of map div. Great for multi-monitor setups.
        OpenLayers.Control.Navigation.prototype.documentDrag = true;
        this._initMap();
    },
    _initMap: function _initMap() {
        var self = this;
        this._configProj = this.getProj(this.mbMap.options.srs);
        this._startProj = this.getProj(this.mbMap.options.targetsrs || this.mbMap.options.srs);


        this.mapMaxExtent = {
            projection: this._configProj,
            // using null or open bounds here causes failures in map, overview and other places
            // @todo: make applications work with open / undefined max extent
            extent: OpenLayers.Bounds.fromArray(this.mbMap.options.extents.max)
        };

        this.mapStartExtent = {
            projection: this._configProj,
            extent: OpenLayers.Bounds.fromArray(this.mbMap.options.extents.start || this.mbMap.options.extents.max)
        };
        var baseLayer = new OpenLayers.Layer('fake', {
            visibility: false,
            isBaseLayer: true,
            maxExtent: this._transformExtent(this.mapMaxExtent.extent, this._configProj, this._startProj).toArray(),
            projection: this._startProj
        });
        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent.extent, this._configProj, this._startProj).toArray(),
            maxResolution: this.mbMap.options.maxResolution,
            numZoomLevels: this.mbMap.options.scales ? this.mbMap.options.scales.length : this.mbMap.options.numZoomLevels,
            projection: this._startProj,
            displayProjection: this._startProj,
            units: this._startProj.proj.units,
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
        var olMap = new OpenLayers.Map(this.mbMap.element.get(0), mapOptions);
        // Use a faked, somewhat compatible-ish surrogate for MapQuery Map
        // @todo: eliminate MapQuery method / property access completely
        // * accesses to 'layersList'
        // * layer lookup via 'mqlid' on source definitions

        this.map = new NotMapQueryMap(this.mbMap.element, olMap);

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
        })(this.map.olMap);

        this.setView(true);
        this.parseURL();
        if (this.mbMap.options.targetscale) {
            this.map.olMap.zoomToScale(this.mbMap.options.targetscale, true);
        }
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
            lonlat = lonlat.transform(this.getProj(singlePoi.srs), this._startProj);
            this.map.olMap.setCenter(lonlat);
        } else {
            var mapExtra = this.mbMap.options.extra;
            var startExtent;
            if (mapExtra && mapExtra.bbox) {
                startExtent = OpenLayers.Bounds.fromArray(mapExtra.bbox);
            } else {
                startExtent = this._transformExtent(this.mapStartExtent.extent, this._configProj, this._startProj);
            }
            this.map.olMap.zoomToExtent(startExtent, true);
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
    initializeSourceLayers: function() {
        var self = this;
        // @todo 3.1.0: event binding is historically tied to initializing layers ... resolve / separate?
        this.map.olMap.events.register('movestart', this, $.proxy(this._checkChanges, this));
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
    /**
     * @param {string} srscode
     * @return {OpenLayers.Projection}
     */
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
    generateSourceId: function() {
        var id = 'auto-src-' + (this.baseId + 1);
        ++this.baseId;
        return id;
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
     * @param {Number|String} id
     * @return {Mapbender.Source|null}
     */
    getSourceById: function(id) {
        return _.findWhere(this.sourceTree, {id: '' + id}) || null;
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
     * Updates the options.treeOptions within the source with new values from layerOptionsMap.
     * Always reapplies states to engine (i.e. affected layers are re-rendered).
     * Alawys fires an 'mbmapsourcechanged' event.
     *
     * @param {Object} source
     * @param {Object<string, Model~LayerTreeOptionWrapper>} layerOptionsMap
     * @private
     */
    _updateSourceLayerTreeOptions: function(source, layerOptionsMap) {
        var gsHandler = this.getGeoSourceHandler(source);
        gsHandler.applyTreeOptions(source, layerOptionsMap);
        var newStates = gsHandler.calculateLeafLayerStates(source, this.getScale());
        var changedStates = gsHandler.applyLayerStates(source, newStates);
        var layerParams = gsHandler.getLayerParameters(source, newStates);
        this._resetSourceVisibility(source, layerParams.layers, layerParams.infolayers, layerParams.styles);

        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: {
                changed: {
                    children: $.extend(true, {}, layerOptionsMap, changedStates)
                },
                sourceIdx: {id: source.id}
            }
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
            var layerParams = gsHandler.getLayerParameters(source, newStates);
            this._resetSourceVisibility(source, layerParams.layers, layerParams.infolayers, layerParams.styles);
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
     * @param {Array<string>} layers
     * @param {Array<string>} infolayers
     * @param {Array<string>} styles
     *
     * @returns {boolean}
     * @private
     */
    _resetSourceVisibility: function(source, layers, infolayers, styles) {
        var olLayer = this.getNativeLayer(source);
        if (!olLayer) {
            return false;
        }
        olLayer.queryLayers = infolayers;
        var targetVisibility = !!layers.length;
        var visibilityChanged = targetVisibility !== olLayer.getVisibility();
        var layersChanged =
            ((olLayer.params.LAYERS || '').toString() !== layers.toString()) ||
            ((olLayer.params.STYLES || '').toString() !== styles.toString())
        ;

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
                LAYERS: layers,
                STYLES: styles
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
     * @typedef {Object} Model~CenterOptionsMapQueryish
     * @property {Array<Number>} position
     * @property {Number} [zoom]
     */
    /**
     * @param {Array<Number>|OpenLayers.LonLat|Model~CenterOptionsMapQueryish} lonLat
     * @param zoom
     */
    center: function(lonLat, zoom) {
        // Compatibility hack for legacy elements (e.g. old SimpleSearch) expecting MapQuery API
        var _lonLat = lonLat, _zoom = zoom;
        if (lonLat) {
            if (typeof lonLat.position !== 'undefined') {
                console.warn("Calling center with MapQuery-style options is deprecated", arguments);
                _lonLat = new OpenLayers.LonLat(lonLat.position[0], lonLat.position[1]);
                _zoom = lonLat.zoom || zoom;
            }
        } else {
            _lonLat = null;
        }
        this.map.olMap.setCenter(_lonLat, _zoom);
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
                this.map.olMap.zoomToExtent(bounds);
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
    setOpacity: function(source, opacity) {
        if (typeof opacity === 'number' && !isNaN(opacity) && opacity >= 0 && opacity <= 1 && source) {
            source.configuration.options.opacity = opacity;
            this.getNativeLayer(source).setOpacity(opacity);
        } else {
            console.error("Invalid opacity", opacity, source);
        }
    },
    _getSourceLayerExtents: function(source, layerId) {
        return Mapbender.source[source.type].getLayerExtents(source, layerId) || null;
    },
    /**
     * Zooms to layer
     * @param {Object} options
     * @property {String} options.sourceId
     * @property {String} options.layerId
     */
    zoomToLayer: function(options) {
        var source = this.getSourceById(options.sourceId);
        var extents = source && this._getSourceLayerExtents(source, options.layerId);
        if (extents) {
            var bounds;
            var extentArray, extProj;
            var currentProj = this.map.olMap.getProjectionObject();
            var srsOrder = [currentProj.projCode].concat(Object.keys(extents));
            for (var i = 0; i < srsOrder.length; ++i) {
                var srsName = srsOrder[i];
                var extent = extents[srsName];
                extProj = extent && this.getProj(srsName);
                if (extProj) {
                    extentArray = extents[srsName];
                    break;
                }
            }
            if (extentArray) {
                if (source.type === 'wms' && source.configuration.options.version === '1.3.0') {
                    var projDefaults = OpenLayers.Projection.defaults[extProj.projCode];
                    var yx = projDefaults && projDefaults.yx;
                    if (yx) {
                        // Seriously.
                        // See http://portal.opengeospatial.org/files/?artifact_id=14416 page 18
                        extentArray = [extentArray[1], extentArray[0], extentArray[3], extentArray[2]];
                    }
                }
                bounds = OpenLayers.Bounds.fromArray(extentArray);
                bounds = this._transformExtent(bounds, extProj, currentProj);
            }

            if (bounds) {
                this.mbMap.zoomToExtent(bounds, true);
            }
        }
    },
    /**
     * Gets a mapping of all defined extents for a layer, keyed on SRS
     * @param {Object} options
     * @property {String} options.sourceId
     * @property {String} options.layerId
     * @return {Object<String, Array<Number>>}
     */
    getLayerExtents: function(options) {
        var source = this.getSourceById(options.sourceId);
        if (source) {
            return this._getSourceLayerExtents(source, options.layerId);
        } else {
            console.warn("Source not found", options);
            return null;
        }
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

        var olLayers = sourceDef.initializeLayers();
        for (var i = 0; i < olLayers.length; ++i) {
            var olLayer = olLayers[i];
            olLayer.setVisibility(false);
            var fakeMqlayer = this.map.trackNativeLayer(olLayer);
            this.map.olMap.addLayer(olLayer);
            sourceDef.mqlid = fakeMqlayer.id;
            // source attribute required by older special snowflake versions of FeatureInfo
            fakeMqlayer.source = sourceDef;
            olLayer.mbConfig = sourceDef;
            olLayer.events.register("loadstart", this, this._sourceLoadStart);
            olLayer.events.register("tileerror", this, this._sourceLoadError);
            olLayer.events.register("loadend", this, this._sourceLoadeEnd);
        }

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
                var olLayer = this.getNativeLayer(sourceToRemove);
                if (olLayer) {
                    if (olLayer instanceof OpenLayers.Layer.Grid) {
                        olLayer.clearGrid();
                    }
                    if (olLayer.map) {
                        if (olLayer.map.tileManager) {
                            olLayer.map.tileManager.clearTileQueue({
                                object: olLayer
                            });
                        }
                        olLayer.map.removeLayer(olLayer);
                    }
                    for (var i = 0; i < this.sourceTree.length; i++) {
                        if (this.sourceTree[i].id.toString() === sourceToRemove.id.toString()) {
                            this.sourceTree.splice(i, 1);
                            break;
                        }
                    }
                    if (sourceToRemove.mqlid && this.map.layersList[sourceToRemove.mqlid]) {
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
     * @deprecated
     */
    changeSource: function(options) {
        if (options.change) {
            var changeOpts = options.change;
            if (changeOpts.options) {
                switch (changeOpts.options.type) {
                    case 'selected':
                    case 'info':
                        var sourceToChange = this.getSource(changeOpts.sourceIdx);
                        this.mbMap.fireModelEvent({
                            name: 'beforeSourceChanged',
                            value: {
                                source: sourceToChange,
                                changeOptions: changeOpts
                            }
                        });
                        console.warn("Use controlLayer instead of changeSource with type " + changeOpts.options.type);
                        this._updateSourceLayerTreeOptions(sourceToChange, changeOpts.options.children);
                        return;
                    default:
                        break;
                }
            } else if (changeOpts.layerRemove) {
                console.warn("Use removeLayer instead of changeSource");
                this.removeLayer(changeOpts.layerRemove.sourceIdx.id, changeOpts.layerRemove.layer.options.id);
                return;
            }
        }
        console.error("Unsupported changeSource options", options);
        throw new Error("Unsupported changeSource options");
    },
    removeLayer: function(sourceId, layerId) {
        var source = this.getSource({id: sourceId});
        var gs = this.getGeoSourceHandler(source, true);
        var eventData = {
            changed: {
                childRemoved: gs.removeLayer(source, {options: {id: layerId}}),
                sourceIdx: {id: sourceId}
            }
        };
        this._checkSource(source, true, false);
        this.mbMap.fireModelEvent({
            name: 'sourceChanged',
            value: eventData
        });
    },
    setSourceVisibility: function(sourceId, state) {
        var source = this.getSource({id: sourceId});
        var newProps = {};
        var rootLayerId = source.configuration.children[0].options.id;
        newProps[rootLayerId] = {
            options: {
                treeOptions: {
                    selected: state
                }
            }
        };
        this._updateSourceLayerTreeOptions(source, newProps);
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
            var toChangeOptions = Mapbender.source[source.type].createOptionsLayerState(source, options,
                defaultSelected, mergeSelected);
            this._updateSourceLayerTreeOptions(source, toChangeOptions.change.options.children);
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
        var sourceObj = this.getSource({id: sourceId});
        var geoSource = Mapbender.source[sourceObj.type];

        geoSource.setLayerOrder(sourceObj, newLayerIdOrder);

        this.mbMap.fireModelEvent({
            name: 'sourceMoved',
            // no receiver uses the bizarre "changeOptions" return value
            // on this event
            value: null
        });
        this._checkSource(sourceObj, true, false);
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
     * @param {OpenLayers.Projection} oldProj
     * @param {OpenLayers.Projection} newProj
     * @private
     */
    _changeLayerProjection: function(olLayer, oldProj, newProj) {
        var layerOptions = {
            // passing projection as string is preferable to passing the object,
            // because it also auto-initializes units and projection-inherent maxExtent
            projection: newProj.projCode
        };
        if (olLayer.maxExtent) {
            layerOptions.maxExtent = this._transformExtent(olLayer.maxExtent, oldProj, newProj);
        }
        olLayer.addOptions(layerOptions);
    },
    /*
     * Changes the map's projection.
     */
    changeProjection: function(srsCode) {
        var self = this;
        var newProj;
        if (srsCode.projection) {
            console.warn("Legacy object-style argument passed to changeProjection");
            newProj = this.getProj(srsCode.projection.projCode);
        } else {
            newProj = this.getProj(srsCode);
        }
        var oldProj = this.map.olMap.getProjectionObject();
        if (oldProj.projCode === newProj.projCode) {
            return;
        }
        $(this.mbMap.element).trigger('mbmapbeforesrschange', {
            from: oldProj,
            to: newProj,
            mbMap: this.mbMap
        });
        var nLayers = this.map.olMap.layers.length;
        var i, olLayer, mbSource, gsHandler;
        for (i = 0; i < nLayers; ++i) {
            olLayer = this.map.olMap.layers[i];
            mbSource = olLayer.mbConfig;
            gsHandler = mbSource && this.getGeoSourceHandler(mbSource);
            if (gsHandler) {
                gsHandler.beforeSrsChange(mbSource, olLayer, newProj.projCode);
            }
        }
        var center = this.map.olMap.getCenter().clone().transform(oldProj, newProj);
        // transform base layer last
        // base layer determines overall map properties (max extent, units, resolutions etc)
        var baseLayer = this.map.olMap.baseLayer || this.map.olMap.layers[0];
        for (i = 0; i < nLayers; ++i) {
            olLayer = this.map.olMap.layers[i];
            mbSource = olLayer.mbConfig;
            gsHandler = mbSource && this.getGeoSourceHandler(mbSource);
            if (gsHandler) {
                gsHandler.changeProjection(mbSource, newProj);
            }
            if (olLayer !== baseLayer) {
                self._changeLayerProjection(olLayer, oldProj, newProj);
            }
        }
        if (baseLayer) {
            this._changeLayerProjection(baseLayer, oldProj, newProj);
        }
        this.map.olMap.projection = newProj;
        this.map.olMap.displayProjection = newProj;
        this.map.olMap.units = newProj.proj.units;
        this.map.olMap.maxExtent = this._transformExtent(this.mapMaxExtent.extent, this.mapMaxExtent.projection, newProj);
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
        this.mbMap.fireModelEvent({
            name: 'srschanged',
            value: {
                projection: newProj,
                from: oldProj,
                to: newProj,
                mbMap: this.mbMap
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
        var treeOptions = {};
        if (selected !== null && typeof selected !== 'undefined') {
            treeOptions.selected = !!selected;
        }
        if (info !== null && typeof info !== 'undefined') {
            treeOptions.info = !!info;
        }
        if (Object.keys(treeOptions).length) {
            layerMap['' + layerId] = {
                options: {
                    treeOptions: treeOptions
                }
            };
        }
        if (Object.keys(layerMap).length) {
            this._updateSourceLayerTreeOptions(this.getSource({id: sourceId}), layerMap);
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
        if (source.configuration && source.configuration.children) {
            // sourceTreeish
            return source;
        }
        console.error("Cannot find configuration in given source", source);
        throw new Error("Cannot find configuration in given source");
    },
    /**
     * @param {*} anything
     * @return {OpenLayers.Layer|null}
     */
    getNativeLayer: function(anything) {
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
        var olLayer = this.getNativeLayer(sourceOrLayer);
        var source = this.getMbConfig(sourceOrLayer);
        var extent_ = extent || this.getMapExtent();
        var gsHandler = this.getGeoSourceHandler(source);
        var units = this.map.olMap.getUnits();
        var dataOut = [];
        var commonLayerData = {
            type: source.configuration.type,
            sourceId: source.id
            // @todo: provide opacity and changeAxis here?
        };
        var resFromScale = function(scale) {
            return scale && (OpenLayers.Util.getResolutionFromScale(scale, units)) || null;
        };
        var leafInfoMap = gsHandler.getExtendedLeafInfo(source, scale, extent_);
        if (gsHandler.getSingleLayerUrl) {
            _.forEach(leafInfoMap, function(item) {
                if (item.state.visibility) {
                    dataOut.push($.extend({}, commonLayerData, {
                        url: gsHandler.getSingleLayerUrl(olLayer, extent_, item.layer.options.name, item.layer.options.style),
                        minResolution: resFromScale(item.layer.options.minScale),
                        maxResolution: resFromScale(item.layer.options.maxScale),
                        order: item.order
                    }));
                }
            });
            dataOut.sort(function(a, b) {
                return a.order - b.order;
            });
        } else {
            // hopefully building a bridge for forks / feature branches with non-WMS layers or customized geosource code
            console.warn("Extended print config generation not possible on current source, falling back to the old ways", source.configuration.type, source);
            var layerParams, url;
            if (gsHandler.getLayerParameters) {
                // Get active layer parameters explicitly without side effects
                layerParams = gsHandler.getLayerParameters(source, _.mapObject(leafInfoMap, function(item) {
                    return item.state;
                }));
            } else {
                console.warn("Geosource handler for current source doesn't support getLayerParameters method, falling back to the oldest of old ways", source.configuration.type, source);
                // This should give us a lot more than just the active layers + styles, but also changes
                // the source's embedded layer states as a side effect, potentially leading to erroneous display
                // in LayerTree and Legend Elements on the next proper update
                layerParams = gsHandler.changeOptions(source, scale || this.getScale());
            }
            if (layerParams.layers.length) {
                // alter params for getURL call implicit to getPrintConfig
                var prevLayers = olLayer.params.LAYERS;
                var prevStyles = olLayer.params.STYLES;
                olLayer.params.LAYERS = layerParams.layers;
                olLayer.params.STYLES = layerParams.styles;
                url = gsHandler.getPrintConfig(olLayer, extent_).url;
                // restore params
                olLayer.params.LAYERS = prevLayers;
                olLayer.params.STYLES = prevStyles;
                // Decorate generated url with combined min/max resolution from OpenLayers layer (= multiple Mapbender layers from a single source)
                dataOut.push($.extend({}, commonLayerData, {
                    url: url,
                    minResolution: olLayer.minResolution,
                    maxResolution: olLayer.maxResolution
                }));
            }
        }
        return dataOut;
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
        var visibleLayersParam = new Mapbender.Util.Url(window.location.href).getParameter('visiblelayers');
        if (visibleLayersParam) {
            this.processVisibleLayersParam(visibleLayersParam);
        }
    },
    /**
     * Super legacy, some variants of wmcstorage want to use this to replace the map's initial max extent AND
     * initial SRS, which only really works when called immediately before an SRS switch. Very unsafe to use.
     * @deprecated
     */
    replaceInitialMaxExtent: function(newMaxExtent, newMaxExtentSrs) {
        var proj, mx;
        if (typeof newMaxExtentSrs === 'string') {
            proj = this.getProj(newMaxExtentSrs);
        } else if (newMaxExtentSrs && newMaxExtentSrs.projCode) {
            proj = this.getProj(newMaxExtentSrs.projCode);
        }
        if (!proj) {
            throw new Error("Invalid newMaxTentSrs omission");
        }
        if ($.isArray(newMaxExtent)) {
            mx = OpenLayers.Bounds.fromArray(newMaxExtent);
        } else {
            mx = newMaxExtent;
        }
        if (!mx || !(mx instanceof OpenLayers.Bounds)) {
            throw new Error("Invalid newMaxExtent (empty or bad type)");
        }
        this._configProj = proj;
        this.mapMaxExtent = $.extend(this.mapMaxExtent || {}, {
            projection: proj,
            extent: mx
        });
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