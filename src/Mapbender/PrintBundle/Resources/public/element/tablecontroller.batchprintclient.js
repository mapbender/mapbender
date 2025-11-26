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
     * @constructor
     */
    Mapbender.BatchPrintTableController = function(options) {
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
        this.onDeleteFrame = typeof options.onDeleteFrame === 'function' ? options.onDeleteFrame : function() {};
        this.onFrameReorder = typeof options.onFrameReorder === 'function' ? options.onFrameReorder : function() {};
        
        // Map hover handler reference
        this.mapHoverHandler = null;
    };

    /**
     * Highlight a pinned feature on the map
     * @param {number} frameId - Frame ID to highlight
     */
    Mapbender.BatchPrintTableController.prototype.highlightFeature = function(frameId) {
        var frameData = this.frameManager.getFrame(frameId);
        
        if (frameData) {
            frameData.feature.setStyle(this.styleConfig.createHighlightedFrameStyle(frameData.feature));
        }
    };

    /**
     * Remove highlight from a pinned feature
     * @param {number} frameId - Frame ID to unhighlight
     */
    Mapbender.BatchPrintTableController.prototype.unhighlightFeature = function(frameId) {
        var frameData = this.frameManager.getFrame(frameId);
        
        if (frameData) {
            var defaultStyle = this.getDefaultStyle ? this.getDefaultStyle() : null;
            frameData.feature.setStyle(defaultStyle);
        }
    };

    /**
     * Update the frame tracking table UI
     */
    Mapbender.BatchPrintTableController.prototype.updateTable = function() {
        var $tbody = $('.-fn-frame-table tbody', this.$element);
        
        if (!$tbody.length) {
            console.error('Frame table tbody not found');
            return;
        }
        
        $tbody.empty();
        
        // Show/hide delete all button and toggle empty state based on whether there are frames
        var $deleteAllBtn = $('.-fn-delete-all-frames', this.$element);
        var $emptyState = $('.-fn-frame-table-empty', this.$element);
        var $tableContent = $('.-fn-frame-table-content', this.$element);
        
        var frames = this.frameManager.getFrames();
        if (frames.length > 0) {
            $deleteAllBtn.show();
            $emptyState.hide();
            $tableContent.show();
        } else {
            $deleteAllBtn.hide();
            $emptyState.show();
            $tableContent.hide();
        }
        
        var self = this;
        frames.forEach(function(frameData, index) {
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
            // Normalize to -180 to +180 range for display
            var rotation = frameData.rotation;
            if (rotation > 180) {
                rotation = rotation - 360;
            }
            var rotationText = Math.round(rotation * 10) / 10 + '°';
            $row.append($('<td></td>').text(rotationText));
            
            // Delete button
            var $deleteCell = $('<td></td>');
            var $deleteIcon = $('<i class="fa fa-trash"></i>');
            $deleteIcon.attr('title', 'Delete frame');
            $deleteIcon.on('click', function(e) {
                e.stopPropagation();
                self.onDeleteFrame(frameData.id);
            });
            $deleteCell.append($deleteIcon);
            $row.append($deleteCell);
            
            // Add hover handlers for highlighting
            $row.on('mouseenter', function() {
                self.highlightFeature(frameData.id);
                self.rotationController.showControls(frameData.id);
                $(this).addClass('highlight');
            });
            
            $row.on('mouseleave', function() {
                self.unhighlightFeature(frameData.id);
                self.rotationController.hideControls(frameData.id);
                $(this).removeClass('highlight');
            });
            
            $tbody.append($row);
        });
        
        // Initialize sortable functionality for table rows
        this._initSortable();
        
        // Add map hover handlers for highlighting table rows
        this._setupMapHoverHandlers();
    };

    /**
     * Initialize sortable functionality for frame table rows
     * @private
     */
    Mapbender.BatchPrintTableController.prototype._initSortable = function() {
        var self = this;
        var $tbody = $('.-fn-frame-table tbody', this.$element);
        
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
                self._handleReorder();
            }
        });
    };

    /**
     * Handle reordering of frame table rows
     * @private
     */
    Mapbender.BatchPrintTableController.prototype._handleReorder = function() {
        var $tbody = $('.-fn-frame-table tbody', this.$element);
        
        // Get new order of frame IDs from table rows
        var newOrder = [];
        $tbody.find('tr').each(function() {
            var frameId = parseInt($(this).attr('data-frame-id'));
            newOrder.push(frameId);
        });
        
        // Notify widget of reorder
        this.onFrameReorder(newOrder);
        
        // Update frame numbers in table to reflect new order
        $tbody.find('tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    };

    /**
     * Setup hover handlers on map features to highlight table rows
     * A frame is considered "entered" when mouse is over the feature itself OR its rotation controls
     * @private
     */
    Mapbender.BatchPrintTableController.prototype._setupMapHoverHandlers = function() {
        var self = this;
        var map = this.map.getModel().olMap;
        
        // Remove existing handler if present
        if (this.mapHoverHandler) {
            map.un('pointermove', this.mapHoverHandler);
        }
        
        this.mapHoverHandler = function(evt) {
            var enteredFrameIds = [];
            var frames = self.frameManager.getFrames();
            
            // Only check for frames if we have any
            if (frames.length === 0) {
                return;
            }
            
            // Get coordinate for geometry-based checks
            var coordinate = evt.coordinate;
            
            // Check if cursor is inside any frame feature (polygon contains point)
            try {
                var layerBridge = Mapbender.vectorLayerPool.getElementLayer(self.widget, self.pinnedFramesLayer);
                
                // Check each frame to see if coordinate is inside its polygon
                frames.forEach(function(frameData) {
                    var geometry = frameData.feature.getGeometry();
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
            var rotationOverlay = self.rotationController.getOverlayLayer();
            if (rotationOverlay) {
                var overlaySource = rotationOverlay.getSource();
                var overlayFeatures = overlaySource.getFeatures();
                
                overlayFeatures.forEach(function(feature) {
                    var featureType = feature.get('type');
                    if (featureType === 'rotation-box') {
                        var frameId = feature.get('frameId');
                        var geometry = feature.getGeometry();
                        
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
            $('.-fn-frame-table tbody tr', self.$element).removeClass('highlight');
            
            frames.forEach(function(frameData) {
                var isEntered = enteredFrameIds.indexOf(frameData.id) !== -1;
                
                if (isEntered) {
                    // Show controls and highlight when mouse is over feature or its controls
                    self.rotationController.showControls(frameData.id);
                    self.highlightFeature(frameData.id);
                    var $row = $('.-fn-frame-table tbody tr[data-frame-id="' + frameData.id + '"]', self.$element);
                    $row.addClass('highlight');
                } else {
                    // Hide controls and remove highlight when mouse leaves
                    self.rotationController.hideControls(frameData.id);
                    self.unhighlightFeature(frameData.id);
                }
            });
        };
        
        map.on('pointermove', this.mapHoverHandler);
    };

    /**
     * Cleanup handler when destroying controller
     */
    Mapbender.BatchPrintTableController.prototype.destroy = function() {
        if (this.mapHoverHandler) {
            var map = this.map.getModel().olMap;
            map.un('pointermove', this.mapHoverHandler);
            this.mapHoverHandler = null;
        }
        
        // Destroy sortable if exists
        var $tbody = $('.-fn-frame-table tbody', this.$element);
        if ($tbody.hasClass('ui-sortable')) {
            $tbody.sortable('destroy');
        }
    };

})(jQuery);
