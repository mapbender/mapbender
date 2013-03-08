(function($) {

    $.widget("mapbender.mbOverview", {
        options: {
            layerset: []
        },
        overview: null,
        layersOrigExtents: {},
        mapOrigExtents: {},
        startproj: null,

        /**
         * Creates the overview
         */
        _create: function() {
            if(this.options.target === null
                || this.options.target.replace(/^\s+|\s+$/g, '') === ""
                || !$('#' + this.options.target)){
                alert('The target element "map" is not defined for an overview.');
                return;
            }
            var self = this;
            $(document).one('mapbender.setupfinished', function() {
                $('#' + self.options.target).mbMap('ready', $.proxy(self._setup, self));
            });
        },
        
        /**
         * Initializes the overview
         */
        _setup: function() {
            var self = this;
            var mbMap = $('#' + this.options.target).data('mbMap');
            $(this.element).addClass(this.options.position);
            if(!this.options.maximized) {
                $(this.element).addClass("closed");
            }
            
            var max_ext = this.options.extents && this.options.extents.max ? this.options.extents.max : mbMap.options.extents.max;
            max_ext = max_ext ? OpenLayers.Bounds.fromArray(max_ext) : null;
//            var min_ext = this.options.extents && this.options.extents.min ? this.options.extents.min : mbMap.options.extents.min;
//            min_ext = min_ext ? OpenLayers.Bounds.fromArray(min_ext) : null;
            var proj = this.options.srs ? new OpenLayers.Projection(this.options.srs) : mbMap.map.olMap.getProjectionObject();
            if(proj.projCode === 'EPSG:4326') {
                proj.proj.units = 'degrees';
            }
            this.startproj = proj;
            var layers_overview = [];
            $.each(Mapbender.configuration.layersets[self.options.layerset],
                function(idx, item) {
                    $.each(item, function(idx2, layerDef) {
                        if(layerDef.type === "wms"){
                            var ls = "";
                            $.each(layerDef.configuration.layers, function(idx3, layDef) {
                                if(layDef.name && layDef.name !== ""){
                                    ls += "," + layDef.name;
                                }
                            });
                            layers_overview.push(new OpenLayers.Layer.WMS(
                                layerDef.title,
                                layerDef.configuration.url, {
                                    layers: ls.substring(1)
                                }));
                            self._addOrigLayerExtent(layerDef);
                        }
                    });
                });
            this.mapOrigExtents = {
                max: {
                    projection: proj,
                    extent: max_ext
                }
//                min: {
//                    projection: proj,
//                    extent: min_ext
//                }
            };
            
            this.overview = new OpenLayers.Control.OverviewMap({
                div: $(self.element).find('#mb-element-overview-map').get(0),
                size: new OpenLayers.Size(self.options.width, self.options.height),
                //maximized: self.options.maximized,
                mapOptions: {
                    maxExtent: max_ext,
                    projection: proj,
                    theme: null
                },
                layers: layers_overview
            });
            if(this.options.fixed) {
                $.extend(this.overview, {
                    minRatio: 1,
                    maxRatio: 1000000000,
                    autoPan: false
                });
            }
            mbMap.map.olMap.addControl(this.overview);
            $(document).bind('mbsrsselectorsrschanged', $.proxy(this._changeSrs, this));
            $(self.element).find('.handle').bind('click', $.proxy(this._openClose, this));
        },
        
        /**
         * Opens/closes the overview element
         */
        _openClose: function(event){
            if($(this.element).hasClass('closed')){
                $(this.element).removeClass('closed');
            } else {
                $(this.element).addClass('closed');
            }
        },
        
        /*
         * Transforms an extent into 'projection' projection.
         */
        _transformExtent: function(extentObj, projection){
            if(extentObj.extent != null){
                if(extentObj.projection.projCode == projection.projCode){
                    return extentObj.extent.clone();
                } else {
                    var newextent = extentObj.extent.clone();
                    newextent.transform(extentObj.projection, projection);
                    return newextent;
                }
            } else {
                return null;
            }
        },
        
        /**
         * Cahnges the overview srs
         */
        _changeSrs: function(event, srs){
            var self = this;
            var oldProj = this.overview.ovmap.projection;
            var center = this.overview.ovmap.getCenter().transform(oldProj, srs.projection);
            this.overview.ovmap.projection = srs.projection;
            this.overview.ovmap.displayProjection= srs.projection;
            this.overview.ovmap.units = srs.projection.proj.units;

            this.overview.ovmap.maxExtent = this._transformExtent(
                this.mapOrigExtents.max, srs.projection);
            $.each(self.overview.ovmap.layers, function(idx, layer){
                //            if(layer.isBaseLayer){
                layer.projection = srs.projection;
                layer.units = srs.projection.proj.units;
                if(!self.layersOrigExtents[layer.id]){
                    self._addOrigLayerExtent(layer);
                }
                if(layer.maxExtent && layer.maxExtent != self.overview.ovmap.maxExtent){
                    layer.maxExtent = self._transformExtent(
                        self.layersOrigExtents[layer.id].max, srs.projection);
                }

//                if(layer.minExtent){
//                    layer.minExtent = self._transformExtent(
//                        self.layersOrigExtents[layer.id].minExtent, srs.projection);
//                }
                layer.initResolutions();
            //            }
            });
            this.overview.update();
            this.overview.ovmap.setCenter(center, this.overview.ovmap.getZoom(), false, true);
        },
        
        /**
         * Adds a layer's original extent into the widget layersOrigExtent.
         */
        _addOrigLayerExtent: function(layer) {
            if(layer.olLayer) {
                layer = layer.olLayer;
            }
            if(!this.layersOrigExtents[layer.id]){
//                var extProjection = new OpenLayers.Projection(this.options.srs);
//                if(extProjection.projCode === 'EPSG:4326') {
//                    extProjection.proj.units = 'degrees';
//                }
                this.layersOrigExtents[layer.id] = {
                    max: {
                        projection: this.startproj,
                        extent: layer.maxExtent ? layer.maxExtent.clone() : null
                    }
//                    min: {
//                        projection: extProjection,
//                        extent: layer.minExtent ? layer.minExtent.clone() : null
//                    }
                };
            }
        }
        
    });

})(jQuery);