(function($) {

    $.widget("mapbender.mbPrintClient",  {
        options: {
            style: {
                fillColor:     '#ffffff',
                fillOpacity:   0.5,
                strokeColor:   '#000000',
                strokeOpacity: 1.0,
                strokeWidth:    2
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
        popup: true,
    
        _create: function() {
            if(!Mapbender.checkTarget("mbPrintClient", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            //this.elementUrl = Mapbender.configuration.elementPath + me.attr('id') + '/';
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },
    
        _setup: function(){     
            this.map = $('#' + this.options.target).data('mbMap');
     
            $('input[name="scale_text"],select[name="scale_select"], input[name="rotation"]', this.element)
            .bind('change', $.proxy(this._updateGeometry, this));
            $('input[name="scale_text"], input[name="rotation"]', this.element)
            .bind('keyup', $.proxy(this._updateGeometry, this));
            $('select[name="template"]', this.element)
            .bind('change', $.proxy(this._getPrintSize, this))
            .trigger('change');
        },
    
        open: function() {  
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!$('body').data('mbPopup')) {             
                $("body").mbPopup();         
                $("body").mbPopup("addButton", "Close", "button right", function(){
                    //close
                    $("body").mbPopup("close");
                    self._close();
                    
                }).mbPopup("addButton", "Print", "button left", function(){
                    //print
                    self._print();
                }).mbPopup('showCustom', {
                    title:"Print Client", 
                    showHeader:true, 
                    content: this.element, 
                    draggable: true,
                    width: 300,
                    height: 100,
                    showCloseButton: false
                });
            }
            
            this._loadPrintFormats();           
            this._updateElements();                
            this._updateGeometry(true);
        },
    
        _close: function() {
            //this._super('close');
            this.popup = false;
            this._updateElements();
        },
    
        _loadPrintFormats: function() {
            var self = this;     
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
            this.layer.addFeatures(this.feature);
            this.layer.redraw();
        },
    
        _updateElements: function() {
            var self = this;

            if(true == this.popup){
            //if(this.isOpen()) {
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
                if(layer.olLayer.params.LAYERS.length === 0){
                    continue;
                }    
                if(!(0 === type.indexOf('OpenLayers.Layer.'))) {
                    window.console && console.log('Layer is of unknown type for print.', layer);
                    continue;
                }
            
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
            data = {
                template: template
            };
            var url =  Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/template';
            $.ajax({
                url: url,
                type: 'POST',
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify(data),
                success: function(data) {
                    self.width = data['width'];
                    self.height = data['height'];
                    self._updateGeometry();
                }
            })
        }
    });

})(jQuery);
