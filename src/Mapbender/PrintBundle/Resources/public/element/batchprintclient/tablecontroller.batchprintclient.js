/**
 * TableController for BatchPrintClient
 * 
 * Manages frame table UI including:
 * - Table rendering and updates
 * - Row sorting and reordering
 * - Feature highlighting on hover
 * - Map hover handlers for table row highlighting
 */
(function($) {
    'use strict';

    window.Mapbender = window.Mapbender || {};
    
    /**
     * TableController for BatchPrintClient
     * Manages frame table UI
     */
    class BatchPrintTableController {
        /**
         * Creates a new TableController
         * @param {Object} options - Configuration options
         * @param {jQuery} options.$element - Widget root element
         * @param {Object} options.widget - Widget instance (for layer access)
         * @param {Mapbender.BatchPrintStyleConfig} options.styleConfig - Style configuration
         * @param {Mapbender.BatchPrintFrameManager} options.frameManager - Frame manager instance
         * @param {Mapbender.BatchPrintRotationController} options.rotationController - Rotation controller instance
         * @param {Object} options.map - Mapbender map widget
         * @param {number} options.pinnedFramesLayer - Layer index for pinned frames
         * @param {number} options.hitToleranceFrame - Hit tolerance for frames
         * @param {number} options.hitToleranceRotation - Hit tolerance for rotation controls
         * @param {Function} options.getDefaultStyle - Callback to get default frame style
         * @param {Function} options.onDeleteFrame - Callback when frame is deleted (frameId)
         * @param {Function} options.onFrameReorder - Callback when frames are reordered (newOrder array)
         */
        constructor(options) {
            this.$element = options.$element;
            this.widget = options.widget;
            this.styleConfig = options.styleConfig;
            this.frameManager = options.frameManager;
            this.rotationController = options.rotationController;
            this.map = options.map;
            this.pinnedFramesLayer = options.pinnedFramesLayer;
            this.hitToleranceFrame = options.hitToleranceFrame;
            this.hitToleranceRotation = options.hitToleranceRotation;
            this.getDefaultStyle = typeof options.getDefaultStyle === 'function' ? options.getDefaultStyle : null;
            this.onDeleteFrame = typeof options.onDeleteFrame === 'function' ? options.onDeleteFrame : () => {};
            this.onFrameReorder = typeof options.onFrameReorder === 'function' ? options.onFrameReorder : () => {};
            
            // Map hover handler reference
            this.mapHoverHandler = null;
        }

        /**
         * Highlight a pinned feature on the map
         * @param {number} frameId - Frame ID to highlight
         */
        highlightFeature(frameId) {
            const frameData = this.frameManager.getFrame(frameId);
            
            if (frameData) {
                frameData.feature.setStyle(this.styleConfig.createHighlightedFrameStyle(frameData.feature));
            }
        }

        /**
         * Remove highlight from a pinned feature
         * @param {number} frameId - Frame ID to unhighlight
         */
        unhighlightFeature(frameId) {
            const frameData = this.frameManager.getFrame(frameId);
            
            if (frameData) {
                // Reset to default style (thin black border)
                const defaultStyle = this.getDefaultStyle ? this.getDefaultStyle() : null;
                frameData.feature.setStyle(defaultStyle);
            }
        }

        /**
         * Update the frame tracking table UI
         */
        updateTable() {
            const $tbody = $('.-fn-frame-table tbody', this.$element);
            
            if (!$tbody.length) {
                console.error('Frame table tbody not found');
                return;
            }
            
            $tbody.empty();
            
            // Show/hide delete all button wrapper and toggle empty state based on whether there are frames
            const $deleteAllBtnWrapper = $('.-fn-frame-table .frame-table-buttons', this.$element);
            const $emptyState = $('.-fn-frame-table-empty', this.$element);
            const $tableContent = $('.-fn-frame-table-content', this.$element);
            
            const frames = this.frameManager.getFrames();
            if (frames.length > 0) {
                $deleteAllBtnWrapper.show();
                $emptyState.hide();
                $tableContent.show();
            } else {
                $deleteAllBtnWrapper.hide();
                $emptyState.show();
                $tableContent.hide();
            }
            
            frames.forEach((frameData, index) => {
                const $row = $('<tr></tr>');
                $row.attr('data-frame-id', frameData.id);
                
                // Frame number (display order position, not ID)
                $row.append($('<td></td>').text(index + 1));
                
                // Scale
                $row.append($('<td></td>').text('1:' + frameData.scale));
                
                // Template - use stored label or fall back to template name formatting
                const templateDisplay = frameData.templateLabel || frameData.template || '';
                $row.append($('<td></td>').text(templateDisplay));
                
                // Quality (DPI)
                $row.append($('<td></td>').text(frameData.quality + ' dpi'));
                
                // Rotation (rounded to 1 decimal place)
                // Normalize to -180 to +180 range for display
                let rotation = frameData.rotation;
                if (rotation > 180) {
                    rotation = rotation - 360;
                }
                const rotationText = Math.round(rotation * 10) / 10;
                $row.append($('<td></td>').text(rotationText));
                
                // Delete button
                const $deleteCell = $('<td></td>');
                const $deleteIcon = $('<i class="fa far fa-trash-can clickable hover-highlight-effect ms-2"></i>');
                $deleteIcon.attr('title', 'Delete frame');
                $deleteIcon.on('click', (e) => {
                    e.stopPropagation();
                    this.onDeleteFrame(frameData.id);
                });
                $deleteCell.append($deleteIcon);
                $row.append($deleteCell);
                
                // Add hover handlers for highlighting
                $row.on('mouseenter', () => {
                    this.highlightFeature(frameData.id);
                    this.rotationController.showControls(frameData.id);
                    $row.addClass('highlight');
                });
                
                $row.on('mouseleave', () => {
                    this.unhighlightFeature(frameData.id);
                    this.rotationController.hideControls(frameData.id);
                    $row.removeClass('highlight');
                });
                
                $tbody.append($row);
            });
            
            // Initialize sortable functionality for table rows
            this._initSortable();
            
            // Add map hover handlers for highlighting table rows
            this._setupMapHoverHandlers();
        }

        /**
         * Initialize sortable functionality for frame table rows
         * @private
         */
        _initSortable() {
            const $tbody = $('.-fn-frame-table tbody', this.$element);
            
            // Destroy existing sortable if it exists
            if ($tbody.hasClass('ui-sortable')) {
                $tbody.sortable('destroy');
            }
            
            // Initialize jQuery UI sortable
            $tbody.sortable({
                axis: 'y',
                cursor: 'move',
                handle: 'td',
                helper: (e, tr) => {
                    const $originals = tr.children();
                    const $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                start: (e, ui) => {
                    // Add visual feedback during drag
                    ui.item.addClass('dragging');
                },
                stop: (e, ui) => {
                    // Remove visual feedback
                    ui.item.removeClass('dragging');
                },
                update: (e, ui) => {
                    this._handleReorder();
                }
            });
        }

        /**
         * Handle reordering of frame table rows
         * @private
         */
        _handleReorder() {
            const $tbody = $('.-fn-frame-table tbody', this.$element);
            
            // Get new order of frame IDs from table rows
            const newOrder = [];
            $tbody.find('tr').each(function() {
                const frameId = parseInt($(this).attr('data-frame-id'));
                newOrder.push(frameId);
            });
            
            // Notify widget of reorder
            this.onFrameReorder(newOrder);
            
            // Update frame numbers in table to reflect new order
            $tbody.find('tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        }

        /**
         * Setup hover handlers on map features to highlight table rows
         * A frame is considered "entered" when mouse is over the feature itself OR its rotation controls
         * @private
         */
        _setupMapHoverHandlers() {
            const map = this.map.getModel().olMap;
            
            // Remove existing handler if present
            if (this.mapHoverHandler) {
                map.un('pointermove', this.mapHoverHandler);
            }
            
            this.mapHoverHandler = (evt) => {
                const enteredFrameIds = [];
                const frames = this.frameManager.getFrames();
                
                // Only check for frames if we have any
                if (frames.length === 0) {
                    return;
                }
                
                // Get coordinate for geometry-based checks
                const coordinate = evt.coordinate;
                
                // Check if cursor is inside any frame feature (polygon contains point)
                try {
                    const layerBridge = Mapbender.vectorLayerPool.getElementLayer(this.widget, this.pinnedFramesLayer);
                    
                    // Check each frame to see if coordinate is inside its polygon
                    frames.forEach(frameData => {
                        const geometry = frameData.feature.getGeometry();
                        if (geometry && geometry.intersectsCoordinate(coordinate)) {
                            if (enteredFrameIds.indexOf(frameData.id) === -1) {
                                enteredFrameIds.push(frameData.id);
                            }
                        }
                    });
                } catch (e) {
                    // Layer not yet initialized, skip frame detection
                    return;
                }
                
                // Check if cursor is inside any rotation control bounding box
                const rotationOverlay = this.rotationController.getOverlayLayer();
                if (rotationOverlay) {
                    const overlaySource = rotationOverlay.getSource();
                    const overlayFeatures = overlaySource.getFeatures();
                    
                    overlayFeatures.forEach(feature => {
                        const featureType = feature.get('type');
                        if (featureType === 'rotation-box') {
                            const frameId = feature.get('frameId');
                            const geometry = feature.getGeometry();
                            
                            // Check if coordinate is inside the rotation box polygon
                            if (geometry.intersectsCoordinate(coordinate)) {
                                if (enteredFrameIds.indexOf(frameId) === -1) {
                                    enteredFrameIds.push(frameId);
                                }
                            }
                        }
                    });
                }
                
                // Update visibility and highlighting for all frames
                $('.-fn-frame-table tbody tr', this.$element).removeClass('highlight');
                
                frames.forEach(frameData => {
                    const isEntered = enteredFrameIds.indexOf(frameData.id) !== -1;
                    
                    if (isEntered) {
                        // Show controls and highlight when mouse is over feature or its controls
                        this.rotationController.showControls(frameData.id);
                        this.highlightFeature(frameData.id);
                        const $row = $('.-fn-frame-table tbody tr[data-frame-id="' + frameData.id + '"]', this.$element);
                        $row.addClass('highlight');
                    } else {
                        // Hide controls and remove highlight when mouse leaves
                        this.rotationController.hideControls(frameData.id);
                        this.unhighlightFeature(frameData.id);
                    }
                });
            };
            
            map.on('pointermove', this.mapHoverHandler);
        }

        /**
         * Cleanup handler when destroying controller
         */
        destroy() {
            if (this.mapHoverHandler) {
                const map = this.map.getModel().olMap;
                map.un('pointermove', this.mapHoverHandler);
                this.mapHoverHandler = null;
            }
            
            // Destroy sortable if exists
            const $tbody = $('.-fn-frame-table tbody', this.$element);
            if ($tbody.hasClass('ui-sortable')) {
                $tbody.sortable('destroy');
            }
        }
    }

    // Export to global namespace
    Mapbender.BatchPrintTableController = BatchPrintTableController;

})(jQuery);
