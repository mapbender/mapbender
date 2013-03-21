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
        if(!Mapbender.checkTarget("mbOverview", this.options.target)){
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
        if(this.options.anchor === "left-top"){
            $(this.element).css({
                left: this.options.position[0],
                top: this.options.position[1]
            });
        } else if(this.options.anchor === "right-top"){
            $(this.element).css({
                right: this.options.position[0],
                top: this.options.position[1]
            });
        } else if(this.options.anchor === "left-bottom"){
            $(this.element).css({
                left: this.options.position[0],
                bottom: this.options.position[1]
            });
        } else if(this.options.anchor === "right-bottom"){
            $(this.element).css({
                right: this.options.position[0],
                bottom: this.options.position[1]
            });
        }
        var mbMap = $('#' + this.options.target).data('mbMap');
        $(this.element).addClass(this.options.anchor);
        if(!this.options.maximized) {
            $(this.element).addClass("closed");
        }

        var max_ext = this.options.extents && this.options.extents.max ? this.options.extents.max : mbMap.options.extents.max;
        max_ext = max_ext ? OpenLayers.Bounds.fromArray(max_ext) : null;
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
        if(layers_overview.length === 0){
            Mapbender.error('The overview element has no layer.');
            return;
        }
        this.mapOrigExtents = {
            max: {
                projection: proj,
                extent: max_ext
            }
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
        $(document).bind('mbmapsrschanged', $.proxy(this._changeSrs, this));
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
            layer.projection = srs.projection;
            layer.units = srs.projection.proj.units;
            if(!self.layersOrigExtents[layer.id]){
                self._addOrigLayerExtent(layer);
            }
            if(layer.maxExtent && layer.maxExtent != self.overview.ovmap.maxExtent){
                layer.maxExtent = self._transformExtent(
                    self.layersOrigExtents[layer.id].max, srs.projection);
            }
            layer.initResolutions();
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
            this.layersOrigExtents[layer.id] = {
                max: {
                    projection: this.startproj,
                    extent: layer.maxExtent ? layer.maxExtent.clone() : null
                }
            };
        }
    }

});

})(jQuery);