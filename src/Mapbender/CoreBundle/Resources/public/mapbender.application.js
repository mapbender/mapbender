var Mapbender = Mapbender || {};

Mapbender.ElementRegistry = function() {
    this.readyElements = {};
    this.readyCallbacks = {};

    this.onElementReady = function(targetId, callback) {
        if(true === callback) {
            // Register as ready
            this.readyElements[targetId] = true;

            // Execute all callbacks registered so far
            if('undefined' !== typeof this.readyCallbacks[targetId]) {
                for(var idx in this.readyCallbacks[targetId]) {
                    this.readyCallbacks[targetId][idx]();
                }

                // Finally, remove readyCallback list, so they may be garbage
                // collected if no one else is keeping them
                delete this.readyCallbacks[targetId];
            }
        } else if('function' === typeof callback) {
            if(true === this.readyElements[targetId]) {
                // If target is ready already, execute callback right away
                callback();
            } else {
                // Register callback for targetId for later execution
                this.readyCallbacks[targetId] = this.readyCallbacks[targetId] || [];
                this.readyCallbacks[targetId].push(callback);
            }
        } else {
            throw 'ElementRegistry.onElementReady callback must be function or undefined!';
        }
    }
};
Mapbender.elementRegistry = new Mapbender.ElementRegistry();

Mapbender.setup = function() {
    // Initialize all elements by calling their init function with their options
    $.each(Mapbender.configuration.elements, function(id, data) {
        // Split for namespace and widget name
        var widget = data.init.split('.');

        // Register for ready event to operate ElementRegistry
        var readyEvent = widget[1].toLowerCase() + 'ready';
        $('#' + id).one(readyEvent, function(event) {
            for(var i in Mapbender.configuration.elements) {
                var conf = Mapbender.configuration.elements[i],
                widget = conf.init.split('.'),
                readyEvent = widget[1].toLowerCase() + 'ready';
                if(readyEvent === event.type) {
                    Mapbender.elementRegistry.onElementReady(i, true);
                }
            }
        });

        // This way we call by namespace and widget name
        // The namespace is kinda useless, as $.widget creates a function with
        // the widget name directly in the jQuery object, too. Still, let's be
        // futureproof.
        $[widget[0]][widget[1]](data.configuration, '#' + id);
    });

    // Tell the world that all widgets have been set up. Some elements will
    // need this to make calls to other element's widgets
    $(document).trigger('mapbender.setupfinished');
};

Mapbender.error = function(message){
    alert(message);
};

Mapbender.checkTarget = function(widgetName, target, targetname){
    if(target === null || typeof(target) === 'undefined'
        || new String(target).replace(/^\s+|\s+$/g, '') === ""
        || $('#' + target).length === 0){
        Mapbender.error(widgetName + ': a target element ' + (targetname ? '"' + targetname + '"' : '') + ' is not defined.');
        return false;
    } else {
        return true;
    }
};

