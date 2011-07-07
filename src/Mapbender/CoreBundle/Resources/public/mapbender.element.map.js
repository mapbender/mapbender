(function($) {

$.widget("mapbender.mbMap", {
	options: {
		'layerset': null, //mapset for main map
	},

	map: null,
	highlightLayer: null,
	
	_create: function() {
		var self = this,
            me = $(this.element);

        // Prepare initial layers
        var layers = [];
        $.each(Mapbender.configuration.layersets[this.options.layerset], function(idx, layerDef) {
            layers.push(self._convertLayerDef.call(self, layerDef));
        });
        
        var mapOptions = {
            maxExtent: Mapbender.configuration.extents.max,
            maxResolution: 'auto',
            projection: new OpenLayers.Projection(Mapbender.configuration.srs),
            displayProjection: new OpenLayers.Projection(Mapbender.configuration.srs),
            units: Mapbender.configuration.units,
            allOverlays: false,

            layers: layers
        };

        if(this.options.scales) {
            $.extend(mapOptions, {
                scales: this.options.scales
            });
        }

        me.mapQuery(mapOptions);
        this.map = me.data('mapQuery');

        if(Mapbender.configuration.extents.start) {
            this.map.goto({
                box: Mapbender.configuration.extents.start,
            });
        }

        if(this.options.overview) {
            var layerConf = Mapbender.configuration.layersets[this.options.overview][0];
            var layer = new OpenLayers.Layer.WMS(layerConf.title, layerConf.configuration.url, {
                layers: layerConf.configuration.layers
            });
            var overviewControl = new OpenLayers.Control.OverviewMap({
                layers: [layer],
                mapOptions: {
                    maxExtent: OpenLayers.Bounds.fromArray(Mapbender.configuration.extents.max),
                    projection: new OpenLayers.Projection(Mapbender.configuration.srs)
                }
            });
            this.map.olMap.addControl(overviewControl);
        }
        this.map.olMap.addControl(new OpenLayers.Control.Scale());
        this.map.olMap.addControl(new OpenLayers.Control.LayerSwitcher());
        this.map.olMap.addControl(new OpenLayers.Control.PanZoomBar());

        self._trigger('ready');
	},

    goto: function(options) {
        this.map.goto(options);
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
            this.map.goto({box: bounds.toArray()});
        }

        this.highlightLayer.bind('featureselected',   function() { self._trigger('highlightselected', arguments); });
        this.highlightLayer.bind('featureunselected', function() { self._trigger('highlightunselected', arguments); });
    },

    layer: function(layerDef) {
        this.map.layers(this._convertLayerDef(layerDef));
    },

    _convertLayerDef: function(layerDef) {
        var mqLayerDef = null;
        switch(layerDef.type) {
            case 'wms':
                mqLayerDef = {
                    type:        'wms',
                    label:       layerDef.title,
                    url:         layerDef.configuration.url,

                    layers:      layerDef.configuration.layers,
                    queryLayers: layerDef.configuration.queryLayers,

                    transparent: layerDef.configuration.transparent,
                    format:      layerDef.configuration.format,

                    isBaseLayer:   layerDef.configuration.baselayer,
                    opacity:     layerDef.configuration.opacity,
                    visible:     layerDef.configuration.visible,
                    tiled:       layerDef.configuration.tiled
                };
                return mqLayerDef
                break;
            case 'wfs':
                return {
                    type: 'wfs',
                    version: layerDef.configuration.version,
                    label: layerDef.title,
                    url: layerDef.configuration.url,
                    featureType: layerDef.configuration.featureType,
                    featureNS: layerDef.configuration.featureNS,
                };

            default:
                throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
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
    }
});

})(jQuery);
