(function() {
    /**
     * Batch Print Client Widget
     * 
     * Extends MbPrint with multiframe/serial printing support:
     * - Multiple print frame selection with mouse-following
     * - Click to pin frames to map
     * - Frame table management
     * - Drag-to-rotate functionality with visual handles
     * - Batch print submission (both queued and direct)
     */
    class MbBatchPrintClient extends Mapbender.Element.MbPrint {
        // Frame management (initialized in constructor)
        frameManager = null;
        
        // Rotation controller (initialized in constructor)
        rotationController = null;
        
        // Table controller (initialized in constructor)
        tableController = null;
        
        // Mouse-following state
        mouseFollowActive = false;
        mouseMoveHandler = null;
        mouseClickHandler = null;
        mapHoverHandler = null;
        
        // Layer constants
        PINNED_FRAMES_LAYER = 1;  // Layer index for pinned frame features
        PINNED_FRAMES_ZINDEX = 1000;  // Top layer - actual print frames
        ROTATION_ZINDEX = 999;         // Middle layer - interactive rotation controls
        TRACK_LAYER_ZINDEX = 998;      // Bottom layer - reference track/guide layer
        
        // Interaction tolerances
        hitToleranceFrame = 5;
        hitToleranceRotation = 10;
        
        // Animation and timing
        dragEndDelay = 50;
        tabSwitchDelay = 50;
        
        // Map view settings
        trackFitPadding = [100, 100, 100, 100];
        trackFitDuration = 500;
        trackFitMaxZoom = 16;
        
        // Style configuration (initialized in constructor)
        styleConfig = null;

        constructor(configuration, $element) {
            super(configuration, $element);
            
            // Defer initialization until parent setup is complete
            var self = this;
            Mapbender.elementRegistry.waitReady('.mb-element-map').then(function(mbMap) {
                self._setupBatchPrint(mbMap);
            });
        }
        
        /**
         * Override _setup to handle geofile section visibility
         * @private
         */
        _setup() {
            super._setup();
            
            // In dialog mode, show geofile upload section
            // In sidepane mode, hide it until frame is activated
            if (this.useDialog_) {
                $('.-fn-geofile-upload', this.$element).show();
            } else {
                $('.-fn-geofile-upload', this.$element).hide();
            }
        }
        
        /**
         * Setup batch print functionality after map is ready
         * @private
         */
        _setupBatchPrint(mbMap) {
            var self = this;
            
            // Initialize style configuration
            this.styleConfig = new Mapbender.BatchPrintStyleConfig();
            
            // Initialize frame manager
            this.frameManager = new Mapbender.BatchPrintFrameManager({
                styleConfig: this.styleConfig,
                onFrameAdded: function(frameData) {
                    self._addPinnedFeatureToMap(frameData.feature);
                },
                onFrameRemoved: function(frameData) {
                    if (self.rotationController) {
                        self.rotationController.removeHandle(frameData.id);
                    }
                    var layerBridge = Mapbender.vectorLayerPool.getElementLayer(self, self.PINNED_FRAMES_LAYER);
                    layerBridge.removeNativeFeatures([frameData.feature]);
                }
            });
            
            // Initialize rotation controller
            this.rotationController = new Mapbender.BatchPrintRotationController({
                map: this.map.getModel().olMap,
                widget: this,
                styleConfig: this.styleConfig,
                rotationZIndex: this.ROTATION_ZINDEX,
                hitTolerance: this.hitToleranceRotation,
                dragEndDelay: this.dragEndDelay,
                getFrameById: function(frameId) {
                    return self.frameManager.getFrame(frameId);
                },
                onRotationComplete: function(frameId) {
                    self.tableController.updateTable();
                }
            });
            
            // Initialize table controller
            this.tableController = new Mapbender.BatchPrintTableController({
                $element: this.$element,
                widget: this,
                styleConfig: this.styleConfig,
                frameManager: this.frameManager,
                rotationController: this.rotationController,
                map: this.map,
                pinnedFramesLayer: this.PINNED_FRAMES_LAYER,
                hitToleranceFrame: this.hitToleranceFrame,
                hitToleranceRotation: this.hitToleranceRotation,
                getDefaultStyle: function() {
                    return self._getDefaultStyle();
                },
                onDeleteFrame: function(frameId) {
                    self._deleteFrame(frameId);
                },
                onFrameReorder: function(newOrder) {
                    self.frameManager.reorder(newOrder);
                }
            });
            
            // Setup property proxies for backward compatibility
            this._setupPropertyProxies();
            
            // Add unique class identifier
            this.$element.addClass('mb-element-batchprintclient');

            // Remove rotation control (form row containing the rotation input)
            $('input[name="rotation"]', this.$element).parent().remove();
            
            // Initialize geofile handler
            this.geofileHandler = new Mapbender.BatchPrintGeofileHandler({
                $element: this.$element,
                widget: this,
                map: this.map,
                styleConfig: this.styleConfig,
                trackLayerZIndex: this.TRACK_LAYER_ZINDEX,
                trackFitPadding: this.trackFitPadding,
                trackFitDuration: this.trackFitDuration,
                trackFitMaxZoom: this.trackFitMaxZoom,
                onFramePlaced: function(coord, bearing, previousRotation) {
                    return self._placeFrameAtPosition(coord, bearing, previousRotation);
                }
            });
            
            // Mark element layers as internal for print filtering
            var selectionLayer = Mapbender.vectorLayerPool.getElementLayer(this, 0);
            if (selectionLayer && selectionLayer.getNativeLayer()) {
                selectionLayer.getNativeLayer().set('batchPrintClientInternal', true);
            }
            
            var pinnedLayer = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            if (pinnedLayer && pinnedLayer.getNativeLayer()) {
                pinnedLayer.getNativeLayer().set('batchPrintClientInternal', true);
            }
            
            // Change submit button text
            $('input[type="submit"]', this.$element).val(Mapbender.trans('mb.print.printclient.batchprint.btn.submit'));
            
            // Setup delete all frames button
            $('.-fn-delete-all-frames', this.$element).on('click', function() {
                self._deleteAllFrames();
            });
            
            // Stop mouse-follow when mouse enters widget
            this.$element.on('mouseenter', function() {
                if (self.mouseFollowActive) {
                    self._stopMouseFollow();
                }
                // Hide selection frame when mouse enters widget
                if (self.feature) {
                    self.feature.setStyle(self.styleConfig.createEmptyStyle());  // Make invisible
                }
            });
            
            // Restart mouse-follow when mouse leaves widget
            this.$element.on('mouseleave', function() {
                if (self.selectionActive && !self.mouseFollowActive) {
                    self._startMouseFollow();
                }
                // Show selection frame when mouse leaves widget
                if (self.feature) {
                    self.feature.setStyle(null);  // Reset to default style
                    self._redrawSelectionFeatures();
                }
            });
        }
        
        /**
         * Setup property proxies for backward compatibility
         * @private
         */
        _setupPropertyProxies() {
            var self = this;
            
            Object.defineProperty(this, 'pinnedFeatures', {
                get: function() { return self.frameManager.getFrames(); },
                configurable: true
            });
            
            Object.defineProperty(this, 'featureCounter', {
                get: function() { return self.frameManager.frameCounter; },
                set: function(value) { self.frameManager.frameCounter = value; },
                configurable: true
            });
        }
        
        /**
         * Filter out internal BatchPrintClient layers from print output
         * All internal layers are marked with 'batchPrintClientInternal' property
         * @param {ol.layer.Vector} layer - OpenLayers vector layer
         * @returns {boolean} True if layer should be included in print
         * @private
         */
        _filterVectorLayer(layer) {
            return layer.get('batchPrintClientInternal') !== true;
        }
        
        /**
         * Override parent's _pickScale to select a scale two steps bigger (more zoomed out)
         * than the current map scale for batch printing
         */
        _pickScale(closestToMapScale) {
            // Get the scale that the parent would choose (current map scale or smallest that fits)
            const parentScale = super._pickScale(closestToMapScale);
            
            // Get available scales from options
            var scales = this.options.scales;
            if (!scales || !scales.length) {
                return parentScale;
            }
            
            // Find the index of the parent's chosen scale
            var currentIndex = scales.indexOf(parentScale);
            if (currentIndex === -1) {
                return parentScale;
            }
            
            // Move two steps backward in the array (bigger scale = more zoomed out)
            // Using 2 steps provides a good balance between coverage and detail for batch printing
            var SCALE_STEPS_ZOOM_OUT = 2;
            var targetIndex = Math.max(currentIndex - SCALE_STEPS_ZOOM_OUT, 0);
            return scales[targetIndex];
        }
        
        /**
         * Override parent's _activateSelection to start mouse-following
         */
        _activateSelection() {
            // Call grandparent to avoid parent's drag interaction
            this._clearFeature(this.feature);
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.raiseElementLayers(this);
            Mapbender.vectorLayerPool.showElementLayers(this);
            
            // Show geofile upload section when frame is activated (only in sidepane mode)
            if (!this.useDialog_) {
                $('.-fn-geofile-upload', this.$element).show();
            }
            
            var self = this;
            this._getTemplateSize().then(function() {
                self.selectionActive = true;
                self._setScale();
                
                // Create initial feature at map center
                var model = self.map.getModel();
                var center = model.getCurrentMapCenter();
                self.feature = self._createFeature(self._getPrintScale(), center, self.inputRotation_);
                self._redrawSelectionFeatures();
                
                // Start mouse follow instead of drag interaction
                self._startMouseFollow();
                
                $('input[type="submit"]', self.$form).removeClass('hidden');
            });
        }
        
        /**
         * Override parent's _deactivateSelection to cleanup
         */
        _deactivateSelection() {
            this._stopMouseFollow();
            this._clearPinnedFeatures();
            
            // Hide geofile upload section when frame is deactivated (only in sidepane mode)
            if (!this.useDialog_) {
                $('.-fn-geofile-upload', this.$element).hide();
            }
            
            // Cleanup geofile handler
            if (this.geofileHandler) {
                this.geofileHandler.clear();
            }
            
            // Cleanup controllers
            if (this.tableController) {
                this.tableController.destroy();
            }
            
            // Remove map hover handler
            if (this.mapHoverHandler) {
                var map = this.map.getModel().olMap;
                map.un('pointermove', this.mapHoverHandler);
                this.mapHoverHandler = null;
            }
            
            this.featureCounter = 0;
            if (this.tableController) {
                this.tableController.updateTable();
            }
            super._deactivateSelection();
        }
        
        /**
         * Start mouse-following behavior for frame placement
         */
        _startMouseFollow() {
            if (this.mouseFollowActive) {
                return;
            }
            
            this.mouseFollowActive = true;
            
            var self = this;
            var map = this.map.getModel().olMap;
            var $mapElement = $(map.getTargetElement());
            
            // Use OpenLayers click event - fires immediately on click
            this.mouseClickHandler = function(evt) {
                if (!self.mouseFollowActive || self.rotationController.isCurrentlyRotating()) {
                    return;
                }
                
                // Pin the current frame
                self._pinCurrentFrame();
            };
            
            map.on('click', this.mouseClickHandler);
            
            // Mouse move handler - update feature position
            this.mouseMoveHandler = function(evt) {
                if (!self.mouseFollowActive || !self.feature) {
                    return;
                }
                
                var pixel = map.getEventPixel(evt.originalEvent);
                var coordinate = map.getCoordinateFromPixel(pixel);
                
                // Hide mouse-move-frame when hovering over any feature (excluding the selection frame itself)
                var hasOtherFeature = map.forEachFeatureAtPixel(pixel, function(mapFeature) {
                    return mapFeature !== self.feature;
                });
                
                if (hasOtherFeature) {
                    self.feature.setStyle(self.styleConfig.createEmptyStyle());  // Make invisible
                } else {
                    self.feature.setStyle(null);  // Reset to default style
                    self._redrawSelectionFeatures();
                }
                
                if (coordinate) {
                    self._moveFeatureToCoordinate(coordinate);
                }
            };
            
            $mapElement.on('mousemove', this.mouseMoveHandler);
            
            // Hide feature when mouse leaves map and enters any widget
            $mapElement.on('mouseleave', function() {
                if (self.feature) {
                    self.feature.setStyle(self.styleConfig.createEmptyStyle());  // Make invisible
                }
            });
            
            $mapElement.on('mouseenter', function() {
                if (self.feature && self.mouseFollowActive) {
                    self.feature.setStyle(null);  // Reset to default style
                    self._redrawSelectionFeatures();
                }
            });
        }
        
        /**
         * Stop mouse-following behavior
         */
        _stopMouseFollow() {
            if (!this.mouseFollowActive) {
                return;
            }
            
            this.mouseFollowActive = false;
            
            var map = this.map.getModel().olMap;
            var $mapElement = $(map.getTargetElement());
            
            if (this.mouseMoveHandler) {
                $mapElement.off('mousemove', this.mouseMoveHandler);
                this.mouseMoveHandler = null;
            }
            
            if (this.mouseClickHandler) {
                map.un('click', this.mouseClickHandler);
                this.mouseClickHandler = null;
            }
        }
        
        /**
         * Move the current selection feature to a coordinate
         */
        _moveFeatureToCoordinate(coordinate) {
            if (!this.feature || !coordinate) {
                return;
            }
            
            var geom = this.feature.getGeometry();
            if (!geom) {
                return;
            }
            
            var currentCenter = this.map.getModel().getFeatureCenter(this.feature);
            if (!currentCenter) {
                return;
            }
            
            var dx = coordinate[0] - currentCenter[0];
            var dy = coordinate[1] - currentCenter[1];
            
            geom.translate(dx, dy);
        }
        
        /**
         * Rotate the current selection feature by a bearing in degrees
         * @param {number} bearingDegrees - Bearing in degrees from East (0 = East, 90 = North, -90 = South)
         * @param {number|null} previousRotation - Previous frame's rotation in degrees (for continuity)
         */
        _rotateCurrentFeature(bearingDegrees, previousRotation) {
            if (!this.feature) {
                return;
            }
            
            var geom = this.feature.getGeometry();
            if (!geom) {
                return;
            }
            
            var entry = this._getFeatureEntry(this.feature);
            if (!entry) {
                return;
            }
            
            // The bearing represents the direction of the track (angle from East)
            // We want to rotate the frame so this direction is parallel to one of its axes
            
            // Normalize bearing to -180 to 180 range
            var normalizedBearing = ((bearingDegrees + 180) % 360) - 180;
            
            // Calculate two possible target rotations:
            // Portrait: Track aligned with horizontal axis
            var portrait = normalizedBearing;
            // Landscape: Track aligned with vertical axis (90° rotated)
            var landscape = normalizedBearing - 90;
            if (landscape < -180) landscape += 360;
            
            var targetRotation;
            
            if (previousRotation !== null) {
                // Choose the option that is closer to the previous frame's rotation
                // to maintain smooth transitions and avoid sudden jumps
                var diff1 = Math.abs(portrait - previousRotation);
                var diff2 = Math.abs(landscape - previousRotation);
                
                // Handle wraparound at -180/180 boundary
                if (diff1 > 180) diff1 = 360 - diff1;
                if (diff2 > 180) diff2 = 360 - diff2;
                
                targetRotation = (diff1 < diff2) ? portrait : landscape;
            } else {
                // First frame: choose based on whether track is more horizontal or vertical
                var absNormalizedBearing = Math.abs(normalizedBearing);
                if (absNormalizedBearing <= 45 || absNormalizedBearing >= 135) {
                    // Track is more horizontal
                    targetRotation = portrait;
                } else {
                    // Track is more vertical
                    targetRotation = landscape;
                }
            }
            
            // Calculate current total rotation
            var currentRotationRadians = entry.rotationBias + entry.tempRotation;
            
            // Convert target rotation to radians
            var targetRotationRadians = targetRotation * (Math.PI / 180);
            
            // Calculate rotation delta needed
            var rotationDelta = targetRotationRadians - currentRotationRadians;
            
            // Get feature center as rotation anchor
            var center = this.map.getModel().getFeatureCenter(this.feature);
            
            // Apply rotation to geometry
            geom.rotate(rotationDelta, center);
            
            // Update the feature entry's rotation bias
            entry.rotationBias = targetRotationRadians;
            entry.tempRotation = 0;
        }
        
        /**
         * Pin the current frame to the map (create static copy)
         */
        _pinCurrentFrame() {
            if (!this.feature) {
                return;
            }
            
            // Clone the current feature geometry
            var geom = this.feature.getGeometry().clone();
            var pinnedFeature = new ol.Feature(geom);
            
            // Get current rotation from feature entry (in radians) and convert to degrees
            var entry = this._getFeatureEntry(this.feature);
            var totalRotationRadians = entry.rotationBias + entry.tempRotation;
            var totalRotationDegrees = totalRotationRadians * (180 / Math.PI);
            
            // Get extent from feature geometry
            var extent = this.feature.getGeometry().getExtent();
            var extentWidth = extent[2] - extent[0];
            var extentHeight = extent[3] - extent[1];
            
            // Get template value and label
            var $templateSelect = $('select[name="template"]', this.$form);
            var templateValue = $templateSelect.val();
            var templateLabel = $templateSelect.find('option:selected').text();
            
            // Create frame data and add to manager
            var frameData = {
                feature: pinnedFeature,
                rotation: totalRotationDegrees,
                scale: this._getPrintScale(),
                center: this.map.getModel().getFeatureCenter(pinnedFeature),
                template: templateValue,
                templateLabel: templateLabel,
                quality: $('select[name="quality"]', this.$form).val(),
                extent: {
                    width: extentWidth,
                    height: extentHeight
                }
            };
            
            // Add frame through manager (assigns ID and triggers callbacks)
            this.frameManager.addFrame(frameData);
            
            // Update frame tracking table
            this.tableController.updateTable();
            
            // Create new selection feature at same position for next frame
            var center = this.map.getModel().getFeatureCenter(this.feature);
            this._resetSelectionFeature();
            this._moveFeatureToCoordinate(center);
            
            // Refresh all rotation control overlays
            this.rotationController.refreshAll();
        }
        
        /**
         * Get default style for pinned features
         * @returns {ol.style.Style} Default style with black outline and white fill
         */
        _getDefaultStyle() {
            return this.styleConfig.createDefaultFrameStyle();
        }
        
        /**
         * Add pinned feature to map with thin black border
         */
        _addPinnedFeatureToMap(feature) {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            var nativeLayer = layerBridge.getNativeLayer();
            
            // Ensure pinned frames layer has higher z-index than rotation overlay
            nativeLayer.setZIndex(this.PINNED_FRAMES_ZINDEX);
            
            // Set custom style with thin black border
            var style = this._getDefaultStyle();
            feature.setStyle(style);
            layerBridge.addNativeFeatures([feature]);
            
            // Add rotation handle overlay for this feature
            var frameData = this.frameManager.getFrameByFeature(feature);
            if (frameData) {
                this.rotationController.addHandle(frameData.id, feature);
            }
        }
        
        /**
         * Delete a pinned frame by ID
         */
        _deleteFrame(frameId) {
            var frameData = this.frameManager.removeFrame(frameId);
            
            if (frameData) {
                // Frame removal callbacks already handled by frameManager
                // Just update the UI
                this.tableController.updateTable();
                
                if (this.selectionActive && !this.mouseFollowActive) {
                    this._startMouseFollow();
                }
            }
        }
        
        /**
         * Clear all pinned features
         */
        _clearPinnedFeatures() {
            // Clear rotation controller
            if (this.rotationController) {
                this.rotationController.clearAll();
            }
            
            // Clear pinned features from map layer
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            layerBridge.clear();
            
            // Clear frame manager data
            this.frameManager.clear();
        }
        
        /**
         * Override _resetSelectionFeature to prevent drag interaction
         */
        _resetSelectionFeature() {
            var previous = this.feature;
            var model = this.map.getModel();
            var center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            this.feature = this._createFeature(this._getPrintScale(), center, this.inputRotation_);
            this._clearFeature(previous);
            this._redrawSelectionFeatures();
        }

        
        /**
         * Override _onSubmit to collect and submit all pinned frames
         */
        async _onSubmit(evt) {
            evt.preventDefault();
            
            if (!this._validateSubmission()) {
                return false;
            }
            
            var selectionCenter = this.feature ? this.map.getModel().getFeatureCenter(this.feature) : null;
            
            try {
                var jobs = await this._collectAllJobData();
                this._submitBatchJobs(jobs);
                this._restoreUIState(selectionCenter);
            } catch (error) {
                this._handleSubmissionError(error, selectionCenter);
            }
            
            return false;
        }
        
        /**
         * Validate submission requirements
         * @returns {boolean} True if validation passes, false otherwise
         */
        _validateSubmission() {
            if (!this.selectionActive) {
                return false;
            }
            
            if (this.pinnedFeatures.length === 0) {
                Mapbender.info(Mapbender.trans('mb.print.printclient.batchprint.alert.noframes'));
                return false;
            }
            
            return true;
        }
        
        /**
         * Collect job data for all pinned frames
         * @returns {Promise<Array>} Array of job data objects
         */
        async _collectAllJobData() {
            if (!this.$form || !this.$form.length) {
                throw new Error('Form element not found');
            }
            
            var jobs = [];
            var $scaleSelect = $('select[name="scale_select"]', this.$form);
            var $templateSelect = $('select[name="template"]', this.$form);
            var $qualitySelect = $('select[name="quality"]', this.$form);
            
            if (!$scaleSelect.length || !$templateSelect.length || !$qualitySelect.length) {
                throw new Error('Required form elements not found');
            }
            
            for (var i = 0; i < this.pinnedFeatures.length; i++) {
                var frameData = this.pinnedFeatures[i];
                
                // Store original form values
                var originalScale = $scaleSelect.val();
                var originalTemplate = $templateSelect.val();
                var originalQuality = $qualitySelect.val();
                
                try {
                    // Temporarily apply frame-specific settings
                    $scaleSelect.val(frameData.scale);
                    if (frameData.template) $templateSelect.val(frameData.template);
                    if (frameData.quality) $qualitySelect.val(frameData.quality);
                    
                    // Collect job data using parent's method
                    this.feature = frameData.feature;
                    var job = await Mapbender.Element.MbPrint.prototype._collectJobData.call(this);
                    
                    // Apply frame-specific adjustments
                    job.rotation = -frameData.rotation;  // CRITICAL: Negate for backend
                    if (frameData.template) job.template = frameData.template;
                    if (frameData.quality) job.quality = frameData.quality;
                    job.scale_select = frameData.scale;
                    
                    if (frameData.extent) {
                        job.extent = {
                            width: frameData.extent.width,
                            height: frameData.extent.height
                        };
                    }
                    
                    jobs.push(job);
                } catch (error) {
                    console.error('Failed to collect job data for frame ' + (i + 1) + ':', error);
                    throw new Error('Failed to collect data for frame ' + (i + 1) + ': ' + error.message);
                } finally {
                    // Always restore original form values
                    $scaleSelect.val(originalScale);
                    $templateSelect.val(originalTemplate);
                    $qualitySelect.val(originalQuality);
                }
            }
            
            this.feature = null;
            return jobs;
        }
        
        /**
         * Submit batch jobs via form submission
         * @param {Array} jobs - Array of job data objects
         */
        _submitBatchJobs(jobs) {
            if (!this.$form || !this.$form.length) {
                console.error('Cannot submit: form element not found');
                throw new Error('Form element not found');
            }
            
            var $hiddenFields = $('.-fn-hidden-fields', this.$form);
            if (!$hiddenFields.length) {
                console.error('Cannot submit: hidden fields container not found');
                throw new Error('Hidden fields container not found');
            }
            
            $hiddenFields.empty();
            
            var $dataField = $('<input type="hidden" name="data">');
            $dataField.val(JSON.stringify(jobs));
            $hiddenFields.append($dataField);
            
            this.$form[0].submit();
        }
        
        /**
         * Restore UI state after successful submission
         * @param {Array} selectionCenter - Center coordinates to restore selection feature
         */
        _restoreUIState(selectionCenter) {
            var self = this;
            
            // Restore selection feature position
            if (selectionCenter) {
                this._resetSelectionFeature();
                this._moveFeatureToCoordinate(selectionCenter);
            }
            
            // Switch to results tab after short delay
            var $tabs = $('.tab-container', this.$element);
            if ($tabs.length) {
                window.setTimeout(function() {
                    $tabs.tabs({active: 1});
                }, this.tabSwitchDelay);
            }
        }
        
        /**
         * Place a frame at a specific position (called by geofileHandler)
         * @param {Array} coord - Coordinates [x, y]
         * @param {number|null} bearing - Bearing in degrees (null if no rotation)
         * @param {number|null} previousRotation - Previous frame rotation for continuity
         * @returns {number|null} Current rotation in degrees
         * @private
         */
        _placeFrameAtPosition(coord, bearing, previousRotation) {
            // Move current feature to this position
            this._moveFeatureToCoordinate(coord);
            
            // Rotate if bearing provided
            if (bearing !== null) {
                this._rotateCurrentFeature(bearing, previousRotation);
            }
            
            // Pin the frame
            this._pinCurrentFrame();
            
            // Get and return current rotation for next frame
            if (bearing !== null) {
                var entry = this._getFeatureEntry(this.feature);
                return entry ? (entry.rotationBias + entry.tempRotation) * (180 / Math.PI) : previousRotation;
            }
            
            return previousRotation;
        }
        
        /**
         * Handle submission errors
         * @param {Error} error - The error that occurred
         * @param {Array} selectionCenter - Center coordinates to restore selection feature
         */
        _handleSubmissionError(error, selectionCenter) {
            console.error('Batch print submission failed:', error);
            
            // Restore selection feature on error
            if (selectionCenter) {
                this._resetSelectionFeature();
                this._moveFeatureToCoordinate(selectionCenter);
            }
            
            var errorMessage = Mapbender.trans('mb.print.printclient.batchprint.alert.error') + ': ' + error.message;
            Mapbender.error(errorMessage);
        }
        
        /**
         * Delete all frames at once
         */
        _deleteAllFrames() {
            if (this.frameManager.getCount() === 0) {
                return;
            }
            
            // Clear all pinned features from the map
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            layerBridge.clear();
            
            // Clear rotation controller
            if (this.rotationController) {
                this.rotationController.clearAll();
            }
            
            // Clear frame manager
            this.frameManager.clear();
            
            // Update table and UI
            this.tableController.updateTable();
            
            // Show success message only after frames were actually deleted
            $('.-fn-geofile-status', this.$element)
                .text(Mapbender.trans('mb.print.printclient.batchprint.alldeleted'))
                .removeClass('text-danger')
                .addClass('text-success');
        }
    }

    window.Mapbender.Element = window.Mapbender.Element || {};
    window.Mapbender.Element.MbBatchPrintClient = MbBatchPrintClient;
})();
