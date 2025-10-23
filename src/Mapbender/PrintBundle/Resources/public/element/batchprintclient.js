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
        multiFrameData: [],
        pinnedFeatures: [],
        
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

        _setup: function(){
            this._super();
            
            // Add unique class identifier
            this.element.addClass('mb-element-batchprintclient');

            // Remove rotation control (text input with name="rotation")
            $('input[name="rotation"]', this.element).closest('.mb-3').remove();
            
            // Initialize multiframe functionality
            this.featureCounter = 0;
            this.multiFrameData = [];
            this.pinnedFeatures = [];
            
            var self = this;
            
            // Change submit button text
            $('input[type="submit"]', this.element).val(Mapbender.trans('mb.print.printclient.btn.batchprint'));
            
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
        _pickScale: function() {
            // Get the scale that the parent would choose (current map scale or smallest that fits)
            var parentScale = this._superApply(arguments);
            
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
            
            // Remove map hover handler
            if (this.mapHoverHandler) {
                var map = this.map.getModel().olMap;
                map.un('pointermove', this.mapHoverHandler);
                this.mapHoverHandler = null;
            }
            
            this.featureCounter = 0;
            this.multiFrameData = [];
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
            
            // Mouse click handler - pin frames on click
            this.mouseClickHandler = function(evt) {
                if (!self.mouseFollowActive || self.isRotating || self.isDraggingFrame) {
                    return;
                }
                
                // Always pin the current frame
                self._pinCurrentFrame();
            };
            
            $mapElement.on('click', this.mouseClickHandler);
            
            // Mouse move handler - update feature position
            this.mouseMoveHandler = function(evt) {
                if (!self.mouseFollowActive || !self.feature) {
                    return;
                }
                
                var pixel = map.getEventPixel(evt.originalEvent);
                var coordinate = map.getCoordinateFromPixel(pixel);
                
                if (coordinate) {
                    self._moveFeatureToCoordinate(coordinate);
                }
            };
            
            $mapElement.on('mousemove', this.mouseMoveHandler);
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
                $mapElement.off('click', this.mouseClickHandler);
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
            
            // Get current rotation
            var entry = this._getFeatureEntry(this.feature);
            var totalRotation = entry.rotationBias + entry.tempRotation;
            
            // Get extent from feature geometry
            var extent = this.feature.getGeometry().getExtent();
            var extentWidth = extent[2] - extent[0];
            var extentHeight = extent[3] - extent[1];
            
            // Store pinned feature data including current UI settings and extent
            var frameData = {
                id: this.featureCounter,
                feature: pinnedFeature,
                rotation: totalRotation,
                scale: this._getPrintScale(),
                center: this.map.getModel().getFeatureCenter(pinnedFeature),
                template: $('select[name="template"]', this.$form).val(),
                quality: $('select[name="quality"]', this.$form).val(),
                extent: {
                    width: extentWidth,
                    height: extentHeight
                }
            };
            
            this.pinnedFeatures.push(frameData);
            this.multiFrameData.push(frameData);
            
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
         * Add pinned feature to map with black outline and rotation interaction
         */
        _addPinnedFeatureToMap: function(feature) {
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 1);
            var nativeLayer = layerBridge.getNativeLayer();
            
            // Ensure pinned frames layer has higher z-index than rotation overlay
            nativeLayer.setZIndex(1000);
            
            // Create black outline style for pinned features (normal state)
            var style = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#000000',
                    width: 2
                }),
                fill: new ol.style.Fill({
                    color: 'rgba(255, 255, 255, 0.5)'
                })
            });
            
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
                var highlightStyle = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#0066cc',
                        width: 3
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(0, 102, 204, 0.3)'
                    })
                });
                frameData.feature.setStyle(highlightStyle);
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
                var normalStyle = new ol.style.Style({
                    stroke: new ol.style.Stroke({
                        color: '#000000',
                        width: 2
                    }),
                    fill: new ol.style.Fill({
                        color: 'rgba(255, 255, 255, 0.5)'
                    })
                });
                frameData.feature.setStyle(normalStyle);
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
            
            var self = this;
            this.pinnedFeatures.forEach(function(frameData) {
                var $row = $('<tr></tr>');
                $row.attr('data-frame-id', frameData.id);
                
                // Frame number
                $row.append($('<td></td>').text('Frame ' + frameData.id));
                
                // Scale
                $row.append($('<td></td>').text('1:' + frameData.scale));
                
                // Template - extract readable name
                var templateName = frameData.template || '';
                var templateDisplay = templateName.split('/').pop().replace(/_/g, ' ').trim();
                $row.append($('<td></td>').text(templateDisplay));
                
                // Quality (DPI)
                $row.append($('<td></td>').text(frameData.quality + ' dpi'));
                
                // Rotation (rounded to 1 decimal place)
                var rotationText = Math.round(frameData.rotation * 10) / 10 + '°';
                $row.append($('<td></td>').text(rotationText));
                
                // Delete button
                var $deleteCell = $('<td></td>').css('text-align', 'right');
                var $deleteIcon = $('<i class="fa fa-trash"></i>');
                $deleteIcon.css({
                    'cursor': 'pointer',
                    'font-size': '16px',
                    'color': '#000'
                });
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
                    $(this).css('background-color', '#e6f2ff');
                });
                
                $row.on('mouseleave', function() {
                    self._unhighlightFeature(frameData.id);
                    $(this).css('background-color', '');
                });
                
                $tbody.append($row);
            });
            
            // Add map hover handlers for highlighting table rows
            this._setupMapHoverHandlers();
        },
        
        /**
         * Setup hover handlers on map features to highlight table rows
         */
        _setupMapHoverHandlers: function() {
            var self = this;
            var map = this.map.getModel().olMap;
            
            // Remove old handler if exists
            if (this.mapHoverHandler) {
                map.un('pointermove', this.mapHoverHandler);
            }
            
            this.mapHoverHandler = function(evt) {
                // Clear all highlights first
                $('.-fn-frame-table tbody tr', self.element).css('background-color', '');
                self.pinnedFeatures.forEach(function(frameData) {
                    self._unhighlightFeature(frameData.id);
                });
                
                var pixel = evt.pixel;
                var foundFrames = [];
                
                // Collect ALL features at this pixel
                map.forEachFeatureAtPixel(pixel, function(feature) {
                    // Check if this feature is one of our pinned features
                    var frameData = self.pinnedFeatures.find(function(f) {
                        return f.feature === feature;
                    });
                    
                    if (frameData) {
                        foundFrames.push(frameData);
                    }
                });
                
                // Highlight all found frames and their rows
                foundFrames.forEach(function(frameData) {
                    self._highlightFeature(frameData.id);
                    var $row = $('.-fn-frame-table tbody tr[data-frame-id="' + frameData.id + '"]', self.element);
                    $row.css('background-color', '#e6f2ff');
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
            
            this.multiFrameData = this.multiFrameData.filter(function(f) {
                return f.id !== frameId;
            });
            
            if (frameData) {
                // Remove rotation handle overlay
                this._removeRotationHandle(frameId);
                
                var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 1);
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
            var self = this;
            var map = this.map.getModel().olMap;
            
            // Create overlay layer for rotation handles
            var source = new ol.source.Vector();
            this.rotationOverlayLayer = new ol.layer.Vector({
                source: source,
                style: null,  // Features will have their own styles
                zIndex: 999  // Below pinned frames but above map content
            });
            map.addLayer(this.rotationOverlayLayer);
            
            // Create drag interaction for rotation handles and frame dragging
            this.rotationDragInteraction = new ol.interaction.Pointer({
                handleDownEvent: function(evt) {
                    // First check if clicking on rotation handle
                    var overlayFeature = map.forEachFeatureAtPixel(evt.pixel, function(f) {
                        return f;
                    }, {
                        layerFilter: function(layer) {
                            return layer === self.rotationOverlayLayer;
                        }
                    });
                    
                    if (overlayFeature && overlayFeature.get('type') === 'rotation-handle') {
                        // Start rotation
                        self.isRotating = true;
                        self.rotatingFrameId = overlayFeature.get('frameId');
                        self.rotationStartPixel = evt.pixel;
                        
                        var target = map.getTarget();
                        var element = typeof target === 'string' ? document.getElementById(target) : target;
                        element.style.cursor = 'grabbing';
                        
                        return true;
                    }
                    
                    // Check if clicking on a pinned frame
                    var layerBridge = Mapbender.vectorLayerPool.getElementLayer(self, 1);
                    var pinnedFeature = map.forEachFeatureAtPixel(evt.pixel, function(f) {
                        return f;
                    }, {
                        layerFilter: function(layer) {
                            return layer === layerBridge.getNativeLayer();
                        }
                    });
                    
                    if (pinnedFeature) {
                        // Start dragging frame
                        var frameData = self.pinnedFeatures.find(function(f) {
                            return f.feature === pinnedFeature;
                        });
                        
                        if (frameData) {
                            self.isDraggingFrame = true;
                            self.draggedFrameId = frameData.id;
                            self.dragStartCoordinate = evt.coordinate;
                            
                            // Hide the selection frame during drag
                            if (self.feature) {
                                self.feature.setStyle(new ol.style.Style({}));  // Make invisible
                            }
                            
                            var target = map.getTarget();
                            var element = typeof target === 'string' ? document.getElementById(target) : target;
                            element.style.cursor = 'move';
                            
                            return true;
                        }
                    }
                    
                    return false;
                },
                handleDragEvent: function(evt) {
                    // Handle rotation
                    if (self.isRotating) {
                        var frameData = self.pinnedFeatures.find(function(f) {
                            return f.id === self.rotatingFrameId;
                        });
                        
                        if (frameData) {
                            var geometry = frameData.feature.getGeometry();
                            var extent = geometry.getExtent();
                            var center = [(extent[0] + extent[2]) / 2, (extent[1] + extent[3]) / 2];
                            
                            var centerPixel = map.getPixelFromCoordinate(center);
                            var dx1 = self.rotationStartPixel[0] - centerPixel[0];
                            var dy1 = self.rotationStartPixel[1] - centerPixel[1];
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
                            
                            self._updateRotationHandle(self.rotatingFrameId);
                            self.rotationStartPixel = evt.pixel;
                        }
                    }
                    
                    // Handle frame dragging
                    if (self.isDraggingFrame) {
                        var frameData = self.pinnedFeatures.find(function(f) {
                            return f.id === self.draggedFrameId;
                        });
                        
                        if (frameData) {
                            var dx = evt.coordinate[0] - self.dragStartCoordinate[0];
                            var dy = evt.coordinate[1] - self.dragStartCoordinate[1];
                            
                            // Translate the feature geometry
                            frameData.feature.getGeometry().translate(dx, dy);
                            
                            // Update center in frameData
                            frameData.center = [frameData.center[0] + dx, frameData.center[1] + dy];
                            
                            // Update rotation handle position
                            self._updateRotationHandle(self.draggedFrameId);
                            
                            self.dragStartCoordinate = evt.coordinate;
                        }
                    }
                },
                handleUpEvent: function(evt) {
                    if (self.isRotating) {
                        // Update table to reflect new rotation value
                        self._updateFrameTable();
                        
                        setTimeout(function() {
                            self.isRotating = false;
                            self.rotatingFrameId = null;
                        }, 50);
                        return true;
                    }
                    
                    if (self.isDraggingFrame) {
                        // Restore selection frame visibility
                        if (self.feature) {
                            self.feature.setStyle(null);
                            self._redrawSelectionFeatures();
                        }
                        
                        setTimeout(function() {
                            self.isDraggingFrame = false;
                            self.draggedFrameId = null;
                            self.dragStartCoordinate = null;
                        }, 100);
                        return true;
                    }
                    
                    return false;
                }
            });
            
            map.addInteraction(this.rotationDragInteraction);
            
            // Change cursor when hovering over rotation handles or frames
            map.on('pointermove', function(evt) {
                if (evt.dragging) return;
                
                var pixel = map.getEventPixel(evt.originalEvent);
                var target = map.getTarget();
                var element = typeof target === 'string' ? document.getElementById(target) : target;
                
                if (self.isRotating) {
                    element.style.cursor = 'grabbing';
                    return;
                }
                
                if (self.isDraggingFrame) {
                    element.style.cursor = 'move';
                    return;
                }
                
                // Check for rotation handle
                var overlayFeature = map.forEachFeatureAtPixel(pixel, function(f) {
                    return f;
                }, {
                    layerFilter: function(layer) {
                        return layer === self.rotationOverlayLayer;
                    }
                });
                
                if (overlayFeature && overlayFeature.get('type') === 'rotation-handle') {
                    element.style.cursor = 'grab';
                    return;
                }
                
                // Check for pinned frame
                var layerBridge = Mapbender.vectorLayerPool.getElementLayer(self, 1);
                var pinnedFeature = map.forEachFeatureAtPixel(pixel, function(f) {
                    return f;
                }, {
                    layerFilter: function(layer) {
                        return layer === layerBridge.getNativeLayer();
                    }
                });
                
                if (pinnedFeature) {
                    element.style.cursor = 'move';
                    return;
                }
                
                element.style.cursor = '';
            });
        },
        
        /**
         * Add rotation handle overlay for a pinned frame
         */
        _addRotationHandle: function(feature) {
            var frameData = this.pinnedFeatures.find(function(f) {
                return f.feature === feature;
            });
            
            if (!frameData) return;
            
            var geometry = feature.getGeometry();
            var extent = geometry.getExtent();
            
            // Create yellow bounding box (4 line segments)
            var coords = [
                [extent[0], extent[1]],  // bottom-left
                [extent[2], extent[1]],  // bottom-right
                [extent[2], extent[3]],  // top-right
                [extent[0], extent[3]],  // top-left
                [extent[0], extent[1]]   // close
            ];
            
            var boxLine = new ol.geom.LineString(coords);
            var boxFeature = new ol.Feature(boxLine);
            boxFeature.setStyle(new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: '#ffff00',
                    width: 2,
                    lineDash: [5, 5]  // Dotted line
                })
            }));
            boxFeature.set('type', 'rotation-box');
            boxFeature.set('frameId', frameData.id);
            
            // Create rotation handle (circle at bottom-right)
            var handleCoord = [extent[2], extent[1]];
            var handlePoint = new ol.geom.Point(handleCoord);
            var handleFeature = new ol.Feature(handlePoint);
            handleFeature.setStyle(new ol.style.Style({
                image: new ol.style.Circle({
                    radius: 8,
                    fill: new ol.style.Fill({ color: 'rgba(255, 255, 0, 0.6)' }),
                    stroke: new ol.style.Stroke({ color: '#000000', width: 1 })
                })
            }));
            handleFeature.set('type', 'rotation-handle');
            handleFeature.set('frameId', frameData.id);
            
            // Add to overlay layer
            var source = this.rotationOverlayLayer.getSource();
            source.addFeature(boxFeature);
            source.addFeature(handleFeature);
            
            // Track handles
            this.rotationHandles.push({
                frameId: frameData.id,
                boxFeature: boxFeature,
                handleFeature: handleFeature
            });
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
            var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this, 1);
            layerBridge.clear();
            this.pinnedFeatures = [];
            this.multiFrameData = [];
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
                    window.setTimeout(function() {
                        $tabs.tabs({active: 1});
                    }, 50);
                }
            } catch (error) {
                // Restore selection feature on error too
                if (selectionCenter) {
                    this._resetSelectionFeature();
                    this._moveFeatureToCoordinate(selectionCenter);
                }
                alert('Error creating print job: ' + error.message);
            }
            
            return false;
        },

        __dummy__: null
    });

})(jQuery);
