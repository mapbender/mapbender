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
    layersMaxExtent: {},
    highlightLayer: null,
    baseId: 0,

    init: function(mbMap){
        this.mbMap = mbMap;
        var self = this;

        this.srsDefs = this.mbMap.options.srsDefs;
        for(var i = 0; i < this.srsDefs.length; i++){
            Proj4js.defs[this.srsDefs[i].name] = this.srsDefs[i].definition;
        }

        if(typeof(this.mbMap.options.dpi) !== 'undefined'){
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
        var pois = [],
            bbox = null;
        if(this.mbMap.options.extra && this.mbMap.options.extra['bbox']){
            bbox = this.mbMap.options.extra['bbox'] ?
                OpenLayers.Bounds.fromArray(this.mbMap.options.extra['bbox']) :
                start_extent;
        }
        if(this.mbMap.options.extra && this.mbMap.options.extra['pois']){
            $.each(this.mbMap.options.extra['pois'], function(idx, poi){
                pois.push({
                    position: new OpenLayers.LonLat(poi.x, poi.y),
                    label: poi.label,
                    scale: poi.scale
                });
            });
        }
        var mapOptions = {
            maxExtent: this._transformExtent(this.mapMaxExtent, this.proj).toArray(),
            zoomToMaxExtent: false,
            maxResolution: this.mbMap.options.maxResolution,
            numZoomLevels: this.mbMap.options.numZoomLevels,
            projection: this.proj,
            displayProjection: this.proj,
            units: this.proj.proj.units,
            allOverlays: true,
            theme: null,
            layers: [{type: "wms", name: "FAKE", isBaseLayer: true, url: "http://localhost", visibility: false}]
        };

        if(this.mbMap.options.scales){
            $.extend(mapOptions, {
                scales: this.mbMap.options.scales
            });
        }

        $(this.mbMap.element).mapQuery(mapOptions);
        this.map = $(this.mbMap.element).data('mapQuery');
        this.map.layersList.mapquery0.olLayer.isBaseLayer = true;
        this.map.olMap.setBaseLayer(this.map.layersList.mapquery0);
        this._addLayerMaxExtent(this.map.layersList.mapquery0);
        $.each(Mapbender.configuration.layersets[this.mbMap.options.layerset].reverse(), function(lsidx, defArr){
            $.each(defArr, function(idx, layerDef){
                layerDef['origId'] = idx;
                self.addSource({add: {sourceDef: layerDef, before: null, after: null}});
            });
        });

        var poiBox = null,
            poiMarkerLayer = null,
            poiIcon = null,
            poiPopups = [];
        if(pois.length){
            poiMarkerLayer = new OpenLayers.Layer.Markers();
            poiIcon = new OpenLayers.Icon(
                Mapbender.configuration.application.urls.asset +
                this.mbMap.options.poiIcon.image, {
                w: this.mbMap.options.poiIcon.width,
                h: this.mbMap.options.poiIcon.height
            }, {
                x: this.mbMap.options.poiIcon.xoffset,
                y: this.mbMap.options.poiIcon.yoffset
            });
        }
        $.each(pois, function(idx, poi){
            if(!bbox){
                if(!poiBox)
                    poiBox = new OpenLayers.Bounds();
                poiBox.extend(poi.position);
            }

            // Marker
            poiMarkerLayer.addMarker(new OpenLayers.Marker(
                poi.position,
                poiIcon.clone()));

            if(poi.label){
                poiPopups.push(new OpenLayers.Popup.FramedCloud('chicken',
                    poi.position,
                    null,
                    poi.label,
                    null,
                    true,
                    function(){
                        self.mbMap.removePopup(this);
                        this.destroy();
                    }));
            }
        });
        if(poiMarkerLayer){
            this.map.olMap.addLayer(poiMarkerLayer);
        }
        var centered = false;
        if(poiBox){
            if(pois.length == 1 && pois[0].scale){
                this.map.olMap.setCenter(pois[0].position);
                this.map.olMap.zoomToScale(pois[0].scale, true);
            }else{
                this.map.olMap.zoomToExtent(poiBox.scale(1.5));
            }
            centered = true;
        }

        if(bbox){
            this.center({
                box: bbox.toArray()
            });
        }else{
            if(!centered){
                this.center({
                    box: start_extent.extent ? start_extent.extent.toArray() : start_extent.toArray()
                });
            }
        }

        // Popups have to be set after map extent initialization
        $.each(poiPopups, function(idx, popup){
            self.map.olMap.addPopup(popup);
        });

        $(document).bind('mbsrsselectorsrsswitched', $.proxy(self._changeProjection, self));
        this.map.olMap.events.register('zoomend', this, $.proxy(this._checkOutOfScale, this));
        this.map.olMap.events.register('movestart', this, $.proxy(this._checkOutOfBounds, this));
    },
    getProj: function(srscode){
        var proj = null;
        for(var i = 0; i < this.srsDefs.length; i++){
            if(this.srsDefs[i].name === srscode){
                proj = new OpenLayers.Projection(this.srsDefs[i].name);
                if(proj.projCode === 'EPSG:4326'){
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
    _convertLayerDef: function(layerDef){
        if(typeof Mapbender.source[layerDef.type] !== 'object'
            && typeof Mapbender.source[layerDef.type].create !== 'function'){
            throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
        // TODO object should be cleaned up
        var l = $.extend({}, Mapbender.source[layerDef.type].create(layerDef), {mapbenderId: layerDef.id});
        return l;
    },
    generateSourceId: function(){
        this.baseId++;
        return this.baseId.toString();
    },
    getMapState: function(){
        var proj = this.map.olMap.getProjectionObject();
        var ext = this.map.olMap.getExtent();
        var maxExt = this.map.olMap.getMaxExtent();
        var size = this.map.olMap.getSize();
        var state = {
            window: {width: size.w, height: size.h},
            extent: {srs: proj.projCode, minx: ext.left, miny: ext.bottom, maxx: ext.right, maxy: ext.top},
            maxextent: {srs: proj.projCode, minx: maxExt.left, miny: maxExt.bottom, maxx: maxExt.right, maxy: maxExt.top},
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
    /**
     * Returns a source from a sourceTree
     * @param {Object} idObject in form of:
     * - source id -> {id: MYSOURCEID}
     * - mapqyery id -> {mqlid: MYSOURCEMAPQUERYID}
     * - openlayers id -> {ollid: MYSOURCEOPENLAYERSID}
     * - origin id -> {ollid: MYSOURCEORIGINID}
     * @returns source from a sourceTree or null
     */
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
    findSource: function(options){
        var sources = [];
        function findSource(object, options){
            var found = null;
            for(key in options){
                if(object[key]){
                    if(typeof object[key] === 'object'){
                        var res = findSource(object[key], options[key]);
                        if(found === null)
                            found = res;
                        else
                            found = found && res;

                    }else{
                        return object[key] === options[key]
                    }
                }
            }
            return found;
        }
        ;
        for(var i = 0; i < this.sourceTree.length; i++){
            var source = this.sourceTree[i];
            if(findSource(source, options))
                sources.push(source);
        }
        return sources;
    },
    /**
     * Returns the source's position
     */
    getSourcePos: function(source){
        if(source){
            for(var i = 0; i < this.sourceTree.length; i++){
                if(this.sourceTree[i].id.toString() === source.id.toString()){
                    return i;
                }
            }
        }else
            return null;
    },
    /**
     * Returns the source by id
     */
    getSourceLayerById: function(source, layerId){
        if(source && layerId){
            return Mapbender.source[source.type].findLayer(source, {id: layerId});
        }else{
            return null;
        }
    },
    /**
     *Creates a "tochange" object
     */
    createToChange: function(idxKey, idxValue){
        var tochange = {sourceIdx: {}};
        tochange.sourceIdx[idxKey] = idxValue;
        if(this.getSource(tochange.sourceIdx))
            return tochange;
        else
            return null;
    },
    /**
     *Creates a "changed" object
     */
    createChangedObj: function(source){
        if(!source || !source.id){
            return null;
        }
        return {source: source, children: {}};
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
    _checkSource: function(toChangeOpts){
        var source = this.getSource(toChangeOpts.sourceIdx);
        var result = Mapbender.source[source.type].changeOptions(source, this.map.olMap.getScale(), toChangeOpts);
        var mqLayer = this.map.layersList[source.mqlid];
        mqLayer.olLayer.params.LAYERS = result.layers;
        mqLayer.olLayer.queryLayers = result.infolayers;
        return result.changed;
    },
    /**
     *  Redraws the source at the map
     */
    _redrawSource: function(toChangeOpts){
        var source = this.getSource(toChangeOpts.sourceIdx);
        var mqLayer = this.map.layersList[source.mqlid];
        if(mqLayer.olLayer.params.LAYERS.length === 0){
            mqLayer.visible(false);
        }else{
            mqLayer.visible(true);
            mqLayer.olLayer.redraw();
        }
    },
    checkAndRedrawSource: function(toChangeOpts){
        var source = this.getSource(toChangeOpts.sourceIdx);
        var mqLayer = this.map.layersList[source.mqlid];
        if(mqLayer){
            var changed = this._checkAndRedrawSource(source, mqLayer, tochange);
            this._redrawSource(mqLayer);
            return changed;
        }
        return null;
    },
    /**
     * Checks the source changes, redraws the source at the map and
     * returns the source changes.
     */
    _checkAndRedrawSource: function(toChangeOpts){
        var changed = this._checkSource(toChangeOpts);
        this._redrawSource(toChangeOpts);
        return changed;
    },
    /**
     *
     */
    _checkOutOfScale: function(e){
        var self = this;
        $.each(self.sourceTree, function(idx, source){
            self._checkAndRedrawSource({sourceIdx: {id: source.id}, options: {children: {}}});
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
    center: function(options){
        this.map.center(options);
    },
    /**
     *
     */
    _sourceLoadStart: function(e){
        this.mbMap.fireModelEvent({name: 'sourceloadstart', value: {source: this.getSource({ollid: e.element.id})}});
    },
    /**
     *
     */
    _sourceLoadeEnd: function(e){
        this.mbMap.fireModelEvent({name: 'sourceloadend', value: {source: this.getSource({ollid: e.element.id})}});
    },
    /**
     *
     */
    _sourceLoadError: function(e, imgEl){
        var source = this.getSource({ollid: e.element.id});
        var loadError = Mapbender.source[source.type].onLoadError(imgEl, e.element.id, this.map.olMap.getProjectionObject());
        this.mbMap.fireModelEvent({name: 'sourceloaderror', value: {source: source, error: loadError}});
    },
    /**
     *
     */
    highlightOn: function(features, options){
        var self = this;
        if(!this.highlightLayer){
            this.highlightLayer = this.map.layers({type: 'vector', label: 'Highlight'});
            var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer.olLayer, {
                hover: true,
                onSelect: function(feature){
                    self.mbMap._trigger('highlighthoverin', null, {feature: feature});
                },
                onUnselect: function(feature){
                    self.mbMap._trigger('highlighthoverout', null, {feature: feature});
                }
            });
            this.map.olMap.addControl(selectControl);
            selectControl.activate();
        }
        var o = $.extend({}, {clearFirst: true, "goto": true}, options);
        // Remove existing features if requested
        if(o.clearFirst){
            this.highlightLayer.olLayer.removeAllFeatures();
        }
        // Add new highlight features
        this.highlightLayer.olLayer.addFeatures(features);
        // Goto features if requested
        if(o['goto']){
            var bounds = this.highlightLayer.olLayer.getDataExtent();
            this.map.center({box: bounds.toArray()});
        }
        this.highlightLayer.bind('featureselected', function(){
            self.mbMap._trigger('highlightselected', arguments);
        });
        this.highlightLayer.bind('featureunselected', function(){
            self.mbMap._trigger('highlightunselected', arguments);
        });
    },
    /**
     *
     */
    highlightOff: function(){
        if(this.highlightLayer)
            this.highlightLayer.remove();
    },
    /**
     *
     */
    addSource: function(addOptions){
        var self = this;
        if(addOptions.add){
            var sourceDef = addOptions.add.sourceDef, before = addOptions.add.before, after = addOptions.add.after;
            sourceDef.id = this.generateSourceId();

            if(typeof sourceDef.origId === 'undefined')
                sourceDef.origId = sourceDef.id;
            this.mbMap.fireModelEvent({
                name: 'beforeSourceAdded',
                value: {source: sourceDef, before: before, after: after}
            });
            if(!this.getSourcePos(sourceDef)){
                if(!before && !after){
                    before = {source: this.sourceTree[this.sourceTree.length - 1]};
                    after = null;
                }
                this.sourceTree.push(sourceDef);
            }else{
                if(!before && !after){
                    before = {source: this.sourceTree[this.sourceTree.length - 1]};
                    after = null;
                }
            }
            var source = sourceDef;
            var mapQueryLayer = this.map.layers(this._convertLayerDef(source));
            if(mapQueryLayer){
                source.mqlid = mapQueryLayer.id;
                source.ollid = mapQueryLayer.olLayer.id;
                mapQueryLayer.source = source;
                this._addLayerMaxExtent(mapQueryLayer);
                mapQueryLayer.olLayer.events.register("loadstart", mapQueryLayer.olLayer, function(e){
                    self._sourceLoadStart(e);
                });
                mapQueryLayer.olLayer.events.register("tileloaded", mapQueryLayer.olLayer, function(e){
                    var imgEl = $('div[id="' + e.element.id + '"]  .olImageLoadError');
                    if(imgEl.length > 0){
                        self._sourceLoadError(e, imgEl);
                    }else{
                        self._sourceLoadeEnd(e);
                    }
                });
                this.mbMap.fireModelEvent({name: 'sourceAdded', value: {added: {source: source, before: before, after: after}}});
                if(after)
                    this._moveSource(source, before, after);
                this._checkAndRedrawSource({sourceIdx: {id: source.id}, options: {children: {}}});
            }else
                this.sourceTree.splice(this.getSourcePos(sourceDef), 1);
        }else{
            window.console && console.error("CHECK options at model.addSource");
        }
    },
    /**
     *
     */
    removeSource: function(options){
        if(options.remove.sourceIdx){
            var sourceToRemove = this.getSource(options.remove.sourceIdx);
            if(sourceToRemove){
                this.mbMap.fireModelEvent({name: 'beforeSourceRemoved', value: {source: sourceToRemove}});
                var mqLayer = this.map.layersList[sourceToRemove.mqlid];
                if(mqLayer){
                    if(mqLayer.olLayer instanceof OpenLayers.Layer.Grid){
                        mqLayer.olLayer.clearGrid();
                    }
                    var removedMq = mqLayer.remove();
                    if(removedMq){
                        this._removeLayerMaxExtent(mqLayer);
                        for(var i = 0; i < this.sourceTree.length; i++){
                            if(this.sourceTree[i].id.toString() === sourceToRemove.id.toString()){
                                this.sourceTree.splice(i, 1);
                                break;
                            }
                        }
                        if(this.map.layersList[sourceToRemove.mqlid]){
                            delete(this.map.layersList[sourceToRemove.mqlid]);
                        }
                        this.mbMap.fireModelEvent({name: 'sourceRemoved', value: {source: sourceToRemove}});
                    }
                }
            }
        }else{
            window.console && console.error("CHECK options at model.addSource");
        }
    },
    /**
     *
     */
    removeSources: function(keepSources){
        var toRemoveArr = [];
        for(var i = 0; i < this.sourceTree.length; i++){
            var source = this.sourceTree[i];
            if(!keepSources[source.id]){
                toRemoveArr.push({remove: {sourceIdx: {id: source.id}}});
            }
        }
        for(var i = 0; i < toRemoveArr.length; i++){
            this.removeSource(toRemoveArr[i]);
        }
    },
    /**
     *
     */
    changeSource: function(options){
        if(options.change){
            var changeOpts = options.change;
            if(typeof changeOpts.options !== 'undefined'){
                var sourceToChange = this.getSource(changeOpts.sourceIdx);
                this.mbMap.fireModelEvent({name: 'beforeSourceChanged', value: {source: sourceToChange, changeOptions: changeOpts}});
                if(changeOpts.options.type === 'selected'){
                    var result = this._checkSource(changeOpts);
                    var changed = {changed: {children: result.children, sourceIdx: result.sourceIdx}};
                    this.mbMap.fireModelEvent({name: 'sourceChanged', value: changed});//{options: result}});
                    this._redrawSource(changeOpts);
                }
                if(changeOpts.options.type === 'info'){
                    var result = {infolayers: [], changed: {sourceIdx: {id: sourceToChange.id}, children: {}}};
                    result = Mapbender.source[sourceToChange.type].checkInfoLayers(sourceToChange,
                        this.map.olMap.getScale(), changeOpts, result);
                    this.map.layersList[sourceToChange.mqlid].olLayer.queryLayers = result.infolayers;
                    this.mbMap.fireModelEvent({name: 'sourceChanged', value: result});//{options: result}});
                }
                if(changeOpts.options.type === 'toggle'){

                }
            }
            if(changeOpts.move){
                var tomove = {source: this.getSource(changeOpts.move.tomove.sourceIdx)};
                if(changeOpts.move.tomove.layerIdx){
                    tomove['layerId'] = changeOpts.move.tomove.layerIdx.id;
                }
                var before = changeOpts.move.before;
                if(before)
                    before = {source: this.getSource(changeOpts.move.before.sourceIdx), layerId: changeOpts.move.before.layerIdx.id};
                var after = changeOpts.move.after;
                if(after)
                    after = {source: this.getSource(changeOpts.move.after.sourceIdx), layerId: changeOpts.move.after.layerIdx.id};
                this._moveSourceOrLayer(tomove, before, after);
            }
            if(changeOpts.layerRemove){
                var sourceToChange = this.getSource(changeOpts.layerRemove.sourceIdx);
                var layerToRemove = Mapbender.source[sourceToChange.type].findLayer(sourceToChange, changeOpts.layerRemove.layer.options);
                var removedLayer = Mapbender.source[sourceToChange.type].removeLayer(sourceToChange, layerToRemove.layer);
                var changed = {changed: {childRemoved: removedLayer, sourceIdx: changeOpts.layerRemove.sourceIdx}};
                this._checkAndRedrawSource({sourceIdx: changeOpts.layerRemove.sourceIdx, options: {children: {}}});
                this.mbMap.fireModelEvent({name: 'sourceChanged', value: changed});
            }
        }else{
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
    changeLayerState: function(sourceIdObject, options, selectedOther, merge){
        if(typeof merge === 'undefined')
            merge = false;
        if(typeof selectedOther === 'undefined')
            selectedOther = false;
        var source = this.getSource(sourceIdObject);
        if(source !== null){
            var tochange = Mapbender.source[source.type].createOptionsLayerState(source, options, selectedOther, merge);
            this.changeSource(tochange);
        }
        
    },
    _moveSourceOrLayer: function(tomove, before, after){
        var layerToMove;
        if(before && after
            && before.source.id.toString() === after.source.id.toString()
            && before.source.id.toString() === tomove.source.id.toString()){
            window.console && console.log("move layer inside");
            var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, {id: before.layerId});
            var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, {id: after.layerId});
            layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
            this._reorderLayers(tomove.source, layerToMove.layer, beforeLayer.parent, beforeLayer.idx, before, after);
        }else if(before && before.source.id.toString() === tomove.source.id.toString()){
            window.console && console.log("move layer into last pos");
            var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, {id: before.layerId});
            layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
            this._reorderLayers(tomove.source, layerToMove.layer, beforeLayer.parent, beforeLayer.idx, before, after);
        }else if(after && after.source.id.toString() === tomove.source.id.toString()){
            window.console && console.log("move layer into first pos");
            var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, {id: after.layerId});
            layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
            this._reorderLayers(tomove.source, layerToMove.layer, afterLayer.parent, afterLayer.idx, before, after);
        }else if(before && before.source.origId === tomove.source.origId){
            alert("not implemented yet");
            return;
            var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
            if(count.simpleCount === 1){ // remove source
                this._insertLayer(tomove, before, after);
            }else if(count.simpleCount > 1){
                var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
                var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                this.addSource(source_new, before, after);
            }
        }else if(after && after.source.origId === tomove.source.origId){
            alert("not implemented yet");
            return;
            this._insertLayer(tomove, before, after);
        }else if(before && !after){
            if(!tomove.layerId){
                window.console && console.log("move source into last pos");
                this._moveSource(tomove.source, before, after);
            }else{
                alert("not implemented yet");
                return;
                var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                if(count.simpleCount === 1){ // remove source
                    this._moveSource(tomove.source, before, after);
                }else if(count.simpleCount > 1){
                    var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
                    var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                    this.addSource(source_new, before, after);
                }
            }
        }else if(after && !before){ // move source for tree
            if(!tomove.layerId){
                window.console && console.log("move source into first pos");
                this._moveSource(tomove.source, before, after);
            }else{
                alert("not implemented yet");
                return;
                var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                if(count.simpleCount === 1){ // remove source
                    this._moveSource(tomove.source, before, after);
                }else if(count.simpleCount > 1){
                    var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
                    var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                    this.addSource(source_new, before, after);
                }
            }
        }else{
            if(!tomove.layerId){ // move source for tree
                window.console && console.log("move source inside");
                this._moveSource(tomove.source, before, after);
            }else{
                alert("not implemented yet");
                return;
                if(after.source.id === before.source.id){
                    var layerToSplit = Mapbender.source[after.source.type].findLayer(after.source, {id: after.layerId});
                    var new_splitted = this._getNewFromList(after.source, layerToSplit.layer);
                    this.addSource(new_splitted, before, null);
                    var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                    if(count.simpleCount === 1){ // move source
                        this._moveSource(tomove.source, before, {source: new_splitted, layerId: after.layerId});
                    }else if(count.simpleCount > 1){
                        var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
                        var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                        this.addSource(source_new, before, {source: new_splitted, layerId: after.layerId});
                    }
                }else{
                    var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
                    if(count.simpleCount === 1){ // move source
                        var pos = this.getSourcePos(tomove.source);
                        if(pos !== 0 && pos !== (this.sourceTree.length - 1)){
                            var before_cur = this.sourceTree[pos - 1];
                            var after_cur = this.sourceTree[pos + 1];
                        }
                        this._moveSource(tomove.source, before, after);
                        if(pos !== 0 && pos !== (this.sourceTree.length - 1)){
                            this._concatSources(before_cur, after_cur);
                        }
                    }else if(count.simpleCount > 1){
                        var layerToMove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
                        var source_new = this._createSourceFromLayer(tomove.source, layerToMove.layer);
                        this.addSource(source_new, before, {source: new_splitted, layerId: after.layerId});
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
            }else{
                first = source2;
                second = source1;
            }
            var layer = second.configuration.children[0].children[0];
            var list = Mapbender.source[second.type].getLayersList(second, layer, true);
            var layers = list.layers;//.reverse();
            if(layers.length > 0){
                var secondRoot = $.extend(true, {}, second.configuration.children[0]);
                var toremove = this.createToChangeObj(second);
                this.mbMap.fireModelEvent({name: 'beforeSourceRemoved', value: {toremove: toremove}});
                var mqLayer = this.map.layersList[toremove.source.mqlid];
                var removedMq = mqLayer.remove();
                if(removedMq){
                    for(var i = 0; i < this.sourceTree.length; i++){
                        if(this.sourceTree[i].id.toString() === toremove.source.id.toString()){
                            this.sourceTree.splice(i, 1);
                            break;
                        }
                    }
                    if(this.map.layersList[toremove.source.mqlid]){
                        delete(this.map.layersList[toremove.source.mqlid]);
                    }
                    var removedObj = this.createChangedObj(toremove.source);
                    this.mbMap.fireModelEvent({name: 'sourceRemoved', value: removedObj});
                    var toadd = this.createToChangeObj(first);
                    this.mbMap.fireModelEvent({name: 'beforeSourceAdded', value: toadd});
                    var firstRoot = first.configuration.children[0];
                    firstRoot.children = firstRoot.children.concat(secondRoot.children);
                    for(var i = 0; i < layers.length; i++){
                        var addedobj = this.createChangedObj(first);
                        addedobj.children[layers[i].options.id] = layers[i];
                        this.mbMap.fireModelEvent({name: 'sourceAdded', value: addedobj});
                    }
                    this._checkAndRedrawSource(first, this.map.layersList[first.mqlid], this.createToChangeObj(first));
                }
            }
        }
    },
    /**
     *
     */
    _reorderLayers: function(source, layerToMove, targetParent, targetIdx, before, after){
        var removed = Mapbender.source[source.type].removeLayer(source, layerToMove);
        var added = Mapbender.source[source.type].addLayer(source, removed.layer, targetParent, targetIdx);
        var changed = this.createChangedObj(source);
        changed.children[added.options.id] = added;
        changed.layerId = added.options.id;
        changed.after = after;
        changed.before = before;
        this.mbMap.fireModelEvent({name: 'sourceMoved', value: changed});
        this._checkAndRedrawSource({sourceIdx: {id: source.id}, options: {children: {}}});
    },
    /**
     *
     */
    _insertLayer: function(tomove, before, after){
        var layerToRemove = Mapbender.source[tomove.source.type].findLayer(tomove.source, {id: tomove.layerId});
        var count = Mapbender.source[tomove.source.type].layerCount(tomove.source);
        var addedobj, tochange, toremove, removed;
        if(count.simpleCount === 1 && layerToRemove){
            toremove = this.createToChangeObj(tomove.source);
            toremove.children[layerToRemove.layer.options.id] = layerToRemove.layer;
            alert("CHECK _insertLayer removeSource");
//            this.removeSource(toremove);
            removed = layerToRemove;
        }else if(count.simpleCount > 1 && layerToRemove){
            removed = Mapbender.source[tomove.source.type].removeLayer(tomove.source, layerToRemove.layer);
            this._checkAndRedrawSource(tomove.source, this.map.layersList[tomove.source.mqlid], this.createToChangeObj(tomove.source));
        }else{
            return;
        }
        if(before){
            tochange = this.createToChangeObj(before.source);
            this.mbMap.fireModelEvent({name: 'beforeSourceAdded', value: tochange});
            var beforeLayer = Mapbender.source[before.source.type].findLayer(before.source, {id: before.layerId});
            var added = Mapbender.source[before.source.type].addLayer(before.source, removed.layer, beforeLayer.parent, beforeLayer.idx + 1);
            addedobj = this.createChangedObj(before.source);
            //            addedobj = this.createChangedObj(after.source); ??????
            addedobj.children[added.options.id] = added;
            addedobj.before = before;
            addedobj.after = after;
            this.mbMap.fireModelEvent({name: 'sourceAdded', value: addedobj});
            this._checkAndRedrawSource(before.source, this.map.layersList[before.source.mqlid], this.createToChangeObj(before.source));
        }else if(after){
            tochange = this.createToChangeObj(after.source);
            this.mbMap.fireModelEvent({name: 'beforeSourceAdded', value: tochange});
            var afterLayer = Mapbender.source[after.source.type].findLayer(after.source, {id: after.layerId});
            var added = Mapbender.source[after.source.type].addLayer(after.source, removed.layer, afterLayer.parent, afterLayer.idx);
            addedobj = this.createChangedObj(after.source);
            addedobj.children[added.options.id] = added;
            addedobj.before = before;
            addedobj.after = after;
            this.mbMap.fireModelEvent({name: 'sourceAdded', value: addedobj});
            this._checkAndRedrawSource(after.source, this.map.layersList[after.source.mqlid], this.createToChangeObj(after.source));
        }

    },
    /**
     *
     */
    _moveSource: function(source, before, after){
        var old_pos = this.getSourcePos(source);
        var new_pos;
        if(before && before.source && after && after.source){
            var before_pos = this.getSourcePos(before.source);
            var after_pos = this.getSourcePos(after.source);
            if(old_pos <= before_pos)
                new_pos = before_pos;
            else if(old_pos > before_pos)
                new_pos = after_pos;
        }else if(before && before.source){
            new_pos = this.getSourcePos(before.source);
        }else if(after && after.source){
            new_pos = this.getSourcePos(after.source);
        }
        if(old_pos === new_pos)
            return;
        this.sourceTree.splice(new_pos, 0, this.sourceTree.splice(old_pos, 1)[0]);
        var mqL = this.map.layersList[source.mqlid];
        if(old_pos > new_pos){
            mqL.down(Math.abs(old_pos - new_pos));
        }else{
            mqL.up(Math.abs(old_pos - new_pos));
        }
        var changed = this.createChangedObj(source);
        changed.after = after;
        changed.before = before;
        this.mbMap.fireModelEvent({name: 'sourceMoved', value: changed});
    },
    /**
     *
     */
    _createSourceFromLayer: function(source, layerToMove){
        var removed = Mapbender.source[source.type].removeLayer(source, layerToMove);
        var removedObj = this.createChangedObj(source);
        removedObj.children[removed.layer.options.id] = removed.layer;
        this.mbMap.fireModelEvent({name: 'sourceRemoved', value: removedObj});
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
            this.mbMap.fireModelEvent({name: 'sourceRemoved', value: removed});
            this._checkAndRedrawSource(source, this.map.layersList[source.mqlid], this.createToChangeObj(source));
            return source_new;
        }else{
            alert("source:" + source.id + "cannot be splitted");
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
        if(oldProj.projCode === srs.projection.projCode)
            return;
        var center = this.map.olMap.getCenter().transform(oldProj, srs.projection);
        this.map.olMap.projection = srs.projection;
        this.map.olMap.displayProjection = srs.projection;
        this.map.olMap.units = srs.projection.proj.units;
        this.map.olMap.maxExtent = this._transformExtent(this.mapMaxExtent, srs.projection);
        $.each(self.map.olMap.layers, function(idx, layer){
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if(!self.layersMaxExtent[layer.id])
                self._addLayerMaxExtent(layer);
            if(layer.maxExtent && layer.maxExtent != self.map.olMap.maxExtent)
                layer.maxExtent = self._transformExtent(self.layersMaxExtent[layer.id], srs.projection);
            layer.initResolutions();
        });
        this.map.olMap.setCenter(center, this.map.olMap.getZoom(), false, true);
        this.mbMap.fireModelEvent({name: 'srschanged', value: {projection: srs.projection}});
    },
    /*
     * Transforms an extent into destProjection projection.
     */
    _transformExtent: function(extentObj, destProjection){
        if(extentObj.extent != null){
            if(extentObj.projection.projCode == destProjection.projCode){
                return extentObj.extent.clone();
            }else{
                var newextent = extentObj.extent.clone();
                newextent.transform(extentObj.projection, destProjection);
                return newextent;
            }
        }else{
            return null;
        }
    },
    /**
     * Adds a layer's original extent into the widget layersOrigExtent.
     */
    _addLayerMaxExtent: function(layer){
        if(layer.olLayer){
            layer = layer.olLayer;
        }
        if(!this.layersMaxExtent[layer.id]){
            var proj, maxExt;
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
            this.layersMaxExtent[layer.id] = {projection: proj, extent: maxExt};
        }
    },
    /**
     * Removes a layer's origin extent from the widget layersOrigExtent.
     */
    _removeLayerMaxExtent: function(layer){
        if(layer.olLayer){
            layer = layer.olLayer;
        }
        if(this.layersMaxExtent[layer.id]){
            delete(this.layersMaxExtent[layer.id]);
        }
    }

};
