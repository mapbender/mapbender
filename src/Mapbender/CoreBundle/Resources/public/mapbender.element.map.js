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
            srsDefs: [],
            layersets: []
        },
        elementUrl: null,
        model: null,
        map: null,

        /**
         * Creates the map widget
         */
        _create: function(){
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.model = Mapbender.mapEngine.mapModelFactory(this);
            // HACK: place the model instance globally at Mapbender.Model
            if (window.Mapbender.Model) {
                console.error("Mapbender.Model already set", window.Mapbender.Model);
                throw new Error("Can't globally reassing window.Mapbender.Model");
            }
            window.Mapbender.Model = this.model;
            this.map = this.model.map;
            self._trigger('ready');
        },
        getMapState: function(){
            return this.model.getMapState();
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
            return this.options.srsDefs;
        },
        /**
         * Reterns the model
         */
        getModel: function(){
            return this.model;
        },
        /**
         * Emulation shim for old-style MapQuery.Map.prototype.center.
         * See https://github.com/mapbender/mapquery/blob/1.0.2/src/jquery.mapquery.core.js#L298
         * @param {Object} options
         * @deprecated
         */
        setCenter: function(options){
            this.getModel().setCenterMapqueryish(options);
        },
        /*
         * Changes the map's projection.
         */
        changeProjection: function(srs) {
            if (typeof srs === "string") {
                this.model.changeProjection(srs);
            } else {
                // legacy stuff
                var projCode = srs.projCode || (srs.proj && srs.proj.srsCode);
                if (!projCode) {
                    console.error("Invalid srs argument", srs);
                    throw new Error("Invalid srs argument");
                }
                this.model.changeProjection(projCode);
            }
        },
        /**
         * Zooms the map in
         */
        zoomIn: function() {
            this.model.zoomIn();
        },
        /**
         * Zooms the map out
         */
        zoomOut: function() {
            this.model.zoomOut();
        },
        /**
         * Zooms the map to max extent
         */
        zoomToFullExtent: function() {
            this.model.zoomToFullExtent();
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
         * @deprecated
         */
        zoomToScale: function(scale, closest) {
            console.warn("Deprecated zoomToScale call, use engine-independent Model.pickZoomForScale + Model.setZoomLevel");
            this.map.olMap.zoomToScale.apply(this.map.olMap, arguments);
        },
        /**
         * Super legacy, some variants of wmcstorage want to use this to replace the map's initial max extent AND
         * initial SRS, which only really works when called immediately before an SRS switch. Very unsafe to use.
         * @deprecated
         */
        setMaxExtent: function(newMaxExtent, newMaxExtentSrs) {
            this.getModel().replaceInitialMaxExtent(newMaxExtent, newMaxExtentSrs);
        },
        /**
         * Super legacy, never really did anything, only stored the argument in a (long gone) property of the Model
         * @deprecated
         */
        setExtent: function() {
            console.error("mbMap.setExtent called, doesn't do anything, you probably want to call zoomToExtent instead", arguments);
        },
        /**
         * Adds the popup
         */
        addPopup: function(popup){
            this.map.olMap.addPopup(popup);
        },
        /**
         * Removes the popup
         */
        removePopup: function(popup){
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
            $.ajax({
                url: this.elementUrl + 'loadsrs',
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
                    this.options.srsDefs.push(response.data[i]);
                    Mapbender.Projection.extendSrsDefintions([response.data[i]]);
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
        },

        /**
         * Initialize POIs
         * @todo: out of map
         */
        initializePois: function () {
            var self = this,
                poiOptionsList = (this.options && this.options.extra && this.options.extra['pois']) || [];

            if (!poiOptionsList.length) {
                return;
            }

            var pois = poiOptionsList.map(function(poi) {
                var coordinates = [poi.x, poi.y];

                if (poi.srs) {
                    coordinates = Mapbender.Projection.transform(poi.srs, self.model.getCurrentProjectionCode(), coordinates);
                }

                return {
                    position: coordinates,
                    label: poi.label
                };
            });

            var size = [
                this.options.poiIcon.width,
                this.options.poiIcon.height
            ];

            var offset = [
                this.options.poiIcon.xoffset,
                this.options.poiIcon.yoffset
            ];

            var iconStyle = this.model.createIconStyle({
                src: Mapbender.configuration.application.urls.asset + this.options.poiIcon.image,
                size: size,
                offset: offset
            });

            $.each(pois, function(idx, poi) {
                self.poiLayerId = self.model.setMarkerOnCoordinates(poi.position, self.element.attr('id'), self.poiLayerId, iconStyle);

                if (poi.label) {
                    var popupOverlay = new Mapbender.Model.MapPopup(undefined, self.model);
                    popupOverlay.$markup.addClass('flipped');
                    popupOverlay.openPopupOnXYWithCustomContent(poi.position, poi.label);
                }
            });
        },
    });

})(jQuery);

$('body').delegate(':input', 'keydown', function(event){
    event.stopPropagation();
});
