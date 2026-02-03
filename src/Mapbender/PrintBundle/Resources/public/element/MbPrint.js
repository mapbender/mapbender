(function() {

    class MbPrint extends Mapbender.Element.MbImageExport {
        constructor(configuration, $element) {
            super(configuration, $element);

            this.layer = null;
            this.control = null;
            this.feature = null;
            this.width = null;
            this.height = null;
            this.overwriteTemplates = false;
            this.digitizerData = null;
            this.jobList = null;
            this.useDialog_ = null;
            this.$selectionFrameToggle = null;
            // buffer for ajax-loaded 'getTemplateSize' requests
            // we generally don't want to keep reloading size information
            // for the same template(s) within the same session
            this._templateSizeCache = {};
            this.selectionActive = false;
            this.inputRotation_ = 0;
            /** @type {Array<mbPrintClientSelectionEntry>} */
            this.selectionFeatures_ = [];
            this.overviewWidget_ = null;

            Mapbender.elementRegistry.waitReady('.mb-element-overview').then((overviewWidget) => {
                if (!this.overviewWidget_) {
                    this.overviewWidget_ = overviewWidget;
                }
            });
        }

        _setup() {
            const self = this;
            this.$jobList = $('.job-list', this.$element);
            this.elementUrl = Mapbender.configuration.application.urls.element + '/' + this.$element.attr('id') + '/';
            if (this.$jobList.length) {
                this._initJobList(this.$jobList);
            }

            $('select[name="scale_select"]', this.$form).on('change', function() {
                if (self.selectionActive) {
                    self._resetSelectionFeature();
                }
            });
            $('input[name="rotation"]', this.$form).on('keyup', function() {
                self.inputRotation_ = parseInt($(this).val()) || 0;
                if (self.selectionActive && self.feature) {
                    self._resetSelectionFeature();
                }
            });
            $('select[name="template"]', this.$form)
                .on('change', this._onTemplateChange.bind(this));

            this.useDialog_ = !this.$element.closest('.sideContent').length && !this.$element.closest('.mobilePane').length;
            this.$selectionFrameToggle = $('.-fn-toggle-frame', this.$element);
            this.$selectionFrameToggle.toggleClass('hidden', this.useDialog_);
            $('.popupClose', this.$element).toggleClass('hidden', !this.useDialog_);
            $('button[type="submit"], input[type="submit"]', this.$form).toggleClass('hidden', !this.useDialog_);
            this.$selectionFrameToggle.on('click', function() {
                const $button = $(this);
                const wasActive = !!$button.data('active');
                $button.data('active', !wasActive);
                $button.toggleClass('active', !wasActive);
                const buttonText = wasActive ? 'mb.core.printclient.btn.activate'
                                            : 'mb.core.printclient.btn.deactivate';
                $button.text(Mapbender.trans(buttonText));
                if (!wasActive) {
                    self.activate();
                } else {
                    self._deactivateSelection();
                }
            });
            super._setup();
            this.layer = this._initializeSelectionLayer();
            this.map.element.on('mbmapsrschanged', function() {
                self._onSrsChanged();
            });
        }

        getPopupOptions() {
            const options = super.getPopupOptions();
            const resizable = !!this.$jobList.length;
            return Object.assign(options, {
                width: 400,
                cssClass: (options.cssClass && [options.cssClass] || [])
                    .concat('customPrintDialog').join(' '),
                resizable: resizable,
                scrollable: resizable,
            });
        }

        activateByButton(callback, mbButton) {
            if (this.useDialog_) {
                super.activateByButton(callback, mbButton);
                this.activate();
            }
        }

        closeByButton() {
            this.deactivate();
            if (this.popup) {
                if (this.overwriteTemplates) {
                    this._overwriteTemplateSelect(this.options.templates);
                    this.overwriteTemplates = false;
                }
            }
            super.closeByButton();
        }

        _activateSelection() {
            this._clearFeature(this.feature);
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.raiseElementLayers(this);
            Mapbender.vectorLayerPool.showElementLayers(this);
            this._getTemplateSize().then(() => {
                this.selectionActive = true;
                this._setScale();
                this._resetSelectionFeature();
                $('input[type="submit"]', this.$form).removeClass('hidden');
            });
        }

        _deactivateSelection() {
            const wasActive = !!this.selectionActive;
            this.selectionActive = false;
            if (wasActive) {
                this._endDrag();
            }
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.hideElementLayers(this);
            this._clearFeature(this.feature);
            this.selectionFeatures_ = [];
            $('input[type="submit"]', this.$form).addClass('hidden');
        }

        activate() {
            if (this.useDialog_ || this.$selectionFrameToggle.data('active')) {
                this._activateSelection();
            }
            if (this.jobList) {
                this.jobList.resume();
            }
        }

        deactivate() {
            if (this.jobList) {
                this.jobList.pause();
            }
            this._deactivateSelection();
        }

        /**
         * @param {boolean} [closestToMapScale]
         * @return {Number}
         * @private
         */
        _pickScale(closestToMapScale) {
            if (closestToMapScale || (typeof closestToMapScale === 'undefined')) {
                return this._pickClosestMapScale();
            } else {
                return this._pickFittingMapScale();
            }
        }

        /**
         * Picks a print scale where the currently selected print template will fit into
         * the currently visible map viewport.
         *
         * @return {Number}
         * @private
         */
        _pickFittingMapScale() {
            const scalesReverse = this.options.scales.slice().sort(function(a, b) {
                return (a === b) ? 0 : (a > b) * 2 - 1;
            }).reverse();
            const previous = this.feature;
            const model = this.map.getModel();
            const center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            const extent = this.mbMap.getModel().getCurrentExtent();
            const bufferRatio = 1.15;
            const projectedTemplateExtent = this._getPrintBounds(center[0], center[1], 1);
            const maxSize = {
                h: Math.abs(extent.right - extent.left) / bufferRatio,
                v: Math.abs(extent.top - extent.bottom) / bufferRatio
            };
            const baseSize = {
                h: Math.abs(projectedTemplateExtent.right - projectedTemplateExtent.left),
                v: Math.abs(projectedTemplateExtent.top - projectedTemplateExtent.bottom)
            };
            for (let i = 0; i < scalesReverse.length; ++i) {
                const scale = scalesReverse[i];
                if (maxSize.h >= baseSize.h * scale && maxSize.v >= baseSize.v * scale) {
                    return scale;
                }
            }
            return scalesReverse[scalesReverse.length - 1];
        }

        /**
         * Picks the print scale closest to the current map scale.
         *
         * @return {Number}
         * @private
         */
        _pickClosestMapScale() {
            const scales = this.options.scales.slice();
            const currentScale = Math.round(this.map.getModel().getCurrentScale());
            const sorted = scales.sort(function(a, b) {
                const deltaA = Math.abs(a - currentScale);
                const deltaB = Math.abs(b - currentScale);
                if (deltaA === deltaB) {
                    return 0;
                } else {
                    return (deltaA > deltaB) * 2 - 1;
                }
            });
            return sorted[0];
        }

        _setScale() {
            const select = $("select[name='scale_select']", this.$form);
            const scale = this._pickScale(false);
            select.val(scale).trigger('dropdown.changevisual');
        }

        _getPrintBounds(centerX, centerY, scale) {
            const pupm = this.map.getModel().getUnitsPerMeterAt([centerX, centerY]);
            // Compromise mode: match scale on longitude axis, allow distortions
            // to remain on latitude.
            // Otherwise e.g. EPSG:4326 would stop "looking like" EPSG:4326 completely in printout.
            // This is also slightly less (though still somewhat) broken when using rotation.
            const projectedWidth = this.width * scale * pupm.h;
            const projectedHeight = this.height * scale * pupm.h;
            return {
                left: centerX - .5 * projectedWidth,
                right: centerX + .5 * projectedWidth,
                bottom: centerY - .5 * projectedHeight,
                top: centerY + .5 * projectedHeight
            };
        }

        _printBoundsFromFeature(feature, scale) {
            const center = this.map.getModel().getFeatureCenter(feature);
            return this._getPrintBounds(center[0], center[1], scale);
        }

        _resetSelectionFeature() {
            this._endDrag();
            const previous = this.feature;
            const model = this.map.getModel();
            const center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            this.feature = this._createFeature(this._getPrintScale(), center, this.inputRotation_);
            this._clearFeature(previous);
            this._redrawSelectionFeatures();
            this._startDrag(this.feature);
        }

        /**
         * @param {Number} scale
         * @param {Array<Number>} center
         * @param {Number} [rotation]
         * @return {ol.Feature|OpenLayers.Feature.Vector}
         * @private
         */
        _createFeature(scale, center, rotation) {
            const bounds = this._getPrintBounds(center[0], center[1], scale);
            const geom = ol.geom.Polygon.fromExtent([bounds.left, bounds.bottom, bounds.right, bounds.top]);
            const feature = new ol.Feature(geom);
            this.map.getModel().rotateFeature(feature, -rotation || 0);
            this.selectionFeatures_.push({
                feature: feature,
                rotationBias: rotation || 0,
                tempRotation: 0
            });
            return feature;
        }

        _redrawSelectionFeatures() {
            const layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            const features = this.selectionFeatures_.map(function(entry) {
                return entry.feature;
            });
            layerBridge.clear();
            layerBridge.addNativeFeatures(features);
        }

        /**
         * Creates the layer on which the selection feature is drawn.
         *
         * @return {OpenLayers.Layer.Vector|ol.layer.Vector}
         */
        _initializeSelectionLayer() {
            return Mapbender.vectorLayerPool.getElementLayer(this, 0).getNativeLayer();
        }

        /**
         * @param {Number} degrees
         * @private
         */
        _handleControlRotation(degrees) {
            const entry = this._getFeatureEntry(this.feature);
            entry.tempRotation = degrees;
            let total = entry.rotationBias + degrees;
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
        }

        /**
         * Start drag + rotate interaction on the selection feature
         * @param {(OpenLayers.Feature.Vector|ol.Feature)} feature
         */
        _startDrag(feature) {
            // OpenLayers controls do not properly support outside feature updates.
            // They have APIs for this, but they are buggy in different ways
            // => for both engines, always dispose and recreate
            this._endDrag();
            if (this.control) {
                // dispose
                this.map.getModel().olMap.removeInteraction(this.control);
                this.control.dispose();
            }
            // create and activate
            this.control = this._createDragRotateControl();
            this.map.getModel().olMap.addInteraction(this.control);
            this.control.setActive(true);
            this.control.select(feature);
        }

        _endDrag() {
            if (this.control) {
                this.control.setActive(false);
            }
        }

        _createDragRotateControl() {
            const interaction = new ol.interaction.Transform({
                translate: true,
                rotate: this.options.rotatable,
                translateFeature: true,
                stretch: false,
                layers: [this.layer],
                scale: false
            });
            interaction.on('rotating', (data) => {
                const rad2deg = 360. / (2 * Math.PI);
                this._handleControlRotation(-Math.round(rad2deg * data.angle));
            });
            interaction.on('rotateend', (data) => {
                // All 'rotating' event angles are incremental from the start of the rotation interaction
                // When the rotation ends, bake the final value into the inherent rotation bias of the feature
                let entry = this._getFeatureEntry(data.feature);
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
        }

        _getPrintScale() {
            return parseInt($('select[name="scale_select"]', this.$form).val());
        }

        /**
         * Alias to hook into imgExport base class raster layer processing
         * @returns {*}
         * @private
         */
        _getExportScale() {
            return this._getPrintScale();
        }

        _getExportExtent() {
            const scale = this._getPrintScale();
            if (!scale) {
                throw new Error('Invalid scale ' + scale.toString());
            }
            if (!this.feature) {
                throw new Error('No current selection');
            }
            return this._printBoundsFromFeature(this.feature, scale);
        }

        /**
         *
         * @returns {Array<Object.<string, string>>} legend image urls mapped to layer title
         * @private
         */
        async _collectLegends() {
            const legends = [];
            const scale = this._getPrintScale();
            const sources = this._getRasterSources();
            for (let i = 0; i < sources.length; ++i) {
                const source = sources[i];
                const rootLayer = source.getRootLayer();
                const sourceName = source.configuration?.title || source.options?.title || (rootLayer && rootLayer.options.title) || '';
                const leafInfo = Mapbender.Geo.SourceHandler.getExtendedLeafInfo(source, scale, this._getExportExtent());
                const sourceLegendList = [];
                const legendIds = [];

                for (const activeLeaf of Object.values(leafInfo)) {
                    if (activeLeaf.state.visibility) {
                        for (let p = -1; p < activeLeaf.parents.length; ++p) {
                            const legendLayer = (p < 0) ? activeLeaf.layer : activeLeaf.parents[p];
                            const legend = legendLayer.getLegend(true);
                            if (!legend) continue;
                            if (legend.layers) {
                                legend.layers = await Promise.resolve(legend.layers);
                            }
                            if (legendIds.includes(legendLayer.getId())) break;
                            legendIds.push(legendLayer.getId());
                            let remainingParents = activeLeaf.parents.slice(p + 1);
                            let parentNames = remainingParents.map(function(parent) {
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
                            const legendInfo = {
                                layerName: legendLayer.options.title || '',
                                parentNames: parentNames,
                                sourceName: sourceName,
                                ...legend
                            };
                            sourceLegendList.unshift(legendInfo);
                            break;
                        }
                    }
                }
                if (sourceLegendList.length) {
                    // reverse source order
                    legends.unshift(sourceLegendList);
                }
            }
            return legends;
        }

        /**
         * Should return true if the given layer needs to be included in print
         *
         * @param {OpenLayers.Layer.Vector|OpenLayers.Layer} layer
         * @returns {boolean}
         * @private
         */
        _filterGeometryLayer(layer) {
            if (!super._filterGeometryLayer(layer)) {
                return false;
            }
            // don't print own print extent preview layer
            if (layer === this.layer) {
                return false;
            }
            return true;
        }

        /**
         * Should return true if the given feature should be included in print.
         *
         * @param {OpenLayers.Feature.Vector} feature
         * @returns {boolean}
         * @private
         */
        _filterFeature(feature) {
            if (!feature.geometry.intersects(this.feature.geometry)) {
                return false;
            }
            // don't print own print extent preview feature}
            if (feature === this.feature) {
                return false;
            }
            return true;
        }

        _collectOverview() {
            if (this.overviewWidget_ && this.overviewWidget_.overview && typeof (this.overviewWidget_.getPrintData) === 'function'){
                try {
                    return this.overviewWidget_.getPrintData();
                } catch (e) {
                    console.warn("Error collecting overview print data, skipping", e);
                    return null;
                }
            } else {
                return null;
            }
        }

        async _collectJobData() {
            let jobData = await super._collectJobData();
            // Remove upstream rotation value. We have this as a top-level input field. Backend may get confused
            // when we submit both
            delete jobData['rotation'];
            const overview = this._collectOverview();
            const flatCoords = this.feature.getGeometry().getFlatCoordinates();
            const extentFeature = [];
            for (let c = 0; c < flatCoords.length; c += 2) {
                extentFeature.push({
                    x: flatCoords[c],
                    y: flatCoords[c + 1]
                });
            }
            const mapDpi = (this.map.options || {}).dpi || 72;
            Object.assign(jobData, {
                overview: overview,
                mapDpi: mapDpi,
                'extent_feature': extentFeature
            });
            if ($('input[name="printLegend"]', this.$form).prop('checked')) {
                Object.assign(jobData, {
                    legends: await this._collectLegends()
                });
            }
            if (this.digitizerData) {
                Object.assign(jobData, this.digitizerData);
            }
            return jobData;
        }

        _onSubmit(evt) {
            if (!this.selectionActive) {
                // prevent submit without selection (sidepane mode has separate button to start selecting)
                return false;
            }
            const proceed = super._onSubmit(evt);
            let $tabs = $('.tab-container', this.$element);
            if (proceed && $tabs.length) {
                // switch to queue display tab on successful submit
                window.setTimeout(function() {
                    $tabs.tabs({active: 1});
                }, 50);
            }
            return proceed;
        }

        _onTemplateChange() {
            this._getTemplateSize().then(() => {
                if (this.selectionActive) {
                    this._resetSelectionFeature();
                }
            });
        }

        _getTemplateSize() {
            const template = $('select[name="template"]', this.$form).val();
            const cached = this._templateSizeCache[template];
            let promise;
            if (!cached) {
                const url = this.elementUrl + 'getTemplateSize';
                promise = $.ajax({
                    url: url,
                    type: 'GET',
                    data: {template: template},
                    dataType: 'json',
                    success: (data) => {
                        // dimensions delivered in cm, we need m
                        const widthMeters = data.width / 100.0;
                        const heightMeters = data.height / 100.0;
                        this.width = widthMeters;
                        this.height = heightMeters;
                        this._templateSizeCache[template] = {
                            width: widthMeters,
                            height: heightMeters
                        };
                    },
                    error: () => { console.error('getTemplateSize request failed: - template ' + template + ' might not be available'); }
                });
            } else {
                this.width = cached.width;
                this.height = cached.height;
                // Maintain the illusion of an asynchronous operation
                promise = $.Deferred();
                promise.resolve();
            }
            return promise;
        }

        /**
         * @param {OpenLayers.Feature.Vector} feature
         * @return {Object}
         * @private
         */
        _extractPrintAttributes(feature) {
            const attributes = $.extend({}, feature.attributes);
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
        }

        /**
         * @param {OpenLayers.Feature.Vector|Object} attributesOrFeature
         * @param {String} [schemaName]
         * @param {Array<Object>} [templates]
         */
        printDigitizerFeature(attributesOrFeature, schemaName, templates) {
            // Sonderlocke Digitizer
            if (typeof attributesOrFeature !== 'object') {
                const msg = 'Unsupported mbPrintClient.printDigitizerFeature invocation. Must pass in printable attributes object (preferred) or OpenLayers feature to extract them from. Update your mapbender/digitizer to >=1.1.68';
                console.error(msg, arguments);
                throw new Error(msg);
            }
            let attributes;
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
            this.activateByButton();
        }

        _overwriteTemplateSelect(templates) {
            let templateSelect = $('select[name=template]', this.$element);
            let templateList = templateSelect.siblings('.dropdownList');
            let valueContainer = templateSelect.siblings('.dropdownValue');
            templateSelect.empty();
            templateList.empty();
            let count = 0;
            $.each(templates, function(key, template) {
                templateSelect.append($('<option></option>', {
                    'value': template.template,
                    'html': template.label,
                    'class': 'opt-' + count
                }));
                templateList.append($('<li></li>', {
                    'html': template.label,
                    'class': 'item-' + count
                }));
                if (count == 0){
                    valueContainer.text(template.label);
                }
                ++count;
            });
            this.overwriteTemplates = true;
        }

        _onSrsChanged() {
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            this._clearFeature(this.feature);
            if (this.selectionActive) {
                this._resetSelectionFeature();
            }
        }

        /**
         * @param {ol.Feature|OpenLayers.Feature.Vector} feature
         * @return {mbPrintClientSelectionEntry|null}
         * @private
         */
        _getFeatureEntry(feature) {
            return (this.selectionFeatures_.filter(function(entry) {
                return entry.feature === feature;
            })[0]) || null;
        }

        _clearFeature(feature) {
            this.selectionFeatures_ = this.selectionFeatures_.filter(function(o) {
                return o.feature !== feature;
            });
            if (this.feature === feature) {
                this.feature = null;
            }
        }

        _initJobList($jobListPanel) {
            const jobListOptions = {
                url: this.elementUrl + 'queuelist',
                locale: this.options.locale || window.navigator.language
            };
            const jobList = new MbPrintJobList(jobListOptions, $jobListPanel);
            $('.tab-container', this.$element).tabs({
                active: 0,
                classes: {
                    'ui-tabs-active': 'active'
                },
                activate: function (event, ui) {
                    if (ui.newPanel.hasClass('job-list')) {
                        jobList.start();
                    } else {
                        jobList.stop();
                    }
                }
            });
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbPrint = MbPrint;
})();