Mapbender.DefaultModel = {
    mbMap: null,
    domElement: null,
    map: null,
    sourceTree: [],
    extent: null,
    resolution: null,
    units: null,
    proj: null,
    srsDefs: null,
    mapMaxExtent: null,
    layersMaxExtent: {},
    highlightLayer: null,
    
    init: function(mbMap){
        this.mbMap = mbMap;
        var self = this;
        this.domElement = this.mbMap.element;
        
        this.srsDefs = this.mbMap.options.srsDefs;
        for(var i = 0; i < this.srsDefs.length; i++){
            Proj4js.defs[this.srsDefs[i].name] = this.srsDefs[i].definition;
        }

        if(typeof(this.mbMap.options.dpi) !== 'undefined') {
            this.resolution = OpenLayers.DOTS_PER_INCH = this.mbMap.options.dpi;
        }
        
        OpenLayers.ImgPath = Mapbender.configuration.application.urls.asset + this.mbMap.options.imgPath + '/';
        
        this.proj = this.getProj(this.mbMap.options.srs);
        this.units = this.mbMap.options.units; //TODO check if this.units === this.proj.proj.units
        
        this.mapMaxExtent = {
            projection: this.getProj(this.mbMap.options.srs),
            extent: this.mbMap.options.extents.max ?
            OpenLayers.Bounds.fromArray(this.mbMap.options.extents.max) : null
        };
        var start_extent = {
            projection: this.getProj(this.mbMap.options.srs),
            extent: this.mbMap.options.extents.start ?
            OpenLayers.Bounds.fromArray(this.mbMap.options.extents.start) :
            OpenLayers.Bounds.fromArray(this.mbMap.options.extents.max)
        };
        var poi = null, bbox = null;
        if(this.mbMap.options.targetsrs && window.Proj4js) {
            var targetProj = this.getProj(this.mbMap.options.targetsrs);
            if(targetProj){
                this.proj = targetProj;
                start_extent = this._transformExtent(start_extent, targetProj);
                if(this.mbMap.options.extra && this.mbMap.options.extra.type === 'bbox') {
                    bbox = this.mbMap.options.extra.data ?
                    OpenLayers.Bounds.fromArray(this.mbMap.options.extra.data) :
                    start_extent;
                } else if(this.mbMap.options.extra && this.mbMap.options.extra.type === 'poi') {
                    poi = {
                        position: new OpenLayers.LonLat(this.mbMap.options.extra.data.x , this.mbMap.options.extra.data.y),
                        label: this.mbMap.options.extra.data.label,
                        scale: this.mbMap.options.extra.data.scale
                    };
                }
            }
        }
        
        var layers = [];
        var allOverlays = true;
        var hasLayers = false;
        
        //        function addSubs(layer){
        //            if(layer.sublayers) {
        //                $.each(layer.sublayers, function(idx, val) {
        //                    layers.push(val);
        //                    addSubs(val);
        //                });
        //            }
        //        }
        $.each(Mapbender.configuration.layersets[this.mbMap.options.layerset].reverse(), function(idx, defArr) {
            $.each(defArr, function(idx, layerDef) {
                self.sourceTree.push(layerDef);
                //                self.sources.push(layerDef);
                hasLayers = true;
                layerDef.id = idx;
                layers.push(self._convertLayerDef.call(self, layerDef));
                //                addSubs(layers[layers.length-1]);
                allOverlays = allOverlays && (layerDef.configuration.baselayer !== true);
            });
        });
        
        //        if(!hasLayers){
        //            Mapbender.error('The element "map" has no layer.');
        //        }

        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent, this.proj).toArray(),
            zoomToMaxExtent: false,
            maxResolution: this.mbMap.options.maxResolution,
            numZoomLevels: this.mbMap.options.numZoomLevels,
            projection: this.proj,
            displayProjection: this.proj,
            units: this.proj.proj.units,
            allOverlays: allOverlays,
            theme: null,
            layers: [{
                type: "wms", 
                name: "FAKE", 
                isBaseLayer: true, 
                url: "http://localhost", 
                visibility: false
            }]
        };

        if(this.mbMap.options.scales) {
            $.extend(mapOptions, {
                scales: this.mbMap.options.scales
            });
        }
        
        $(this.mbMap.element).mapQuery(mapOptions);
        this.map = $(this.mbMap.element).data('mapQuery');
        this.map.layersList.mapquery0.olLayer.isBaseLayer = true;
        this.map.olMap.setBaseLayer(this.map.layersList.mapquery0);
        this._addLayerMaxExtent(this.map.layersList.mapquery0);
        $.each(layers, function(idx, layer) {
            self._addSourceAtStart(layer);
        });

        if(poi){
            this.center({
                position: poi.position
            });
            if(poi.scale) {
                self.mbMap.zoomToScale(poi.scale);
            }
            if(poi.label) {
                var popup = new OpenLayers.Popup.FramedCloud('chicken',
                    poi.position,
                    null,
                    poi.label,
                    null,
                    true,
                    function() {
                        self.mbMap.removePopup(this);
                        this.destroy();
                    });
                self.mbMap.addPopup(popup);
            }
        } else if(bbox){
            this.center({
                box: bbox.extent.toArray()
            });
        } else {
            this.center({
                box: start_extent.extent.toArray()
            });
        }
        $(document).bind('mbsrsselectorsrsswitched', $.proxy(self._changeProjection, self));
        this.map.olMap.events.register('zoomend', this, $.proxy(this._checkOutOfScale, this));
        this.map.olMap.events.register('movestart', this, $.proxy(this._checkOutOfBounds, this));
    },
    
    getProj: function(srscode){
        var proj = null;
        for(var i = 0; i < this.srsDefs.length; i++){
            if(this.srsDefs[i].name === srscode){
                proj = new OpenLayers.Projection(this.srsDefs[i].name);
                if(proj.projCode === 'EPSG:4326') {
                    proj.proj.units = 'degrees';
                }
                return proj;
            }
        }
        return proj;
    },
    
    getAllSrs: function(){
        return this.srsDefs;
    },
    
    _convertLayerDef: function(layerDef) {
        if(typeof Mapbender.source[layerDef.type] !== 'object'
            && typeof Mapbender.source[layerDef.type].create !== 'function') {
            throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
        // TODO object should be cleaned up
        var l = $.extend({}, Mapbender.source[layerDef.type].create(layerDef), {
            mapbenderId: layerDef.id
        });
        return l;
    },   
    
    generateSourceId: function(){
        return new Date().getTime();
    },
    
    getMapState: function(){
        var proj = this.map.olMap.getProjectionObject();
        var ext = this.map.olMap.getExtent();
        var maxExt = this.map.olMap.getMaxExtent();
        var size = this.map.olMap.getSize();
        var state = {
            window:{
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
        for(var i = 0; i < sources.length; i++){
            var source = $.extend(true, {}, sources[i]);
            source.layers = [];
            var root = source.configuration.children[0].children[0];
            var list = Mapbender.source[source.type].getLayersList(source, root, true);
            $.each(list.layers, function(idx, layer){
                source.layers.push(layer.options.name);
            });
            state.sources.push(source);
        }
        return state;
    },
    
    getSources: function(){
        return this.sourceTree;
    },
    
    getSource: function(idObject){
        var key;
        for(key in idObject){
            break;
        }
        if(key){
            for(var i = 0; i < this.sourceTree.length; i++){
                if(this.sourceTree[i][key] && idObject[key]
                    && this.sourceTree[i][key].toString() === idObject[key].toString()){
                    return this.sourceTree[i];
                }
            }
        }
        return null;
    },
    
    /**
     * Returns the source's position
     */
    getSourcePos: function(source){
        if(source){
            for(var i = 0; i < this.sourceTree.length; i++){
                if(this.sourceTree[i].id.toString() ===  source.id.toString()){
                    return i;
                }
            }
        } else
            return null;
    },
    
    /**
     * Returns the source by id
     */
    getSourceLayerById: function(source, layerId){
        if(source && layerId){
            return Mapbender.source[source.type].findLayer(source,layerId);
        } else {
            return null;
        }
    },
    
    /**
     *Creates a "tochange" object
     */
    createToChangeObj: function(source){
        if(!source || !source.id){
            return null;
        }
        return {
            source: source,
            type: "",
            children: {}
        };
    },
    
    /**
     *Creates a "changed" object
     */
    createChangedObj: function(source){
        if(!source || !source.id){
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
    getScale: function(){
        return this.map.olMap.getScale();
    },
    
    /**
     * Checks the source changes and returns the source changes.
     */
    _checkSource: function(source, mqLayer, tochange){
        if(!tochange){
            tochange = this.createToChangeObj(source);
        }
        var changed = this.createChangedObj(source);
        var result = {
            layers: [],
            infolayers: [],
            changed: changed
        };
        result = Mapbender.source[source.type].checkLayers(source,
            this.map.olMap.getScale(), tochange, result);

        mqLayer.layers = result.layers;
        mqLayer.olLayer.layers = mqLayer.layers;
        mqLayer.olLayer.params.LAYERS = mqLayer.layers;
        mqLayer.olLayer.queryLayers = result.infolayers;
        return result.changed;
    },
    
    /**
     *  Redraws the source at the map
     */
    _redrawSource: function(mqLayer){
        if(mqLayer.olLayer.layers.length === 0){
            mqLayer.visible(false);
        } else {
            mqLayer.visible(true);
            mqLayer.olLayer.redraw();
        }
    },
    
    /**
     * Checks the source changes, redraws the source at the map and
     * returns the source changes.
     */
    _checkAndRedrawSource: function(source, mqLayer, tochange){
        var changed = this._checkSource(source, mqLayer, tochange);
        this._redrawSource(mqLayer);
        return changed;
    },
    
    /**
     * 
     */
    _checkOutOfScale: function(e){
        var self = this;
        //        window.console && console.log("DefaultModel._checkOutOfScale:", e);
        $.each(this.sourceTree, function(idx, source) {
            var mqLayer = self.map.layersList[source.mqlid];
            var tochange = self.createToChangeObj(source);
            var changed = self._checkAndRedrawSource(source, mqLayer, tochange);
            self.mbMap._trigger('sourceChanged', null, changed);
        });
    },
    
    /**
     *
     */
    _checkOutOfBounds: function(e){
    //        window.console && console.log("DefaultModel._checkOutOfBounds:", e);
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
    _addSourceAtStart: function(mqSource){
        var self = this;
        var source = this.getSource({
            id: mqSource.mapbenderId
        });
        var changed = this.createChangedObj(source);
        var result = {
            layers: [],
            infolayers: [],
            changed: changed
        };
        result = Mapbender.source[source.type].checkLayers(source,
            this.map.olMap.getScale(), this.createToChangeObj(source),result);
        mqSource.layers = result.layers;
        if(mqSource.layers.length === 0){
            mqSource.visibility = false;
        }
        var toadd = this.createChangedObj(source);
        this.mbMap.fireModelEvent({
            name: 'beforesourceadded', 
            value: {
                source: toadd
            }
        });
        var addedMq = this.map.layers(mqSource);
        if(addedMq){
            source.mqlid = addedMq.id;
            source.ollid = addedMq.olLayer.id;
            addedMq.source = this.getSource({
                id: source.id
            });
            this._addLayerMaxExtent(addedMq);
            addedMq.olLayer.events.register("loadstart", addedMq.olLayer, function (e) {
                self._sourceLoadStart(e);
            });
            addedMq.olLayer.events.register("tileloaded", addedMq.olLayer, function (e) {
                var imgEl = $('div[id="'+e.element.id+'"]  .olImageLoadError');
                if(imgEl.length > 0){
                    self._sourceLoadError(e, imgEl);
                } else {
                    self._sourceLoadeEnd(e);
                }
            });        
            this.mbMap.fireModelEvent({
                name: 'sourceAdded', 
                value: {
                    mapquerylayer: toadd
                }
            });
        }
    },
    
    /**
     *
     */
    _sourceLoadStart: function(e){
        this.mbMap.fireModelEvent({
            name: 'sourceloadstart', 
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
    _sourceLoadeEnd: function(e){
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
    _sourceLoadError: function(e, imgEl){
        var source = this.getSource({
            ollid: e.element.id
        });
        var loadError = Mapbender.source[source.type].onLoadError(imgEl, e.element.id, this.map.olMap.getProjectionObject());
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
        if(!this.highlightLayer) {
            this.highlightLayer = this.map.layers({
                type: 'vector',
                label: 'Highlight'
            });
            var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer.olLayer, {
                hover: true,
                onSelect: function(feature) {
                    self._trigger('highlighthoverin', null, {
                        feature: feature
                    });
                },
                onUnselect: function(feature) {
                    self._trigger('highlighthoverout', null, {
                        feature: feature
                    });
                }
            });
            this.map.olMap.addControl(selectControl);
            selectControl.activate();
        }
        var o = $.extend({}, {
            clearFirst: true,
            "goto": true
        }, options);

        // Remove existing features if requested
        if(o.clearFirst) {
            this.highlightLayer.olLayer.removeAllFeatures();
        }

        // Add new highlight features
        this.highlightLayer.olLayer.addFeatures(features);

        // Goto features if requested
        if(o['goto']) {
            var bounds = this.highlightLayer.olLayer.getDataExtent();
            this.map.center({
                box: bounds.toArray()
            });
        }

        this.highlightLayer.bind('featureselected',   function() {
            self._trigger('highlightselected', arguments);
        });
        this.highlightLayer.bind('featureunselected', function() {
            self._trigger('highlightunselected', arguments);
        });
    },
    /**
     *
     */
    highlightOff: function() {
        if(this.highlightLayer) {
            this.highlightLayer.remove();
        }
    },
    
    /**
     *
     */
    addSource: function(sourceDef, before, after){
        if(!this.getSourcePos(sourceDef)){
            if(!before && !after){
                before = {
                    source: this.sourceTree[this.sourceTree.length - 1]
                };
                after = null;
            }
            this.sourceTree.push(sourceDef);
        } else {
            if(!before && !after){
                before = {
                    source: this.sourceTree[this.sourceTree.length - 1]
                };
                after = null;
            }
        }
        var tochange = this.createToChangeObj(sourceDef);
        this.mbMap.fireModelEvent({
            name: 'beforeSourceAdded', 
            value: tochange
        });
        var mapQueryLayer = this.map.layers(this._convertLayerDef(sourceDef));
        if(mapQueryLayer){
            sourceDef.mqlid = mapQueryLayer.id;
            sourceDef.ollid = mapQueryLayer.olLayer.id;
            var changed = this.createChangedObj(tochange.source);
            var result = {
                info: [], 
                changed: changed
            };
            result = Mapbender.source[tochange.source.type].checkInfoLayers(tochange.source,
                this.map.olMap.getScale(), tochange, result);
            mapQueryLayer.olLayer.queryLayers = result.info;
            mapQueryLayer.source = this.getSource({
                id: sourceDef.id
            });
            this._addLayerMaxExtent(mapQueryLayer);
            var added = this.createChangedObj(sourceDef);
            added.before = before;
            added.after = after;
            this.mbMap.fireModelEvent({
                name: 'sourceAdded', 
                value: added
            });
            this._moveSource(sourceDef, before, after);
            this._checkAndRedrawSource(sourceDef, mapQueryLayer, this.createToChangeObj(sourceDef));
        } else {
            this.sourceTree.splice(this.getSourcePos(sourceDef), 1);
        }
    },
    
    /**
     *
     */
    removeSource: function(toremove){
        this.mbMap.fireModelEvent({
            name: 'beforeSourceRemoved', 
            value: {
                toremove: toremove
            }
        });
        var position = this.getSourcePos(toremove.source);
        var length = this.sourceTree.length;
        var toconcat1, toconcat2, sourceRemoved = false;
        if(position && position !== 0 && position !== length - 1
            && this.sourceTree[position - 1].configuration.options.url === this.sourceTree[position + 1].configuration.options.url){
            toconcat1 = this.sourceTree[position - 1];
            toconcat2 = this.sourceTree[position + 1];
        }
        var mqLayer = this.map.layersList[toremove.source.mqlid];
        var removedList;
        for (layerid in toremove.children){
            if(!removedList){
                removedList = {};
            }
            var layer = toremove.children[layerid];
            var removed = Mapbender.source[toremove.source.type].removeLayer(toremove.source, layer);
            removedList[removed.layer.options.id] = removed;
        }
        if(removedList){
            if(!Mapbender.source[toremove.source.type].hasLayers(toremove.source, true)){
                var removedMq = mqLayer.remove();
                if(removedMq){
                    this._removeLayerMaxExtent(mqLayer);
                    sourceRemoved = true;
                    for (var i = 0; i < this.sourceTree.length; i++){
                        if(this.sourceTree[i].id.toString() === toremove.source.id.toString()){
                            this.sourceTree.splice(i, 1);
                            break;
                        }
                    }
                    if(this.map.layersList[toremove.source.mqlid]){
                        delete(this.map.layersList[toremove.source.mqlid]);
                    }
                    var removedObj = this.createChangedObj(toremove.source);
                    this.mbMap.fireModelEvent({
                        name: 'sourceRemoved', 
                        value: removedObj
                    });
                }
            } else {
                var changed = this.createChangedObj(toremove.source);
                var tochange = this.createToChangeObj(toremove.source);
                var result = {
                    info: [], 
                    changed: changed
                };
                result = Mapbender.source[toremove.source.type].checkInfoLayers(toremove.source,
                    this.map.olMap.getScale(), tochange, result);
                mqLayer.olLayer.queryLayers = result.info;
                var removedObj = this.createChangedObj(toremove.source);
                for(removed in removedList){
                    removedObj.children[removed] = removedList[removed];
                }
                this.mbMap.fireModelEvent({
                    name: 'sourceRemoved', 
                    value: removedObj
                });
                this._checkAndRedrawSource(toremove.source, mqLayer);
            }
        } else if(mqLayer){
            var removedMq = mqLayer.remove();
            if(removedMq){
                this._removeLayerMaxExtent(mqLayer);
                sourceRemoved = true;
                for (var i = 0; i < this.sourceTree.length; i++){
                    if(this.sourceTree[i].id.toString() === toremove.source.id.toString()){
                        this.sourceTree.splice(i, 1);
                        break;
                    }
                }
                if(this.map.layersList[toremove.source.mqlid]){
                    delete(this.map.layersList[toremove.source.mqlid]);
                }
                var removedObj = this.createChangedObj(toremove.source);
                this.mbMap.fireModelEvent({
                    name: 'sourceRemoved', 
                    value: removedObj
                });
            }
        }
        if(toconcat1 && toconcat2 && sourceRemoved){
            this._concatSources(toconcat1, toconcat2);
        }
    },
    
    /**
     *
     */
    changeSource: function(tochange){
        if(typeof tochange.type.layerTree !== 'undefined'){
            this._changeFromLayerTree(tochange);
        } else if(tochange.type === "changeOptions"){
            this.mbMap.fireModelEvent({
                name: 'beforeSourceChanged', 
                value: {
                    tochange: tochange
                }
            });
            tochange = Mapbender.source[tochange.source.type].changeOptions(tochange);
            var mqLayer = this.map.layersList[tochange.source.mqlid];
            var result = this._checkSource(tochange.source, mqLayer, tochange);
            this.mbMap.fireModelEvent({
                name: 'sourceChanged', 
                value: result
            });
            this._redrawSource(mqLayer);
        }
    },
    
    _changeFromLayerTree: function(tochange){
        if(tochange.type.layerTree === "select"){
            this.mbMap.fireModelEvent({
                name: 'beforeSourceChanged', 
                value: {
                    tochange: tochange
                }
            });
            var mqLayer = this.map.layersList[tochange.source.mqlid];
            var result = this._checkSource(tochange.source, mqLayer, tochange);
            this.mbMap.fireModelEvent({
                name: 'sourceChanged', 
                value: result
            });
            this._redrawSource(mqLayer);
        } else if(tochange.type.layerTree === "info"){
            var mqLayer = this.map.layersList[tochange.source.mqlid];
            var changed = this.createChangedObj(tochange.source);
            var result = {
                info: [], 
                changed: changed
            };
            result = Mapbender.source[tochange.source.type].checkInfoLayers(tochange.source,
                this.map.olMap.getScale(), tochange, result);
            mqLayer.olLayer.queryLayers = result.info;
        } else if(tochange.type.layerTree === "move"){
            var tomove = tochange.children.tomove;
            var before = tochange.children.before;
            var after = tochange.children.after;
            var layerToMove;
            if(before && after
                && before.source.id.toString() === after.source.id.toString()
                && before.source.id.toString() === tomove.source.id.toString()){
                var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, before.layerId);
                var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, after.layerId);
                layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                this._reorderLayers(tomove.source, layerToMove.layer, beforeLayer.parent, beforeLayer.idx, before, after);
            } else if(before && before.source.id.toString() === tomove.source.id.toString()){
                var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, before.layerId);
                layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                this._reorderLayers(tomove.source, layerToMove.layer, beforeLayer.parent, beforeLayer.idx, before, after);
            } else if(after && after.source.id.toString() === tomove.source.id.toString()){
                var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, after.layerId);
                layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                this._reorderLayers(tomove.source, layerToMove.layer, afterLayer.parent, afterLayer.idx, before, after);
            //            } else if(before && before.source.configuration.options.url === tomove.source.configuration.options.url){
            } else if(before && before.source.origId === tomove.source.origId){
                var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                if(count.simpleCount === 1){ // remove source
                    this._insertLayer(tomove, before, after);
                } else if(count.simpleCount > 1){
                    var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                    var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                    this.addSource(source_new, before, after);
                }
            //            } else if(after && after.source.configuration.options.url === tomove.source.configuration.options.url){
            } else if(after && after.source.origId === tomove.source.origId){
                this._insertLayer(tomove, before, after);
            } else if(before && !after){
                if(!tomove.layerId){ // move source for tree
                    this._moveSource(tomove.source, before, after);
                } else {
                    var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                    if(count.simpleCount === 1){ // remove source
                        this._moveSource(tomove.source, before, after);
                    } else if(count.simpleCount > 1){
                        var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                        var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                        this.addSource(source_new, before, after);
                    }
                }
            } else if(after && !before){ // move source for tree
                if(!tomove.layerId){
                    this._moveSource(tomove.source, before, after);
                } else {
                    var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                    if(count.simpleCount === 1){ // remove source
                        this._moveSource(tomove.source, before, after);
                    } else if(count.simpleCount > 1){
                        var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                        var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                        this.addSource(source_new, before, after);
                    }
                }
            } else {
                if(!tomove.layerId){ // move source for tree
                    this._moveSource(tomove.source, before, after);
                } else {
                    if(after.source.id === before.source.id){
                        var layerToSplit = Mapbender.source[after.source.type].findLayer(after.source, after.layerId);
                        var new_splitted = this._getNewFromList(after.source, layerToSplit.layer);
                        this.addSource(new_splitted, before, null);
                        var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                        if(count.simpleCount === 1){ // move source
                            this._moveSource(tomove.source, before, {
                                source: new_splitted, 
                                layerId: after.layerId
                            });
                        } else if(count.simpleCount > 1){
                            var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                            var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                            this.addSource(source_new, before, {
                                source: new_splitted, 
                                layerId: after.layerId
                            });
                        }
                    } else {
                        var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                        if(count.simpleCount === 1){ // move source
                            var pos = this.getSourcePos(tomove.source);
                            if(pos !== 0 && pos !== (this.sourceTree.length - 1)){
                                var before_cur = this.sourceTree[pos-1];
                                var after_cur =  this.sourceTree[pos+1];
                            }
                            this._moveSource(tomove.source, before, after);
                            if(pos !== 0 && pos !== (this.sourceTree.length - 1)){
                                this._concatSources(before_cur, after_cur);
                            }
                        } else if(count.simpleCount > 1){
                            var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
                            var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                            this.addSource(source_new, before, {
                                source: new_splitted, 
                                layerId: after.layerId
                            });
                        }
                    }
                }
            }
        }
    },
    
    /**
     *
     */
    _concatSources: function(source1, source2){
        var pos1 = this.getSourcePos(source1), pos2 = this.getSourcePos(source2);
        if(source1.configuration.url === source2.configuration.url && Math.abs(pos2 - pos1) === 1){
            var first, second;
            if(pos1 < pos2){
                first = source1;
                second = source2;
            } else {
                first = source2;
                second = source1;
            }
            var layer = second.configuration.children[0].children[0];
            var list = Mapbender.source[second.type].getLayersList(second, layer, true);
            var layers = list.layers;//.reverse();
            if(layers.length > 0){
                var secondRoot = $.extend(true, {}, second.configuration.children[0]);
                var toremove = this.createToChangeObj(second);
                this.mbMap.fireModelEvent({
                    name: 'beforeSourceRemoved', 
                    value: {
                        toremove: toremove
                    }
                });

                var mqLayer = this.map.layersList[toremove.source.mqlid];
                var removedMq = mqLayer.remove();
                if(removedMq){
                    for (var i = 0; i < this.sourceTree.length; i++){
                        if(this.sourceTree[i].id.toString() === toremove.source.id.toString()){
                            this.sourceTree.splice(i, 1);
                            break;
                        }
                    }
                    if(this.map.layersList[toremove.source.mqlid]){
                        delete(this.map.layersList[toremove.source.mqlid]);
                    }
                    var removedObj = this.createChangedObj(toremove.source);
                    this.mbMap.fireModelEvent({
                        name: 'sourceRemoved', 
                        value: removedObj
                    });
                    var toadd = this.createToChangeObj(first);
                    this.mbMap.fireModelEvent({
                        name: 'beforeSourceAdded', 
                        value: toadd
                    });
                    var firstRoot = first.configuration.children[0];
                    firstRoot.children = firstRoot.children.concat(secondRoot.children);
                    //                    var lastid = first.configuration.children[0].children[0].options.id;
                    for(var i = 0; i < layers.length; i++){
                        
                        //                        var toadd = this.createToChangeObj(first);
                        //                        this.mbMap.fireModelEvent({
                        //                            name: 'beforeSourceAdded',
                        //                            value: null
                        //                        });
                        ////                        first.configuration.children[0].children = roottoadd.children.concat(first.configuration.children[0].children);
                        //                        this.mbMap.fireModelEvent({
                        //                            name: 'sourceAdded',
                        //                            value: toadd
                        //                        });
                        
                        //                        var afterLayer = Mapbender.source[first.type].findLayer(first, lastid);
                        //                        var added = Mapbender.source[first.type].addLayer(first, layers[i], afterLayer.parent, afterLayer.idx);
                        var addedobj = this.createChangedObj(first);
                        addedobj.children[layers[i].options.id] = layers[i];
                        //                        addedobj.before = before;
                        //                        addedobj.after = after;
                        this.mbMap.fireModelEvent({
                            name: 'sourceAdded', 
                            value: addedobj
                        });
                    //                        lastid = layers[i].options.id;
                    }
                    this._checkAndRedrawSource(first, this.map.layersList[first.mqlid], this.createToChangeObj(first));
                }
            }
        } 
    },
    
    /**
     *
     */
    _concatSourcesI: function(source1, source2){
        if(source1.configuration.url === source2.configuration.url){
            var layer = source2.configuration.children[0].children[0];
            var list = Mapbender.source[source2.type].getLayersList(source2, layer, true);
            var layers = list.layers;//.reverse();
            if(layers.length > 0){
                var toremove = this.createToChangeObj(source2);
                var toadd = this.createToChangeObj(source1);
                for(var i = 0; i < layers.length; i++){
                    toremove.children[layers[i].options.id] = layers[i];
                    toadd.children[layers[i].options.id] = layers[i];
                    Mapbender.source[source2.type].removeLayer(source2, layers[i]);
                    source1.configuration.children[0].children.push(layers[i]);
                }
                this.mbMap.fireModelEvent({
                    name: 'beforeSourceRemoved', 
                    value: {
                        toremove: toremove
                    }
                });

                var mqLayer = this.map.layersList[toremove.source.mqlid];
                var removedMq = mqLayer.remove();
                if(removedMq){
                    for (var i = 0; i < this.sourceTree.length; i++){
                        if(this.sourceTree[i].id.toString() === toremove.source.id.toString()){
                            this.sourceTree.splice(i, 1);
                            break;
                        }
                    }
                    if(this.map.layersList[toremove.source.mqlid]){
                        delete(this.map.layersList[toremove.source.mqlid]);
                    }
                    var removedObj = this.createChangedObj(toremove.source);
                    this.mbMap.fireModelEvent({
                        name: 'sourceRemoved', 
                        value: removedObj
                    });
                }
                this.mbMap.fireModelEvent({
                    name: 'beforeSourceAdded',
                    value: toadd
                });
                this._checkAndRedrawSource(source1, this.map.layersList[source1.mqlid], this.createToChangeObj(source1));
                this.mbMap.fireModelEvent({
                    name: 'sourceAdded',
                    value: toadd
                });
            }
        } 
    },
    
    /**
     *
     */
    _reorderLayers: function(source, layerToMove, targetParent, targetIdx, before, after){ // 
        var tomove = this.createToChangeObj(source);
        this.mbMap.fireModelEvent({
            name: 'beforeSourceMoved',
            value: tomove
        });
        var removed = Mapbender.source[source.type].removeLayer(source, layerToMove);
        //        var removedObj = this.createChangedObj(source);
        //        removedObj.children[removed.layer.options.id] = removed.layer;
        //        this.mbMap.fireModelEvent({
        //            name: 'sourceRemoved', 
        //            value: removedObj
        //        });

        var added = Mapbender.source[source.type].addLayer(source, removed.layer, targetParent, targetIdx);
        //        var addedObj = this.createChangedObj(source);
        //        addedObj.children[added.options.id] = added;
        //        this.mbMap.fireModelEvent({
        //            name: 'sourceAdded', 
        //            value: addedObj
        //        });
        var changed = this.createChangedObj(source);
        changed.children[added.options.id] = added;
        changed.layerId = added.options.id;
        changed.after = after;
        changed.before = before;
        this.mbMap.fireModelEvent({
            name: 'sourceMoved',
            value: changed
        });
        this._checkAndRedrawSource(source, this.map.layersList[source.mqlid]);
    },
    
    /**
     *
     */
    _insertLayer: function(tomove, before, after){
        var layerToRemove = Mapbender.source[tomove.source.type].findLayer(tomove.source, tomove.layerId);
        var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
        var addedobj, tochange, toremove, removed;
        if(count.simpleCount === 1 && layerToRemove){
            toremove = this.createToChangeObj(tomove.source);
            toremove.children[layerToRemove.layer.options.id] = layerToRemove.layer;
            this.removeSource(toremove);
            removed = layerToRemove;
        } else if(count.simpleCount > 1 && layerToRemove){
            removed = Mapbender.source[tomove.source.type].removeLayer(tomove.source, layerToRemove.layer);
            this._checkAndRedrawSource(tomove.source, this.map.layersList[tomove.source.mqlid], this.createToChangeObj(tomove.source));
        } else {
            return;
        }
        if(before){
            tochange = this.createToChangeObj(before.source);
            this.mbMap.fireModelEvent({
                name: 'beforeSourceAdded', 
                value: tochange
            });
            var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, before.layerId);
            var added = Mapbender.source[before.source.type].addLayer(before.source, removed.layer, beforeLayer.parent, beforeLayer.idx + 1);
            addedobj = this.createChangedObj(before.source);
            //            addedobj = this.createChangedObj(after.source); ??????
            addedobj.children[added.options.id] = added;
            addedobj.before = before;
            addedobj.after = after;
            this.mbMap.fireModelEvent({
                name: 'sourceAdded', 
                value: addedobj
            });
            this._checkAndRedrawSource(before.source, this.map.layersList[before.source.mqlid], this.createToChangeObj(before.source));
        } else if(after){
            tochange = this.createToChangeObj(after.source);
            this.mbMap.fireModelEvent({
                name: 'beforeSourceAdded', 
                value: tochange
            });
            var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, after.layerId);
            var added = Mapbender.source[after.source.type].addLayer(after.source, removed.layer, afterLayer.parent, afterLayer.idx);
            addedobj = this.createChangedObj(after.source);
            addedobj.children[added.options.id] = added;
            addedobj.before = before;
            addedobj.after = after;
            this.mbMap.fireModelEvent({
                name: 'sourceAdded', 
                value: addedobj
            });
            this._checkAndRedrawSource(after.source, this.map.layersList[after.source.mqlid], this.createToChangeObj(after.source));
        }
         
    },
    
    /**
     *
     */
    _moveSource: function(source, before, after){
        var old_pos = this.getSourcePos(source);
        var new_pos;
        if(before && before.source){
            new_pos = this.getSourcePos(before.source) + 1;
        } else if(after && after.source){
            new_pos = this.getSourcePos(after.source);
        }
        if(old_pos === new_pos)
            return;
        this.sourceTree.splice(new_pos, 0, this.sourceTree.splice(old_pos, 1)[0]);
        var mqL = this.map.layersList[source.mqlid];
        if(old_pos > new_pos){
            mqL.down(Math.abs(old_pos - new_pos));
        } else {
            mqL.up(Math.abs(old_pos - new_pos));
        }
        var changed = this.createChangedObj(source);
        changed.after = after;
        changed.before = before;
        this.mbMap.fireModelEvent({
            name: 'sourceMoved',
            value: changed
        });
    },
    
    /**
     *
     */
    _createSourceFromLayer: function(source, layerToMove){
        var removed = Mapbender.source[source.type].removeLayer(source, layerToMove);
        var removedObj = this.createChangedObj(source);
        removedObj.children[removed.layer.options.id] = removed.layer;
        this.mbMap.fireModelEvent({
            name: 'sourceRemoved', 
            value: removedObj
        });
        this._checkAndRedrawSource(source, this.map.layersList[source.mqlid], this.createToChangeObj(source));
            
        var source_new = $.extend(true, {}, source);
        source_new.id = this.generateSourceId();
        source_new.configuration.children[0].children = [removed.layer];
        return source_new;
    },
    
    /**
     *
     */
    _getNewFromList: function(source, layer){
        var list = Mapbender.source[source.type].getLayersList(source, layer, true);
        var source_new = $.extend(true, {}, source);
        source_new.id = this.generateSourceId();
        source_new.configuration.children[0].children = [];
        var layers = list.layers;//.reverse();
        if(layers.length > 0){
            var removed = this.createChangedObj(source);
            for(var i = 0; i < layers.length; i++){
                removed.children[layers[i].options.id] = layers[i];
                Mapbender.source[source.type].removeLayer(source, layers[i]);
                source_new.configuration.children[0].children.push(layers[i]);
            }
            //            source_new.configuration.children[0].children = source_new.configuration.children[0].children.concat(layers[i]);
            this.mbMap.fireModelEvent({
                name: 'sourceRemoved', 
                value: removed
            });
            this._checkAndRedrawSource(source, this.map.layersList[source.mqlid], this.createToChangeObj(source));
            return source_new;
        } else {
            alert ("source:"+source.id+"cannot be splitted");
            return null;
        }
    },
    
    /*
     * Changes the map's projection.
     */
    _changeProjection: function(event, srs){
        this.changeProjection(srs);
    },
    
    /*
     * Changes the map's projection.
     */
    changeProjection: function(srs){
        var self = this;
        var oldProj = this.map.olMap.getProjectionObject();
        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
        this.map.olMap.projection = srs.projection;
        this.map.olMap.displayProjection= srs.projection;
        this.map.olMap.units = srs.projection.proj.units;

        this.map.olMap.maxExtent = this._transformExtent(
            this.mapMaxExtent, srs.projection);
        $.each(self.map.olMap.layers, function(idx, layer){
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if(!self.layersMaxExtent[layer.id]){
                self._addLayerMaxExtent(layer);
            }
            if(layer.maxExtent && layer.maxExtent != self.map.olMap.maxExtent){
                layer.maxExtent = self._transformExtent(
                    self.layersMaxExtent[layer.id], srs.projection);
            }

            layer.initResolutions();
        });
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
        this.mbMap._trigger('srsChanged', null, {
            projection: srs.projection
        });
    },

    /*
     * Transforms an extent into destProjection projection.
     */
    _transformExtent: function(extentObj, destProjection){
        if(extentObj.extent != null){
            if(extentObj.projection.projCode == destProjection.projCode){
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
        if(layer.olLayer) {
            layer = layer.olLayer;
        }
        if(!this.layersMaxExtent[layer.id]){
            var proj,maxExt;
            if(layer.options.configuration){
                var bboxes = layer.options.configuration.configuration.options.bbox;
                /* TODO? add "if" for source type 'wms' etc. */
                for(srs in bboxes){
                    if(this.getProj(srs)){
                        proj = this.getProj(srs);
                        maxExt = OpenLayers.Bounds.fromArray(bboxes[srs]);
                        break;
                    }
                }
            }
            if(!proj || !maxExt){
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
        if(layer.olLayer) {
            layer = layer.olLayer;
        }
        if(this.layersMaxExtent[layer.id]){
            delete(this.layersMaxExtent[layer.id]);
        }
    }
    
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);
