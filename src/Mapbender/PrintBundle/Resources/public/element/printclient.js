(function($) {
    /**
     * @typedef {Object} mbPrintClientSelectionEntry
     * @property {Object} feature
     * @property {Number} rotationBias inherent feature geometry rotation, before handing it to rotate interaction
     * @property {Number} tempRotation in-progress feature rotation added by rotate interaction
     */
    $.widget("mapbender.mbPrintClient",  $.mapbender.mbImageExport, {
        options: {
            locale: null,
            rotatable: true,
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
        width: null,
        height: null,
        overwriteTemplates: false,
        digitizerData: null,
        jobList: null,
        useDialog_: null,
        $selectionFrameToggle: null,
        // buffer for ajax-loaded 'getTemplateSize' requests
        // we generally don't want to keep reloading size information
        // for the same template(s) within the same session
        _templateSizeCache: {},
        selectionActive: false,
        inputRotation_: 0,
        /** @type {Array<mbPrintClientSelectionEntry>} */
        selectionFeatures_: [],

        overviewWidget_: null,

        _create: function() {
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-overview').then(function(overviewWidget) {
                if (!self.overviewWidget_) {
                    self.overviewWidget_ = overviewWidget;
                }
            });
            this._super();
        },
        _setup: function(){
            var self = this;
            var $jobList = $('.job-list', this.element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.element.attr('id') + '/';
            if ($jobList.length) {
                this._initJobList($jobList);
            }

            $('select[name="scale_select"]', this.$form).on('change', function() {
                if (self.selectionActive) {
                    self._resetSelectionFeature();
                }
            });
            $('input[name="rotation"]', this.$form).on('keyup', function() {
                /** @this {HTMLInputElement} */
                self.inputRotation_ = parseInt($(this).val()) || 0;
                if (self.selectionActive && self.feature) {
                    self._resetSelectionFeature();
                }
            });
            $('select[name="template"]', this.$form)
                .on('change', $.proxy(this._onTemplateChange, this));

            this.useDialog_ = !this.element.closest('.sideContent').length && !this.element.closest('.mobilePane').length;
            this.$selectionFrameToggle = $('.-fn-toggle-frame', this.element);
            this.$selectionFrameToggle.toggleClass('hidden', this.useDialog_);
            $('.popupClose', this.element).toggleClass('hidden', !this.useDialog_);
            $('button[type="submit"], input[type="submit"]', this.$form).toggleClass('hidden', !this.useDialog_);
            this.$selectionFrameToggle.on('click', function() {
                var $button = $(this);
                var wasActive = !!$button.data('active');
                $button.data('active', !wasActive);
                $button.toggleClass('active', !wasActive);
                var buttonText = wasActive ? 'mb.core.printclient.btn.activate'
                                           : 'mb.core.printclient.btn.deactivate';
                $button.text(Mapbender.trans(buttonText));
                if (!wasActive) {
                    self.activate();
                } else {
                    self._deactivateSelection();
                }
            });
            this._super();
            this.layer = this._initializeSelectionLayer();
            this.map.element.on('mbmapsrschanged', function() {
                self._onSrsChanged();
            });
        },
        getPopupOptions: function() {
            var options = this._superApply(arguments);
            return Object.assign(options, {
                width: 400,
                cssClass: (options.cssClass && [options.cssClass] || [])
                    .concat('customPrintDialog').join(' ')
            });
        },
        open: function(callback){
            this.callback = callback || null;
            if (this.useDialog_) {
                if(!this.popup || !this.popup.$element){
                    this._superApply(arguments);
                }
                this.activate();
            }
        },
        _activateSelection: function() {
            this._clearFeature(this.feature);
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.raiseElementLayers(this);
            Mapbender.vectorLayerPool.showElementLayers(this);
            var self = this;
            this._getTemplateSize().then(function() {
                self.selectionActive = true;
                self._setScale();
                self._resetSelectionFeature();
                $('input[type="submit"]', self.$form).removeClass('hidden');
            });
        },
        _deactivateSelection: function() {
            var wasActive = !!this.selectionActive;
            this.selectionActive = false;
            if (wasActive) {
                this._endDrag();
            }
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.hideElementLayers(this);
            this._clearFeature(this.feature);
            this.selectionFeatures_ = [];
            $('input[type="submit"]', this.$form).addClass('hidden');
        },
        activate: function() {
            if (this.useDialog_ || this.$selectionFrameToggle.data('active')) {
                this._activateSelection();
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
        /**
         * @param {boolean} [closestToMapScale]
         * @return {Number}
         * @private
         */
        _pickScale: function(closestToMapScale) {
            if (closestToMapScale || (typeof closestToMapScale === 'undefined')) {
                return this._pickClosestMapScale();
            } else {
                return this._pickFittingMapScale();
            }
        },
        /**
         * Picks a print scale where the currently selected print template will fit into
         * the currently visible map viewport.
         *
         * @return {Number}
         * @private
         */
        _pickFittingMapScale: function() {
            // @todo: scales should already be sorted; either server side or in constructor
            var scalesReverse = this.options.scales.slice().sort(function(a, b) {
                // basic numeric sort (default JS sort is lexical)
                return (a === b) ? 0 : (a > b) * 2 - 1;
            }).reverse();
            // @todo: extract copy & paste with _getSelectionFeature (next 3 lines) into method
            var previous = this.feature;
            var model = this.map.getModel();
            var center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            var extent = this.mbMap.getModel().getCurrentExtent();
            // "Fifteen percent should be good enough for anyone"...
            // Keep selection inside visible map, even with map extending under top+bottom toolbars
            var bufferRatio = 1.15;
            var projectedTemplateExtent = this._getPrintBounds(center[0], center[1], 1);
            var maxSize = {
                h: Math.abs(extent.right - extent.left) / bufferRatio,
                v: Math.abs(extent.top - extent.bottom) / bufferRatio
            };
            var baseSize = {
                h: Math.abs(projectedTemplateExtent.right - projectedTemplateExtent.left),
                v: Math.abs(projectedTemplateExtent.top - projectedTemplateExtent.bottom)
            };
            for (var i = 0; i < scalesReverse.length; ++i) {
                var scale = scalesReverse[i];
                if (maxSize.h >= baseSize.h * scale && maxSize.v >= baseSize.v * scale) {
                    return scale;
                }
            }
            return scalesReverse[scalesReverse.length - 1];
        },
        /**
         * Picks the print scale closest to the current map scale.
         *
         * @return {Number}
         * @private
         */
        _pickClosestMapScale: function() {
            var scales = this.options.scales.slice();
            var currentScale = Math.round(this.map.getModel().getCurrentScale());
            // sort by absolute difference to current scale
            var sorted = scales.sort(function(a, b) {
                var deltaA = Math.abs(a - currentScale);
                var deltaB = Math.abs(b - currentScale);
                if (deltaA === deltaB) {
                    return 0;
                } else {
                    return (deltaA > deltaB) * 2 - 1;
                }
            });
            return sorted[0];
        },
        _setScale: function() {
            var select = $("select[name='scale_select']", this.$form);
            var scale = this._pickScale(false);
            select.val(scale).trigger('dropdown.changevisual');
        },
        _getPrintBounds: function(centerX, centerY, scale) {
            if (Mapbender.mapEngine.code !== 'ol2') {
                // OpenLayers >= 4
                var pupm = this.map.getModel().getUnitsPerMeterAt([centerX, centerY]);

                // Compromise mode: match scale on longitude axis, allow distortions
                // to remain on latitude.
                // Otherwise e.g. EPSG:4326 would stop "looking like" EPSG:4326 completely in printout.
                // This is also slightly less (though still somewhat) broken when using rotation.
                var projectedWidth = this.width * scale * pupm.h;
                var projectedHeight = this.height * scale * pupm.h; // This is deliberately not pupm.v!

                return {
                    left: centerX - .5 * projectedWidth,
                    right: centerX + .5 * projectedWidth,
                    bottom: centerY - .5 * projectedHeight,
                    top: centerY + .5 * projectedHeight
                };
            }
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
            return {
                left: llBottomLeft.lon,
                right: llTopRight.lon,
                bottom: llBottomLeft.lat,
                top: llTopRight.lat
            };
        },
        _printBoundsFromFeature: function(feature, scale) {
            var center = this.map.getModel().getFeatureCenter(feature);
            return this._getPrintBounds(center[0], center[1], scale);
        },
        _resetSelectionFeature: function() {
            this._endDrag();
            var previous = this.feature;
            var model = this.map.getModel();
            var center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            this.feature = this._createFeature(this._getPrintScale(), center, this.inputRotation_);
            this._clearFeature(previous);
            this._redrawSelectionFeatures();
            this._startDrag(this.feature);
        },
        /**
         * @param {Number} scale
         * @param {Array<Number>} center
         * @param {Number} [rotation]
         * @return {ol.Feature|OpenLayers.Feature.Vector}
         * @private
         */
        _createFeature: function(scale, center, rotation) {
            var bounds = this._getPrintBounds(center[0], center[1], scale);
            var feature;
            if (Mapbender.mapEngine.code === 'ol2') {
                var geometry = OpenLayers.Bounds.prototype.toGeometry.call(bounds);
                feature = new OpenLayers.Feature.Vector(geometry);
            } else {
                var geom = ol.geom.Polygon.fromExtent([bounds.left, bounds.bottom, bounds.right, bounds.top]);
                feature = new ol.Feature(geom);
            }
            this.map.getModel().rotateFeature(feature, -rotation || 0);
            this.selectionFeatures_.push({
                feature: feature,
                rotationBias: rotation || 0,
                tempRotation: 0
            });
            return feature;
        },
        _redrawSelectionFeatures: function() {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            var features = this.selectionFeatures_.map(function(entry) {
                return entry.feature;
            });
            layerBridge.clear();
            layerBridge.addNativeFeatures(features);
        },
        /**
         * Creates the layer on which the selection feature is drawn.
         *
         * @return {OpenLayers.Layer.Vector|ol.layer.Vector}
         */
        _initializeSelectionLayer: function() {
            var layer = Mapbender.vectorLayerPool.getElementLayer(this, 0).getNativeLayer();
            if (Mapbender.mapEngine.code === 'ol2') {
                var self = this;
                layer.styleMap = new OpenLayers.StyleMap({
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
                                    var mapScale = self.map.getModel().getCurrentScale();
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
                                    return self.options.rotatable && feature.attributes.role === 'se-rotate' ? '' : 'none';
                                }
                            }
                        })
                });
            }
            return layer;
        },
        /**
         * @param {Number} degrees
         * @private
         */
        _handleControlRotation: function(degrees) {
            var entry = this._getFeatureEntry(this.feature);
            entry.tempRotation = degrees;
            var total = entry.rotationBias + degrees;
            // limit to +-180
            while (total > 180) {
                total -= 360;
            }
            while (total <= -180) {
                total += 360;
            }
            total = Math.round(total);
            $('input[name="rotation"]', this.$form).val(total);
            this.inputRotation_ = total;
        },
        /**
         * Start drag + rotate interaction on the selection feature
         * @param {(OpenLayers.Feature.Vector|ol.Feature)} feature
         */
        _startDrag: function(feature) {
            // Neither ol2 nor ol4 controls do not properly support outside feature updates.
            // They have APIs for this, but they are buggy in different ways
            // => for both engines, always dispose and recreate
            this._endDrag();
            if (Mapbender.mapEngine.code === 'ol2') {
                if (this.control) {
                    // dispose
                    this.map.map.olMap.removeControl(this.control);
                }
                // create and activate
                this.control = this._createDragRotateControlOl2();
                var entry = this._getFeatureEntry(feature);
                this.map.map.olMap.addControl(this.control);
                this.control.setFeature(feature, {rotation: -entry.rotationBias});
                this.control.activate();
            } else {
                if (this.control) {
                    // dispose
                    this.map.getModel().olMap.removeInteraction(this.control);
                    this.control.dispose();
                }
                // create and activate
                this.control = this._createDragRotateControlOl4();
                this.map.getModel().olMap.addInteraction(this.control);
                this.control.setActive(true);
                this.control.select(feature);
            }
        },
        _endDrag: function() {
            if (this.control) {
                if (Mapbender.mapEngine.code === 'ol2') {
                    // Call twice to work around incomplete unsetFeature cleanup when
                    // control is still active
                    this.control.unsetFeature();
                    this.control.unsetFeature();
                    this.control.deactivate();
                } else {
                    this.control.setActive(false);
                }
            }
        },
        _createDragRotateControlOl2: function() {
            var self = this;
            var control = new OpenLayers.Control.TransformFeature(this.layer, {
                renderIntent: 'transform',
                rotationHandleSymbolizer: 'rotate',
                rotate: this.options.rotatable,
                layer: this.layer
            });
            control.events.on({
                'transform': function(data) {
                    /** @this {OpenLayers.Control.TransformFeature} */
                    var entry = self._getFeatureEntry(this.feature);
                    if (data.rotation) {
                        // per-event rotation is INCREMENTAL (delta on top of previous event)
                        self._handleControlRotation(entry.tempRotation - data.rotation);
                    }
                },
                'transformcomplete': function() {
                    /** @this {OpenLayers.Control.TransformFeature} */
                    var entry = self._getFeatureEntry(this.feature);
                    entry.rotationBias += entry.tempRotation;
                    entry.tempRotation = 0;
                }
            });
            return control;
        },
        _createDragRotateControlOl4: function() {
            var self = this;
            var interaction = new ol.interaction.Transform({
                translate: true,
                rotate: this.options.rotatable,
                translateFeature: true,
                stretch: false,
                layers: [this.layer],
                scale: false
            });
            interaction.on('rotating', /** @this {ol.interaction.Transform} */ function(data) {
                var rad2deg = 360. / (2 * Math.PI);
                self._handleControlRotation(-Math.round(rad2deg * data.angle));
            });
            interaction.on('rotateend', /** @this {ol.interaction.Transform} */ function(data) {
                // All 'rotating' event angles are incremental from the start of the rotation interaction
                // When the rotation ends, bake the final value into the inherent rotation bias of the feature
                var entry = self._getFeatureEntry(data.feature);
                entry.rotationBias += entry.tempRotation;
                entry.tempRotation = 0;
            });
            // Adjust styling
            // Interaction can repeatedly call setDefaultStyle, patching once is not enough
            // setDefaultStyle also ends in a call to drawSketch_
            // To reliably influence style, we need to monkey-patch drawSketch_ itself
            /** @this {ol.interaction.Transform} */
            interaction.drawSketch_ = function(center) {
                // Disable center point translate handle marker ("bigpt").
                this.style.default[0].setImage(null);
                this.style.translate[0].setImage(null);
                this.style.rotate0[0].setImage(null);
                //this.style.default = styleDefault;
                //this.style.translate = styleTranslate;
                ol.interaction.Transform.prototype.drawSketch_.call(this, center);
            };
            return interaction;
        },
        _getPrintScale: function() {
            return parseInt($('select[name="scale_select"]', this.$form).val());
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
            var scale = this._getPrintScale();
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
            var sources = this._getRasterSources();
            for (var i = 0; i < sources.length; ++i) {
                var source = sources[i];
                var rootLayer = source.configuration.children[0];
                var sourceName = source.configuration.title || (rootLayer && rootLayer.options.title) || '';
                var leafInfo = Mapbender.Geo.SourceHandler.getExtendedLeafInfo(source, scale, this._getExportExtent());
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
            if (this.overviewWidget_ && typeof (this.overviewWidget_.getPrintData) === 'function') {
                try {
                    return this.overviewWidget_.getPrintData();
                } catch (e) {
                    console.warn("Error collecting overview print data, skipping", e);
                    return null;
                }
            } else {
                return null;
            }
        },
        _collectJobData: function() {
            var jobData = this._super();
            // Remove upstream rotation value. We have this as a top-level input field. Backend may get confused
            // when we submit both
            delete jobData['rotation'];
            var overview = this._collectOverview();
            var extentFeature;
            if (Mapbender.mapEngine.code === 'ol2') {
                extentFeature = this.feature.geometry.components[0].components.map(function(component) {
                    return {
                        x: component.x,
                        y: component.y
                    };
                });
                // Extracted extent feature is orderer top left => top right => bottom right => bottom left
                // Adjust to print backend expected order bottom left => top left etc (used for coordinates display in certain templates)
                // see https://github.com/mapbender/mapbender/issues/1280
                extentFeature.pop();    // remove duplicated "finishing coordinate"
                extentFeature.splice(0, 0, extentFeature.pop());    // reorder to start with bottom left
                extentFeature.push(extentFeature[0]);   // re-add "finishing coordinate" duplicate
            } else {
                var flatCoords = this.feature.getGeometry().getFlatCoordinates();
                extentFeature = [];
                for (var c = 0; c < flatCoords.length; c += 2) {
                    extentFeature.push({
                        x: flatCoords[c],
                        y: flatCoords[c + 1]
                    });
                }
            }
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
        _onSubmit: function(evt) {
            if (!this.selectionActive) {
                // prevent submit without selection (sidepane mode has separate button to start selecting)
                return false;
            }
            var proceed = this._super(evt);
            var $tabs = $('.tab-container', this.element);
            if (proceed && $tabs.length) {
                // switch to queue display tab on successful submit
                window.setTimeout(function() {
                    $tabs.tabs({active: 1});
                }, 50);
            }
            return proceed;
        },
        _onTemplateChange: function() {
            var self = this;
            this._getTemplateSize().then(function() {
                if (self.selectionActive) {
                    self._resetSelectionFeature();
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
         * @param {Array<Object>} [templates]
         */
        printDigitizerFeature: function(attributesOrFeature, schemaName, templates) {
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

            if (templates && templates.length) {
                this._overwriteTemplateSelect(templates);
                this.overwriteTemplates = true;
            }
            this.open();
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
        _onSrsChanged: function() {
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            this._clearFeature(this.feature);
            if (this.selectionActive) {
                this._resetSelectionFeature();
            }
        },
        /**
         * @param {ol.Feature|OpenLayers.Feature.Vector} feature
         * @return {mbPrintClientSelectionEntry|null}
         * @private
         */
        _getFeatureEntry: function(feature) {
            return (this.selectionFeatures_.filter(function(entry) {
                return entry.feature === feature;
            })[0]) || null;
        },
        _clearFeature: function(feature) {
            this.selectionFeatures_ = this.selectionFeatures_.filter(function(o) {
                return o.feature !== feature;
            });
            if (this.feature === feature) {
                this.feature = null;
            }
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
                    "ui-tabs-active": "active"
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
