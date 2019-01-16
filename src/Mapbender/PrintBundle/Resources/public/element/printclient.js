(function($) {

    $.widget("mapbender.mbPrintClient",  $.mapbender.mbImageExport, {
        options: {
            locale: null,
            style: {
                fillColor:     '#ffffff',
                fillOpacity:   0.5,
                strokeColor:   '#000000',
                strokeOpacity: 1.0,
                strokeWidth:    2
            }
        },
        layer: null,
        control: null,
        feature: null,
        lastRotation: null,
        width: null,
        height: null,
        overwriteTemplates: false,
        digitizerData: null,
        printBounds: null,
        jobList: null,

        _setup: function(){
            var self = this;
            var $jobList = $('.job-list', this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if ($jobList.length) {
                this._initJobList($jobList);
            }

            $('select[name="scale_select"]', this.$form)
                .on('change', $.proxy(this._updateGeometry, this));
            $('input[name="rotation"]', this.$form)
                .on('keyup', $.proxy(this._updateGeometry, this));
            $('select[name="template"]', this.$form)
                .on('change', $.proxy(this._getTemplateSize, this));

            if (this.options.type === 'element') {
                $(this.element).show();
                $(this.element).on('click', '.-fn-toggle-frame', function() {
                    var $button = $(this);
                    var wasActive = !!$button.data('active');
                    $button.data('active', !wasActive);
                    $button.toggleClass('active', !wasActive);
                    var buttonText = wasActive ? 'mb.core.printclient.btn.activate'
                                               : 'mb.core.printclient.btn.deactivate';
                    $button.val(Mapbender.trans(buttonText));
                    self._getTemplateSize();
                    self._updateElements(!wasActive);
                    self._setScale();

                    $('.printSubmit', self.$form).toggleClass('hidden', wasActive);
                });
                $('.printSubmit', this.$form).on('click', $.proxy(this._print, this));
            }
            this.$form.on('submit', this._onSubmit.bind(this));
            this._super();
        },

        open: function(callback){
            this.callback = callback ? callback : null;
            var self = this;
            if (this.options.type === 'dialog') {
                if(!this.popup || !this.popup.$element){
                    this.popup = new Mapbender.Popup({
                            title: self.element.attr('title'),
                            draggable: true,
                            header: true,
                            modal: false,
                            closeOnESC: false,
                            content: self.element,
                            width: 400,
                            scrollable: false,
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
            }
        },

        close: function() {
            if (this.popup) {
                this._updateElements(false);
                if (this.overwriteTemplates) {
                    this._overwriteTemplateSelect(this.options.templates);
                    this.overwriteTemplates = false;
                }
            }
            this._super();
        },

        _setScale: function() {
            var select = $("select[name='scale_select']", this.$form);
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
                rotationField = $('input[name="rotation"]', this.$form);

            if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
                return;
            }
            scale = parseInt(scale);

            var rotation = parseInt(rotationField.val()) || 0;

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
                if (!this.layer) {
                    this.layer = new OpenLayers.Layer.Vector("Print", {
                        styleMap: new OpenLayers.StyleMap({
                            'default': $.extend({}, OpenLayers.Feature.Vector.style['default'], this.options.style)
                        })
                    });
                }
                if (!this.control) {
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
                if (this.control) {
                    this.control.deactivate();
                    this.map.map.olMap.removeControl(this.control);
                    this.control = null;
                }
                if (this.layer) {
                    this.map.map.olMap.removeLayer(this.layer);
                    this.layer = null;
                }
            }
        },

        _getPrintScale: function() {
            return $('select[name="scale_select"]', this.$form).val();
        },
        /**
         * Alias to hook into imgExport base class raster layer processing
         * @returns {*}
         * @private
         */
        _getExportScale: function() {
            return this._getPrintScale();
        },
        _getExportExtent: function() {
            return this.printBounds;
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
                    return false;
                }
                if (layer.children) {
                    var childrenActive = false;
                    for (var i = 0; i < layer.children.length; i++) {
                        var childLegends = _getLegends(layer.children[i]);
                        if (childLegends !== false) {
                            _.assign(legend, childLegends);
                            childrenActive = true;
                        }
                    }
                    if (!childrenActive) {
                        return false;
                    }
                }
                // Only include the legend for a "group" / non-leaf layer if we haven't collected any
                // legend images from leaf layers yet, but at least one leaf layer is actually active
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
                    if (ll && Object.keys(ll).length) {
                        legends = legends.concat(ll);
                    }
                }
            }
            return legends;
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
            var jobData = this._super();
            var overview = this._collectOverview();
            var extentFeature = this.feature.geometry.components[0].components.map(function(component) {
                return {
                    x: component.x,
                    y: component.y
                };
            });
            var mapDpi = (this.map.options || {}).dpi || 72;
            _.assign(jobData, {
                overview: overview,
                mapDpi: mapDpi,
                'extent_feature': extentFeature
            });
            if ($('input[name="printLegend"]', this.$form).prop('checked')) {
                _.assign(jobData, {
                    legends: this._collectLegends()
                });
            }
            if (this.digitizerData) {
                _.assign(jobData, this.digitizerData);
            }
            return jobData;
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
            // switch to queue display tab on successful submit
            $('.tab-container', this.element).tabs({active: 1});
        },
        _getTemplateSize: function() {
            var self = this;
            var template = $('select[name="template"]', this.$form).val();

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

        _initJobList: function($jobListPanel) {
            var jobListOptions = {
                url: this.elementUrl + 'queuelist',
                locale: this.options.locale || window.navigator.language
            };
            var jobList = this.jobList = $['mapbender']['mbPrintClientJobList'].call($jobListPanel, jobListOptions, $jobListPanel);
            $('.tab-container', this.element).tabs({
                active: 0,
                activate: function (event, ui) {
                    if (ui.newPanel.hasClass('job-list')) {
                        jobList.start();
                    } else {
                        jobList.stop();
                    }
                }.bind(this)
            });
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
