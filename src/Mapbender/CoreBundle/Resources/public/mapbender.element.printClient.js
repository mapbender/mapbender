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
        rotateValue: 0,
        overwriteTemplates: false,

        _create: function() {
            if(!Mapbender.checkTarget("mbPrintClient", this.options.target)){
                return;
            }
            var self = this;
            Mapbender.elementRegistry.onElementReady(this.options.target, $.proxy(self._setup, self));
        },

        _setup: function(){
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            this.map = $('#' + this.options.target).data('mapbenderMbMap');

            $('select[name="scale_select"]', this.element)
                .on('change', $.proxy(this._updateGeometry, this));
            $('input[name="rotation"]', this.element)
                .on('keyup', $.proxy(this._updateGeometry, this));
            $('select[name="template"]', this.element)
                .on('change', $.proxy(this._getTemplateSize, this));

            if (this.options.type === 'element') {
                $(this.element).show();
                $(this.element).on('click', '#printToggle', function(){
                    var active = $(this).attr('active');
                    if(active === 'true') {// deactivate
                        $(this).attr('active','false').removeClass('active');
                        $(this).val(Mapbender.trans('mb.core.printclient.btn.activate'));
                        self._updateElements(false);
                        $('.printSubmit', this.element).addClass('hidden');
                    }else{ // activate
                        $(this).attr('active','true').addClass('active');
                        $(this).val(Mapbender.trans('mb.core.printclient.btn.deactivate'));
                        self._getTemplateSize();
                        self._updateElements(true);
                        self._setScale();
                        $('.printSubmit', this.element).removeClass('hidden');
                    }
                });
                $('.printSubmit', this.element).on('click', $.proxy(this._print, this));
            }

            this._trigger('ready');
            this._ready();
        },

        defaultAction: function(callback) {
            this.open(callback);
        },

        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            var me = $(this.element);
            if (this.options.type === 'dialog') {
                if(!this.popup || !this.popup.$element){
                    this.popup = new Mapbender.Popup2({
                            title: self.element.attr('title'),
                            draggable: true,
                            header: true,
                            modal: false,
                            closeButton: false,
                            closeOnESC: false,
                            content: self.element,
                            width: 400,
                            height: 490,
                            cssClass: 'customPrintDialog',
                            buttons: {
                                    'cancel': {
                                        label: Mapbender.trans('mb.core.printclient.popup.btn.cancel'),
                                        cssClass: 'button buttonCancel critical right',
                                        callback: function(){
                                            self.close();
                                        }
                                    },
                                    'ok': {
                                        label: Mapbender.trans('mb.core.printclient.popup.btn.ok'),
                                        cssClass: 'button right',
                                        callback: function(){
                                            self._print();
                                        }
                                    }
                            }
                        });
                    this.popup.$element.on('close', $.proxy(this.close, this));
                }else{
                     return;
                }
                me.show();
                this._getTemplateSize();
                this._updateElements(true);
                this._setScale();
            }
        },

        close: function() {
            if(this.popup){
                this.element.hide().appendTo($('body'));
                this._updateElements(false);
                if(this.popup.$element){
                    this.popup.destroy();
                }
                this.popup = null;
                // reset template select if overwritten
                if(this.overwriteTemplates){
                    this._overwriteTemplateSelect(this.options.templates);
                    this.overwriteTemplates = false;
                }
            }
            this.callback ? this.callback.call() : this.callback = null;
        },

        _setScale: function() {
            var select = $(this.element).find("select[name='scale_select']");
            var styledSelect = select.parent().find(".dropdownValue.iconDown");
            var scales = this.options.scales;
            var currentScale = Math.round(this.map.map.olMap.getScale());
            var selectValue;

            $.each(scales, function(idx, scale) {
                scale = Math.round(scale);
                if(scale === currentScale){
                    selectValue = scales[idx];
                    return false;
                }
                if(scale > currentScale){
                    selectValue = scales[idx-1];
                    return false;
                }
            });
            if(currentScale <= Math.round(scales[0])){
                selectValue = Math.round(scales[0]);
            }
            if(currentScale > Math.round(scales[scales.length-1])){
                selectValue = Math.round(scales[scales.length-1]);
            }

            select.val(selectValue);
            styledSelect.html('1:'+selectValue);

            this._updateGeometry(true);
        },

        _updateGeometry: function(reset) {
            var width = this.width,
                height = this.height,
                scale = this._getPrintScale(),
                rotationField = $(this.element).find('input[name="rotation"]');

            // remove all not numbers from input
            rotationField.val(rotationField.val().replace(/[^\d]+/,''));

            if (rotationField.val() === '' && this.rotateValue > '0'){
                rotationField.val('0');
            }
            var rotation = rotationField.val();
            this.rotateValue = rotation;

            if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
                if(null !== this.lastScale) {
                //$('input[name="scale_text"]').val(this.lastScale).change();
                }
                return;
            }
            scale = parseInt(scale);

            if(!(!isNaN(parseFloat(rotation)) && isFinite(rotation))) {
                if(null !== this.lastRotation) {
                    rotationField.val(this.lastRotation).change();
                }
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

            if(this.map.map.olMap.units === 'degrees' || this.map.map.olMap.units === 'dd') {
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
            }

            this.feature.geometry.rotate(rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
            this.layer.addFeatures(this.feature);
            this.layer.redraw();
        },

        _updateElements: function(active) {
            var self = this;

            if(true === active){
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
            }else{
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
            return $(this.element).find('select[name="scale_select"]').val();
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

        _print: function() {
            var form = $('form#formats', this.element);
            var extent = this._getPrintExtent();

            // Felder für extent, center und layer dynamisch einbauen
            var fields = $();

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

            // extent feature
            var feature_coords = new Array();
            var feature_comp = this.feature.geometry.components[0].components;
            for(var i = 0; i < feature_comp.length-1; i++) {
                feature_coords[i] = new Object();
                feature_coords[i]['x'] = feature_comp[i].x;
                feature_coords[i]['y'] = feature_comp[i].y;
            }

            $.merge(fields, $('<input />', {
                type: 'hidden',
                name: 'extent_feature',
                value: JSON.stringify(feature_coords)
            }));

            // wms layer
            var sources = this.map.getSourceTree(), lyrCount = 0;

            function _getLegends(layer) {
                var legend = null;
                if (layer.options.legend && layer.options.legend.url && layer.options.treeOptions.selected == true) {
                    legend = {};
                    legend[layer.options.title] = layer.options.legend.url;
                }
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        var help = _getLegends(layer.children[i]);
                        if (help) {
                            legend = legend ? legend : {};
                            for (key in help) {
                                legend[key] = help[key];
                            }
                        }
                    }
                }
                return legend;
            }
            var legends = [];

            for (var i = 0; i < sources.length; i++) {
                var layer = this.map.map.layersList[sources[i].mqlid],
                        type = layer.olLayer.CLASS_NAME;

                if (0 !== type.indexOf('OpenLayers.Layer.')) {
                    continue;
                }

                if (Mapbender.source[sources[i].type] && typeof Mapbender.source[sources[i].type].getPrintConfig === 'function') {
                    var source = sources[i],
                            scale = this._getPrintScale(),
                            toChangeOpts = {options: {children: {}}, sourceIdx: {mqlid: source.mqlid}};
                    var visLayers = Mapbender.source[source.type].changeOptions(source, scale, toChangeOpts);
                    if (visLayers.layers.length > 0) {
                        var prevLayers = layer.olLayer.params.LAYERS;
                        var prevStyles = layer.olLayer.params.STYLES;
                        layer.olLayer.params.LAYERS = visLayers.layers;
                        layer.olLayer.params.STYLES = visLayers.styles;

                        var opacity = sources[i].configuration.options.opacity;
                        var lyrConf = Mapbender.source[sources[i].type].getPrintConfig(layer.olLayer,
                            this.map.map.olMap.getExtent(),
                            scale,
                            sources[i].configuration.options.proxy);
                        lyrConf.opacity = opacity;

                        // flag to change axis order
                        lyrConf.changeAxis = this._changeAxis(layer.olLayer);

                        $.merge(fields, $('<input />', {
                            type: 'hidden',
                            name: 'layers[' + lyrCount + ']',
                            value: JSON.stringify(lyrConf),
                            weight: this.map.map.olMap.getLayerIndex(layer.olLayer)
                        }));
                        layer.olLayer.params.LAYERS = prevLayers;
                        layer.olLayer.params.STYLES = prevStyles;
                        lyrCount++;

                        if (sources[i].type === 'wms') {
                            var ll = _getLegends(sources[i].configuration.children[0]);
                            if (ll) {
                                legends.push(ll);
                            }
                        }
                    }
                }
            }

            //legend
            if($('input[name="printLegend"]',form).prop('checked')){
                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: 'legends',
                    value: JSON.stringify(legends)
                }));
            }

            // Iterating over all vector layers, not only the ones known to MapQuery
            var geojsonFormat = new OpenLayers.Format.GeoJSON();
            for(var i = 0; i < this.map.map.olMap.layers.length; i++) {
                var layer = this.map.map.olMap.layers[i];
                if ('OpenLayers.Layer.Vector' !== layer.CLASS_NAME || layer.visibility === false || this.layer === layer) {
                    continue;
                }

                var geometries = [];
                for(var idx = 0; idx < layer.features.length; idx++) {
                    var feature = layer.features[idx];
                    if (!feature.onScreen(true)) continue

                    if(this.feature.geometry.intersects(feature.geometry)){
                        var geometry = geojsonFormat.extract.geometry.apply(geojsonFormat, [feature.geometry]);

                        if(feature.style !== null){
                            geometry.style = feature.style;
                        }else{
                            geometry.style = layer.styleMap.createSymbolizer(feature,feature.renderIntent);
                        }
                        // only visible features
                        if(geometry.style.fillOpacity > 0 && geometry.style.strokeOpacity > 0){
                            geometries.push(geometry);
                        } else if (geometry.style.label !== undefined){
                            geometries.push(geometry);
                        }
                    }
                }

                var lyrConf = {
                    type: 'GeoJSON+Style',
                    opacity: 1,
                    geometries: geometries
                };

                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: 'layers[' + (lyrCount + i) + ']',
                    value: JSON.stringify(lyrConf),
                    weight: this.map.map.olMap.getLayerIndex(layer)
                }));
            }

            // overview map
            var ovMap = this.map.map.olMap.getControlsByClass('OpenLayers.Control.OverviewMap')[0],
            count = 0;
            if (undefined !== ovMap){
                for(var i = 0; i < ovMap.layers.length; i++) {
                    var url = ovMap.layers[i].getURL(ovMap.map.getExtent());
                    var extent = ovMap.map.getExtent();
                    var mwidth = extent.getWidth();
                    var size = ovMap.size;
                    var width = size.w;
                    var res = mwidth / width;
                    var scale = Math.round(OpenLayers.Util.getScaleFromResolution(res,'m'));

                    var overview = {};
                    overview.url = url;
                    overview.scale = scale;

                    // flag to change axis order
                    overview.changeAxis = this._changeAxis(ovMap.layers[i]);

                    $.merge(fields, $('<input />', {
                        type: 'hidden',
                        name: 'overview[' + count + ']',
                        value: JSON.stringify(overview)
                    }));
                    count++;
                }
            }

            $('div#layers', form).empty();
            fields.appendTo(form.find('div#layers'));

            // Post in neuen Tab (action bei form anpassen)
            var url =  this.elementUrl + 'print';

            form.get(0).setAttribute('action', url);
            form.attr('target', '_blank');
            form.attr('method', 'post');

            if (lyrCount === 0){
                Mapbender.info(Mapbender.trans('mb.core.printclient.info.noactivelayer'));
            }else{
                // we click a hidden submit button to check the required fields
                form.find('input[type="submit"]').click();
            }

            if(this.options.autoClose){
                this.popup.close();
            }
        },

        _changeAxis: function(layer) {
            var olMap = this.map.map.olMap;
            var currentProj = olMap.displayProjection.projCode;

            if (layer.params.VERSION === '1.3.0') {
                if (OpenLayers.Projection.defaults.hasOwnProperty(currentProj) && OpenLayers.Projection.defaults[currentProj].yx) {
                    return true;
                }
            }

            return false;
        },

        _getTemplateSize: function() {
            var self = this;
            var template = $('select[name="template"]', this.element).val();

            var url =  this.elementUrl + 'getTemplateSize';
            $.ajax({
                url: url,
                type: 'GET',
                data: {template: template},
                dataType: "json",
                success: function(data) {
                    self.width = data.width;
                    self.height = data.height;
                    self._updateGeometry();
                }
            });
        },

        printDigitizerFeature: function(schemaName,featureId){
            // add hidden fields to submit featureId and schemaName
            var form = $('form#formats', this.element);

            if(!form.has('input[name="digitizer_feature[id]"]').length) {
                form.append($('<input />', {
                    type: 'hidden',
                    name: 'digitizer_feature[id]'
                })).append($('<input />', {
                    type: 'hidden',
                    name: 'digitizer_feature[schemaName]'
                }));
            }

            $('input[name="digitizer_feature[id]"]', form).val(featureId);
            $('input[name="digitizer_feature[schemaName]"]', form).val(schemaName);

            this.open();

            this._getDigitizerTemplates(schemaName);

            this._setDigitizerPrintScale(schemaName);
        },

        _getDigitizerTemplates: function(schemaName) {
            var self = this;

            var url =  this.elementUrl + 'getDigitizerTemplates';
            $.ajax({
                url: url,
                type: 'GET',
                data: {schemaName: schemaName},
                success: function(data) {
                    self._overwriteTemplateSelect(data);
                }
            });
        },

        _overwriteTemplateSelect: function(templates) {
            var templateSelect = $('select[name=template]', this.element);
            var templateList = templateSelect.siblings(".dropdownList");
            var valueContainer = templateSelect.siblings(".dropdownValue");

            templateSelect.empty();
            templateList.empty();

            var count = 0;
            $.each(templates, function(key,template) {
                templateSelect.append($('<option></option>', {
                    'value': template.template,
                    'html': template.label,
                    'class': "opt-" + count
                }));
                templateList.append($('<li></li>', {
                    'html': template.label,
                    'class': "item-" + count
                }));
                if(count == 0){
                    valueContainer.text(template.label);
                }
                ++count;
            });
            this.overwriteTemplates = true;
        },

        _setDigitizerPrintScale: function(schemaName) {
            var self = this;

            var url =  self.elementUrl + 'getDigitizerPrintScale';
            $.ajax({
                url: url,
                type: 'GET',
                data: {schemaName: schemaName},
                success: function(data) {
                    if (isNaN(data)) {
                        return;
                    }
                    $('[name=scale_select] option[value="' + data + '"]', self.element).attr('selected', true);
                    $('[name=scale_select]').parent().find('.dropdownValue').text('1:' + data);
                    self._updateGeometry();
                }
            });
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
