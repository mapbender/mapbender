(function($) {

$.widget("mapbender.mbPrintClient", $.ui.dialog, {
    options: {
        style: {
            fillColor:     '#ffffff',
            fillOpacity:   0.5,
            strokeColor:   '#000000',
            strokeOpacity: 1.0,
            strokeWidth:    2
        },
        position: {
            my: 'right top',
            at: 'right top',
            offset: '-50 50'
        }
    },
    map: null,
    layer: null,
    control: null,
    feature: null,
    lastScale: null,
    lastRotation: null,
    width: null,
    height: null,
    
    _create: function() 
    {
        var self = this;       
        this._super('_create');      
        this._super('option', 'position', $.extend({}, this.options.position, {
            of: window
        }));
        
        $('#printbutton', this.element)
            .bind('click', $.proxy(this._print, this));
        this._loadPrintFormats();
        
        $('input[name="scale_text"],select[name="scale_select"], input[name="rotation"]', this.element)
            .bind('change', $.proxy(this._updateGeometry, this));
        $('input[name="scale_text"], input[name="rotation"]', this.element)
            .bind('keyup', $.proxy(this._updateGeometry, this));
        $('select[name="template"]', this.element)
            .bind('change', $.proxy(this._getPrintSize, this))
            .trigger('change');
    },
    
    open: function() {     
        this._super('open');

        this.map = $('#' + this.options.target).data('mbMap');
        
        this._updateElements();                
        this._updateGeometry(true);
    },
    
    close: function() {
        this._super('close');
        this._updateElements();
    },
    
    _loadPrintFormats: function() {
        var self = this;
//        if(null !== this.options.printer.metadata) {
//            throw "Not implemented";
//        }
        
        var count = 0;
        var quality_levels = this.options.quality_levels;
        var quality = $('select[name="quality"]', this.element);
        quality.empty();  
        if (null === quality_levels){
            quality.parent().hide();
        } else {
            for(key in quality_levels) {
                quality.append($('<option></option>', {
                    'value': key,
                    'html': quality_levels[key]
                }));
                count++;
            }
            if(count < 2) {
                quality.parent().hide();
            } else {
                quality.parent().show();
            }
        }
        
        var scale_text = $('input[name="scale_text"]', this.element),
            scale_select = $('select[name="scale_select"]', this.element);
        if(null === this.options.scales) {
            var scale = 5000;
            scale_text.val(scale).parent().show();
            scale_select.empty().parent().hide()
        } else {
            scale_text.val('').parent().hide();
            scale_select.empty();
            for(key in this.options.scales) {
                var scale = this.options.scales[key];
                scale_select.append($('<option></option>', {                    
                    'value': scale,
                    'html': '1:' + scale
                }));
            }
            scale_select.parent().show();
        }
        
        var rotation = $('input[name="rotation"]', this.element); 
        var sliderDiv = $('#slider', this.element);
        if(true === this.options.rotatable){
            rotation.val(0).parent().show();
            var slider = sliderDiv.slider({
                min: 0,
                max: 360,
                range: "min",
                step: 5,
                value: rotation.val(),
                slide: function( event, ui ) {
                    rotation.val(ui.value);
                    self._updateGeometry(false);
                }
            });
            $(rotation).keyup(function() {
                slider.slider( "value", this.value );
            }); 
        } else {
            rotation.parent().hide();
        }
        console.log('hier');
        // Copy extra fields
        var opt_fields = this.options.optional_fields;
        var hasAttr = false;
        for(field in opt_fields){
            hasAttr = true;
            break;
        }
        if(hasAttr) {
            var extra_fields = $('#extra_fields', this.element).empty(),
                extra_form = $('#extra_forms form[name="extra"]');
            if(extra_form.length > 0) {
                extra_fields.html(extra_form.html());
            }
        }else{
            $('#extra_fields').hide();
        }
    },
    
    _updateGeometry: function(reset) {
        var template = this.element.find('select[name="template"]').val();
        var width = this.width;
        var height = this.height;
        var scale = this._getPrintScale();
        var rotation = $('input[name="rotation"]').val();
        
        if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
            if(null !== this.lastScale) {
                //$('input[name="scale_text"]').val(this.lastScale).change();                
            }
            return;
        }        
        scale = parseInt(scale);
        
        if(!(!isNaN(parseFloat(rotation)) && isFinite(rotation))) {
            if(null !== this.lastRotation) {
                $('input[name="rotation"]').val(this.lastRotation).change();                
            }
            return;
        }        
        rotation= parseInt(-rotation);
        
        this.lastScale = scale;
        
        var world_size = {
                x: width * scale / 100,
                y: height * scale / 100
            };
        
        var center = (reset === true || !this.feature) ?
            this.map.map.olMap.getCenter() :
            this.feature.geometry.getBounds().getCenterLonLat();
        
        if(this.feature) {
            this.layer.removeAllFeatures();
            this.feature = null;
        }
        
        this.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
            center.lon - 0.5 * world_size.x,
            center.lat - 0.5 * world_size.y,
            center.lon + 0.5 * world_size.x,
            center.lat + 0.5 * world_size.y).toGeometry(), {});
        this.feature.world_size = world_size;
        this.feature.geometry.rotate(rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
        /*
        var centroid = this.feature.geometry.getCentroid();
        var centroid_lonlat = new OpenLayers.LonLat(centroid.x,centroid.y);
        var centroid_pixel = this.map.map.olMap.getViewPortPxFromLonLat(centroid_lonlat);
        var centroid_geodesSize = this.map.map.olMap.getGeodesicPixelSize(centroid_pixel);
        
        //var geodes_width = size.width * scale / (centroid_geodesSize.w * 100000);
        //var geodes_height = size.height * scale / (centroid_geodesSize.h * 100000);
        var geodes_diag = Math.sqrt(centroid_geodesSize.w*centroid_geodesSize.w + centroid_geodesSize.h*centroid_geodesSize.h) / Math.sqrt(2) * 100000;
        
        var geodes_width = size.width * scale / (geodes_diag);
        var geodes_height = size.height * scale / (geodes_diag);
        
        var ll_pixel_x = centroid_pixel.x - (geodes_width) / 2;
        var ll_pixel_y = centroid_pixel.y + (geodes_height) / 2;
        var ur_pixel_x = centroid_pixel.x + (geodes_width) / 2;
        var ur_pixel_y = centroid_pixel.y - (geodes_height) /2 ;
        var ll_pixel = new OpenLayers.Pixel(ll_pixel_x, ll_pixel_y);
        var ur_pixel = new OpenLayers.Pixel(ur_pixel_x, ur_pixel_y);
        var ll_lonlat = this.map.map.olMap.getLonLatFromPixel(ll_pixel);
        var ur_lonlat = this.map.map.olMap.getLonLatFromPixel(ur_pixel);
        
        console.log(geodes_diag);
               
        console.log(ll_lonlat, ur_lonlat, ll_pixel, ur_pixel, geodes_width, geodes_height);
        this.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
            ll_lonlat.lon,
            ur_lonlat.lat,
            ur_lonlat.lon,
            ll_lonlat.lat).toGeometry(), {});
        this.feature.world_size = {
            x: ur_lonlat.lon - ll_lonlat.lon,
            y: ur_lonlat.lat - ll_lonlat.lat
        };
        */
        this.layer.addFeatures(this.feature);
        this.layer.redraw();
    },
    
    _updateElements: function() {
        var self = this;
        
        if(this.isOpen()) {
            if(null === this.layer) {
                
                this.layer = new OpenLayers.Layer.Vector("Print", {
                    styleMap: new OpenLayers.StyleMap({
                        'default': $.extend({}, OpenLayers.Feature.Vector.style['default'], this.options.style)
                    })
                });
            }
            if(null === this.control) {
                this.control = new OpenLayers.Control.DragFeature(this.layer,  {
                    onComplete: function() {
                        self._updateGeometry(false);
                    }
                });
            }
            this.map.map.olMap.addLayer(this.layer);
            this.map.map.olMap.addControl(this.control);
            this.control.activate();
            
            this._updateGeometry(true);
        } else {
            if(null !== this.control) {
                this.control.deactivate();
                this.map.map.olMap.removeControl(this.control);
            }
            if(null !== this.layer) {
                this.map.map.olMap.removeLayer(this.layer);
            }
        }
    },
    
    _getPrintScale: function() {
        return $('select[name="scale_select"],input[name="scale_text"]').filter(':visible').val();
    },
    
    _print: function() {
        if (this.options.print_directly) {
            this._printDirectly()
        } else {
            //@TODO
            this._printQueued();
        }
    },
    
    _getPrintExtent: function() {
        var data = {
                extent: {},
                center: {}
            };
            
        data.extent.width = this.feature.world_size.x;
        data.extent.height = this.feature.world_size.y;
        data.center.x = this.feature.geometry.getBounds().getCenterLonLat().lon;
        data.center.y = this.feature.geometry.getBounds().getCenterLonLat().lat;     
        
        return data;
    },
    
    _printDirectly: function() {
        var form = $('form#formats', this.element),
            extent = this._getPrintExtent();
        form.find('div.layers').html('');
        var template_key = this.element.find('select[name="template"]').val(),
            format = this.options.templates[template_key].format;
        
        // Felder für extent, center und layer dynamisch einbauen
        var fields = $();
        
        $.merge(fields, $('<input />', {
            type: 'hidden',
            name: 'format',
            value: format
        }));
        
        $.merge(fields, $('<input />', {
            type: 'hidden',
            name: 'extent[width]',
            value: extent.extent.width
        }));
        
        $.merge(fields, $('<input />', {
            type: 'hidden',
            name: 'extent[height]',
            value: extent.extent.height
        }));
        
        $.merge(fields, $('<input />', {
            type: 'hidden',
            name: 'center[x]',
            value: extent.center.x
        }));
        
        $.merge(fields, $('<input />', {
            type: 'hidden',
            name: 'center[y]',
            value: extent.center.y
        }));
        
        var sources = this.map.getSourceTree(), num = 0;
        
        for(var i = 0; i < sources.length; i++) {
            var layer = this.map.map.layersList[sources[i].mqlid],
                type = layer.olLayer.CLASS_NAME;
//            if(!sources[i].configuration.children[0].state.visibility){
            if(layer.olLayer.params.LAYERS.length === 0){
                continue;
            }    
            if(!(0 === type.indexOf('OpenLayers.Layer.'))) {
                window.console && console.log('Layer is of unknown type for print.', layer);
                continue;
            }
            
//            type = type.substr(17).toLowerCase();
            if(layer.olLayer.type === 'vector') {
                // Vector layers are all the same:
                //   * Get all features as GeoJSON
                //   * TODO: Get Styles...  
                // TODO: Implement this thing
            } else if(Mapbender.source[sources[i].type] && typeof Mapbender.source[sources[i].type].getPrintConfig === 'function') {
                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: 'layers[' + num + ']',
                    value: JSON.stringify(Mapbender.source[sources[i].type].getPrintConfig(layer.olLayer, this.map.map.olMap.getExtent()))
                }));
                num++;
            }
        }
        
        fields.appendTo(form.find('div#layers'));
        // Post in neuen Tab (action bei form anpassen)
        
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/direct';   
        
        //form.attr('action', url);
        form.get(0).setAttribute('action', url);
        form.attr('target', '_blank');
        form.attr('method', 'post');
        form.submit();
        
        // Felder und action wieder rausnehmen
        //console.log(fields);
        //form.attr('action', null);
        //fields.remove();
    },
    
