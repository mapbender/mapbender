(function($) {

    $.widget("mapbender.mbPrintClient",  $.mapbender.mbImageExport, {
        options: {
            locale: null,
            style: {
                fillColor:     '#ffffff',
                fillOpacity:   0.5,
                strokeColor:   '#000000',
                strokeOpacity: 1.0,
                strokeWidth:    2,
                cursor: 'all-scroll'
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
        jobList: null,
        $selectionFrameToggle: null,
        // buffer for ajax-loaded 'getTemplateSize' requests
        // we generally don't want to keep reloading size information
        // for the same template(s) within the same session
        _templateSizeCache: {},
        selectionActive: false,

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
                .on('change', $.proxy(this._onTemplateChange, this));

            this.$selectionFrameToggle = $('.-fn-toggle-frame', this.element);
            if (this.options.type === 'element') {
                $(this.element).show();
                this.$selectionFrameToggle.on('click', function() {
                    var $button = $(this);
                    var wasActive = !!$button.data('active');
                    $button.data('active', !wasActive);
                    $button.toggleClass('active', !wasActive);
                    var buttonText = wasActive ? 'mb.core.printclient.btn.activate'
                                               : 'mb.core.printclient.btn.deactivate';
                    $button.val(Mapbender.trans(buttonText));
                    if (!wasActive) {
                        self.activate();
                    } else {
                        self._deactivateSelection();
                    }
                });
                $('.printSubmit', this.$form).on('click', $.proxy(this._print, this));
            } else {
                // popup comes with its own buttons
                $('.printSubmit', this.$form).remove();
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
                                            this.close();
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
                    this.popup.$element.one('close', $.proxy(this.close, this));
                }
                this.activate();
            }
        },
        _activateSelection: function(reset) {
            var self = this;
            this._getTemplateSize().then(function() {
                var layer = self._getSelectionLayer();
                var control = self._getSelectionDragControl();
                self.map.map.olMap.addLayer(layer);
                self.map.map.olMap.addControl(control);
                self.selectionActive = true;
                control.activate();
                if (reset) {
                    self._setScale();       // NOTE: will end in a call to _updateGeometry(true)
                } else {
                    self._updateGeometry(false);
                }
                $('.printSubmit', self.$form).removeClass('hidden');
            });
        },
        _deactivateSelection: function() {
            var wasActive = !!this.selectionActive;
            this.selectionActive = false;
            if (wasActive && this.control) {
                this.control.deactivate();
                this.map.map.olMap.removeControl(this.control);
            }
            if (wasActive && this.layer) {
                this.map.map.olMap.removeLayer(this.layer);
            }
            $('.printSubmit', this.$form).addClass('hidden');
        },
        activate: function() {
            if (!this.$selectionFrameToggle.length || this.$selectionFrameToggle.data('active')) {
                var resetScale = !this._isSelectionOnScreen();
                this._activateSelection(resetScale);
            }
            if (this.jobList) {
                this.jobList.resume();
            }
        },
        deactivate: function() {
            if (this.jobList) {
                this.jobList.pause();
            }
            this._deactivateSelection();
        },
        close: function() {
            this.deactivate();
            if (this.popup) {
                if (this.overwriteTemplates) {
                    this._overwriteTemplateSelect(this.options.templates);
                    this.overwriteTemplates = false;
                }
            }
            this._super();
        },
        _isSelectionOnScreen: function() {
            if (this.feature && this.feature.geometry) {
                var viewGeometry = this.map.map.olMap.getExtent().toGeometry();
                return viewGeometry.intersects(this.feature.geometry);
            } else {
                return false;
            }
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
        _getPrintBounds: function(centerX, centerY, scale) {
            // adjust for geodesic pixel aspect ratio so
            // a) our print region selection rectangle appears with ~the same visual aspect ratio as
            //    the main map region in the template, for any projection
            // b) any pixel aspect distortion on geodesic projections is matched WYSIWIG in generated printout
            var centerPixel = this.map.map.olMap.getPixelFromLonLat({lon: centerX, lat: centerY});
            var kmPerPixel = this.map.map.olMap.getGeodesicPixelSize(centerPixel);
            var pixelHeight = this.height * scale / (kmPerPixel.h * 1000);
            var pixelAspectRatio = kmPerPixel.w / kmPerPixel.h;
            var pixelWidth = this.width * scale * pixelAspectRatio / (kmPerPixel.w * 1000);

            var pxBottomLeft = centerPixel.add(-0.5 * pixelWidth, -0.5 * pixelHeight);
            var pxTopRight = centerPixel.add(0.5 * pixelWidth, 0.5 * pixelHeight);
            var llBottomLeft = this.map.map.olMap.getLonLatFromPixel(pxBottomLeft);
            var llTopRight = this.map.map.olMap.getLonLatFromPixel(pxTopRight);
            var x0 = llBottomLeft.lon, x1 = llTopRight.lon, y0 = llBottomLeft.lat, y1 = llTopRight.lat;
            return new OpenLayers.Bounds.fromArray([x0, y0, x1, y1]);
        },
        _printBoundsFromFeature: function(feature, scale) {
            var featureCenter = this.feature.geometry.getBounds().getCenterLonLat();
            return this._getPrintBounds(featureCenter.lon, featureCenter.lat, scale);
        },

        _updateGeometry: function(reset) {
            var scale = this._getPrintScale(),
                rotationField = $('input[name="rotation"]', this.$form);

            if(!(!isNaN(parseFloat(scale)) && isFinite(scale) && scale > 0)) {
                return;
            }
            scale = parseInt(scale);

            var rotation = parseInt(rotationField.val()) || 0;

            this.control.unsetFeature();

            var center;
            if (reset) {
                center = this.map.map.olMap.getCenter();
            } else {
                center = this.feature.geometry.getBounds().getCenterLonLat();
            }
            var bounds = this._getPrintBounds(center.lon, center.lat, scale);
            this.feature = new OpenLayers.Feature.Vector(bounds.toGeometry());

            this.feature.geometry.rotate(-rotation, new OpenLayers.Geometry.Point(center.lon, center.lat));
            this._redrawSelectionFeatures([this.feature]);
            this.control.setFeature(this.feature, {rotation: -rotation});
        },
        _redrawSelectionFeatures: function(features) {
            var layer = this._getSelectionLayer();
            layer.removeAllFeatures();
            layer.addFeatures(features);
            layer.redraw();
        },
        /**
         * Gets the layer on which the selection feature is drawn. Layer is created on first call, then reused
         * for the rest of the session.
         *
         * @return {OpenLayers.Layer.Vector}
         */
        _getSelectionLayer: function() {
            if (!this.layer) {
                var self = this;
                this.layer = new OpenLayers.Layer.Vector("Print", {
                    styleMap: new OpenLayers.StyleMap({
                        'default': $.extend({}, OpenLayers.Feature.Vector.style['default'], this.options.style),
                        'transform': new OpenLayers.Style({
                            display: '${getDisplay}',
                            cursor: 'all-scroll',
                            pointRadius: 0,
                            fillColor: 'none',
                            fillOpacity: 0,
                            strokeColor: '#000'
                        }, {
                            context: {
                                getDisplay: function(feature) {
                                    // hide the resize handle at the se corner
                                    return feature.attributes.role === 'se-resize' ? 'none' : '';
                                }
                            }
                        }),
                        'rotate': new OpenLayers.Style({
                            display: '${getDisplay}',
                            cursor: 'pointer',
                            pointRadius: "${getPointRadius}",
                            fillColor: '#ddd',
                            fillOpacity: 1,
                            strokeColor: '#000'
                        }, {
                            context: {
                                getPointRadius: function(feature) {
                                    var mapScale = self.map.getModel().getScale();
                                    var printScale = self._getPrintScale();
                                    // Make the point smaller for high ratios of mapScale / printScale
                                    // so it doesn't start to obscure the print rectangle.
                                    // This is not an accurate measure of relative size. The rect size
                                    // depends on tempalte choice as well. It's just easier than calculating
                                    // a pixel size from the feature.
                                    // noinspection JSCheckFunctionSignatures
                                    return parseInt(Math.max(3, Math.min(15, Math.sqrt(1000 * printScale / mapScale))));
                                },
                                getDisplay: function(feature) {
                                    // only display rotate handle at the se corner
                                    return feature.attributes.role === 'se-rotate' ? '' : 'none';
                                }
                            }
                        })
                    })
                });
            }
            return this.layer;
        },
        /**
         * Gets the drag control used to rotate and
         * move the selection feature around over the map.
         * Control is created on first call, then reused.
         * Implicitly creates the selection layer, too, if not yet done.
         */
        _getSelectionDragControl: function() {
            var self = this;
            if (!this.control) {
                this.control = new OpenLayers.Control.TransformFeature(this._getSelectionLayer(), {
                    renderIntent: 'transform',
                    rotationHandleSymbolizer: 'rotate'
                });
                this.control.events.on({
                    'transformcomplete': function() {
                        var userRotation = 360 - this.rotation;
                        if (userRotation > 180) {
                            userRotation -= 360;
                        }
                        $('input[name="rotation"]', self.$form).val(userRotation);
                    }
                });
            }
            return this.control;
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
            var scale = parseInt(this._getPrintScale());
            if (!scale) {
                throw new Error("Invalid scale " + scale.toString());
            }
            if (!this.feature) {
                throw new Error("No current selection");
            }
            return this._printBoundsFromFeature(this.feature, scale);
        },
        /**
         *
         * @returns {Array<Object.<string, string>>} legend image urls mapped to layer title
         * @private
         */
        _collectLegends: function() {
            var legends = [];
            var scale = this._getPrintScale();
            var sources = this._getRasterSourceDefs();
            for (var i = 0; i < sources.length; ++i) {
                var source = sources[i];
                var rootLayer = source.configuration.children[0];
                var sourceName = source.configuration.title || (rootLayer && rootLayer.options.title) || '';
                var gsHandler = this.map.model.getGeoSourceHandler(source);
                var leafInfo = gsHandler.getExtendedLeafInfo(source, scale, this._getExportExtent());
                var sourceLegendList = [];
                _.forEach(leafInfo, function(activeLeaf) {
                    if (activeLeaf.state.visibility) {
                        for (var p = -1; p < activeLeaf.parents.length; ++p) {
                            var legendLayer = (p < 0) ? activeLeaf.layer : activeLeaf.parents[p];
                            if (legendLayer.options.legend && legendLayer.options.legend.url) {
                                var remainingParents = activeLeaf.parents.slice(p + 1);
                                var parentNames = remainingParents.map(function(parent) {
                                    return parent.options.title;
                                });
                                parentNames = parentNames.filter(function(x) {
                                    // remove all empty values
                                    return !!x;
                                });
                                // @todo: deduplicate same legend urls, picking a reasonably shared (parent / source) title
                                // NOTE that this can only safely be done server-side, post urlProcessor->getInternalUrl()
                                //      because sources going through the instance tunnel will always have distinct legend
                                //      urls per layer, no matter how unique the internal urls are.
                                var legendInfo = {
                                    url: legendLayer.options.legend.url,
                                    layerName: legendLayer.options.title || '',
                                    parentNames: parentNames,
                                    sourceName: sourceName
                                };
                                // reverse layer order per source
                                sourceLegendList.unshift(legendInfo);
                                break;
                            }
                        }
                    }
                });
                if (sourceLegendList.length) {
                    // reverse source order
                    legends.unshift(sourceLegendList);
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
                    height: Math.abs(ovMap.ovmap.getExtent().getHeight()),
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
        _onTemplateChange: function() {
            var self = this;
            this._getTemplateSize().then(function() {
                if (self.selectionActive) {
                    self._updateGeometry();
                }
            });
        },
        _getTemplateSize: function() {
            var self = this;
            var template = $('select[name="template"]', this.$form).val();
            var cached = this._templateSizeCache[template];
            var promise;
            if (!cached) {
                var url =  this.elementUrl + 'getTemplateSize';
                promise = $.ajax({
                    url: url,
                    type: 'GET',
                    data: {template: template},
                    dataType: "json",
                    success: function(data) {
                        // dimensions delivered in cm, we need m
                        var widthMeters = data.width / 100.0;
                        var heightMeters = data.height / 100.0;
                        self.width = widthMeters;
                        self.height = heightMeters;
                        self._templateSizeCache[template] = {
                            width: widthMeters,
                            height: heightMeters
                        };
                    },
                    error: function() {
                        console.error("getTemplateSize request failed: - template "+template+" might not be available");
                    }
                });
            } else {
                this.width = cached.width;
                this.height = cached.height;
                // Maintain the illusion of an asynchronous operation
                promise = $.Deferred();
                promise.resolve();
            }
            return promise;
        },
        /**
         * @param {OpenLayers.Feature.Vector} feature
         * @return {Object}
         * @private
         */
        _extractPrintAttributes: function(feature) {
            var attributes = $.extend({}, feature.attributes);
            if (feature.data) {
                // Digitizerish OpenLayers feature, 'attributes' property out of date,
                // non-standard 'data' property contains current values
                $.extend(attributes, feature.data);
                if (typeof feature.fid !== 'undefined') {
                    // Non-standard Digitizerish 'fid' property on OpenLayers feature
                    // overrides id
                    attributes.id = feature.fid;
                    attributes.fid = feature.fid;
                }
            }
            return attributes;
        },
        /**
         * @param {OpenLayers.Feature.Vector|Object} attributesOrFeature
         * @param {String} [schemaName]
         */
        printDigitizerFeature: function(attributesOrFeature, schemaName) {
            // Sonderlocke Digitizer
            if (typeof attributesOrFeature !== 'object') {
                var msg = "Unsupported mbPrintClient.printDigitizerFeature invocation. Must pass in printable attributes object (preferred) or OpenLayers feature to extract them from. Update your mapbender/digitizer to >=1.1.68";
                console.error(msg, arguments);
                throw new Error(msg);
            }
            var attributes;
            if (attributesOrFeature.attributes) {
                // Standard OpenLayers feature; see https://github.com/openlayers/ol2/blob/release-2.13.1/lib/OpenLayers/Feature/Vector.js#L44
                attributes = this._extractPrintAttributes(attributesOrFeature);
            } else {
                // Plain-old-data attributesOrFeature object (preferred invocation method)
                attributes = attributesOrFeature;
            }

            this.digitizerData = {
                // Freeze attribute values in place now.
                // Also, if the resulting object is not serializable (cyclic refs), let's run into that error right now
                digitizer_feature: JSON.parse(JSON.stringify(attributes))
            };

            if (schemaName) {
                var self = this;
                this._getDigitizerTemplates(schemaName).then(function() {
                    self.open()
                });
            } else {
                this.open();
            }
        },

        _getDigitizerTemplates: function(schemaName) {
            var self = this;

            var url =  this.elementUrl + 'getDigitizerTemplates';
            return $.ajax({
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

        _initJobList: function($jobListPanel) {
            var jobListOptions = {
                url: this.elementUrl + 'queuelist',
                locale: this.options.locale || window.navigator.language
            };
            var jobList = this.jobList = $['mapbender']['mbPrintClientJobList'].call($jobListPanel, jobListOptions, $jobListPanel);
            $('.tab-container', this.element).tabs({
                active: 0,
                classes: {
                    // inherit colors etc from .tabContainerAlt.tab onto ui-tabs-tab
                    "ui-tabs-tab": "tab"
                },
                activate: function (event, ui) {
                    if (ui.newPanel.hasClass('job-list')) {
                        jobList.start();
                    } else {
                        jobList.stop();
                    }
                }.bind(this)
            });
        }
    });

})(jQuery);
