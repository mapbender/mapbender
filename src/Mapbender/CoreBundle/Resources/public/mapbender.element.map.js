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
            layersets: []
        },
        elementUrl: null,
        model: null,
        map: null,
        readyState: false,

        /**
         * Creates the map widget
         */
        _create: function(){
            OpenLayers.ProxyHost = Mapbender.configuration.application.urls.proxy + '?url=';
            var self = this,
                    me = $(this.element);
            //Todo: Move to a seperate file. ADD ALL THE EPSGCODES!!!!111
            jQuery.extend(OpenLayers.Projection.defaults, {'EPSG:31466': {yx : true}});
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.model = Mapbender.Model;
            this.model.init(this);
            $.extend(this.options, {
                layerDefs: [],
                poiIcon: this.options.poiIcon
            });
            this.map = me.data('mapQuery');
            self._trigger('ready');
            this._ready();
        },
        getMapState: function(){
            return this.model.getMapState();
        },
        sourceById: function(idObject){
            return this.model.getSource(idObject);
        },
        mqLayerBySourceId: function(idObject){
            var source = this.sourceById(idObject);
            return this.map.layersList[source.mqlid];
        },
        /**
         *
         */
        addSource: function(sourceDef, mangleIds) {
            // legacy support: callers that do not know about the mangleIds argument most certainly want ids mangled
            this.model.addSourceFromConfig(sourceDef, !!mangleIds || typeof mangleIds === 'undefined');
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
         * Triggers an event from the model.
         * options.name - name of the event,
         * options.value - parameter in the form of:
         * options.value.mapquerylayer - for a MapQuery.Layer,
         * options.value.source - for a source from the model.sourceTree,
         * options.value.tochange - for a "tochange" object
         * options.value.changed -  for a "changed" object
         */
        fireModelEvent: function(options){
//            window.console && console.log(options.name, options.value);
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
            return this.model.getAllSrs();
        },
        /**
         * Reterns the model
         */
        getModel: function(){
            return this.model;
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
            }
        },
        /**
         *
         */
        _ready: function(){
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
                    Proj4js.defs[response.data[i].name] = response.data[i].definition;
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
