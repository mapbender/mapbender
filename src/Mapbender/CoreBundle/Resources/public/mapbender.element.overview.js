(function($){

    $.widget("mapbender.mbOverview", {
        options: {
            layerset: []
        },
        overview: null,

        /**
         * Creates the overview
         */
        _create: function(){
            if(!Mapbender.checkTarget("mbOverview", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        /**
         * Initializes the overview
         */
        _setup:         function() {
            var widget = this;
            var options = widget.options;
            var mbMap = $('#' + options.target).data('mapbenderMbMap');
            var model = mbMap.model;
            var element = $(widget.element);
            var projection = model.map.olMap.projection;
            var maxExtent = model.map.olMap.maxExtent;
            var overviewLayers = [];
            var layerSet = Mapbender.configuration.layersets[options.layerset];
            var overviewContainer = $('.overviewContainer', widget.element).get(0);

            element.addClass(options.anchor);

            $.each(layerSet, function(idx, item) {
                $.each(item, function(idx2, layerDef) {
                    if(layerDef.type !== "wms") {
                        return;
                    }
                    var wmsLayer = widget.createWmsLayer(layerDef, {
                        isBaseLayer: idx === 0
                    });

                    overviewLayers.push(wmsLayer);
                });
            });

            if(!overviewLayers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return;
            }

            var overviewOptions = {
                layers: overviewLayers,
                div: overviewContainer,
                size: new OpenLayers.Size(options.width, options.height),
                //maximized: widget.options.maximized,
                mapOptions: {
                    maxExtent: (maxExtent && maxExtent.clone()) || null,
                    projection: projection,
                    theme: null
                }
            };

            if(options.fixed){
                $.extend(overviewOptions, {
                    minRatio: 1,
                    maxRatio: 1000000000
                    // ,autoPan: false
                });
            }

            widget.overview = new OpenLayers.Control.OverviewMap(overviewOptions);

            mbMap.map.olMap.addControl(widget.overview);

            $(document).bind('mbmapsrschanged', $.proxy(widget._changeSrs, widget));
            element.find('.toggleOverview').bind('click', $.proxy(widget._openClose, widget));
            
            if(!options.maximized){
                element.addClass("closed");
            }    
                
            widget._ready();
        },

        /**
         * Create WMS layer by definition
         * @param layerDefinition
         * @param options
         * @returns {*}
         */
        createWmsLayer: function(layerDefinition, options) {
            var ls = "";
            var layerConfiguration = layerDefinition.configuration;
            var layerOptions = layerConfiguration.options;
            var layers = Mapbender.source[layerDefinition.type].getLayersList(layerDefinition, layerConfiguration.children[0], true);
            var url = layerOptions.url;

            for (var i = 0; i < layers.layers.length; i++) {
                ls += layers.layers[i].options.name !== "" ? "," + layers.layers[i].options.name : "";
            }

            // Add proxy if needed
            if(layerOptions.proxy) {
                url = OpenLayers.ProxyHost + encodeURIComponent(url);
            }

            return new OpenLayers.Layer.WMS(layerDefinition.title, url, {
                version:     layerOptions.version,
                layers:      ls.substring(1),
                format:      layerOptions.format,
                transparent: layerOptions.transparent
            }, $.extend({
                opacity:    layerOptions.opacity,
                singleTile: true
            }, options));
        },

        /**
         * Opens/closes the overview element
         */
        _openClose: function(event){
            var self = this;
            $(this.element).toggleClass('closed');
            window.setTimeout(function(){
                if(!$(self.element).hasClass('closed')){
                    self.overview.ovmap.updateSize();
                }
            }, 300);
        },

        /**
         * Cahnges the overview srs
         */
        _changeSrs: function(event, srs) {
            console.log("Overview changesrs event", event, srs);
            var widget = this;
            var overview = widget.overview;
            /**
             * @type {null|OpenLayers.Map}
             */
            var ovMap = overview.ovmap;
            var oldProj = ovMap.getProjectionObject();
            if (oldProj.projCode === srs.projection.projCode) {
                return;
            }
            var center = ovMap.getCenter().transform(oldProj, srs.projection);

            ovMap.projection = srs.projection;
            ovMap.displayProjection = srs.projection;
            ovMap.units = srs.projection.proj.units;
            if (ovMap.maxExtent) {
                ovMap.maxExtent = ovMap.maxExtent.clone();
                ovMap.maxExtent.transform(oldProj, srs.projection);
            }

            $.each(ovMap.layers, function(idx, layer) {
                layer.projection = srs.projection;
                layer.units = srs.projection.proj.units;
                if (layer.maxExtent) {
                    layer.maxExtent = layer.maxExtent.clone();
                    layer.maxExtent.transform(oldProj, srs.projection);
                }
                layer.initResolutions();
            });
            console.log("New overview params", center, ovMap.getZoom());

            ovMap.setCenter(center, ovMap.getZoom(), false, true);
        },

        /**
         * Puts callback on ready
         * @param {function} callback Function this runs after widget is ready
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }
        },

        /**
         * Runs if widget is ready
         */
        _ready: function() {
            var widget = this;
            widget._trigger('ready');

            widget.readyState = true;
        }

    });

})(jQuery);
