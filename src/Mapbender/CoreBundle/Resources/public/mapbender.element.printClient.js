(function($) {

$.widget("mapbender.mbPrintClient", $.ui.dialog, {
    options: {
        style: {
            fillColor:     '#ffffff',
            fillOpacity:   0.5,
            strokeColor:   '#000000',
            strokeOpacity: 1.0,
            strokeWidth:    3
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
    
    _create: function() {
        var self = this;
        
        this._super('_create');
        
        this._super('option', 'position', $.extend({}, this.options.position, {
            of: window
        }));
        
        this.option('buttons', {
            'Print': $.proxy(this._print, this)
        });
        
        this._loadPrintFormats();
        
        $('select[name="format"],select[name="orientation"],input[name="scale_text"],select[name="scale_select"], input[name="rotation"]', this.element)
            .bind('change', $.proxy(this._updateGeometry, this));
        $('input[name="scale_text"], input[name="rotation"]', this.element)
            .bind('keyup', $.proxy(this._updateGeometry, this));
        $('input[name="free_extent"]', this.element).bind('change',$.proxy(this._updateElements, this));      
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
        //console.log(this.options);
        if(null !== this.options.printer.metadata) {
            throw "Not implemented";
        }
        
        $('select[name="format"]', this.element)
            .bind('change', $.proxy(this._selectFormat, this))
            .trigger('change');
    },
    
    _selectFormat: function(event) {
        var format_key = $(event.target).val(),
            format = this.options.formats[format_key],
            count = 0;
        
        var orientation = $('select[name="orientation"]', this.element);
        orientation.empty();
        
        for(key in format.orientations) {
            orientation.append($('<option></option>', {
                'value': key,
                'html': format.orientations[key].label
            }));
            count++;
        }
        if(count < 2) {
            orientation.parent().hide();
        } else {
            orientation.parent().show();
        }
        
        count = 0;
        var quality = $('select[name="quality"]', this.element);
        quality.empty();
        
        if (null === format.quality_levels){
            quality.parent().hide();
        } else {
            for(key in format.quality_levels) {
                quality.append($('<option></option>', {
                    'value': key,
                    'html': format.quality_levels[key]
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
        if(null === format.scales) {
            var scale = 5000;
            scale_text.val(scale)
                .parent().show();
            scale_select.empty().parent().hide()
        } else {
            scale_text.val('').parent().hide();
            scale_select.empty();
            for(key in format.scales) {
                var scale = format.scales[key];
                scale_select.append($('<option></option>', {                    
                    'value': scale,
                    'html': '1:' + scale
                }));
            }
            scale_select.parent().show();
        }
        
        var title_block_selector = $('input[name="title_block_selector"]', this.element); 
        if(true === format.title_block_selector){
            title_block_selector.get(0).checked = true;
            title_block_selector.parent().show();
        } else {
            title_block_selector.parent().hide();
        }
        
        var rotation = $('input[name="rotation"]', this.element); 
        if(true === format.rotatable){
            rotation.val(0).parent().show();
        } else {
            rotation.parent().hide();
        }
        
        var free_extent = $('input[name="free_extent"]', this.element); 
        if(true === format.free_extent){
            free_extent.parent().show();
        } else {
            free_extent.parent().hide();
        }
        
        // Copy extra fields
        var extra_fields = $('.extra_fields', this.element).empty(),
            extra_form = $('.extra_forms form[name="' + format_key  + '"]');
        if(extra_form.length > 0) {
            extra_fields.html(extra_form.html());
        }
    },
    
    _updateGeometry: function(reset) {
        if($('input[name="free_extent"]', this.element).get(0).checked) {
            return;
        }
        
        var format = this.element.find('select[name="format"]').val();
        var orientation = this.element.find('select[name="orientation"]').val();
        var size = this.options.formats[format].orientations[orientation];
        var width = size.width;
        var height = size.height;
        var scale = this._getPrintScale();
        var rotation = $('input[name="rotation"]').val();
        
        if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
            if(null !== this.lastScale) {
                $('input[name="scale_text"]').val(this.lastScale).change();                
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
                x: size.width * scale / 100,
                y: size.height * scale / 100
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
        
        this.layer.addFeatures(this.feature);
        this.layer.redraw();
    },
    
    _updateElements: function() {
        var isFreeExtent = $('input[name="free_extent"]', this.element).get(0).checked;
        
        if(this.isOpen() && !isFreeExtent) {
            if(null === this.layer) {
                
                this.layer = new OpenLayers.Layer.Vector("Print", {
                    styleMap: new OpenLayers.StyleMap({
                        'default': $.extend({}, OpenLayers.Feature.Vector.style['default'], this.options.style)
                    })
                });
            }
            if(null === this.control) {
                this.control = new OpenLayers.Control.DragFeature(this.layer);
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
        var isFreeExtent = $('input[name="free_extent"]', this.element).get(0).checked,
            data = {
                format: $('select[name="format"]', this.element).val(),
                orientation: $('select[name="orientation"]', this.element).val(),
                quality: $('select[name="quality"]', this.element).val(),
                rotation: $('input[name="rotation"]', this.element).val(),
                isFreeExtent: isFreeExtent,
                extent: {
                    width: null,
                    height: null
                },
                center: {
                    x: null,
                    y: null
                },
                layers: []
            };
        
        if(isFreeExtent) {
            var extent = this.map.map.olMap.getExtent();
            data.extent.width = extent.right - extent.left;
            data.extent.height = Math.abs(extent.bottom - extent.top);
            data.center.x = extent.getCenterLonLat().lon,
            data.center.y = extent.getCenterLonLat().lat
        } else {
            data.extent.width = this.feature.world_size.x;
            data.extent.height = this.feature.world_size.y;
            data.center.x = this.feature.geometry.getBounds().getCenterLonLat().lon;
            data.center.y = this.feature.geometry.getBounds().getCenterLonLat().lat;
        }
        
        var layers = this.map.map.olMap.layers;
        for(var i = 0; i < layers.length; i++) {
            var layer = layers[i],
                type = layer.CLASS_NAME;
                
            if(!(0 === type.indexOf('OpenLayers.Layer.'))) {
                window.console && console.log('Layer is of unknown type for print.', layer);
                continue;
            }
            
            type = type.substr(17).toLowerCase();
            if(type === 'vector') {
                // Vector layers are all the same:
                //   * Get all features as GeoJSON
                //   * TODO: Get Styles...
                
                // TODO: Implement this thing
            } else if(Mapbender.layer[type] && typeof Mapbender.layer[type].getPrintConfig === 'function') {
                data.layers.push(Mapbender.layer[type].getPrintConfig(layer, this.map.map.olMap.getExtent()));
            }
        }
        
        // Collect extra fields
        var extra = {};
        var form_array = $('.format-form form', this.element).serializeArray();
        $.each(form_array, function(idx, field) {
            if('extra[' === field.name.substr(0, 6)) {
                extra[field.name.substr(6, field.name.length - 7)] = field.value;
            }
        });
        data.extra = extra;        
        
        if (this.options.print_directly) {
            this._printDirectly(data);
        } else {
            //@TODO
        }
    },
    
    _printDirectly: function(data){
        var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/direct';
        $.ajax({
            url: url,
            type: 'POST',
            contentType: "application/json; charset=utf-8",
            //contentType: "application/x-www-form-urlencoded",
            dataType: "json",
            data: JSON.stringify(data)
            //data: data
        })
    }
});

})(jQuery);
