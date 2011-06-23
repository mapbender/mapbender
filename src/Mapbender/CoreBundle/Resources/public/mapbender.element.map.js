(function($) {

$.widget("mapbender.mbMap", {
	options: {
		'srs': 'EPSG:4326',
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
        
        me.mapQuery({
            maxExtent: Mapbender.configuration.extents.max,
            maxResolution: 'auto',
            projection: new OpenLayers.Projection(Mapbender.configuration.srs),
            displayProjection: new OpenLayers.Projection(Mapbender.configuration.srs),
            units: Mapbender.configuration.units,

            layers: layers
        });
        this.map = me.data('mapQuery');

        if(Mapbender.configuration.extents.start) {
            this.map.goto({
                box: Mapbender.configuration.extents.start,
            });
        }

        self._trigger('ready');
	},

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
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
                //baselayer, layers, opacity, visible
                mqLayerDef = {
                    type: 'wms',
                    label: layerDef.title,
                    url: layerDef.configuration.url,
                    layers: layerDef.configuration.layers,
                    transparent: layerDef.configuration.transparent,
                    format: layerDef.configuration.format
                };
                return mqLayerDef
                break;
            default:
                throw "Layer type " + layerDef.type + " is not supported by mapbender.mapquery-map";
        }
    }
});

})(jQuery);
