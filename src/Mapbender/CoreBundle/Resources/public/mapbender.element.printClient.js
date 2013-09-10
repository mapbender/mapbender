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
        popupIsOpen: true,

        _create: function() {
            if(!Mapbender.checkTarget("mbPrintClient", this.options.target)){
                return;
            }
            var self = this;
            var me = this.element;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function(){
            this.map = $('#' + this.options.target).data('mapbenderMbMap');

            $('input[name="scale_text"],select[name="scale_select"], input[name="rotation"]', this.element)
            .bind('change', $.proxy(this._updateGeometry, this));
            $('input[name="scale_text"], input[name="rotation"]', this.element)
            .bind('keyup', $.proxy(this._updateGeometry, this));
            $('select[name="template"]', this.element)
            .bind('change', $.proxy(this._getPrintSize, this))
            .trigger('change');
            this._trigger('ready');
            this._ready();
        },

        open: function() {
            this.defaultAction();
        },
        
        defaultAction: function() {
            var self = this;
            var me = $(this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + me.attr('id') + '/';
            if(!this.popup || !this.popup.$element){
                this.popup = new Mapbender.Popup2({
                        title: self.element.attr('title'),
                        draggable: true,
                        header: true,
                        modal: false,
                        closeButton: false,
                        closeOnPopupCloseClick: false,
                        closeOnESC: false,
                        content: self.element,
                        width: 320,
                        buttons: {
                                'cancel': {
                                    label: 'Cancel',
                                    cssClass: 'button buttonCancel critical right',
                                    callback: function(){
                                        self.close();
                                    }
                                },
                                'ok': {
                                    label: 'Print',
                                    cssClass: 'button right',
                                    callback: function(){
                                        self._print();
                                    }
                                }
                        }
                    });
             } else {
                 if (this.popupIsOpen === false){
                    this.popup.open(self.element);
                 }
            }
            me.show();        
            
            //this.popup.$element.on('closed', function() {self.close();});
            
            this.popupIsOpen = true;
            this._loadPrintFormats();
            this._updateElements();
            this._updateGeometry(true);
        },

        close: function() {
            this.element.hide().appendTo($('body'));
            this.popupIsOpen = false;
            this._updateElements();
            this.popup.close();
        },

        _loadPrintFormats: function() {
            var self = this;
            var count = 0;
            var quality_levels = this.options.quality_levels;
            var quality = $('select[name="quality"]', this.element);
            var list = quality.siblings(".dropdownList");
            var valueContainer = quality.siblings(".dropdownValue");
            list.empty();
            quality.empty();
            if (null === quality_levels){
                quality.parent().hide();
            } else {
                for(key in quality_levels) {
                    quality.append($('<option></option>', {
                        'value': key,
                        'html': quality_levels[key],
                        'class': "opt-" + count
                    }));
                    list.append($('<li></li>', {
                        'html': quality_levels[key],
                        'class': "item-" + count
                    }));

                    if(count == 0){
                        valueContainer.text(quality_levels[key]);
                    }

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
            list = scale_select.siblings(".dropdownList");
            list.empty();
            var valueContainer = scale_select.siblings(".dropdownValue");
            count = 0;
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
                        'html': '1:' + scale,
                        'class': "opt-" + count
                    }));
                    list.append($('<li></li>', {
                        'html': '1:' + scale,
                        'class': "item-" + count
                    }));
                    if(count == 0){
                        valueContainer.text('1:' + scale);
                    }
                    ++count;
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
                var extra_fields = $('#extra_fields', this.element).empty();

                for(var field in opt_fields){
                    extra_fields.append($('<label></label>', {
                        'html': opt_fields[field].label,
                        'class': 'labelInput'
                    }));
                    extra_fields.append($('<input></input>', {
                        'type': 'text',
                        'class': 'input',
                        'name': 'extra['+field+']',
                        'style': 'margin-left: 3px'
                    }));
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
            
            var centroid = this.feature.geometry.getCentroid();
            var centroid_lonlat = new OpenLayers.LonLat(centroid.x,centroid.y);
            var centroid_pixel = this.map.map.olMap.getViewPortPxFromLonLat(centroid_lonlat);
            var centroid_geodesSize = this.map.map.olMap.getGeodesicPixelSize(centroid_pixel);

            var geodes_diag = Math.sqrt(centroid_geodesSize.w*centroid_geodesSize.w + centroid_geodesSize.h*centroid_geodesSize.h) / Math.sqrt(2) * 100000;

            var geodes_width = width * scale / (geodes_diag);
            var geodes_height = height * scale / (geodes_diag);

            var ll_pixel_x = centroid_pixel.x - (geodes_width) / 2;
            var ll_pixel_y = centroid_pixel.y + (geodes_height) / 2;
            var ur_pixel_x = centroid_pixel.x + (geodes_width) / 2;
            var ur_pixel_y = centroid_pixel.y - (geodes_height) /2 ;
            var ll_pixel = new OpenLayers.Pixel(ll_pixel_x, ll_pixel_y);
            var ur_pixel = new OpenLayers.Pixel(ur_pixel_x, ur_pixel_y);
            var ll_lonlat = this.map.map.olMap.getLonLatFromPixel(ll_pixel);
            var ur_lonlat = this.map.map.olMap.getLonLatFromPixel(ur_pixel);

            this.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
                ll_lonlat.lon,
                ur_lonlat.lat,
                ur_lonlat.lon,
                ll_lonlat.lat).toGeometry(), {});
            this.feature.world_size = {
                x: ur_lonlat.lon - ll_lonlat.lon,
                y: ur_lonlat.lat - ll_lonlat.lat
            };
            
            this.feature.geometry.rotate(rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
            this.layer.addFeatures(this.feature);
            this.layer.redraw();
        },

        _updateElements: function() {
            var self = this;

            if(true == this.popupIsOpen){
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
            return $('select[name="scale_select"],input[name="scale_text"]').val();
        },

        _print: function() {
            this._printDirectly();
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
            format = this.options.templates[template_key].format,
            file_prefix = this.options.file_prefix;
            
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
            
            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'file_prefix',
                value: file_prefix
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
        },

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
        },
        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            } else {
                this.readyCallbacks.push(callback);
            }
        },
        /**
         *
         */
        _ready: function() {
            for(callback in this.readyCallbacks) {
                callback();
                delete(this.readyCallbacks[callback]);
            }
            this.readyState = true;
        }
    });

})(jQuery);
