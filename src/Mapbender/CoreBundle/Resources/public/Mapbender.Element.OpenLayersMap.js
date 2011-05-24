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
			var layer = new OpenLayers.Layer.WMS(def.title, def.configuration.url, 
			{
				layers: def.configuration.layers,
                                format: def.configuration.format,
                                transparent: def.configuration.transparent
			}, { 
				isBaseLayer: def.configuration.baselayer,
				visibility: def.configuration.visible
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

	highlight: function(geom) {
			if(!this.highlightLayer) {
				var hll = this.highlightLayer = new OpenLayers.Layer.Vector();
				this.map.addLayer(hll);
			} 
			geojsonFormat = new OpenLayers.Format.GeoJSON();
			
			hll.removeAllFeatures();
			hll.addFeatures(geojsonFormat.read(geom));
			var extent = hll.getDataExtent();
			this.map.zoomToExtent(extent);
	}
});

})(jQuery);
