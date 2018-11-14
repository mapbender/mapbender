(function($) {

    $.widget("mapbender.mbPrintClient",  $.mapbender.mbImageExport, {
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
        digitizerData: null,
        printBounds: null,

        _setup: function(){
            var self = this;
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';

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
            $('form', this.element).on('submit', this._onSubmit.bind(this));
            this._super();
        },

        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
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
                    this._getTemplateSize();
                    this._updateElements(true);
                    this._setScale();
                }
                $(this.element).show();
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
                if(scale == currentScale){
                    selectValue = scales[idx];
                    return false;
                }
                if(scale > currentScale){
                    selectValue = scales[idx-1];
                    return false;
                }
            });
            if(currentScale <= scales[0]){
                selectValue = scales[0];
            }
            if(currentScale > scales[scales.length-1]){
                selectValue = scales[scales.length-1];
            }

            select.val(selectValue);
            styledSelect.html('1:'+selectValue);

            this._updateGeometry(true);
        },

        _updateGeometry: function(reset) {
            var scale = this._getPrintScale(),
                rotationField = $(this.element).find('input[name="rotation"]');

            // remove all not numbers from input
            rotationField.val(rotationField.val().replace(/[^\d]+/,''));

            if (rotationField.val() === '' && this.rotateValue > '0'){
                rotationField.val('0');
            }
            this.rotateValue = rotationField.val();

            if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
                return;
            }
            scale = parseInt(scale);

            if (isNaN(parseFloat(this.rotateValue)) || !isFinite(this.rotateValue)) {
                if(null !== this.lastRotation) {
                    rotationField.val(this.lastRotation).change();
                }
            }
            var rotation = parseInt(this.rotateValue) || 0;

            var center = (reset === true || !this.feature) ?
            this.map.map.olMap.getCenter() :
            this.feature.geometry.getBounds().getCenterLonLat();

            if(this.feature) {
                this.layer.removeAllFeatures();
                this.feature = null;
            }

            // adjust for geodesic pixel aspect ratio so
            // a) our print region selection rectangle appears with ~the same visual aspect ratio as
            //    the main map region in the template, for any projection
            // b) any pixel aspect distortion on geodesic projections is matched WYSIWIG in generated printout
            var centerPixel = this.map.map.olMap.getPixelFromLonLat(center);
            var kmPerPixel = this.map.map.olMap.getGeodesicPixelSize(centerPixel);
            var pixelHeight = this.height * scale / (kmPerPixel.h * 1000);
            var pixelAspectRatio = kmPerPixel.w / kmPerPixel.h;
            var pixelWidth = this.width * scale * pixelAspectRatio / (kmPerPixel.w * 1000);

            var pxBottomLeft = centerPixel.add(-0.5 * pixelWidth, -0.5 * pixelHeight);
            var pxTopRight = centerPixel.add(0.5 * pixelWidth, 0.5 * pixelHeight);
            var llBottomLeft = this.map.map.olMap.getLonLatFromPixel(pxBottomLeft);
            var llTopRight = this.map.map.olMap.getLonLatFromPixel(pxTopRight);
            this.feature = new OpenLayers.Feature.Vector(new OpenLayers.Bounds(
                llBottomLeft.lon,
                llBottomLeft.lat,
                llTopRight.lon,
                llTopRight.lat
            ).toGeometry(), {});
            // copy bounds before rotation
            this.printBounds = this.feature.geometry.getBounds().clone();

            this.feature.geometry.rotate(-rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
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
            return {
                extent: {
                    width: this.printBounds.getWidth(),
                    height: this.printBounds.getHeight()
                },
                center: {
                    x: this.printBounds.getCenterLonLat().lon,
                    y: this.printBounds.getCenterLonLat().lat
                }
            };
        },
        /**
         *
         * @param sourceDef
         * @param {number} [scale]
         * @returns {{layers: *, styles: *}}
         * @private
         */
        _getRasterVisibilityInfo: function(sourceDef, scale) {
            var scale_ = scale || this._getPrintScale();
            var toChangeOpts = {options: {children: {}}, sourceIdx: {mqlid: sourceDef.mqlid}};
            var geoSourceResponse = Mapbender.source[sourceDef.type].changeOptions(sourceDef, scale_, toChangeOpts);
            return {
                layers: geoSourceResponse.layers,
                styles: geoSourceResponse.styles
            };
        },
        /**
         *
         * @returns {Array<Object.<string, string>>} legend image urls mapped to layer title
         * @private
         */
        _collectLegends: function() {
            var legends = [];
            var scale = this._getPrintScale();
            function _getLegends(layer) {
                var legend = {};
                if (!layer.options.treeOptions.selected) {
                    return legend;
                }
                if (layer.children) {
                    for (var i = 0; i < layer.children.length; i++) {
                        _.assign(legend, _getLegends(layer.children[i]));
                    }
                }
                // Only include the legend for a "group" / non-leaf layer if we haven't collected any
                // legend images from leaf layers yet.
                if (!Object.keys(legend).length) {
                    if (layer.options.legend && layer.options.legend.url && layer.options.treeOptions.selected) {
                        legend[layer.options.title] = layer.options.legend.url;
                    }
                }
                return legend;
            }
            var sources = this._getRasterSourceDefs();
            for (var i = 0; i < sources.length; ++i) {
                var source = sources[i];
                if (source.type === 'wms' && this._getRasterVisibilityInfo(source, scale).layers.length) {
                    var ll = _getLegends(sources[i].configuration.children[0]);
                    if (ll) {
                        legends = legends.concat(ll);
                    }
                }
            }
            return legends;
        },
        _collectRasterLayerData: function() {
            var sources = this._getRasterSourceDefs();
            var scale = this._getPrintScale();

            var dataOut = [];

            for (var i = 0; i < sources.length; i++) {
                var sourceDef = sources[i];
                var visLayers = this._getRasterVisibilityInfo(sourceDef, scale);
                var layer = this.map.map.layersList[sourceDef.mqlid].olLayer;
                if (visLayers.layers.length > 0) {
                    var prevLayers = layer.params.LAYERS;
                    var prevStyles = layer.params.STYLES;
                    layer.params.LAYERS = visLayers.layers;
                    layer.params.STYLES = visLayers.styles;

                    var opacity = sourceDef.configuration.options.opacity;
                    var layerData = Mapbender.source[sourceDef.type].getPrintConfig(layer, this.printBounds, sourceDef.configuration.options.proxy);
                    layerData.opacity = opacity;
                    // flag to change axis order
                    layerData.changeAxis = this._changeAxis(layer);
                    dataOut.push(layerData);

                    layer.params.LAYERS = prevLayers;
                    layer.params.STYLES = prevStyles;
                }
            }

            return dataOut;
        },
        /**
         * Should return true if the given layer needs to be included in print
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterGeometryLayer: function(layer) {
            if (!this._super(layer)) {
                return false;
            }
            // don't print own print extent preview layer
            if (layer === this.layer) {
                return false;
            }
            return true;
        },
        /**
         * Should return true if the given feature should be included in print.
         *
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {boolean}
         * @private
         */
        _filterFeature: function(feature) {
            if (!this._super(feature)) {
                return false;
            }
            if (!feature.geometry.intersects(this.feature.geometry)) {
                return false;
            }
            // don't print own print extent preview feature}
            if (feature === this.feature) {
                return false;
            }
            return true;
        },
        _collectOverview: function() {
            // overview map
            var self = this;
            var ovMap = (this.map.map.olMap.getControlsByClass('OpenLayers.Control.OverviewMap') || [null])[0];
            var changeAxis = false;
            var overviewLayers = (ovMap && ovMap.layers || []).map(function(layer) {
                // this is the same for all layers, basically set on first iteration
                changeAxis = self._changeAxis(layer);
                // NOTE: bbox / width / height are discarded and replaced by print backend
                return layer.getURL(ovMap.map.getExtent());
            });
            if (overviewLayers.length) {
                var ovCenter = ovMap.ovmap.getCenter();
                return {
                    layers: overviewLayers,
                    center: {
                        x: ovCenter.lon,
                        y: ovCenter.lat
                    },
                    height: ovMap.ovmap.getExtent().getHeight(),
                    changeAxis: changeAxis
                };
            } else {
                return null;
            }
        },
        _collectJobData: function() {
            var extent = this._getPrintExtent();
            var extentFeature = this.feature.geometry.components[0].components.map(function(component) {
                return {
                    x: component.x,
                    y: component.y
                };
            });
            var rasterLayers = this._collectRasterLayerData();
            var geometryLayers = this._collectGeometryLayers();
            var overview = this._collectOverview();

            var data = {
                extent: {
                    width: extent.extent.width,
                    height: extent.extent.height
                },
                center: {
                    x: extent.center.x,
                    y: extent.center.y
                },
                // @todo: this is pretty wild for an inlined expression, extract a method
                'extent_feature': extentFeature,
                layers: rasterLayers.concat(geometryLayers),
                legends: this._collectLegends(),
                overview: overview
            };
            if (this.digitizerData) {
                _.assign(data, this.digitizerData);
            }
            return data;
        },
        _submitJob: function(jobData) {
            var form = $('form#formats', this.element);
            var fields = $();
            var appendField = function(name, value) {
                $.merge(fields, $('<input />', {
                    type: 'hidden',
                    name: name,
                    value: value
                }));
            };
            _.forEach(jobData, function(value, key) {
                if (value === null || typeof value === 'undefined') {
                    return;
                }
                switch (key) {
                    case 'legends':
                        if ($('input[name="printLegend"]',form).prop('checked')) {
                            appendField(key, JSON.stringify(value));
                        }
                        break;
                    case 'extent_feature':
                        appendField(key, JSON.stringify(value));
                        break;
                    default:
                        if ($.isArray(value)) {
                            for (var i = 0; i < value.length; ++i) {
                                switch (key) {
                                    case 'layers':
                                        appendField('' + key + '[' + i + ']', JSON.stringify(value[i]));
                                        break;
                                    default:
                                        appendField('' + key + '[' + i + ']', value[i]);
                                        break;
                                }
                            }
                        } else if (typeof value === 'string') {
                            appendField(key, value);
                        } else if (Object.keys(value).length) {
                            if (key === 'overview') {
                                appendField('' + key, JSON.stringify(value));
                            } else {
                                Object.keys(value).map(function(k) {
                                    appendField('' + key + '['+k+']', value[k]);
                                });
                            }
                        } else {
                            appendField(key, value);
                        }
                        break;
                }
            });
            $('div#layers', form).empty();
            fields.appendTo(form.find('div#layers'));

            form.find('input[type="submit"]').click();
        },
        _print: function() {
            var jobData = this._collectJobData();
            if (!jobData.layers.length) {
                Mapbender.info(Mapbender.trans('mb.core.printclient.info.noactivelayer'));
                return;
            }

            this._submitJob(jobData);
        },
        _onSubmit: function(evt) {
            if (this.options.autoClose){
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
                    // dimensions delivered in cm, we need m
                    self.width = data.width / 100.0;
                    self.height = data.height / 100.0;
                    self._updateGeometry();
                }
            });
        },

        printDigitizerFeature: function(schemaName,featureId){
            // Sonderlocke Digitizer
            this.digitizerData = {
                digitizer_feature: {
                    id: featureId,
                    schemaName: schemaName
                }
            };

            this._getDigitizerTemplates(schemaName);
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
                    // open changed dialog
                    self.open();
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

        /**
         *
         */
        ready: function(callback) {
            if(this.readyState === true) {
                callback();
            }
        },
        /**
         *
         */
        _ready: function() {
            this.readyState = true;
        }
    });

})(jQuery);
