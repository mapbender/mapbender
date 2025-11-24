(function($) {
    /**
     * Batch Print Client Widget
     * 
     * Extends mbPrintClient with multiframe/serial printing support:
     * - Multiple print frame selection with mouse-following
     * - Click to pin frames to map
     * - Frame table management
     * - Drag-to-rotate functionality with visual handles
     * - Batch print submission (both queued and direct)
     */
    $.widget("mapbender.mbBatchPrintClient", $.mapbender.mbPrintClient, {
        // Multiframe printing state
        featureCounter: 0,
        pinnedFeatures: [],     // Active array of frame data used for UI operations and print submission
        
        // Mouse-following state
        mouseFollowActive: false,
        mouseMoveHandler: null,
        mouseClickHandler: null,
        mapHoverHandler: null,
        isRotating: false,
        isDraggingFrame: false,
        draggedFrameId: null,
        dragStartCoordinate: null,
        
        // Rotation overlay layer and interaction
        rotationOverlayLayer: null,
        rotationDragInteraction: null,
        rotationHandles: [],  // Track {frameId, handleFeature, boxFeatures}
        
        // Layer constants
        PINNED_FRAMES_LAYER: 1,  // Layer index for pinned frame features
        PINNED_FRAMES_ZINDEX: 1000,  // Top layer - actual print frames
        ROTATION_ZINDEX: 999,         // Middle layer - interactive rotation controls
        TRACK_LAYER_ZINDEX: 998,      // Bottom layer - reference track/guide layer
        
        // Configurable colors (can be overridden in client implementations)
        highlightColor: '#0066cc',        // Blue color matching Mapbender's highlight color
        rotationControlOpacity: 0.6,      // Opacity for rotation handle fill
        highlightFillOpacity: 0.3,        // Opacity for frame highlight fill
        defaultFrameStrokeColor: '#000000',
        defaultFrameFillColor: 'rgba(255, 255, 255, 0.5)',
        trackColor: '#FF0000',
        trackFillOpacity: 0.1,
        
        // Style dimensions
        highlightStrokeWidthThin: 0.5,
        highlightStrokeWidthNormal: 2,
        frameStrokeWidth: 2,
        rotationBoxStrokeWidth: 2,
        rotationHandleStrokeWidth: 1,
        rotationHandleRadius: 8,
        trackStrokeWidth: 3,
        trackPointRadius: 6,
        trackPointStrokeWidth: 2,
        
        // Line dash patterns
        rotationBoxLineDash: [5, 5],
        
        // Interaction tolerances
        hitToleranceFrame: 5,
        hitToleranceRotation: 10,
        
        // Animation and timing
        dragEndDelay: 50,
        frameDragEndDelay: 100,
        tabSwitchDelay: 50,
        
        // Map view settings
        trackFitPadding: [100, 100, 100, 100],
        trackFitDuration: 500,
        trackFitMaxZoom: 16,

        /**
         * Get highlight fill color with configured opacity
         * @returns {string} RGBA color string
         */
        _getHighlightFillColor: function() {
            var rgb = Mapbender.StyleUtil.parseCssColor(this.highlightColor);
            return 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + this.highlightFillOpacity + ')';
        },

        _setup: function(){
            this._super();
            
            // Add unique class identifier
            this.element.addClass('mb-element-batchprintclient');

            // Remove rotation control (form row containing the rotation input)
            $('input[name="rotation"]', this.element).parent().remove();
            
            // Initialize multiframe functionality
            this.featureCounter = 0;
            this.pinnedFeatures = [];
            this.geofileLayer = null;
            this.geofileFeatures = [];
            
            var self = this;
            
            // Change submit button text
            $('input[type="submit"]', this.element).val(Mapbender.trans('mb.print.printclient.batchprint.btn.submit'));
            
            // Setup geospatial file upload handlers
            this._setupGeofileUploadHandlers();
            
            // Setup delete all frames button
            $('.-fn-delete-all-frames', this.element).on('click', function() {
                self._deleteAllFrames();
            });
            
            // Stop mouse-follow when mouse enters widget
            this.element.on('mouseenter', function() {
                if (self.mouseFollowActive) {
                    self._stopMouseFollow();
                }
                // Hide selection frame when mouse enters widget
                if (self.feature) {
                    self.feature.setStyle(new ol.style.Style({}));  // Make invisible
                }
            });
            
            // Restart mouse-follow when mouse leaves widget
            this.element.on('mouseleave', function() {
                if (self.selectionActive && !self.mouseFollowActive) {
                    self._startMouseFollow();
                }
                // Show selection frame when mouse leaves widget
                if (self.feature) {
                    self.feature.setStyle(null);  // Reset to default style
                    self._redrawSelectionFeatures();
                }
            });
        },
        
        /**
         * Override parent's _pickScale to select a scale two steps bigger (more zoomed out)
         * than the current map scale for batch printing
         */
        _pickScale: function(closestToMapScale) {
            // Get the scale that the parent would choose (current map scale or smallest that fits)
            const parentScale = this._super(closestToMapScale);
            
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
            var targetIndex = Math.max(currentIndex - 2, 0);
            return scales[targetIndex];
        },
        
        /**
         * Override parent's _activateSelection to start mouse-following
         */
        _activateSelection: function() {
            // Call grandparent to avoid parent's drag interaction
            this._clearFeature(this.feature);
            Mapbender.vectorLayerPool.getElementLayer(this, 0).clear();
            Mapbender.vectorLayerPool.raiseElementLayers(this);
            Mapbender.vectorLayerPool.showElementLayers(this);
            
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
        },
        
        /**
         * Override parent's _deactivateSelection to cleanup
         */
        _deactivateSelection: function() {
            this._stopMouseFollow();
            this._clearPinnedFeatures();
            this._clearGeofileLayer();
            
            // Remove map hover handler
            if (this.mapHoverHandler) {
                var map = this.map.getModel().olMap;
                map.un('pointermove', this.mapHoverHandler);
                this.mapHoverHandler = null;
            }
            
            this.featureCounter = 0;
            this._updateFrameTable();
            this._super();
        },
        
        /**
         * Start mouse-following behavior for frame placement
         */
        _startMouseFollow: function() {
            if (this.mouseFollowActive) {
                return;
            }
            
            this.mouseFollowActive = true;
            
            var self = this;
            var map = this.map.getModel().olMap;
            var $mapElement = $(map.getTargetElement());
            
            // Initialize rotation overlay layer if not already created
            if (!this.rotationOverlayLayer) {
                this._initializeRotationOverlay();
            }
            
            // Use OpenLayers click event - fires immediately on click
            this.mouseClickHandler = function(evt) {
                if (!self.mouseFollowActive || self.isRotating || self.isDraggingFrame) {
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
                    self.feature.setStyle(new ol.style.Style({}));  // Make invisible
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
                    self.feature.setStyle(new ol.style.Style({}));  // Make invisible
                }
            });
            
            $mapElement.on('mouseenter', function() {
                if (self.feature && self.mouseFollowActive) {
                    self.feature.setStyle(null);  // Reset to default style
                    self._redrawSelectionFeatures();
                }
            });
        },
        
        /**
         * Stop mouse-following behavior
         */
        _stopMouseFollow: function() {
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
        },
        
        /**
         * Move the current selection feature to a coordinate
         */
        _moveFeatureToCoordinate: function(coordinate) {
            if (!this.feature) {
                return;
            }
            
            var geom = this.feature.getGeometry();
            var currentCenter = this.map.getModel().getFeatureCenter(this.feature);
            var dx = coordinate[0] - currentCenter[0];
            var dy = coordinate[1] - currentCenter[1];
            
            geom.translate(dx, dy);
        },
        
        /**
         * Rotate the current selection feature by a bearing in degrees
         * @param {number} bearingDegrees - Bearing in degrees from East (0 = East, 90 = North, -90 = South)
         * @param {number|null} previousRotation - Previous frame's rotation in degrees (for continuity)
         */
        _rotateCurrentFeature: function(bearingDegrees, previousRotation) {
            if (!this.feature) {
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
            
            // Get current feature entry and calculate current total rotation
            var entry = this._getFeatureEntry(this.feature);
            var currentRotationRadians = entry.rotationBias + entry.tempRotation;
            
            // Convert target rotation to radians
            var targetRotationRadians = targetRotation * (Math.PI / 180);
            
            // Calculate rotation delta needed
            var rotationDelta = targetRotationRadians - currentRotationRadians;
            
            // Get feature center as rotation anchor
            var center = this.map.getModel().getFeatureCenter(this.feature);
            
            // Apply rotation to geometry
            var geom = this.feature.getGeometry();
            geom.rotate(rotationDelta, center);
            
            // Update the feature entry's rotation bias
            entry.rotationBias = targetRotationRadians;
            entry.tempRotation = 0;
        },
        
        /**
         * Pin the current frame to the map (create static copy)
         */
        _pinCurrentFrame: function() {
            if (!this.feature) {
                return;
            }
            
            this.featureCounter++;
            
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
            
            // Store pinned feature data including current UI settings and extent
            var frameData = {
                id: this.featureCounter,
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
            
            this.pinnedFeatures.push(frameData);
            
            // Add pinned feature to a separate layer with black styling
            this._addPinnedFeatureToMap(pinnedFeature);
            
            // Update frame tracking table
            this._updateFrameTable();
            
            // Create new selection feature at same position for next frame
            var center = this.map.getModel().getFeatureCenter(this.feature);
            this._resetSelectionFeature();
            this._moveFeatureToCoordinate(center);
            
            // Refresh all rotation control overlays
            this._refreshRotationControls();
        },
        
        /**
         * Get default style for pinned features
         * @returns {ol.style.Style} Default style with black outline and white fill
         */
        _getDefaultStyle: function() {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.defaultFrameStrokeColor,
                    width: this.frameStrokeWidth
                }),
                fill: new ol.style.Fill({
                    color: this.defaultFrameFillColor
                })
            });
        },
        
        /**
         * Add pinned feature to map with black outline and rotation interaction
         */
        _addPinnedFeatureToMap: function(feature) {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            var nativeLayer = layerBridge.getNativeLayer();
            
            // Ensure pinned frames layer has higher z-index than rotation overlay
            nativeLayer.setZIndex(this.PINNED_FRAMES_ZINDEX);
            
            // Create black outline style for pinned features (normal state)
            var style = this._getDefaultStyle();
            
            feature.setStyle(style);
            layerBridge.addNativeFeatures([feature]);
            
            // Add rotation handle overlay for this feature
            this._addRotationHandle(feature);
        },
        
        /**
         * Highlight a pinned feature on the map
         */
        _highlightFeature: function(frameId) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.id === frameId;
            });
            
            if (frameData) {
                // Get the actual polygon coordinates from the rotated geometry
                var geometry = frameData.feature.getGeometry();
                var coordinates = geometry.getCoordinates()[0]; // Get exterior ring coordinates
                
                // The polygon coordinates are ordered: [bottom-left, top-left, top-right, bottom-right, bottom-left]
                // Top edge is from index 1 (top-left) to index 2 (top-right)
                var topLineCoords = [
                    coordinates[1],  // top-left
                    coordinates[2]   // top-right
                ];
                
                // Base highlight style with very thin border and fill
                var baseStyle = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: this.highlightColor,
                        width: this.highlightStrokeWidthThin
                    }),
                    fill: new ol.style.Fill({
                        color: this._getHighlightFillColor()
                    })
                });
                
                // Additional style for normal top border
                var topBorderStyle = new ol.style.Style({
                    geometry: new ol.geom.LineString(topLineCoords),
                    stroke: new ol.style.Stroke({
                        color: this.highlightColor,
                        width: this.highlightStrokeWidthNormal
                    })
                });
                
                // Apply both styles - the array creates a multi-style rendering
                frameData.feature.setStyle([baseStyle, topBorderStyle]);
            }
        },
        
        /**
         * Remove highlight from a pinned feature
         */
        _unhighlightFeature: function(frameId) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.id === frameId;
            });
            
            if (frameData) {
                frameData.feature.setStyle(this._getDefaultStyle());
            }
        },
        
        /**
         * Update the frame tracking table UI
         */
        _updateFrameTable: function() {
            var $tbody = $('.-fn-frame-table tbody', this.element);
            
            if (!$tbody.length) {
                console.error('Frame table tbody not found');
                return;
            }
            
            $tbody.empty();
            
            // Show/hide delete all button and toggle empty state based on whether there are frames
            var $deleteAllBtn = $('.-fn-delete-all-frames', this.element);
            var $emptyState = $('.-fn-frame-table-empty', this.element);
            var $tableContent = $('.-fn-frame-table-content', this.element);
            
            if (this.pinnedFeatures.length > 0) {
                $deleteAllBtn.addClass('show');
                $emptyState.hide();
                $tableContent.show();
            } else {
                $deleteAllBtn.removeClass('show');
                $emptyState.show();
                $tableContent.hide();
            }
            
            var self = this;
            this.pinnedFeatures.forEach(function(frameData, index) {
                var $row = $('<tr></tr>');
                $row.attr('data-frame-id', frameData.id);
                
                // Frame number (display order position, not ID)
                $row.append($('<td></td>').text(index + 1));
                
                // Scale
                $row.append($('<td></td>').text('1:' + frameData.scale));
                
                // Template - use stored label or fall back to template name formatting
                var templateDisplay = frameData.templateLabel || frameData.template || '';
                $row.append($('<td></td>').text(templateDisplay));
                
                // Quality (DPI)
                $row.append($('<td></td>').text(frameData.quality + ' dpi'));
                
                // Rotation (rounded to 1 decimal place)
                var rotationText = Math.round(frameData.rotation * 10) / 10 + '°';
                $row.append($('<td></td>').text(rotationText));
                
                // Delete button
                var $deleteCell = $('<td></td>');
                var $deleteIcon = $('<i class="fa fa-trash"></i>');
                $deleteIcon.attr('title', 'Delete frame');
                $deleteIcon.on('click', function(e) {
                    e.stopPropagation();
                    self._deleteFrame(frameData.id);
                });
                $deleteCell.append($deleteIcon);
                $row.append($deleteCell);
                
                // Add hover handlers for highlighting
                $row.on('mouseenter', function() {
                    self._highlightFeature(frameData.id);
                    self._showRotationControls(frameData.id);  // Show rotation controls
                    $(this).addClass('highlight');
                });
                
                $row.on('mouseleave', function() {
                    self._unhighlightFeature(frameData.id);
                    self._hideRotationControls(frameData.id);  // Hide rotation controls
                    $(this).removeClass('highlight');
                });
                
                $tbody.append($row);
            });
            
            // Initialize sortable functionality for table rows
            this._initFrameTableSortable();
            
            // Add map hover handlers for highlighting table rows
            this._setupMapHoverHandlers();
        },
        
        /**
         * Initialize sortable functionality for frame table rows
         */
        _initFrameTableSortable: function() {
            var self = this;
            var $tbody = $('.-fn-frame-table tbody', this.element);
            
            // Destroy existing sortable if it exists
            if ($tbody.hasClass('ui-sortable')) {
                $tbody.sortable('destroy');
            }
            
            // Initialize jQuery UI sortable
            $tbody.sortable({
                axis: 'y',
                cursor: 'move',
                handle: 'td',
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                start: function(e, ui) {
                    // Add visual feedback during drag
                    ui.item.addClass('dragging');
                },
                stop: function(e, ui) {
                    // Remove visual feedback
                    ui.item.removeClass('dragging');
                },
                update: function(e, ui) {
                    self._handleFrameTableReorder();
                }
            });
        },
        
        /**
         * Handle reordering of frame table rows by updating pinnedFeatures array
         */
        _handleFrameTableReorder: function() {
            var self = this;
            var $tbody = $('.-fn-frame-table tbody', this.element);
            
            // Get new order of frame IDs from table rows
            var newOrder = [];
            $tbody.find('tr').each(function() {
                var frameId = parseInt($(this).attr('data-frame-id'));
                newOrder.push(frameId);
            });
            
            // Reorder pinnedFeatures array to match new table order
            var reorderedFeatures = [];
            newOrder.forEach(function(frameId) {
                var frameData = self.pinnedFeatures.find(function(f) {
                    return f.id === frameId;
                });
                if (frameData) {
                    reorderedFeatures.push(frameData);
                }
            });
            
            this.pinnedFeatures = reorderedFeatures;
            
            // Update frame numbers in table to reflect new order
            $tbody.find('tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        },
        
        /**
         * Setup hover handlers on map features to highlight table rows and show/hide rotation controls
         * A frame is considered "entered" when mouse is over the feature itself OR its rotation controls
         */
        _setupMapHoverHandlers: function() {
            var self = this;
            var map = this.map.getModel().olMap;
            
            // Remove existing handler if present
            if (this.mapHoverHandler) {
                map.un('pointermove', this.mapHoverHandler);
            }
            
            this.mapHoverHandler = function(evt) {
                var enteredFrameIds = [];
                
                // Detect pinned features at cursor position
                var layerBridge = Mapbender.vectorLayerPool.getElementLayer(self, self.PINNED_FRAMES_LAYER);
                map.forEachFeatureAtPixel(evt.pixel, function(feature) {
                    var frameData = self.pinnedFeatures.find(function(f) {
                        return f.feature === feature;
                    });
                    
                    if (frameData && enteredFrameIds.indexOf(frameData.id) === -1) {
                        enteredFrameIds.push(frameData.id);
                    }
                }, {
                    layerFilter: function(layer) {
                        return layer === layerBridge.getNativeLayer();
                    },
                    hitTolerance: self.hitToleranceFrame
                });
                
                // Detect rotation controls (dotted box and circle) at cursor position
                map.forEachFeatureAtPixel(evt.pixel, function(feature) {
                    var featureType = feature.get('type');
                    if (featureType === 'rotation-box' || featureType === 'rotation-handle') {
                        var frameId = feature.get('frameId');
                        if (enteredFrameIds.indexOf(frameId) === -1) {
                            enteredFrameIds.push(frameId);
                        }
                    }
                }, {
                    layerFilter: function(layer) {
                        return layer === self.rotationOverlayLayer;
                    },
                    hitTolerance: self.hitToleranceRotation
                });
                
                // Update visibility and highlighting for all frames
                $('.-fn-frame-table tbody tr', self.element).removeClass('highlight');
                
                self.pinnedFeatures.forEach(function(frameData) {
                    var isEntered = enteredFrameIds.indexOf(frameData.id) !== -1;
                    
                    if (isEntered) {
                        // Show controls and highlight when mouse is over feature or its controls
                        self._showRotationControls(frameData.id);
                        self._highlightFeature(frameData.id);
                        var $row = $('.-fn-frame-table tbody tr[data-frame-id="' + frameData.id + '"]', self.element);
                        $row.addClass('highlight');
                    } else {
                        // Hide controls and remove highlight when mouse leaves
                        self._hideRotationControls(frameData.id);
                        self._unhighlightFeature(frameData.id);
                    }
                });
            };
            
            map.on('pointermove', this.mapHoverHandler);
        },
        
        /**
         * Delete a pinned frame by ID
         */
        _deleteFrame: function(frameId) {
            var frameData = null;
            this.pinnedFeatures = this.pinnedFeatures.filter(function(f) {
                if (f.id === frameId) {
                    frameData = f;
                    return false;
                }
                return true;
            });
            
            if (frameData) {
                // Remove rotation handle overlay
                this._removeRotationHandle(frameId);
                
                var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
                layerBridge.removeNativeFeatures([frameData.feature]);
            }
            
            this._updateFrameTable();
            
            if (this.selectionActive && !this.mouseFollowActive) {
                this._startMouseFollow();
            }
        },
        
        /**
         * Initialize the rotation overlay layer and drag interaction
         */
        _initializeRotationOverlay: function() {
            this._createRotationOverlayLayer();
            this._createRotationDragInteraction();
            this._setupCursorHandlers();
        },
        
        /**
         * Create and configure the rotation overlay layer
         */
        _createRotationOverlayLayer: function() {
            var self = this;
            var map = this.map.getModel().olMap;
            var source = new ol.source.Vector();
            this.rotationOverlayLayer = new ol.layer.Vector({
                source: source,
                style: null,  // Features will have their own styles
                zIndex: this.ROTATION_ZINDEX  // Below pinned frames but above map content
            });
            map.addLayer(this.rotationOverlayLayer);
        },
        
        /**
         * Create drag interaction for rotation handles and frame dragging
         */
        _createRotationDragInteraction: function() {
            var self = this;
            var map = this.map.getModel().olMap;
            
            this.rotationDragInteraction = new ol.interaction.Pointer({
                handleDownEvent: function(evt) {
                    return self._handleRotationDragDown(evt);
                },
                handleDragEvent: function(evt) {
                    self._handleRotationDrag(evt);
                },
                handleUpEvent: function(evt) {
                    return self._handleRotationDragUp(evt);
                }
            });
            
            map.addInteraction(this.rotationDragInteraction);
        },
        
        /**
         * Handle mouse down event for rotation and frame dragging
         */
        _handleRotationDragDown: function(evt) {
            var map = this.map.getModel().olMap;
            
            // Check if clicking on rotation handle
            var overlayFeature = this._getRotationHandleAtPixel(evt.pixel);
            if (overlayFeature) {
                return this._startRotation(overlayFeature, evt.pixel);
            }
            
            // Check if clicking on a pinned frame
            var frameData = this._getFrameDataAtPixel(evt.pixel);
            if (frameData) {
                return this._startFrameDrag(frameData, evt.coordinate);
            }
            
            return false;
        },
        
        /**
         * Get rotation handle feature at given pixel
         */
        _getRotationHandleAtPixel: function(pixel) {
            var self = this;
            var map = this.map.getModel().olMap;
            
            var overlayFeature = map.forEachFeatureAtPixel(pixel, function(f) {
                return f;
            }, {
                layerFilter: function(layer) {
                    return layer === self.rotationOverlayLayer;
                }
            });
            
            return (overlayFeature && overlayFeature.get('type') === 'rotation-handle') ? overlayFeature : null;
        },
        
        /**
         * Get frame data for pinned frame at given pixel
         */
        _getFrameDataAtPixel: function(pixel) {
            var self = this;
            var map = this.map.getModel().olMap;
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            
            var pinnedFeature = map.forEachFeatureAtPixel(pixel, function(f) {
                return f;
            }, {
                layerFilter: function(layer) {
                    return layer === layerBridge.getNativeLayer();
                }
            });
            
            if (!pinnedFeature) return null;
            
            return this.pinnedFeatures.find(function(f) {
                return f.feature === pinnedFeature;
            });
        },
        
        /**
         * Set cursor style for the map element
         * @param {ol.Map} map - OpenLayers map instance
         * @param {string} cursor - CSS cursor value (e.g., 'grabbing', 'move', 'grab', '')
         */
        _setCursorStyle: function(map, cursor) {
            var target = map.getTarget();
            var element = typeof target === 'string' ? document.getElementById(target) : target;
            element.style.cursor = cursor;
        },
        
        /**
         * Start rotation operation
         */
        _startRotation: function(handleFeature, pixel) {
            this.isRotating = true;
            this.rotatingFrameId = handleFeature.get('frameId');
            this.rotationStartPixel = pixel;
            
            var map = this.map.getModel().olMap;
            this._setCursorStyle(map, 'grabbing');
            
            return true;
        },
        
        /**
         * Start frame dragging operation
         */
        _startFrameDrag: function(frameData, coordinate) {
            this.isDraggingFrame = true;
            this.draggedFrameId = frameData.id;
            this.dragStartCoordinate = coordinate;
            
            // Hide the selection frame during drag
            if (this.feature) {
                this.feature.setStyle(new ol.style.Style({}));  // Make invisible
            }
            
            var map = this.map.getModel().olMap;
            this._setCursorStyle(map, 'move');
            
            return true;
        },
        
        /**
         * Handle drag event for rotation and frame movement
         */
        _handleRotationDrag: function(evt) {
            if (this.isRotating) {
                this._processRotationDrag(evt);
            } else if (this.isDraggingFrame) {
                this._processFrameDrag(evt);
            }
        },
        
        /**
         * Process rotation drag movement
         */
        _processRotationDrag: function(evt) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.id === this.rotatingFrameId;
            }.bind(this));
            
            if (!frameData) return;
            
            var map = this.map.getModel().olMap;
            var geometry = frameData.feature.getGeometry();
            var extent = geometry.getExtent();
            var center = [(extent[0] + extent[2]) / 2, (extent[1] + extent[3]) / 2];
            
            var centerPixel = map.getPixelFromCoordinate(center);
            var dx1 = this.rotationStartPixel[0] - centerPixel[0];
            var dy1 = this.rotationStartPixel[1] - centerPixel[1];
            var dx2 = evt.pixel[0] - centerPixel[0];
            var dy2 = evt.pixel[1] - centerPixel[1];
            
            var angle1 = Math.atan2(dy1, dx1);
            var angle2 = Math.atan2(dy2, dx2);
            // Negate angleDelta to make rotation follow mouse direction
            var angleDelta = -(angle2 - angle1);
            
            geometry.rotate(angleDelta, center);
            
            var degrees = angleDelta * (180 / Math.PI);
            frameData.rotation = (frameData.rotation + degrees) % 360;
            if (frameData.rotation < 0) frameData.rotation += 360;
            
            this._updateRotationHandle(this.rotatingFrameId);
            this.rotationStartPixel = evt.pixel;
        },
        
        /**
         * Process frame drag movement
         */
        _processFrameDrag: function(evt) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.id === this.draggedFrameId;
            }.bind(this));
            
            if (!frameData) return;
            
            var dx = evt.coordinate[0] - this.dragStartCoordinate[0];
            var dy = evt.coordinate[1] - this.dragStartCoordinate[1];
            
            // Translate the feature geometry
            frameData.feature.getGeometry().translate(dx, dy);
            
            // Update center in frameData
            frameData.center = [frameData.center[0] + dx, frameData.center[1] + dy];
            
            // Update rotation handle position
            this._updateRotationHandle(this.draggedFrameId);
            
            this.dragStartCoordinate = evt.coordinate;
        },
        
        /**
         * Handle drag end event
         */
        _handleRotationDragUp: function(evt) {
            if (this.isRotating) {
                return this._finishRotation();
            }
            
            if (this.isDraggingFrame) {
                return this._finishFrameDrag();
            }
            
            return false;
        },
        
        /**
         * Finish rotation operation
         */
        _finishRotation: function() {
            var self = this;
            
            // Update table to reflect new rotation value
            this._updateFrameTable();
            
            // Delay flag reset to prevent immediate re-triggering of click handlers
            // that might fire as the drag ends
            setTimeout(function() {
                self.isRotating = false;
                self.rotatingFrameId = null;
            }, self.dragEndDelay);
            
            return true;
        },
        
        /**
         * Finish frame dragging operation
         */
        _finishFrameDrag: function() {
            var self = this;
            
            // Restore selection frame visibility
            if (this.feature) {
                this.feature.setStyle(null);
                this._redrawSelectionFeatures();
            }
            
            // Delay flag reset to prevent immediate re-triggering of click handlers
            // that might fire as the drag ends. Slightly longer delay than rotation
            // to ensure all drag-related events have fully completed
            setTimeout(function() {
                self.isDraggingFrame = false;
                self.draggedFrameId = null;
                self.dragStartCoordinate = null;
            }, self.frameDragEndDelay);
            
            return true;
        },
        
        /**
         * Setup cursor handlers for hover interactions
         */
        _setupCursorHandlers: function() {
            var self = this;
            var map = this.map.getModel().olMap;
            
            map.on('pointermove', function(evt) {
                if (evt.dragging) return;
                
                var pixel = map.getEventPixel(evt.originalEvent);
                
                // Handle active state cursors
                if (self.isRotating) {
                    self._setCursorStyle(map, 'grabbing');
                    return;
                }
                
                if (self.isDraggingFrame) {
                    self._setCursorStyle(map, 'move');
                    return;
                }
                
                // Check for rotation handle hover
                var overlayFeature = self._getRotationHandleAtPixel(pixel);
                if (overlayFeature) {
                    self._setCursorStyle(map, 'grab');
                    return;
                }
                
                // Check for pinned frame hover
                var frameData = self._getFrameDataAtPixel(pixel);
                if (frameData) {
                    self._setCursorStyle(map, 'move');
                    return;
                }
                
                self._setCursorStyle(map, '');
            });
        },
        
        /**
         * Add rotation handle overlay for a pinned frame
         * Creates invisible but interactive rotation controls (dotted box and circle)
         */
        _addRotationHandle: function(feature) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.feature === feature;
            });
            
            if (!frameData) return;
            
            var geometry = feature.getGeometry();
            var extent = geometry.getExtent();
            
            // Create dotted bounding box around feature
            var boxCoords = [
                [extent[0], extent[1]],  // bottom-left
                [extent[2], extent[1]],  // bottom-right
                [extent[2], extent[3]],  // top-right
                [extent[0], extent[3]],  // top-left
                [extent[0], extent[1]]   // close polygon
            ];
            var boxFeature = new ol.Feature(new ol.geom.LineString(boxCoords));
            boxFeature.set('type', 'rotation-box');
            boxFeature.set('frameId', frameData.id);
            
            // Create rotation handle circle at bottom-right corner
            var handleFeature = new ol.Feature(new ol.geom.Point([extent[2], extent[1]]));
            handleFeature.set('type', 'rotation-handle');
            handleFeature.set('frameId', frameData.id);
            
            // Add features to overlay layer (initially invisible via empty style)
            var source = this.rotationOverlayLayer.getSource();
            source.addFeature(boxFeature);
            source.addFeature(handleFeature);
            
            // Track handles for later updates
            this.rotationHandles.push({
                frameId: frameData.id,
                boxFeature: boxFeature,
                handleFeature: handleFeature
            });
        },
        
        /**
         * Show rotation controls (dotted frame and circle) for a specific frame
         */
        _showRotationControls: function(frameId) {
            var handle = this.rotationHandles.find(function(h) {
                return h.frameId === frameId;
            });
            
            if (!handle) return;
            
            // Parse color and create semi-transparent fill
            var rgb = Mapbender.StyleUtil.parseCssColor(this.highlightColor);
            var fillColor = 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + this.rotationControlOpacity + ')';
            
            // Apply visible style to dotted box
            handle.boxFeature.setStyle(new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.highlightColor,
                    width: this.rotationBoxStrokeWidth,
                    lineDash: this.rotationBoxLineDash
                })
            }));
            
            // Apply visible style to rotation handle circle
            handle.handleFeature.setStyle(new ol.style.Style({
                image: new ol.style.Circle({
                    radius: this.rotationHandleRadius,
                    fill: new ol.style.Fill({ color: fillColor }),
                    stroke: new ol.style.Stroke({ color: this.defaultFrameStrokeColor, width: this.rotationHandleStrokeWidth })
                })
            }));
        },
        
        /**
         * Hide rotation controls (dotted frame and circle) for a specific frame
         * Uses empty style to keep geometry for hit detection while making invisible
         */
        _hideRotationControls: function(frameId) {
            var handle = this.rotationHandles.find(function(h) {
                return h.frameId === frameId;
            });
            
            if (!handle) return;
            
            // Apply empty style - features remain interactive but invisible
            var emptyStyle = new ol.style.Style({});
            handle.boxFeature.setStyle(emptyStyle);
            handle.handleFeature.setStyle(emptyStyle);
        },
        
        /**
         * Update rotation handle position after feature is rotated
         */
        _updateRotationHandle: function(frameId) {
            var handle = this.rotationHandles.find(function(h) {
                return h.frameId === frameId;
            });
            
            if (!handle) return;
            
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.id === frameId;
            });
            
            if (!frameData) return;
            
            var geometry = frameData.feature.getGeometry();
            var extent = geometry.getExtent();
            
            // Update box
            var coords = [
                [extent[0], extent[1]],
                [extent[2], extent[1]],
                [extent[2], extent[3]],
                [extent[0], extent[3]],
                [extent[0], extent[1]]
            ];
            handle.boxFeature.getGeometry().setCoordinates(coords);
            
            // Update handle position (bottom-right)
            handle.handleFeature.getGeometry().setCoordinates([extent[2], extent[1]]);
        },
        
        /**
         * Remove rotation handle overlay for a frame
         */
        _removeRotationHandle: function(frameId) {
            var handleIndex = this.rotationHandles.findIndex(function(h) {
                return h.frameId === frameId;
            });
            
            if (handleIndex !== -1) {
                var handle = this.rotationHandles[handleIndex];
                var source = this.rotationOverlayLayer.getSource();
                source.removeFeature(handle.boxFeature);
                source.removeFeature(handle.handleFeature);
                this.rotationHandles.splice(handleIndex, 1);
            }
        },
        
        /**
         * Refresh rotation handles - update positions for all frames
         */
        _refreshRotationControls: function() {
            var self = this;
            this.rotationHandles.forEach(function(handle) {
                self._updateRotationHandle(handle.frameId);
            });
        },
        
        /**
         * Clear all pinned features
         */
        _clearPinnedFeatures: function() {
            // Clear rotation overlay layer
            if (this.rotationOverlayLayer) {
                this.rotationOverlayLayer.getSource().clear();
            }
            this.rotationHandles = [];
            
            // Clear pinned features
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            layerBridge.clear();
            this.pinnedFeatures = [];
        },
        
        /**
         * Override _resetSelectionFeature to prevent drag interaction
         */
        _resetSelectionFeature: function() {
            var previous = this.feature;
            var model = this.map.getModel();
            var center = previous && model.getFeatureCenter(previous) || model.getCurrentMapCenter();
            this.feature = this._createFeature(this._getPrintScale(), center, this.inputRotation_);
            this._clearFeature(previous);
            this._redrawSelectionFeatures();
        },
        
        /**
         * Filter out GeoJSON+Style layers from job data (removes frame features from print)
         */
        _filterFrameLayers: function(job) {
            if (job.layers && Array.isArray(job.layers)) {
                job.layers = job.layers.filter(function(layer) {
                    return layer.type !== 'GeoJSON+Style';
                });
            }
        },
        
        /**
         * Override _onSubmit to collect and submit all pinned frames
         */
        _onSubmit: async function(evt) {
            evt.preventDefault();
            
            if (!this.selectionActive) {
                return false;
            }
            
            if (this.pinnedFeatures.length === 0) {
                alert(Mapbender.trans('mb.print.printclient.batchprint.alert.noframes'));
                return false;
            }
            
            // Store the current selection feature center to restore it after submission
            var selectionCenter = this.feature ? this.map.getModel().getFeatureCenter(this.feature) : null;
            
            var jobs = [];
            
            try {
                for (var i = 0; i < this.pinnedFeatures.length; i++) {
                    var frameData = this.pinnedFeatures[i];
                    
                    // Temporarily set the UI selectors to this frame's settings
                    var $scaleSelect = $('select[name="scale_select"]', this.$form);
                    var $templateSelect = $('select[name="template"]', this.$form);
                    var $qualitySelect = $('select[name="quality"]', this.$form);
                    
                    var originalScale = $scaleSelect.val();
                    var originalTemplate = $templateSelect.val();
                    var originalQuality = $qualitySelect.val();
                    
                    $scaleSelect.val(frameData.scale);
                    if (frameData.template) $templateSelect.val(frameData.template);
                    if (frameData.quality) $qualitySelect.val(frameData.quality);
                    
                    this.feature = frameData.feature;
                    var job = await $.mapbender.mbPrintClient.prototype._collectJobData.call(this);
                    // CRITICAL: Negate rotation to match backend expectation
                    job.rotation = -frameData.rotation;
                    
                    // Explicitly add template, quality, and scale_select
                    if (frameData.template) job.template = frameData.template;
                    if (frameData.quality) job.quality = frameData.quality;
                    job.scale_select = frameData.scale;
                    
                    // Use stored extent from when frame was pinned
                    if (frameData.extent) {
                        job.extent = {
                            width: frameData.extent.width,
                            height: frameData.extent.height
                        };
                    }
                    
                    // Restore original values
                    $scaleSelect.val(originalScale);
                    $templateSelect.val(originalTemplate);
                    $qualitySelect.val(originalQuality);
                    
                    this._filterFrameLayers(job);
                    
                    jobs.push(job);
                }
                
                this.feature = null;
                
                var $hiddenFields = $('.-fn-hidden-fields', this.$form);
                $hiddenFields.empty();
                
                var $dataField = $('<input type="hidden" name="data">');
                $dataField.val(JSON.stringify(jobs));
                $hiddenFields.append($dataField);
                
                this.$form[0].submit();
                
                // Restore selection feature after submission
                if (selectionCenter) {
                    this._resetSelectionFeature();
                    this._moveFeatureToCoordinate(selectionCenter);
                }
                
                var $tabs = $('.tab-container', this.element);
                if ($tabs.length) {
                    // Delay tab switch to allow form submission to complete
                    // and ensure smooth UI transition
                    window.setTimeout(function() {
                        $tabs.tabs({active: 1});
                    }, self.tabSwitchDelay);
                }
            } catch (error) {
                // Restore selection feature on error too
                if (selectionCenter) {
                    this._resetSelectionFeature();
                    this._moveFeatureToCoordinate(selectionCenter);
                }
                alert(Mapbender.trans('mb.print.printclient.batchprint.alert.error') + ': ' + error.message);
            }
            
            return false;
        },

        /**
         * Setup geospatial file upload handlers (KML, GeoJSON, etc.)
         */
        _setupGeofileUploadHandlers: function() {
            var self = this;
            
            // File input change handler - automatically load geospatial file when selected
            $('.-fn-geofile-file-input', this.element).on('change', function() {
                var file = this.files && this.files[0];
                
                if (file) {
                    // Automatically load the geospatial file
                    self._loadGeospatialFile();
                } else {
                    // No file selected - hide buttons and clear status
                    $('.-fn-geofile-status', self.element).text('');
                    $('.-fn-geofile-buttons', self.element).removeClass('show');
                    $('.-fn-place-frames-button', self.element).removeClass('show');
                }
            });
            
            // Clear file button handler
            $('.-fn-clear-geofile-button', this.element).on('click', function() {
                self._clearGeofileLayer();
            });
            
            // Place frames along track button handler
            $('.-fn-place-frames-button', this.element).on('click', function() {
                self._placeFramesAlongTrack();
            });
        },
        
        /**
         * Load and display geospatial file on map (KML, GeoJSON, GPX, GML)
         */
        _loadGeospatialFile: function() {
            var $fileInput = $('.-fn-geofile-file-input', this.element);
            var file = $fileInput[0].files && $fileInput[0].files[0];
            
            if (!file) {
                alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.selectfile'));
                return;
            }
            
            var self = this;
            var map = this.map.getModel().olMap;
            var featureProjection = map.getView().getProjection().getCode();
            
            Mapbender.FileUtil.readGeospatialFile(file, {
                dataProjection: 'EPSG:4326',
                featureProjection: featureProjection,
                onSuccess: function(features, file) {
                    try {
                        self._renderTrackFeatures(features, file);
                        $('.-fn-geofile-status', self.element)
                            .text(Mapbender.trans('mb.print.printclient.batchprint.geofile.loaded') + ': ' + file.name)
                            .addClass('text-success')
                            .removeClass('text-danger');
                        $('.-fn-geofile-buttons', self.element).addClass('show');
                        $('.-fn-place-frames-button', self.element).addClass('show');
                    } catch (error) {
                        self._handleFileLoadError(error, file);
                    }
                },
                onError: function(error, file) {
                    self._handleFileLoadError(error, file);
                }
            });
        },
        
        /**
         * Handle file loading errors
         * @param {Error} error - The error object
         * @param {File} file - The file that failed to load
         */
        _handleFileLoadError: function(error, file) {
            alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.error') + ': ' + error.message);
            $('.-fn-geofile-status', this.element)
                .text(Mapbender.trans('mb.print.printclient.batchprint.geofile.error') + ': ' + error.message)
                .addClass('text-danger')
                .removeClass('text-success');
            $('.-fn-geofile-buttons', this.element).removeClass('show');
            $('.-fn-place-frames-button', this.element).removeClass('show');
        },
        
        /**
         * Render track features from uploaded geospatial file
         * @param {Array<ol.Feature>} features - Parsed features
         * @param {File} file - The uploaded file object
         */
        _renderTrackFeatures: function(features, file) {
            var map = this.map.getModel().olMap;
            
            if (!features || features.length === 0) {
                throw new Error('No features found in file');
            }
            
            // Validate: must contain exactly one feature
            if (features.length !== 1) {
                throw new Error('File must contain exactly one feature (found ' + features.length + ')');
            }
            
            // Validate: feature must be a LineString
            var geometry = features[0].getGeometry();
            if (!geometry || geometry.getType() !== 'LineString') {
                var foundType = geometry ? geometry.getType() : 'no geometry';
                throw new Error('File must contain a LineString (found ' + foundType + ')');
            }
            
            // Clear existing geospatial layer if present
            this._clearGeofileLayer();
            
            // Create new vector layer for geospatial features
            var source = new ol.source.Vector({
                features: features
            });
            
            var rgb = Mapbender.StyleUtil.parseCssColor(this.trackColor);
            var trackFillColor = 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + this.trackFillOpacity + ')';
            
            this.geofileLayer = new ol.layer.Vector({
                source: source,
                style: new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: this.trackColor,
                        width: this.trackStrokeWidth
                    }),
                    fill: new ol.style.Fill({
                        color: trackFillColor
                    }),
                    image: new ol.style.Circle({
                        radius: this.trackPointRadius,
                        fill: new ol.style.Fill({
                            color: this.trackColor
                        }),
                        stroke: new ol.style.Stroke({
                            color: '#FFFFFF',
                            width: this.trackPointStrokeWidth
                        })
                    })
                }),
                zIndex: this.TRACK_LAYER_ZINDEX
            });
            
            // Add layer to map
            map.addLayer(this.geofileLayer);
            
            // Store features for reference
            this.geofileFeatures = features;
            
            // Zoom to LineString extent with padding
            var extent = geometry.getExtent();
            if (!ol.extent.isEmpty(extent)) {
                map.getView().fit(extent, {
                    padding: this.trackFitPadding,
                    duration: this.trackFitDuration,
                    maxZoom: this.trackFitMaxZoom
                });
            }
        },
        
        /**
         * Clear geospatial layer from map
         */
        _clearGeofileLayer: function() {
            if (this.geofileLayer) {
                var map = this.map.getModel().olMap;
                map.removeLayer(this.geofileLayer);
                this.geofileLayer = null;
                this.geofileFeatures = [];
            }
            
            // Reset file input, status and hide all geofile-related buttons
            $('.-fn-geofile-file-input', this.element).val('');
            $('.-fn-geofile-status', this.element)
                .text('')
                .removeClass('success error');
            $('.-fn-geofile-buttons', this.element).removeClass('show');
            $('.-fn-place-frames-button', this.element).removeClass('show');
        },
        
        /**
         * Place print frames along the geospatial track
         */
        _placeFramesAlongTrack: function() {
            if (!this.geofileFeatures || this.geofileFeatures.length === 0) {
                alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.loadfirst'));
                return;
            }
            
            var lineString = this.geofileFeatures[0].getGeometry();
            if (!lineString || lineString.getType() !== 'LineString') {
                alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.invalidgeometry'));
                return;
            }
            
            // Check if frame adjustment is enabled
            var adjustFrames = $('.-fn-adjust-frames-checkbox', this.element).is(':checked');
            
            // Get the overlap percentage from input field (default 10%)
            var inputOverlap = parseFloat($('.-fn-frame-overlap-input', this.element).val()) || 10;
            
            // Check bounds and stop if out of range
            if (inputOverlap < 0 || inputOverlap > 50) {
                Mapbender.error(Mapbender.trans('mb.print.printclient.batchprint.overlap.outofbounds', {
                    value: inputOverlap
                }));
                return;
            }
            
            var overlapPercent = inputOverlap;
            
            // Get the current template size to determine frame spacing
            var templateWidth = this.width;
            var templateHeight = this.height;
            var scale = this._getPrintScale();
            var unitsPerMeterAtFirstCoordinate = this.map.getModel().getUnitsPerMeterAt(lineString.getFirstCoordinate());
            
            // Calculate frame width in map units (use the smaller dimension for overlap calculation)
            var frameSize = Math.min(templateWidth, templateHeight) * scale * unitsPerMeterAtFirstCoordinate.h;
            
            // Get total length
            var totalLength = lineString.getLength();
            
            // Calculate ideal spacing based on overlap percentage
            // If overlap is 10%, spacing is 90% of frame size (frameSize * 0.9)
            var spacingFactor = 1 - (overlapPercent / 100);
            var idealSpacing = frameSize * spacingFactor;
            
            // Calculate number of frames needed to cover the entire track
            // We need at least 2 frames (start and end), and enough to cover the distance
            var numFrames = Math.max(2, Math.ceil(totalLength / idealSpacing) + 1);
            
            // Recalculate actual spacing to evenly distribute frames from start to end
            var actualSpacing = totalLength / (numFrames - 1);
            
            // Place frames along the line with even spacing
            var previousRotation = null;
            for (var i = 0; i < numFrames; i++) {
                var distance = i * actualSpacing;
                
                // Get coordinate at this distance
                var coord = Mapbender.GeometryUtil.getCoordinateAtDistance(lineString, distance);
                if (!coord) break;
                
                // Move current feature to this position
                this._moveFeatureToCoordinate(coord);
                
                // If adjust frames is enabled, rotate the frame according to the track direction
                if (adjustFrames) {
                    var bearing = Mapbender.GeometryUtil.getBearingAtDistance(lineString, distance);
                    this._rotateCurrentFeature(bearing, previousRotation);
                    
                    // Store the rotation for the next frame to maintain continuity
                    var entry = this._getFeatureEntry(this.feature);
                    previousRotation = (entry.rotationBias + entry.tempRotation) * (180 / Math.PI);
                }
                
                // Pin the frame
                this._pinCurrentFrame();
            }
            
            $('.-fn-geofile-status', this.element)
                .text(Mapbender.trans('mb.print.printclient.batchprint.geofile.placed', {count: this.pinnedFeatures.length}))
                .addClass('text-success')
                .removeClass('text-danger');
        },
        

        

        
        /**
         * Delete all frames at once
         */
        _deleteAllFrames: function() {
            if (this.pinnedFeatures.length === 0) {
                return;
            }
            
            if (!confirm(Mapbender.trans('mb.print.printclient.batchprint.confirm.deleteall', {count: this.pinnedFeatures.length}))) {
                return;
            }
            
            // Clear all pinned features from the map
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, this.PINNED_FRAMES_LAYER);
            layerBridge.clear();
            
            // Clear rotation handles
            this.rotationHandles = [];
            if (this.rotationOverlayLayer) {
                var source = this.rotationOverlayLayer.getSource();
                source.clear();
            }
            
            // Clear data arrays
            this.pinnedFeatures = [];
            this.featureCounter = 0;
            
            // Update table and UI
            this._updateFrameTable();
            
            $('.-fn-geofile-status', this.element)
                .text(Mapbender.trans('mb.print.printclient.batchprint.alldeleted'))
                .removeClass('success error');
        }
    });

})(jQuery);
