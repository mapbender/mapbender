/*Marker*/
(function($) {

$.widget("mapbender.ol_map", {
	options: {
		'srs': 'EPSG:4326',
		'main': null, //mapset for main map
		'overview': null //mapset for overview map
	},

	map: null,
	highlightLayer: null,
	
	_create: function() {
		var self = this;

		//TODO: Listen to changes on Mapbender.configuration.srs
		if(Mapbender.configuration.srs) {		
			this._setOption('srs', Mapbender.configuration.srs);
		}

		var opts = $.extend({}, {
			theme: null,
			maxResolution: 'auto',
			units: 'm',
			projection: this.options.srs
		});
	
		if(Mapbender.configuration.extents.max) {
			$.extend(opts, { maxExtent: OpenLayers.Bounds.fromArray(Mapbender.configuration.extents.max) });
		}

		if(this.options.scales) {
			$.extend(opts, { scales: this.options.scales });
		}

		$.extend(opts, { controls: [
			new OpenLayers.Control.Navigation(),
			new OpenLayers.Control.ArgParser(),
			new OpenLayers.Control.Attribution(),
			new OpenLayers.Control.PanZoomBar(),
			new OpenLayers.Control.Scale(),
			new OpenLayers.Control.ZoomBox(),
			new OpenLayers.Control.LayerSwitcher()
		] });

	    if(this.options.overview) {
            var layerConf = Mapbender.configuration.layersets[this.options.overview][0];
            var layer = new OpenLayers.Layer.WMS(layerConf.title, layerConf.configuration.url, {
                layers: layerConf.configuration.layers
            });
            opts['controls'].push(new OpenLayers.Control.OverviewMap({
                layers: [layer]
            }));
        }
		
        this.map = new OpenLayers.Map(opts);

		if(this.options.main)
			this._loadLayers('main');

		this.map.render(this.element[0]);


		if(Mapbender.configuration.extents.start) {
			this._setOption('extent', Mapbender.configuration.extents.start);
		}

	},

	_loadLayers: function(target) {
		if(!this.map)
			return;

		var self = this;	
		$.each(Mapbender.configuration.layersets[target], function(key, def) {
			/*
			title
			configuration
				layers
				url
			*/
			// only WMS for now
			var defaults = {
				baselayer: true,
				visible: true,
				opacity: 100,
				tiled: true
			};
			def.configuration = $.extend({}, defaults, def.configuration);

			var layer = new OpenLayers.Layer.WMS(def.title, def.configuration.url, 
			{
				layers: def.configuration.layers,
                                format: def.configuration.format,
                                transparent: def.configuration.transparent
			}, { 
				isBaseLayer: def.configuration.baselayer,
				visibility: def.configuration.visible,
				opacity: def.configuration.opacity / 100.0,
				noMagic: true,
				singleTile: !def.configuration.tiled
			});
			self.map.addLayer(layer);
		});
	},

	_setOption: function(key, value) {
		switch(key) {
			// Set SRS on Map
			case 'srs':
				if(this.map) {
					//TODO
				} else {
					this.options.srs = value;
				}
				break;
			case 'extent':
				this.options.extent = OpenLayers.Bounds.fromArray(value);
				this.map.zoomToExtent(this.options.extent);
		}
	},

	destroy: function() {
		$.Widget.prototype.destroy.call(this);
	},

	highlight: function(features) {
            var self = this;
			if(!this.highlightLayer) {
				this.highlightLayer = new OpenLayers.Layer.Vector();
				this.map.addLayer(this.highlightLayer);
                var selectControl = new OpenLayers.Control.SelectFeature(this.highlightLayer, {
                    hover: true,
                    onSelect: function(feature) {
                        self._trigger('highlighthoverin', null, {
                            feature: feature
                        });
                    },
                    onUnselect: function(feature) {
                        self._trigger('highlighthoverout', null, {
                            feature: feature
                        });
                    }
                });
                this.map.addControl(selectControl);
                selectControl.activate();
			}
            var hll = this.highlightLayer;

			hll.removeAllFeatures();
			hll.addFeatures(features);
			var extent = hll.getDataExtent();
			this.map.zoomToExtent(extent);
	},

    addPopup: function(popup) {
        this.map.addPopup(popup);
    },

    removePopup: function(popup) {
        this.map.removePopup(popup);
    },

    zoomToExtent: function(extent) {
        this.map.zoomToExtent(extent);
    }
});

})(jQuery);
