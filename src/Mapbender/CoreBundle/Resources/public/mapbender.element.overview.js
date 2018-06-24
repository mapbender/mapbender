(function($){

    $.widget("mapbender.mbOverview", {
        options: {
            layerset: []
        },
        control_: null,
        layersOrigExtents: {},
        // @todo 3.1.0: remove this attribute and its usages
        mapOrigExtents_: {},
        startproj: null,
        $viewport_: null,
        mbMap_: null,

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
            this.mbMap_ = $('#' + this.options.target).data('mapbenderMbMap');
            var $element = $(this.element);
            this.$viewport_ = $('.viewport', $element);
            if (!$element.hasClass('closed')) {
                // if we start closed, wait with initialization until opened
                this._initDisplay();
            }
            this._ready();
            $element.find('.toggleOverview').bind('click', this._openClose.bind(this));
        },
        _initDisplay: function() {
            switch (this.mbMap_.engineCode) {
                case 'ol4':
                    this._initAsOl4Control();
                    break;
                case 'mq-ol2':
                    this._initAsOl2Control();
                    break;
                default:
                    throw new Error("Unhandled engine code " + this.mbMap_.engineCode);
            }
            $(document).bind('mbmapsrschanged', this._changeSrs.bind(this));
        },
        _initAsOl4Control: function() {
            // @see https://github.com/openlayers/openlayers/blob/v4.6.5/src/ol/control/overviewmap.js

            var mainMapModel = this.mbMap_.model;
            var maxExtent = mainMapModel.getMaxExtent();
            this.$viewport_.width(this.options.width).height(this.options.height);
            var viewportId = this.$viewport_.attr('id');

            var sources = Mapbender.Model.sourcesFromLayerSetId("" + this.options.layerset);
            var layers = sources.map(function(source) {
                var layer = Mapbender.Model.layerFactoryStatic(source, maxExtent);
                // Also activate all sources and all layers.
                // This is backwards-compatible behvavior. The old overview never
                // evaluated "visible", or any other config state on the source nor
                // its layers
                // NOTE: we can only do this AFTER creating and attach the layer via factory
                source.setState(true);
                return layer;
            });
            var controlOptions = {
                collapsible: true,
                collapsed: false,
                target: viewportId,
                layers: layers,
                view: new ol.View({
                    projection: mainMapModel.getCurrentProjectionObject(), //map.getView().getProjection(),
                    center: mainMapModel.map.getView().getCenter()
                })
            };
            this.control_ = new ol.control.OverviewMap(controlOptions);
            mainMapModel.map.addControl(this.control_);
        },
        _initAsOl2Control: function() {
            var overviewLayers = [];
            var layerSet = Mapbender.configuration.layersets[this.options.layerset];

            this.mapOrigExtents_ = {
                max: {
                    projection: srs,
                    extent: maxExtent
                }
            };
            this.startproj = srs;
            $.each(layerSet.reverse(), function(idx, item) {
                $.each(item, function(idx2, layerDef) {
                    if(layerDef.type !== "wms") {
                        return;
                    }
                    var wmsLayer = widget.createWmsLayer(layerDef, {
                        isBaseLayer: idx === 0
                    });

                    overviewLayers.push(wmsLayer);
                    widget._addOrigLayerExtent(layerDef);
                });
            });

            if(!overviewLayers.length){
                Mapbender.info(Mapbender.trans("mb.core.overview.nolayer"));
                return;
            }

            var overviewOptions = {
                layers: overviewLayers,
                div: this.$viewport_.get(0),
                size: new OpenLayers.Size(this.options.width, this.options.height),
                mapOptions: {
                    maxExtent: maxExtent,
                    projection: projection,
                    theme: null
                }
            };

            if (this.options.fixed) {
                $.extend(overviewOptions, {
                    minRatio: 1,
                    maxRatio: 1000000000
                    // ,autoPan: false
                });
            }

            this.control_ = new OpenLayers.Control.OverviewMap(overviewOptions);

            this.mbMap_.map.olMap.addControl(this.control_);

            window.setTimeout(function(){
                this.control_.ovmap.updateSize();
            }.bind(this), 300);
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
            if (!this.control_) {
                $(this.element).removeClass('closed');
                window.setTimeout(function() {
                    this._initDisplay();
                }.bind(this), 300);
            } else {
                $(this.element).toggleClass('closed');
            }
        },
        /**
         * Transforms an extent into 'projection' projection.
         */
        _transformExtent: function(extentObj, projection){
            if(extentObj.extent != null){
                if(extentObj.projection.projCode == projection.projCode){
                    return extentObj.extent.clone();
                }else{
                    var newextent = extentObj.extent.clone();
                    newextent.transform(extentObj.projection, projection);
                    return newextent;
                }
            }else{
                return null;
            }
        },

        /**
         * Cahnges the overview srs
         */
        _changeSrs: function(event, srs) {
            var widget = this;
            // @todo 3.1.0: this won't work on OL4, starting here
            var ovMap = this.control_.ovmap;
            var oldProj = ovMap.projection;
            var center = ovMap.getCenter().transform(oldProj, srs.projection);

            ovMap.projection = srs.projection;
            ovMap.displayProjection = srs.projection;
            ovMap.units = srs.projection.proj.units;
            ovMap.maxExtent = widget._transformExtent(widget.mapOrigExtents_.max, srs.projection);

            $.each(ovMap.layers, function(idx, layer) {
                layer.projection = srs.projection;
                layer.units = srs.projection.proj.units;
                if(!widget.layersOrigExtents[layer.id]) {
                    widget._addOrigLayerExtent(layer);
                }
                if(layer.maxExtent && layer.maxExtent != widget.control_.ovmap.maxExtent) {
                    layer.maxExtent = widget._transformExtent(widget.layersOrigExtents[layer.id].max, srs.projection);
                }
                layer.initResolutions();
            });

            this.control_.update();
            ovMap.setCenter(center, ovMap.getZoom(), false, true);
        },

        /**
         * Adds a layer's original extent into the widget layersOrigExtent.
         */
        _addOrigLayerExtent: function(layer) {
            var widget = this;
            var extents = widget.layersOrigExtents;

            if(layer.olLayer) {
                layer = layer.olLayer;
            }
            if(!extents[layer.id]) {
                extents[layer.id] = {
                    max: {
                        projection: widget.startproj,
                        extent:     layer.maxExtent ? layer.maxExtent.clone() : null
                    }
                };
            }
        },

        /**
         * Puts callback on ready
         * @param {function} callback Function this runs after widget is ready
         */
        ready: function(callback){
            if(this.readyState === true){
                callback();
            }else{
                this.readyCallbacks.push(callback);
            }
        },

        /**
         * Runs if widget is ready
         */
        _ready: function() {
            var widget = this;
            widget._trigger('ready');

            for (var callback in widget.readyCallbacks) {
                callback();
                delete(widget.readyCallbacks[callback]);
            }
            widget.readyState = true;
        }

    });

})(jQuery);
