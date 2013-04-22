(function($) {

OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';

$.widget("mapbender.mbMap", {
//    options: {
//        layerset: null, //mapset for main map
//        dpi: OpenLayers.DOTS_PER_INCH,
//        srs: 'EPSG:4326',
//        srsDefs: [],
//        units: 'degrees',
//        extents: {
//            max: [-180, -90, 180, 90],
//            start: [-180, -90, 180, 90]
//        },
//        maxResolution: 'auto',
//        imgPath: 'bundles/mapbendercore/mapquery/lib/openlayers/img'
//    },
    model: null,
//    allSrsTemp: [],
//    allSrs: {},
//    numSrs: 0,
//    proj4js: null,
//    layersOrigExtents: {},
//    mapOrigExtents: {},
    map: null,
//    highlightLayer: null,
    readyState: false,
    readyCallbacks: [],
//    controls: [],

    _create: function() {
        // @TODO: This works around the fake layerset for now
        if(this.options.layerset === null) {
                var layersetIds = [];
            $.each(Mapbender.configuration.layersets, function(key, val) {
                layersetIds.push(key);
            });
            this.option('layerset', layersetIds[0]);
        }
        var self = this,
            me = $(this.element);
        this.model = Mapbender.DefaultModel;
        this.model.init(this);
        this.options = { layerDefs: []}; // romove all options
        this.map = me.data('mapQuery');
        self._trigger('ready');
        this._ready();
    },

    /**
     * DEPRECATED
     */
    "goto": function(options) {
        this.map.center(options);
    },
    
    addSource: function(sourceDef){
        this.model.addSource(sourceDef, null, null);
    },
    
    /**
     * Triggers an event from the model.
     * options.name - name of the event,
     * options.value - parameter in the form of:
     * options.value.mapquerylayer - for a MapQuery.Layer,
     * options.value.source - for a source from the model.sourceTree,
     * options.value.tochange - for a "tochange" object
     * (see model.createToChangeObj(id)),
     * options.value.changed -  for a "changed" object
     * (see model.createChangedObj(id)).
     **/
    fireModelEvent: function(options){
        window.console && console.log("fireEvent:", options);
        this._trigger(options.name, null, options.value);
    },
    
    /**
     * Returns a sourceTree from model.
     **/
    getSourceTree: function() {
        return this.model.sourceTree;
    },

    genereateSourceId: function() {
        return this.model.generateSourceId();
    },
    
    getAllSrs: function(){
        return this.model.getAllSrs();
    },
    
    getModel: function(){
        return this.model;
    },
    
    zoomIn: function() {
        // TODO: MapQuery?
        this.map.olMap.zoomIn();
    },

    zoomOut: function() {
        // TODO: MapQuery?
        this.map.olMap.zoomOut();
    },

    zoomToFullExtent: function() {
        // TODO: MapQuery?
        this.map.olMap.zoomToMaxExtent();
    },

    zoomToExtent: function(extent, scale) {
        //TODO: MapQuery?
        this.map.olMap.zoomToExtent(extent);
        if(scale) {
            this.map.olMap.zoomToScale(scale, true);
        }
    },

    zoomToScale: function(scale) {
        this.map.olMap.zoomToScale(scale, true);
    },

    panMode: function() {
        this.map.mode('pan');
    },

    addPopup: function(popup) {
        //TODO: MapQuery
        this.map.olMap.addPopup(popup);
    },

    removePopup: function(popup) {
        //TODO: MapQuery
        this.map.olMap.removePopup(popup);
    },

    scales: function() {
        var scales = [];
        for(var i = 0; i < this.map.olMap.getNumZoomLevels(); ++i) {
            var res = this.map.olMap.getResolutionForZoom(i);
            scales.push(OpenLayers.Util.getScaleFromResolution(res, this.map.olMap.units));
        }
        return scales;
    },
    
    ready: function(callback) {
        window.console && console.log("READY DEPRE:", callback);
        if(this.readyState === true) {
            callback();
        } else {
            this.readyCallbacks.push(callback);
        }
    },

    _ready: function() {
        window.console && console.log("_READY DEPRE");
        for(callback in this.readyCallbacks) {
            callback();
            delete(this.readyCallbacks[callback]);
        }
        this.readyState = true;
    }
//    center: function(options) {
//        this.map.center(options);
//    },
//
//    highlight: function(features, options) {
//        var self = this;
//        if(!this.highlightLayer) {
//            this.highlightLayer = this.map.layers({
//                type: 'vector',
//                label: 'Highlight'});
//            var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer.olLayer, {
//                hover: true,
//                onSelect: function(feature) {
//                    self._trigger('highlighthoverin', null, { feature: feature });
//                },
//                onUnselect: function(feature) {
//                    self._trigger('highlighthoverout', null, { feature: feature });
//                }
//            });
//            this.map.olMap.addControl(selectControl);
//            selectControl.activate();
//        }
//
//
//        var o = $.extend({}, {
//            clearFirst: true,
//            "goto": true
//        }, options);
//
//        // Remove existing features if requested
//        if(o.clearFirst) {
//            this.highlightLayer.olLayer.removeAllFeatures();
//        }
//
//        // Add new highlight features
//        this.highlightLayer.olLayer.addFeatures(features);
//
//        // Goto features if requested
//        if(o['goto']) {
//            var bounds = this.highlightLayer.olLayer.getDataExtent();
//            this.map.center({box: bounds.toArray()});
//        }
//
//        this.highlightLayer.bind('featureselected',   function() { self._trigger('highlightselected', arguments); });
//        this.highlightLayer.bind('featureunselected', function() { self._trigger('highlightunselected', arguments); });
//    },
//
//    layer: function(layerDef) {
//        var l = this._convertLayerDef(layerDef)
//        this.rootLayers.push(l);
//        this.map.layers(l);
//    },
//
//    // untested!
//    appendLayer: function(layerDef, parentId) {
//        if(!parentId) {
//            this.layer(layerDef);
//            return;
//        }
//        var self = this;
//        var newLayer = this._convertLayerDef(layerDef);
//        this.map.layers(newLayer);
//
//        function _append(_, layer) {
//            if(layer.mapbenderId === parentId) {
//                if(!layer.sublayers) layer.sublayers = [];
//                layer.sublayers.push(newLayer);
//            } else {
//                if(layer.sublayers) {
//                    $.each(layer.sublayers, _append);
//                }
//            }
//        }
//
//        $.each(this.rootLayers, _append);
//
//        this.rebuildStacking();
//    },
//
//    /**
//     * Insert a layer before or after a sibling. Untested!
//     */
//    insert: function(layerDef, siblingId, before) {
//        var self = this;
//        var newLayer = this._convertLayerDef(layerDef);
//        this.map.layers(newLayer);
//
//        function _insert(list) {
//            for(var i = 0; i < list.length; ++i) {
//                if(list[i].mapbenderId === siblingId) {
//                    if(before) {
//                        list.splice(i, 0, newLayer);
//                        break;
//                    } else {
//                        list.splice(i+1, 0, newLayer);
//                        break;
//                    }
//                } else {
//                    if(list[i].sublayers) {
//                        _insert(list[i].sublayers);
//                    }
//                }
//            }
//        }
//
//        _insert(this.rootLayers);
//
//        this.rebuildStacking();
//    },
//
//    rebuildStacking: function() {
//        var self = this;
//        var pos = 0;
//        function _rebuild(layer){
//            if(layer.sublayers) {
//                $.each(layer.sublayers, function(idx, val) {
//                           self.layerById(val.mapbenderId).position(pos++);
//                           _rebuild(val);
//                       });
//            }
//        }
//
//        for(var i = 0; i < this.rootLayers.length; ++i) {
//            self.layerById(this.rootLayers[i].mapbenderId).position(pos++);
//            _rebuild(this.rootLayers[i]);
//        }
//    },

//    /**
//     * Moves a layer up (direction == true) or down (direction == false) on the same level in the layer hierarchy.
//     */
//    move: function(id, direction) {
//        var self = this;
//        function _move(list) {
//            var idx = null;
//            for(var i = 0; i < list.length; ++i) {
//                if(list[i].mapbenderId === id) {
//                    idx = i;
//                    break;
//                }
//            }
//            if(idx !== null) {
//                if(direction && idx > 0) {
//                    var lay = list[idx];
//                    list[idx] = list[idx-1];
//                    list[idx-1] = lay;
//                    lay = self.layerById(id);
//                    self.rebuildStacking();
//                } else if(!direction && idx < list.length - 1) {
//                    var lay = list[idx];
//                    list[idx] = list[idx+1];
//                    list[idx+1] = lay;
//                    lay = self.layerById(id);
//                    self.rebuildStacking();
//                }
//            } else {
//                for(i = 0; i < list.length; ++i) {
//                    if(list[i].sublayers) {
//                        _move(list[i].sublayers);
//                    }
//                }
//            }
//        }
//        _move(this.rootLayers);
//    },
//
//    _convertLayerDef: function(layerDef) {
//        var self = this;
//        if(typeof Mapbender.layer[layerDef.type] !== 'object'
//            && typeof Mapbender.layer[layerDef.type].create !== 'function') {
//            throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
//        }
//        // TODO object should be cleaned up
//        var l = $.extend({}, Mapbender.layer[layerDef.type].create(layerDef), { mapbenderId: layerDef.id, configuration: layerDef });
//        if(layerDef.configuration.sublayers) {
//            l.sublayers = [];
//            $.each(layerDef.configuration.sublayers, function(idx, val) {
//                       l.sublayers.push(self._convertLayerDef({id: idx, type: 'wms', configuration: val}));
//                   });
//        }
//        return l;
//    },
//    removeById: function(id) {
//        var self = this;
//        function _remove(_, layer) {
//            self.layerById(layer.mapbenderId).remove();
//            if(layer.sublayers) {
//                $.each(layer.sublayers, _remove);
//            }
//        }
//
//        $.each(this.rootLayers, function(idx, layer) {
//                   if(layer.mapbenderId === id) {
//                       _remove(null, layer);
//                   }
//        });
//    },
//
//    /**
//     * Searches for a MapQuery layer by it's Mapbender id.
//     * Returns the layer or null if not found.
//     */
//    layerById: function(id) {
//        var layer = null;
//        $.each(this.map.layers(), function(idx, mqLayer) {
//            if(mqLayer.options.mapbenderId === id) {
//                layer = mqLayer;
//                return false;
//            }
//        });
//        return layer;
//    },
//
//    /**
//     * Listen to newly added layers in the MapQuery object
//     */
//    _onAddLayer: function(event, layer) {
//        var listener = Mapbender.layer[layer.olLayer.type].onLoadStart;
//        if(typeof listener === 'function') {
//            listener.call(layer);
////            this._addOrigLayerExtent(layer);
//        }
//    },

//    /*
//     * Sets a new map's projection.
//     */
//    _changeMapProjection: function(event, srs){
//        var self = this;
//        var oldProj = this.map.olMap.getProjectionObject();
//        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
//        this.map.olMap.projection = srs.projection;
//        this.map.olMap.displayProjection= srs.projection;
//        this.map.olMap.units = srs.projection.proj.units;
//
//        this.map.olMap.maxExtent = this._transformExtent(
//                this.mapOrigExtents.max, srs.projection);
//        $.each(self.map.olMap.layers, function(idx, layer){
////            if(layer.isBaseLayer){
//            layer.projection = srs.projection;
//            layer.units = srs.projection.proj.units;
//            if(!self.layersOrigExtents[layer.id]){
//                self._addOrigLayerExtent(layer);
//            }
//            if(layer.maxExtent && layer.maxExtent != self.map.olMap.maxExtent){
//                layer.maxExtent = self._transformExtent(
//                        self.layersOrigExtents[layer.id].max, srs.projection);
//            }
//
//            if(layer.minExtent){
//                layer.minExtent = self._transformExtent(
//                        self.layersOrigExtents[layer.id].minExtent, srs.projection);
//            }
//            layer.initResolutions();
////            }
//        });
//        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
//        this._trigger('srsChanged', null, {
//            projection: srs.projection
//        });
//    },
//
//    /*
//     * Transforms an extent into destProjection projection.
//     */
//    _transformExtent: function(extentObj, destProjection){
//        if(extentObj.extent != null){
//            if(extentObj.projection.projCode == destProjection.projCode){
//                return extentObj.extent.clone();
//            } else {
//                var newextent = extentObj.extent.clone();
//                newextent.transform(extentObj.projection, destProjection);
//                return newextent;
//            }
//        } else {
//            return null;
//        }
//    },
//
//    /**
//     * Adds a layer's original extent into the widget layersOrigExtent.
//     */
//    _addOrigLayerExtent: function(layer) {
//        if(layer.olLayer) {
//            layer = layer.olLayer;
//        }
//        if(!this.layersOrigExtents[layer.id]){
//            var extProjection = new OpenLayers.Projection(this.options.srs);
//            if(extProjection.projCode === 'EPSG:4326') {
//                extProjection.proj.units = 'degrees';
//            }
//            this.layersOrigExtents[layer.id] = {
//                max: {
//                    projection: extProjection,
//                    extent: layer.maxExtent ? layer.maxExtent.clone() : null},
//                min: {
//                    projection: extProjection,
//                    extent: layer.minExtent ? layer.minExtent.clone() : null}
//            };
//        }
//    },
//
//    /**
//     * Removes a layer's origin extent from the widget layersOrigExtent.
//     */
//    _removeOrigLayerExtent: function(layer) {
//        if(layer.olLayer) {
//            layer = layer.olLayer;
//        }
//        if(this.layersOrigExtent[layer.id]){
//            delete(this.layersOrigExtent[layer.id]);
//        }
//    },
//
//
//    /**
//     * Listen to removed layer in the MapQuery object
//     */
//    _onRemoveLayer: function(event, layer) {
//        this._removeOrigLayerExtent(layer);
//    },
});

})(jQuery);