//    _printQueued: function(){
//        var isFreeExtent = $('input[name="free_extent"]', this.element).get(0).checked,
//            data = {
//                format: $('select[name="format"]', this.element).val(),
//                orientation: $('select[name="orientation"]', this.element).val(),
//                quality: $('select[name="quality"]', this.element).val(),
//                rotation: $('input[name="rotation"]', this.element).val(),
//                isFreeExtent: isFreeExtent,
//                extent: {
//                    width: null,
//                    height: null
//                },
//                center: {
//                    x: null,
//                    y: null
//                },
//                layers: []
//            };
//        
//        $.extend(true, data, this._getPrintExtent());
//        
//        var layers = this.map.map.olMap.layers;
//        for(var i = 0; i < layers.length; i++) {
//            var layer = layers[i],
//                type = layer.CLASS_NAME;
//                
//            if(!(0 === type.indexOf('OpenLayers.Layer.'))) {
//                window.console && console.log('Layer is of unknown type for print.', layer);
//                continue;
//            }
//            
//            type = type.substr(17).toLowerCase();
//            if(type === 'vector') {
//                // Vector layers are all the same:
//                //   * Get all features as GeoJSON
//                //   * TODO: Get Styles...
//                
//                // TODO: Implement this thing
//            } else if(Mapbender.layer[type] && typeof Mapbender.layer[type].getPrintConfig === 'function') {
//                data.layers.push(Mapbender.layer[type].getPrintConfig(layer, this.map.map.olMap.getExtent()));
//            }
//        }
//        
//        // Collect extra fields
//        var extra = {};
//        var form_array = $('.format-form form', this.element).serializeArray();
//        $.each(form_array, function(idx, field) {
//            if('extra[' === field.name.substr(0, 6)) {
//                extra[field.name.substr(6, field.name.length - 7)] = field.value;
//            }
//        });
//        data.extra = extra;        
//        
//        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/direct';
//        $.ajax({
//            url: url,
//            type: 'POST',
//            contentType: "application/json; charset=utf-8",
//            dataType: "json",
//            data: JSON.stringify(data),
//            success: function(data) {
//                //@TODO
//            }
//        })
//    },
    
    _getPrintSize: function() {
        var self = this;
        var template = $('select[name="template"]', this.element).val();
        data = {template: template};
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/template';
        $.ajax({
            url: url,
            type: 'POST',
            //contentType: "application/json; charset=utf-8",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify(data),
            success: function(data) {
                console.log('size: '+data['width']+' '+data['height']);
                self.width = data['width'];
                self.height = data['height'];
                self._updateGeometry();
            }
        })
    }
});

})(jQuery);
