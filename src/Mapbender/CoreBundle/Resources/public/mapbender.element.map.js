(function($){
    $.widget("mapbender.mbMap", {
        options: {
            poiIcon: {
                image: 'bundles/mapbendercore/image/pin_red.png',
                width: 32,
                height: 41,
                xoffset: -6,
                yoffset: -38
            },
            targetscale: null,
            layersets: []
        },
        srsDefinitions: [],
        elementUrl: null,
        model: null,
        map: null,
        readyState: false,
        readyCallbacks: [],
        /**
         * Creates the map widget
         */
        _create: function(){
            //OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';
            var self = this,
                    me = $(this.element);
            //Todo: Move to a seperate file. ADD ALL THE EPSGCODES!!!!111
            //jQuery.extend(OpenLayers.Projection.defaults, {'EPSG:31466': {yx : true}});
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';

            this.srsDefinitions = this.options.srsDefs || [];
            // Patch missing SRS definitions into proj4
            // This avoids errors when initializing the OL4 view with
            // "exotic" / non-geodesic projections such as EPSG:25832
            Mapbender.Projection.extendSrsDefintions(this.srsDefinitions, true, true);

            this.engineCode = Mapbender.configuration.application.mapEngineCode;

            var modelOptions = {
                srs: this.options.srs,
                maxExtent: Mapbender.Model.sanitizeExtent(this.options.extents.max),
                startExtent: Mapbender.Model.sanitizeExtent(this.options.extents.start),
                scales : this.options.scales,
                dpi: this.options.dpi,
                tileSize: this.options.tileSize
            };


            this.model = new Mapbender.Model(this.element.attr('id'), modelOptions);
            _.forEach(this.options.layersets.reverse(), function(layerSetId) {
                this.model.addLayerSetById(layerSetId);
            }.bind(this));


            $.extend(this.options, {
                layerDefs: [],
                poiIcon: this.options.poiIcon
            });

            // NOTE: startExtent is technically mutually exclusive with
            //    center and / or targetscale
            //    However, ol may not properly initialize the view if we don't
            //    go to a defined extent (which we always have) first
            if (this.options.center) {
                this.model.setCenter(this.options.center);
            }
            if (this.options.targetscale) {
                this.model.setScale(this.options.targetscale);
            }


            this.map = me.data('mapQuery');
            self._trigger('ready');
            this._ready();
        },
        /**
         * DEPRECATED
         */
        "goto": function(options){
            this.map.center(options);
        },
        setExtent: function(extent){
            this.model.extent = extent;
        },
        setMaxExtent: function(extent, projection){
            if(typeof projection === "string") {
                this.model.mapMaxExtent = {
                    projection: this.model.getProj(projection),
                    extent: extent
                };
            } else {
                this.model.mapMaxExtent = {
                    projection: projection,
                    extent: extent
                };
            }
        },
        /**
         *
         */
        getMapState: function(){
            return this.model.getMapState();
        },
        /**
         *
         */
        addSource: function(sourceDef, mangleIds) {
            // legacy support: callers that do not know about the mangleIds argument most certainly want ids mangled
            var doMangle = !!mangleIds || typeof mangleIds === 'undefined';
            var source =this.model.addSourceFromConfig(sourceDef, doMangle);
            if (this.engineCode === 'ol4') {
                // @todo 3.1.0: only the map widget itself should ever fire mbmap* events
                //      The old code does this in the Mapbender.Model
                //      Fix that, then remove the if
                this.fireModelEvent({
                    name: 'sourceadded',
                    value:{
                        added:{
                            source: source
                        }
                    }
                });
            }
        },
        /**
         *
         */
        removeSource: function(toChangeObj){
            if(toChangeObj && toChangeObj.remove && toChangeObj.remove.sourceIdx) {
                this.model.removeSource(toChangeObj);
            }
        },
        /**
         *
         */
        removeSources: function(keepSources){
            this.model.removeSources(keepSources);
        },
        /**
         *
         */
        changeSource: function(toChangeObj){
            if(toChangeObj && toChangeObj.source && toChangeObj.type) {
                this.model.changeSource(toChangeObj);
            }
        },
        /**
         * Triggers an event
         * options.name - name of the event (mbmap prefix will be added, result lowercased)
         * options.value - will be passed into the event handler callables as the second argument
         *
         * @see https://github.com/jquery/jquery-ui/blob/1.12.1/ui/widget.js#L659
         */
        fireModelEvent: function(options) {
            this._trigger(options.name, null, options.value);
        },
        /**
         * Returns a sourceTree from model.
         **/
        getSourceTree: function(){
            return this.model.sourceTree;
        },
        /**
         * Returns all defined srs
         */
        getAllSrs: function(){
            return this.srsDefinitions;
        },
        /**
         * Reterns the model
         */
        getModel: function(){
            return this.model;
        },
        getCenterOptions: function(){
            return {
                center: this.map.olMap.getCenter(),
                zoom: this.map.olMap.getZoom()
            };
        },
        setCenter: function(options){
            if(typeof options.box !== 'undefined' && typeof options.position !== 'undefined' && typeof options.zoom !== 'undefined')
                this.map.center(options);
            else if(typeof options.center !== 'undefined' && typeof options.zoom !== 'undefined') {
                this.map.olMap.updateSize();
                this.map.olMap.setCenter(options.center, options.zoom);
            }
        },
        /*
         * Changes the map's projection.
         */
        changeProjection: function(srs){
            if(typeof srs === "string")
                this.model.changeProjection({
                    projection: this.model.getProj(
                            srs)
                });
            else
                this.model.changeProjection({
                    projection: srs
                });
        },
        /**
         * Zooms the map in
         */
        zoomIn: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomIn();
        },
        /**
         * Zooms the map out
         */
        zoomOut: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomOut();
        },
        /**
         * Zooms the map to max extent
         */
        zoomToFullExtent: function(){
            // TODO: MapQuery?
            this.map.olMap.zoomToMaxExtent();
        },
        /**
         * Zooms the map to extent
         */
        zoomToExtent: function(extent, closest){
            if(typeof closest === 'undefined')
                closest = true;
            this.map.olMap.zoomToExtent(extent, closest);
        },
        /**
         * Zooms the map to scale
         */
        zoomToScale: function(scale, closest){
            if(typeof closest === 'undefined')
                closest = false;
            this.map.olMap.zoomToScale(scale, closest);
        },
        /**
         *
         */
        panMode: function(){
            this.map.mode('pan');
        },
        /**
         * Adds the popup
         */
        addPopup: function(popup){
            //TODO: MapQuery
            this.map.olMap.addPopup(popup);
        },
        /**
         * Removes the popup
         */
        removePopup: function(popup){
            //TODO: MapQuery
            this.map.olMap.removePopup(popup);
        },
        /**
         * Returns the scale list
         * @deprecated, just get options.scales yourself
         */
        scales: function(){
            return this.options.scales;
        },
        /**
         * Sets opacity to source
         * @param {spource} source
         * @param {float} opacity
         */
        setOpacity: function(source, opacity){
            this.model.setOpacity(source, opacity);
        },
        /**
         * Zooms to layer
         * @param {object} options of form { sourceId: XXX, layerId: XXX }
         */
        zoomToLayer: function(options){
            this.model.zoomToLayer(options);
        },
        /**
         *
         */
        ready: function(callback){
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function(){
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        },
        /**
         * Turns on the highlight layer at map
         */
        highlightOn: function(features, options){
            this.model.highlightOn(features, options);
        },
        /**
         * Turns off the highlight layer at map
         */
        highlightOff: function(features){
            this.model.highlightOff(features);
        },
        /**
         * Loads the srs definitions from server
         */
        loadSrs: function(srslist){
            var self = this;
            $.ajax({
                url: self.elementUrl + 'loadsrs',
                type: 'POST',
                data: {
                    srs: srslist
                },
                dataType: 'json',
                contetnType: 'json',
                context: this,
                success: this._loadSrsSuccess,
                error: this._loadSrsError
            });
            return false;
        },
        /**
         * Loads the srs definitions from server
         */
        _loadSrsSuccess: function(response, textStatus, jqXHR){
            if(response.data) {
                for(var i = 0; i < response.data.length; i++) {
                    proj4.defs(response.data[i].name, response.data[i].definition);
                    this.model.srsDefs.push(response.data[i]);
                    this.fireModelEvent({
                        name: 'srsadded',
                        value: response.data[i]
                    });
                }
            } else if(response.error) {
                Mapbender.error(Mapbender.trans(response.error));
            }
        },
        /**
         * Loads the srs definitions from server
         */
        _loadSrsError: function(response){
            Mapbender.error(Mapbender.trans(response));
        }
    });

})(jQuery);

$('body').delegate(':input', 'keydown', function(event){
    event.stopPropagation();
});
