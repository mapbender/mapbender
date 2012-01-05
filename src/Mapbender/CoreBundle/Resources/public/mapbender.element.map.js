(function($) {

OpenLayers.ProxyHost = Mapbender.configuration.proxies.open + '?url=';

$.widget("mapbender.mbMap", {
    options: {
        layerset: null, //mapset for main map
        dpi: OpenLayers.DOTS_PER_INCH,
        srs: 'EPSG:4326',
        units: 'degrees',
        extents: {
            max: [-180, -90, 180, 90],
            start: [-180, -90, 180, 90]
        },
        maxResolution: 'auto'
    },

    map: null,
    highlightLayer: null,

    _create: function() {
        var self = this,
            me = $(this.element);

        if(typeof(this.options.dpi) !== 'undefined') {
            OpenLayers.DOTS_PER_INCH = this.options.dpi;
        }

        OpenLayers.ImgPath = Mapbender.configuration.assetPath + '/bundles/mapbendercore/mapquery/lib/openlayers/img/';

        // Prepare initial layers
        var layers = [];
        var allOverlays = true;
        $.each(Mapbender.configuration.layersets[this.options.layerset], function(idx, layerDef) {
			layers.push(self._convertLayerDef.call(self, layerDef));
            allOverlays = allOverlays && (layerDef.configuration.baselayer !== true);
        });

        var mapOptions = {
            maxExtent: this.options.extents.max,
            maxResolution: this.options.maxResolution,
            numZoomLevels: this.options.numZoomLevels,
            projection: new OpenLayers.Projection(this.options.srs),
            displayProjection: new OpenLayers.Projection(this.options.srs),
            units: this.options.units,
            allOverlays: allOverlays,
            theme: null,
            layers: layers
        };

        if(this.options.scales) {
            $.extend(mapOptions, {
                scales: this.options.scales
            });
        }

        me.mapQuery(mapOptions);
        this.map = me.data('mapQuery');

        //TODO: Bind all events
        this.map.bind('zoomend', function() { self._trigger('zoomend', arguments); });

        if(this.options.extents.start) {
            this.map.center({
                box: this.options.extents.start
            });
        }
        if(this.options.extra.type) {
            switch(this.options.extra.type) {
                case 'poi':
                    this.map.center({
                        position: [ this.options.extra.data.x,
                            this.options.extra.data.y ]
                    });
                    if(this.options.extra.data.scale) {
                        this.zoomToScale(this.options.extra.data.scale);
                    }

                    if(this.options.extra.data.label) {
                        var position = new OpenLayers.LonLat(
                            this.options.extra.data.x,
                            this.options.extra.data.y);
                        var popup = new OpenLayers.Popup.FramedCloud('chicken',
                            position,
                            null,
                            this.options.extra.data.label,
                            null,
                            true,
                            function() {
                                self.removePopup(this);
                                this.destroy();
                            });
                        this.addPopup(popup);
                    }
                    break;
                case 'bbox':
                    this.map.center({
                        box: [
                            this.options.extra.data.xmin, this.options.extra.data.ymin,
                            this.options.extra.data.xmax, this.options.extra.data.ymax
                        ]});
            }
        }

        if(this.options.overview) {
//			var layerConf = Mapbender.configuration.layersets[this.options.overview.layerset][0];
//			var layer = new OpenLayers.Layer.WMS(layerConf.title, layerConf.configuration.url, {
//				layers: $.map(layerConf.configuration.layers, function(layer) {
//					return layer.name;
//				})
//			});

			var layers_ = [];
			$.each(Mapbender.configuration.layersets[this.options.overview.layerset], function(idx, layerDef) {
				layers_.push(self._convertLayerDef.call(self, layerDef));
			});
			window.console && console.log(layers_);
			var res = $.MapQuery.Layer.types[layers_[0].type].call(this, layers_[0]);
			var overviewOptions = {
                layers: res.layer,
                mapOptions: {
                    maxExtent: OpenLayers.Bounds.fromArray(this.options.extents.max),
                    projection: new OpenLayers.Projection(this.options.srs)
                }
            };
            if(this.options.overview.fixed) {
                $.extend(overviewOptions, {
                    minRatio: 1,
                    maxRatio: 1000000000
                });
            }
            var overviewControl = new OpenLayers.Control.OverviewMap(overviewOptions);
            this.map.olMap.addControl(overviewControl);
        }
        this.map.olMap.addControl(new OpenLayers.Control.Scale());
        this.map.olMap.addControl(new OpenLayers.Control.PanZoomBar());

        self._trigger('ready');
    },

    /**
     * DEPRECATED
     */
    goto: function(options) {
        this.map.center(options);
    },

    center: function(options) {
        this.map.center(options);
    },

    highlight: function(features, options) {
        var self = this;
        if(!this.highlightLayer) {
            this.highlightLayer = this.map.layers({
                type: 'vector',
                label: 'Highlight'});
            var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer.olLayer, {
                hover: true,
                onSelect: function(feature) {
                    self._trigger('highlighthoverin', null, { feature: feature });
                },
                onUnselect: function(feature) {
                    self._trigger('highlighthoverout', null, { feature: feature });
                }
            });
            this.map.olMap.addControl(selectControl);
            selectControl.activate();
        }


        var o = $.extend({}, {
            clearFirst: true,
            goto: true
        }, options);

        // Remove existing features if requested
        if(o.clearFirst) {
            this.highlightLayer.olLayer.removeAllFeatures();
        }

        // Add new highlight features
        this.highlightLayer.olLayer.addFeatures(features);

        // Goto features if requested
        if(o.goto) {
            var bounds = this.highlightLayer.olLayer.getDataExtent();
            this.map.center({box: bounds.toArray()});
        }

        this.highlightLayer.bind('featureselected',   function() { self._trigger('highlightselected', arguments); });
        this.highlightLayer.bind('featureunselected', function() { self._trigger('highlightunselected', arguments); });
    },

    layer: function(layerDef) {
        this.map.layers(this._convertLayerDef(layerDef));
    },

    _convertLayerDef: function(layerDef) {
        if(typeof Mapbender.layer[layerDef.type] !== 'object'
            && typeof Mapbender.layer[layerDef.type].create !== 'function') {
            throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
        // TODO object should be cleaned up
        return $.extend(Mapbender.layer[layerDef.type].create(layerDef), { mapbenderId: layerDef.id, configuration: layerDef });
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
        //TODO: MapQuery
    },

    addPopup: function(popup) {
        //TODO: MapQuery
        this.map.olMap.addPopup(popup);
    },

    removePopup: function(popup) {
        //TODO: MapQuery
        this.map.olMap.removePopup(popup);
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
    }
});

})(jQuery);
