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
    const DEFAULT_HIT_TOLERANCE = 10;
    const DEFAULT_DRAG_END_DELAY = 50;
    const FRAME_HIT_TOLERANCE = 5;
    
    /**
     * RotationController for BatchPrintClient
     * Manages rotation functionality
     */
    class BatchPrintRotationController {
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
         */
        constructor(options) {
            this.map = options.map;
            this.widget = options.widget;
            this.styleConfig = options.styleConfig;
            this.rotationZIndex = options.rotationZIndex;
            this.hitTolerance = options.hitTolerance || DEFAULT_HIT_TOLERANCE;
            this.dragEndDelay = options.dragEndDelay || DEFAULT_DRAG_END_DELAY;
            this.getFrameById = options.getFrameById || null;
            this.onRotationComplete = typeof options.onRotationComplete === 'function' ? options.onRotationComplete : () => {};
            
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
        }

        /**
         * Create the rotation overlay layer
         * @private
         */
        _createOverlayLayer() {
            const source = new ol.source.Vector();
            this.overlayLayer = new ol.layer.Vector({
                source: source,
                style: null,  // Features will have their own styles
                zIndex: this.rotationZIndex
            });
            // Mark as internal layer for print filtering
            this.overlayLayer.set('batchPrintClientInternal', true);
            this.map.addLayer(this.overlayLayer);
        }

        /**
         * Create drag interaction for rotation handles
         * @private
         */
        _createDragInteraction() {
            this.dragInteraction = new ol.interaction.Pointer({
                handleDownEvent: (evt) => this._handleDragDown(evt),
                handleDragEvent: (evt) => this._handleDrag(evt),
                handleUpEvent: (evt) => this._handleDragUp(evt)
            });
            
            this.map.addInteraction(this.dragInteraction);
        }

        /**
         * Setup cursor handlers for rotation controls
         * @private
         */
        _setupCursorHandlers() {
            this.map.on('pointermove', (evt) => {
                if (evt.dragging) return;
                
                // Handle active rotation cursor
                if (this.isRotating) {
                    this._setCursor('grabbing');
                    return;
                }
                
                // Handle active frame dragging cursor
                if (this.isDraggingFrame) {
                    this._setCursor('move');
                    return;
                }
                
                // Check for rotation handle hover
                const handle = this._getHandleAtPixel(evt.pixel);
                if (handle) {
                    this._setCursor('grab');
                    return;
                }
                
                // Check for frame hover (draggable)
                const frameData = this._getFrameAtPixel(evt.pixel);
                if (frameData) {
                    this._setCursor('move');
                    return;
                }
                
                // Reset cursor if not over rotation controls or frames
                this._setCursor('');
            });
        }

        /**
         * Set map cursor style
         * @private
         */
        _setCursor(cursor) {
            const target = this.map.getTarget();
            const element = typeof target === 'string' ? document.getElementById(target) : target;
            element.style.cursor = cursor;
        }

        /**
         * Get rotation handle at pixel
         * @private
         */
        _getHandleAtPixel(pixel) {
            const feature = this.map.forEachFeatureAtPixel(pixel, (f) => f, {
                layerFilter: (layer) => layer === this.overlayLayer
            });
            
            return (feature && feature.get('type') === 'rotation-handle') ? feature : null;
        }

        /**
         * Get frame data for pinned frame at given pixel
         * @private
         */
        _getFrameAtPixel(pixel) {
            if (!this.widget || !this.widget.frameManager) {
                return null;
            }
            
            const layerBridge = Mapbender.vectorLayerPool.getElementLayer(this.widget, 1); // PINNED_FRAMES_LAYER
            
            const pinnedFeature = this.map.forEachFeatureAtPixel(pixel, (f) => f, {
                layerFilter: (layer) => layer === layerBridge.getNativeLayer(),
                hitTolerance: FRAME_HIT_TOLERANCE
            });
            
            if (!pinnedFeature) {
                return null;
            }
            
            // Get frame data by feature - use frameManager directly for O(n) lookup
            return this.widget.frameManager.getFrameByFeature(pinnedFeature);
        }

        /**
         * Handle drag down event
         * @private
         */
        _handleDragDown(evt) {
            const handleFeature = this._getHandleAtPixel(evt.pixel);
            
            if (handleFeature) {
                this.isRotating = true;
                this.rotatingFrameId = handleFeature.get('frameId');
                this.rotationStartPixel = evt.pixel;
                this._setCursor('grabbing');
                return true;
            }
            
            // Check if clicking on a pinned frame
            const frameData = this._getFrameAtPixel(evt.pixel);
            if (frameData) {
                this.isDraggingFrame = true;
                this.draggedFrameId = frameData.id;
                this.dragStartCoordinate = evt.coordinate;
                this._setCursor('move');
                return true;
            }
            
            return false;
        }

        /**
         * Handle drag event
         * @private
         */
        _handleDrag(evt) {
            if (this.isRotating) {
                if (!this.getFrameById) return;
                const frameData = this.getFrameById(this.rotatingFrameId);
                if (!frameData || !frameData.feature) return;
                
                const geometry = frameData.feature.getGeometry();
                if (!geometry) return;
                const extent = geometry.getExtent();
                const center = [(extent[0] + extent[2]) / 2, (extent[1] + extent[3]) / 2];
                
                const centerPixel = this.map.getPixelFromCoordinate(center);
                const dx1 = this.rotationStartPixel[0] - centerPixel[0];
                const dy1 = this.rotationStartPixel[1] - centerPixel[1];
                const dx2 = evt.pixel[0] - centerPixel[0];
                const dy2 = evt.pixel[1] - centerPixel[1];
                
                const angle1 = Math.atan2(dy1, dx1);
                const angle2 = Math.atan2(dy2, dx2);
                // Negate angleDelta to make rotation follow mouse direction
                const angleDelta = -(angle2 - angle1);
                
                geometry.rotate(angleDelta, center);
                
                const degrees = angleDelta * (180 / Math.PI);
                frameData.rotation = (frameData.rotation + degrees) % 360;
                if (frameData.rotation < 0) frameData.rotation += 360;
                
                this.updateHandle(this.rotatingFrameId);
                this.rotationStartPixel = evt.pixel;
            } else if (this.isDraggingFrame) {
                if (!this.getFrameById) return;
                const frameData = this.getFrameById(this.draggedFrameId);
                if (!frameData || !frameData.feature) return;
                
                const geometry = frameData.feature.getGeometry();
                if (!geometry) return;
                
                const dx = evt.coordinate[0] - this.dragStartCoordinate[0];
                const dy = evt.coordinate[1] - this.dragStartCoordinate[1];
                
                // Translate the feature geometry
                geometry.translate(dx, dy);
                
                // Update center in frameData
                frameData.center = [frameData.center[0] + dx, frameData.center[1] + dy];
                
                // Update rotation handle position
                this.updateHandle(this.draggedFrameId);
                
                this.dragStartCoordinate = evt.coordinate;
            }
        }

        /**
         * Handle drag up event
         * @private
         */
        _handleDragUp(evt) {
            if (this.isRotating) {
                const frameId = this.rotatingFrameId;
                
                // Delay flag reset to prevent immediate re-triggering
                setTimeout(() => {
                    this.isRotating = false;
                    this.rotatingFrameId = null;
                    this._setCursor('');
                    this.onRotationComplete(frameId);
                }, this.dragEndDelay);
                
                return true;
            } else if (this.isDraggingFrame) {
                const frameId = this.draggedFrameId;
                
                // Delay flag reset to prevent immediate re-triggering
                setTimeout(() => {
                    this.isDraggingFrame = false;
                    this.draggedFrameId = null;
                    this._setCursor('');
                    if (this.onRotationComplete) {
                        this.onRotationComplete(frameId);
                    }
                }, this.dragEndDelay);
                
                return true;
            }
            
            return false;
        }

        /**
         * Add rotation handle for a frame
         * @param {number} frameId - Frame ID
         * @param {ol.Feature} feature - Frame feature
         */
        addHandle(frameId, feature) {
            const geometry = feature.getGeometry();
            const extent = geometry.getExtent();
            
            // Create dotted bounding box as a polygon from extent
            const boxFeature = new ol.Feature(ol.geom.Polygon.fromExtent(extent));
            boxFeature.set('type', 'rotation-box');
            boxFeature.set('frameId', frameId);
            
            // Create rotation handle circle at bottom-right corner
            const handleFeature = new ol.Feature(new ol.geom.Point([extent[2], extent[1]]));
            handleFeature.set('type', 'rotation-handle');
            handleFeature.set('frameId', frameId);
            
            // Add to overlay layer with empty style (initially invisible)
            const emptyStyle = this.styleConfig.createEmptyStyle();
            boxFeature.setStyle(emptyStyle);
            handleFeature.setStyle(emptyStyle);
            
            const source = this.overlayLayer.getSource();
            source.addFeature(boxFeature);
            source.addFeature(handleFeature);
            
            // Track handle
            this.handles.push({
                frameId: frameId,
                boxFeature: boxFeature,
                handleFeature: handleFeature
            });
        }

        /**
         * Remove rotation handle for a frame
         * @param {number} frameId - Frame ID
         */
        removeHandle(frameId) {
            const handleIndex = this.handles.findIndex(h => h.frameId === frameId);
            
            if (handleIndex !== -1) {
                const handle = this.handles[handleIndex];
                const source = this.overlayLayer.getSource();
                source.removeFeature(handle.boxFeature);
                source.removeFeature(handle.handleFeature);
                this.handles.splice(handleIndex, 1);
            }
        }

        /**
         * Update rotation handle position for a frame
         * @param {number} frameId - Frame ID
         */
        updateHandle(frameId) {
            const handle = this.handles.find(h => h.frameId === frameId);
            
            if (!handle) return;
            
            if (!this.getFrameById) return;
            const frameData = this.getFrameById(frameId);
            if (!frameData) return;
            
            const geometry = frameData.feature.getGeometry();
            const extent = geometry.getExtent();
            
            // Update box coordinates - polygon needs array of rings
            const coords = [
                [extent[0], extent[1]],
                [extent[2], extent[1]],
                [extent[2], extent[3]],
                [extent[0], extent[3]],
                [extent[0], extent[1]]
            ];
            handle.boxFeature.getGeometry().setCoordinates([coords]);
            
            // Update handle position (bottom-right)
            handle.handleFeature.getGeometry().setCoordinates([extent[2], extent[1]]);
        }

        /**
         * Show rotation controls for a frame
         * @param {number} frameId - Frame ID
         */
        showControls(frameId) {
            const handle = this.handles.find(h => h.frameId === frameId);
            
            if (!handle) return;
            
            handle.boxFeature.setStyle(this.styleConfig.createRotationBoxStyle());
            handle.handleFeature.setStyle(this.styleConfig.createRotationHandleStyle());
        }

        /**
         * Hide rotation controls for a frame
         * @param {number} frameId - Frame ID
         */
        hideControls(frameId) {
            const handle = this.handles.find(h => h.frameId === frameId);
            
            if (!handle) return;
            
            const emptyStyle = this.styleConfig.createEmptyStyle();
            handle.boxFeature.setStyle(emptyStyle);
            handle.handleFeature.setStyle(emptyStyle);
        }

        /**
         * Refresh all rotation handles (update positions)
         */
        refreshAll() {
            this.handles.forEach(handle => this.updateHandle(handle.frameId));
        }

        /**
         * Clear all rotation handles
         */
        clearAll() {
            if (this.overlayLayer) {
                this.overlayLayer.getSource().clear();
            }
            this.handles = [];
        }

        /**
         * Get overlay layer
         * @returns {ol.layer.Vector}
         */
        getOverlayLayer() {
            return this.overlayLayer;
        }

        /**
         * Check if currently rotating
         * @returns {boolean}
         */
        isCurrentlyRotating() {
            return this.isRotating;
        }

        /**
         * Destroy the controller and cleanup
         */
        destroy() {
            if (this.dragInteraction) {
                this.map.removeInteraction(this.dragInteraction);
                this.dragInteraction = null;
            }
            
            if (this.overlayLayer) {
                this.map.removeLayer(this.overlayLayer);
                this.overlayLayer = null;
            }
            
            this.handles = [];
        }
    }

    // Export to global namespace
    Mapbender.BatchPrintRotationController = BatchPrintRotationController;

})();
