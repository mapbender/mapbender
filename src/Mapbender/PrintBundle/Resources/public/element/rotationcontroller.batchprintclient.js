/**
 * RotationController for BatchPrintClient
 * 
 * Manages rotation functionality including:
 * - Rotation overlay layer with interactive controls
 * - Drag interactions for rotating frames
 * - Rotation handle management (show/hide/update)
 * - Cursor management for rotation operations
 */
(function() {
    'use strict';

    window.Mapbender = window.Mapbender || {};
    
    // Constants
    var DEFAULT_HIT_TOLERANCE = 10;
    var DEFAULT_DRAG_END_DELAY = 50;
    var FRAME_HIT_TOLERANCE = 5;
    
    /**
     * Creates a new RotationController
     * @param {Object} options - Configuration options
     * @param {ol.Map} options.map - OpenLayers map instance
     * @param {Mapbender.BatchPrintStyleConfig} options.styleConfig - Style configuration
     * @param {number} options.rotationZIndex - Z-index for rotation overlay layer
     * @param {number} options.hitTolerance - Hit tolerance for rotation controls in pixels
     * @param {number} options.dragEndDelay - Delay before resetting rotation state after drag
     * @param {Function} options.getFrameById - Callback to get frame data by ID
     * @param {Function} options.onRotationComplete - Callback when rotation is complete (frameId)
     * @constructor
     */
    Mapbender.BatchPrintRotationController = function(options) {
        this.map = options.map;
        this.widget = options.widget;
        this.styleConfig = options.styleConfig;
        this.rotationZIndex = options.rotationZIndex;
        this.hitTolerance = options.hitTolerance || DEFAULT_HIT_TOLERANCE;
        this.dragEndDelay = options.dragEndDelay || DEFAULT_DRAG_END_DELAY;
        this.getFrameById = options.getFrameById || null;
        this.onRotationComplete = typeof options.onRotationComplete === 'function' ? options.onRotationComplete : function() {};
        
        // Rotation state
        this.isRotating = false;
        this.rotatingFrameId = null;
        this.rotationStartPixel = null;
        
        // Dragging state
        this.isDraggingFrame = false;
        this.draggedFrameId = null;
        this.dragStartCoordinate = null;
        
        // Rotation overlay layer and handles
        this.overlayLayer = null;
        this.dragInteraction = null;
        this.handles = [];  // Array of {frameId, boxFeature, handleFeature}
        
        // Initialize
        this._createOverlayLayer();
        this._createDragInteraction();
        this._setupCursorHandlers();
    };

    /**
     * Create the rotation overlay layer
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._createOverlayLayer = function() {
        var source = new ol.source.Vector();
        this.overlayLayer = new ol.layer.Vector({
            source: source,
            style: null,  // Features will have their own styles
            zIndex: this.rotationZIndex
        });
        this.map.addLayer(this.overlayLayer);
    };

    /**
     * Create drag interaction for rotation handles
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._createDragInteraction = function() {
        var self = this;
        
        this.dragInteraction = new ol.interaction.Pointer({
            handleDownEvent: function(evt) {
                return self._handleDragDown(evt);
            },
            handleDragEvent: function(evt) {
                self._handleDrag(evt);
            },
            handleUpEvent: function(evt) {
                return self._handleDragUp(evt);
            }
        });
        
        this.map.addInteraction(this.dragInteraction);
    };

    /**
     * Setup cursor handlers for rotation controls
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._setupCursorHandlers = function() {
        var self = this;
        
        this.map.on('pointermove', function(evt) {
            if (evt.dragging) return;
            
            // Handle active rotation cursor
            if (self.isRotating) {
                self._setCursor('grabbing');
                return;
            }
            
            // Check for rotation handle hover
            var handle = self._getHandleAtPixel(evt.pixel);
            if (handle) {
                self._setCursor('grab');
                return;
            }
            
            // Reset cursor if not over rotation controls
            self._setCursor('');
        });
    };

    /**
     * Set map cursor style
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._setCursor = function(cursor) {
        var target = this.map.getTarget();
        var element = typeof target === 'string' ? document.getElementById(target) : target;
        element.style.cursor = cursor;
    };

    /**
     * Get rotation handle at pixel
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._getHandleAtPixel = function(pixel) {
        var self = this;
        
        var feature = this.map.forEachFeatureAtPixel(pixel, function(f) {
            return f;
        }, {
            layerFilter: function(layer) {
                return layer === self.overlayLayer;
            }
        });
        
        return (feature && feature.get('type') === 'rotation-handle') ? feature : null;
    };

    /**
     * Get frame data for pinned frame at given pixel
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._getFrameAtPixel = function(pixel) {
        var self = this;
        
        if (!this.widget || !this.widget.frameManager) {
            return null;
        }
        
        var layerBridge = Mapbender.vectorLayerPool.getElementLayer(this.widget, 1); // PINNED_FRAMES_LAYER
        
        var pinnedFeature = this.map.forEachFeatureAtPixel(pixel, function(f) {
            return f;
        }, {
            layerFilter: function(layer) {
                return layer === layerBridge.getNativeLayer();
            },
            hitTolerance: FRAME_HIT_TOLERANCE
        });
        
        if (!pinnedFeature) {
            return null;
        }
        
        // Get frame data by feature - use frameManager directly for O(n) lookup
        return this.widget.frameManager.getFrameByFeature(pinnedFeature);
    };

    /**
     * Handle drag down event
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._handleDragDown = function(evt) {
        var handleFeature = this._getHandleAtPixel(evt.pixel);
        
        if (handleFeature) {
            this.isRotating = true;
            this.rotatingFrameId = handleFeature.get('frameId');
            this.rotationStartPixel = evt.pixel;
            this._setCursor('grabbing');
            return true;
        }
        
        // Check if clicking on a pinned frame
        var frameData = this._getFrameAtPixel(evt.pixel);
        if (frameData) {
            this.isDraggingFrame = true;
            this.draggedFrameId = frameData.id;
            this.dragStartCoordinate = evt.coordinate;
            this._setCursor('move');
            return true;
        }
        
        return false;
    };

    /**
     * Handle drag event
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._handleDrag = function(evt) {
        if (this.isRotating) {
            if (!this.getFrameById) return;
            var frameData = this.getFrameById(this.rotatingFrameId);
            if (!frameData || !frameData.feature) return;
            
            var geometry = frameData.feature.getGeometry();
            if (!geometry) return;
            var extent = geometry.getExtent();
            var center = [(extent[0] + extent[2]) / 2, (extent[1] + extent[3]) / 2];
            
            var centerPixel = this.map.getPixelFromCoordinate(center);
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
            
            this.updateHandle(this.rotatingFrameId);
            this.rotationStartPixel = evt.pixel;
        } else if (this.isDraggingFrame) {
            if (!this.getFrameById) return;
            var frameData = this.getFrameById(this.draggedFrameId);
            if (!frameData || !frameData.feature) return;
            
            var geometry = frameData.feature.getGeometry();
            if (!geometry) return;
            
            var dx = evt.coordinate[0] - this.dragStartCoordinate[0];
            var dy = evt.coordinate[1] - this.dragStartCoordinate[1];
            
            // Translate the feature geometry
            geometry.translate(dx, dy);
            
            // Update center in frameData
            frameData.center = [frameData.center[0] + dx, frameData.center[1] + dy];
            
            // Update rotation handle position
            this.updateHandle(this.draggedFrameId);
            
            this.dragStartCoordinate = evt.coordinate;
        }
    };

    /**
     * Handle drag up event
     * @private
     */
    Mapbender.BatchPrintRotationController.prototype._handleDragUp = function(evt) {
        if (this.isRotating) {
            var self = this;
            var frameId = this.rotatingFrameId;
            
            // Delay flag reset to prevent immediate re-triggering
            setTimeout(function() {
                self.isRotating = false;
                self.rotatingFrameId = null;
                self._setCursor('');
                self.onRotationComplete(frameId);
            }, this.dragEndDelay);
            
            return true;
        } else if (this.isDraggingFrame) {
            var self = this;
            var frameId = this.draggedFrameId;
            
            // Delay flag reset to prevent immediate re-triggering
            setTimeout(function() {
                self.isDraggingFrame = false;
                self.draggedFrameId = null;
                self._setCursor('');
                if (self.onRotationComplete) {
                    self.onRotationComplete(frameId);
                }
            }, this.dragEndDelay);
            
            return true;
        }
        
        return false;
    };

    /**
     * Add rotation handle for a frame
     * @param {number} frameId - Frame ID
     * @param {ol.Feature} feature - Frame feature
     */
    Mapbender.BatchPrintRotationController.prototype.addHandle = function(frameId, feature) {
        var geometry = feature.getGeometry();
        var extent = geometry.getExtent();
        
        // Create dotted bounding box as a polygon from extent
        var boxFeature = new ol.Feature(ol.geom.Polygon.fromExtent(extent));
        boxFeature.set('type', 'rotation-box');
        boxFeature.set('frameId', frameId);
        
        // Create rotation handle circle at bottom-right corner
        var handleFeature = new ol.Feature(new ol.geom.Point([extent[2], extent[1]]));
        handleFeature.set('type', 'rotation-handle');
        handleFeature.set('frameId', frameId);
        
        // Add to overlay layer with empty style (initially invisible)
        var emptyStyle = this.styleConfig.createEmptyStyle();
        boxFeature.setStyle(emptyStyle);
        handleFeature.setStyle(emptyStyle);
        
        var source = this.overlayLayer.getSource();
        source.addFeature(boxFeature);
        source.addFeature(handleFeature);
        
        // Track handle
        this.handles.push({
            frameId: frameId,
            boxFeature: boxFeature,
            handleFeature: handleFeature
        });
    };

    /**
     * Remove rotation handle for a frame
     * @param {number} frameId - Frame ID
     */
    Mapbender.BatchPrintRotationController.prototype.removeHandle = function(frameId) {
        var handleIndex = this.handles.findIndex(function(h) {
            return h.frameId === frameId;
        });
        
        if (handleIndex !== -1) {
            var handle = this.handles[handleIndex];
            var source = this.overlayLayer.getSource();
            source.removeFeature(handle.boxFeature);
            source.removeFeature(handle.handleFeature);
            this.handles.splice(handleIndex, 1);
        }
    };

    /**
     * Update rotation handle position for a frame
     * @param {number} frameId - Frame ID
     */
    Mapbender.BatchPrintRotationController.prototype.updateHandle = function(frameId) {
        var handle = this.handles.find(function(h) {
            return h.frameId === frameId;
        });
        
        if (!handle) return;
        
        if (!this.getFrameById) return;
        var frameData = this.getFrameById(frameId);
        if (!frameData) return;
        
        var geometry = frameData.feature.getGeometry();
        var extent = geometry.getExtent();
        
        // Update box coordinates - polygon needs array of rings
        var coords = [
            [extent[0], extent[1]],
            [extent[2], extent[1]],
            [extent[2], extent[3]],
            [extent[0], extent[3]],
            [extent[0], extent[1]]
        ];
        handle.boxFeature.getGeometry().setCoordinates([coords]);
        
        // Update handle position (bottom-right)
        handle.handleFeature.getGeometry().setCoordinates([extent[2], extent[1]]);
    };

    /**
     * Show rotation controls for a frame
     * @param {number} frameId - Frame ID
     */
    Mapbender.BatchPrintRotationController.prototype.showControls = function(frameId) {
        var handle = this.handles.find(function(h) {
            return h.frameId === frameId;
        });
        
        if (!handle) return;
        
        handle.boxFeature.setStyle(this.styleConfig.createRotationBoxStyle());
        handle.handleFeature.setStyle(this.styleConfig.createRotationHandleStyle());
    };

    /**
     * Hide rotation controls for a frame
     * @param {number} frameId - Frame ID
     */
    Mapbender.BatchPrintRotationController.prototype.hideControls = function(frameId) {
        var handle = this.handles.find(function(h) {
            return h.frameId === frameId;
        });
        
        if (!handle) return;
        
        var emptyStyle = this.styleConfig.createEmptyStyle();
        handle.boxFeature.setStyle(emptyStyle);
        handle.handleFeature.setStyle(emptyStyle);
    };

    /**
     * Refresh all rotation handles (update positions)
     */
    Mapbender.BatchPrintRotationController.prototype.refreshAll = function() {
        var self = this;
        this.handles.forEach(function(handle) {
            self.updateHandle(handle.frameId);
        });
    };

    /**
     * Clear all rotation handles
     */
    Mapbender.BatchPrintRotationController.prototype.clearAll = function() {
        if (this.overlayLayer) {
            this.overlayLayer.getSource().clear();
        }
        this.handles = [];
    };

    /**
     * Get overlay layer
     * @returns {ol.layer.Vector}
     */
    Mapbender.BatchPrintRotationController.prototype.getOverlayLayer = function() {
        return this.overlayLayer;
    };

    /**
     * Check if currently rotating
     * @returns {boolean}
     */
    Mapbender.BatchPrintRotationController.prototype.isCurrentlyRotating = function() {
        return this.isRotating;
    };

    /**
     * Destroy the controller and cleanup
     */
    Mapbender.BatchPrintRotationController.prototype.destroy = function() {
        if (this.dragInteraction) {
            this.map.removeInteraction(this.dragInteraction);
            this.dragInteraction = null;
        }
        
        if (this.overlayLayer) {
            this.map.removeLayer(this.overlayLayer);
            this.overlayLayer = null;
        }
        
        this.handles = [];
    };

})();
