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
    /**
     *
     */
    addSource: function(sourceDef, before, after){
        this.model.addSource(sourceDef, before, after);
    },
    /**
     *
     */
    removeSource: function(source){
        if(source && source.source){
            this.model.removeSource(source);
        } else if(source){
            this.model.removeSource(this.model.createChangedObj(source));
        }
    },
    /**
     *
     */
    changeSource: function(source, changetype){
        if(source && source.source && source.type !== ""){
            this.model.changeSource(source);
        } else if(source && changetype !== ""){
            var tochange = this.model.createToChangeObj(source);
            tochange.type = changetype;
            this.model.changeSource(tochange);
        }
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
});

})(jQuery);
