var Mapbender = Mapbender || {};

Mapbender.ElementRegistry = function() {
    this.readyElements = {};
    this.readyCallbacks = {};

    this.onElementReady = function(targetId, callback) {
        if(true === callback) {
            // Register as ready
            this.readyElements[targetId] = true;

            // Execute all callbacks registered so far
            if('undefined' !== this.readyCallbacks[targetId]) {
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
                    widget = data.init.split('.'),
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

Mapbender.checkTarget = function(widgetName, target){
    if(target === null || typeof(target) === 'undefined'
        || new String(target).replace(/^\s+|\s+$/g, '') === ""
        || !$('#' + target)){
        Mapbender.error(widgetName + ': a target element is not defined.');
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
    proj: null,
    srsDefs: null,
    mapMaxExtent: null,
    layersOrigExtents: {},
    rootLayers: [],
    
    
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
        
        function addSubs(layer){
            if(layer.sublayers) {
                $.each(layer.sublayers, function(idx, val) {
                    layers.push(val);
                    addSubs(val);
                });
            }
        }
        $.each(Mapbender.configuration.layersets[this.mbMap.options.layerset].reverse(), function(idx, defArr) {
            $.each(defArr, function(idx, layerDef) {
                hasLayers = true;
                layerDef.id = idx;
                layers.push(self._convertLayerDef.call(self, layerDef));
                self.rootLayers.push(layers[layers.length-1]);
                addSubs(layers[layers.length-1]);
                allOverlays = allOverlays && (layerDef.configuration.baselayer !== true);
            });
        });
        
        if(!hasLayers){
            Mapbender.error('The element "map" has no layer.');
        }
        
        var controls = this.mbMap.addNavigation();

        var mapOptions = {
                maxExtent: this._transformExtent(this.mapMaxExtent, this.proj).toArray(),
                zoomToMaxExtent: false,
                maxResolution: this.mbMap.options.maxResolution,
                numZoomLevels: this.mbMap.options.numZoomLevels,
                projection: this.proj,
                displayProjection: this.proj,
                units: this.proj.proj.units,
                allOverlays: allOverlays,
                theme: null
        };
    
        if(controls.length !== 0){
            $.extend(mapOptions, { controls: controls });
        }

        if(this.mbMap.options.scales) {
            $.extend(mapOptions, { scales: this.mbMap.options.scales });
        }
        //
        $(this.mbMap.element).mapQuery(mapOptions);
        this.map = $(this.mbMap.element).data('mapQuery');
        
        // We have to add our listeners to the map before adding layers...
        // This might change in the future, when the MapQuery map accepts
        // listeners as options
        this.map.bind('mqAddLayer', $.proxy(this._onAddLayer, this));
        this.map.bind('mqRemoveLayer', $.proxy(this._onRemoveLayer, this));

        this.map.layers(layers);

        if(poi){
            this.map.center({
                position: poi.position
            });
            if(poi.scale) {
                this.zoomToScale(poi.scale);
            }
            if(poi.label) {
                var popup = new OpenLayers.Popup.FramedCloud('chicken',
                    poi.position,
                    null,
                    poi.label,
                    null,
                    true,
                    function() {
                        self.removePopup(this);
                        this.destroy();
                    });
                this.addPopup(popup);
            }
        } else if(bbox){
            this.map.center({
                box: bbox.extent.toArray()
            });
        } else {
            this.map.center({
                box: start_extent.extent.toArray()
            });
        }
        var a = start_extent.extent.toArray();
        var b = 0;
        
    },
    
    getProj: function(srscode){
        var proj = null;
        for(var i = 0; i < this.srsDefs.length; i++){
            if(this.srsDefs[i].name === srscode){
                proj = new OpenLayers.Projection(this.srsDefs[i].name);
                if(proj.projCode === 'EPSG:4326') {
                    proj.proj.units = 'degrees';
                }
//                proj = new Proj4js.Proj(this.srsDefs[i].name);
//                if(proj.srsCode === 'EPSG:4326') {
//                    proj.units = 'degrees';
//                }
                return proj;
            }
        }
        return proj;
    },
    
    addSource: function(source, parent, position){
        
    },
    
    removeSource: function(source){
        
    },
    
    
    
    changeSource: function(source, params){
        
    },
    
    /**
     * Moves a layer up (direction == true) or down (direction == false) on the same level in the layer hierarchy.
     */
    move: function(id, direction) {
        var self = this;
        function _move(list) {
            var idx = null;
            for(var i = 0; i < list.length; ++i) {
                if(list[i].mapbenderId === id) {
                    idx = i;
                    break;
                }
            }
            if(idx !== null) {
                if(direction && idx > 0) {
                    var lay = list[idx];
                    list[idx] = list[idx-1];
                    list[idx-1] = lay;
                    lay = self.layerById(id);
                    self.rebuildStacking();
                } else if(!direction && idx < list.length - 1) {
                    var lay = list[idx];
                    list[idx] = list[idx+1];
                    list[idx+1] = lay;
                    lay = self.layerById(id);
                    self.rebuildStacking();
                }
            } else {
                for(i = 0; i < list.length; ++i) {
                    if(list[i].sublayers) {
                        _move(list[i].sublayers);
                    }
                }
            }
        }
        _move(this.rootLayers);
    },
    
    removeById: function(id) {
        var self = this;
        function _remove(_, layer) {
            self.layerById(layer.mapbenderId).remove();
            if(layer.sublayers) {
                $.each(layer.sublayers, _remove);
            }
        }

        $.each(this.rootLayers, function(idx, layer) {
            if(layer.mapbenderId === id) {
                _remove(null, layer);
            }
        });
    },

    /**
     * Searches for a MapQuery layer by it's Mapbender id.
     * Returns the layer or null if not found.
     */
    layerById: function(id) {
        var layer = null;
        $.each(this.map.layers(), function(idx, mqLayer) {
            if(mqLayer.options.mapbenderId === id) {
                layer = mqLayer;
                return false;
            }
        });
        return layer;
    },

    _convertLayerDef: function(layerDef) {
        var self = this;
        if(typeof Mapbender.layer[layerDef.type] !== 'object'
            && typeof Mapbender.layer[layerDef.type].create !== 'function') {
            throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
        // TODO object should be cleaned up
        var l = $.extend({}, Mapbender.layer[layerDef.type].create(layerDef), {
            mapbenderId: layerDef.id, 
            configuration: layerDef
        });
        if(layerDef.configuration.sublayers) {
            l.sublayers = [];
            $.each(layerDef.configuration.sublayers, function(idx, val) {
                l.sublayers.push(self._convertLayerDef({
                    id: idx, 
                    type: 'wms', 
                    configuration: val
                }));
            });
        }
        return l;
    },
    
    center: function(options) {
        this.map.center(options);
    },

    highlight: function(features, options) {
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

    layer: function(layerDef) {
        var l = this._convertLayerDef(layerDef)
        this.rootLayers.push(l);
        this.map.layers(l);
    },

    // untested!
    appendLayer: function(layerDef, parentId) {
        if(!parentId) {
            this.layer(layerDef);
            return;
        }
        var self = this;
        var newLayer = this._convertLayerDef(layerDef);
        this.map.layers(newLayer);

        function _append(_, layer) {
            if(layer.mapbenderId === parentId) {
                if(!layer.sublayers) layer.sublayers = [];
                layer.sublayers.push(newLayer);
            } else {
                if(layer.sublayers) {
                    $.each(layer.sublayers, _append);
                }
            }
        }

        $.each(this.rootLayers, _append);

        this.rebuildStacking();
    },

    /**
     * Insert a layer before or after a sibling. Untested!
     */
    insert: function(layerDef, siblingId, before) {
        var self = this;
        var newLayer = this._convertLayerDef(layerDef);
        this.map.layers(newLayer);

        function _insert(list) {
            for(var i = 0; i < list.length; ++i) {
                if(list[i].mapbenderId === siblingId) {
                    if(before) {
                        list.splice(i, 0, newLayer);
                        break;
                    } else {
                        list.splice(i+1, 0, newLayer);
                        break;
                    }
                } else {
                    if(list[i].sublayers) {
                        _insert(list[i].sublayers);
                    }
                }
            }
        }

        _insert(this.rootLayers);

        this.rebuildStacking();
    },

    rebuildStacking: function() {
        var self = this;
        var pos = 0;
        function _rebuild(layer){
            if(layer.sublayers) {
                $.each(layer.sublayers, function(idx, val) {
                    self.layerById(val.mapbenderId).position(pos++);
                    _rebuild(val);
                });
            }
        }

        for(var i = 0; i < this.rootLayers.length; ++i) {
            self.layerById(this.rootLayers[i].mapbenderId).position(pos++);
            _rebuild(this.rootLayers[i]);
        }
    },
    
    /*
     * Sets a new map's projection.
     */
    changeProjection: function(event, srs){
        var self = this;
        var oldProj = this.map.olMap.getProjectionObject();
        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
        this.map.olMap.projection = srs.projection;
        this.map.olMap.displayProjection= srs.projection;
        this.map.olMap.units = srs.projection.proj.units;

        this.map.olMap.maxExtent = this._transformExtent(
            this.mapMaxExtent, srs.projection);
        $.each(self.map.olMap.layers, function(idx, layer){
            //            if(layer.isBaseLayer){
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if(!self.layersOrigExtents[layer.id]){
                self._addOrigLayerExtent(layer);
            }
            if(layer.maxExtent && layer.maxExtent != self.map.olMap.maxExtent){
                layer.maxExtent = self._transformExtent(
                    self.layersOrigExtents[layer.id].max, srs.projection);
            }

            if(layer.minExtent){
                layer.minExtent = self._transformExtent(
                    self.layersOrigExtents[layer.id].minExtent, srs.projection);
            }
            layer.initResolutions();
        //            }
        });
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
        this._trigger('srsChanged', null, {
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
     * Listen to newly added layers in the MapQuery object
     */
    _onAddLayer: function(event, layer) {
        var listener = Mapbender.layer[layer.olLayer.type].onLoadStart;
        if(typeof listener === 'function') {
            listener.call(layer);
            this._addOrigLayerExtent(layer);
        }
    },
    
    /**
     * Listen to removed layer in the MapQuery object
     */
    _onRemoveLayer: function(event, layer) {
        this._removeOrigLayerExtent(layer);
    },
    
    /**
     * Adds a layer's original extent into the widget layersOrigExtent.
     */
    _addOrigLayerExtent: function(layer) {
        if(layer.olLayer) {
            layer = layer.olLayer;
        }
        if(!this.layersOrigExtents[layer.id]){
//            var extProjection = new OpenLayers.Projection(this.options.srs);
//            if(extProjection.projCode === 'EPSG:4326') {
//                extProjection.proj.units = 'degrees';
//            }
            this.layersOrigExtents[layer.id] = {
                max: {
                    projection: this.proj,
                    extent: layer.maxExtent ? layer.maxExtent.clone() : null
                },
                min: {
                    projection: this.proj,
                    extent: layer.minExtent ? layer.minExtent.clone() : null
                }
            };
        }
    },
    
    /**
     * Removes a layer's origin extent from the widget layersOrigExtent.
     */
    _removeOrigLayerExtent: function(layer) {
        if(layer.olLayer) {
            layer = layer.olLayer;
        }
        if(this.layersOrigExtent[layer.id]){
            delete(this.layersOrigExtent[layer.id]);
        }
    }
    
};

// This calls on document.ready and won't be called when inserted dynamically
// into a existing page. In such case, Mapbender.setup has to be called
// explicitely, see mapbender.application.json.js
$(Mapbender.setup);
