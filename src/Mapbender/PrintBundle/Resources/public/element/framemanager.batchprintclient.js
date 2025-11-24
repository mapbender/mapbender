/**
 * FrameManager for BatchPrintClient
 * 
 * Manages frame data including:
 * - CRUD operations (Create, Read, Update, Delete)
 * - Frame validation and ordering
 * - Frame data storage with Map for O(1) lookups
 * - Frame counter management
 */
(function() {
    'use strict';

    window.Mapbender = window.Mapbender || {};
    
    /**
     * Creates a new FrameManager
     * @param {Object} options - Configuration options
     * @param {Object} options.styleConfig - Style configuration for frames
     * @param {Function} options.onFrameAdded - Callback when frame is added
     * @param {Function} options.onFrameRemoved - Callback when frame is removed
     * @param {Function} options.onFramesCleared - Callback when all frames are cleared
     * @constructor
     */
    Mapbender.BatchPrintFrameManager = function(options) {
        options = options || {};
        
        this.styleConfig = options.styleConfig;
        this.onFrameAdded = typeof options.onFrameAdded === 'function' ? options.onFrameAdded : function() {};
        this.onFrameRemoved = typeof options.onFrameRemoved === 'function' ? options.onFrameRemoved : function() {};
        this.onFramesCleared = typeof options.onFramesCleared === 'function' ? options.onFramesCleared : function() {};
        
        // Data storage
        this.frames = [];  // Ordered array for iteration and display
        this.framesMap = new Map();  // Map for O(1) lookups by ID
        this.frameCounter = 0;
    };

    /**
     * Get all frames in order
     * @returns {Array} Array of frame data objects
     */
    Mapbender.BatchPrintFrameManager.prototype.getFrames = function() {
        return this.frames;
    };

    /**
     * Get frame by ID
     * @param {number} frameId - Frame ID
     * @returns {Object|null} Frame data object or null if not found
     */
    Mapbender.BatchPrintFrameManager.prototype.getFrame = function(frameId) {
        return this.framesMap.get(frameId) || null;
    };

    /**
     * Get frame by feature reference
     * @param {ol.Feature} feature - OpenLayers feature
     * @returns {Object|null} Frame data or null if not found
     */
    Mapbender.BatchPrintFrameManager.prototype.getFrameByFeature = function(feature) {
        for (var i = 0; i < this.frames.length; i++) {
            if (this.frames[i].feature === feature) {
                return this.frames[i];
            }
        }
        return null;
    };

    /**
     * Get total number of frames
     * @returns {number} Frame count
     */
    Mapbender.BatchPrintFrameManager.prototype.getCount = function() {
        return this.frames.length;
    };

    /**
     * Add a new frame
     * @param {Object} frameData - Frame data object
     * @param {ol.Feature} frameData.feature - OpenLayers feature
     * @param {number} frameData.rotation - Rotation in degrees
     * @param {number} frameData.scale - Print scale
     * @param {Array} frameData.center - Center coordinates [x, y]
     * @param {string} frameData.template - Template value
     * @param {string} frameData.templateLabel - Template display label
     * @param {string} frameData.quality - Quality setting
     * @param {Object} frameData.extent - Extent dimensions {width, height}
     * @returns {Object} The added frame data with assigned ID
     */
    Mapbender.BatchPrintFrameManager.prototype.addFrame = function(frameData) {
        this.frameCounter++;
        frameData.id = this.frameCounter;
        
        this.frames.push(frameData);
        this.framesMap.set(frameData.id, frameData);
        
        this.onFrameAdded(frameData);
        
        return frameData;
    };

    /**
     * Remove a frame by ID
     * @param {number} frameId - Frame ID to remove
     * @returns {Object|null} The removed frame data or null if not found
     */
    Mapbender.BatchPrintFrameManager.prototype.removeFrame = function(frameId) {
        var frameData = this.framesMap.get(frameId);
        
        if (!frameData) {
            return null;
        }
        
        // Remove from array
        this.frames = this.frames.filter(function(f) {
            return f.id !== frameId;
        });
        
        // Remove from map
        this.framesMap.delete(frameId);
        
        this.onFrameRemoved(frameData);
        
        return frameData;
    };

    /**
     * Clear all frames
     */
    Mapbender.BatchPrintFrameManager.prototype.clear = function() {
        var hadFrames = this.frames.length > 0;
        
        this.frames = [];
        this.framesMap.clear();
        this.frameCounter = 0;
        
        if (hadFrames) {
            this.onFramesCleared();
        }
    };

    /**
     * Reorder frames to match a new order of frame IDs
     * @param {Array<number>} orderedIds - Array of frame IDs in desired order
     * @returns {boolean} True if reordering was successful
     */
    Mapbender.BatchPrintFrameManager.prototype.reorder = function(orderedIds) {
        if (orderedIds.length !== this.frames.length) {
            console.error('Reorder failed: ID count mismatch');
            return false;
        }
        
        var reorderedFrames = [];
        for (var i = 0; i < orderedIds.length; i++) {
            var frameId = orderedIds[i];
            var frameData = this.framesMap.get(frameId);
            
            if (!frameData) {
                console.error('Reorder failed: Frame ID not found', frameId);
                return false;
            }
            
            reorderedFrames.push(frameData);
        }
        
        this.frames = reorderedFrames;
        return true;
    };

    /**
     * Update a frame's data
     * @param {number} frameId - Frame ID
     * @param {Object} updates - Object with properties to update
     * @returns {boolean} True if update was successful
     */
    Mapbender.BatchPrintFrameManager.prototype.updateFrame = function(frameId, updates) {
        var frameData = this.framesMap.get(frameId);
        
        if (!frameData) {
            return false;
        }
        
        // Apply updates
        for (var key in updates) {
            if (updates.hasOwnProperty(key)) {
                frameData[key] = updates[key];
            }
        }
        
        return true;
    };

    /**
     * Get the next frame counter value (without incrementing)
     * @returns {number} Next frame ID
     */
    Mapbender.BatchPrintFrameManager.prototype.getNextId = function() {
        return this.frameCounter + 1;
    };

    /**
     * Validate frame data structure
     * @param {Object} frameData - Frame data to validate
     * @returns {Object} Validation result {valid: boolean, errors: Array<string>}
     */
    Mapbender.BatchPrintFrameManager.prototype.validateFrame = function(frameData) {
        var errors = [];
        
        if (!frameData) {
            errors.push('Frame data is null or undefined');
            return {valid: false, errors: errors};
        }
        
        if (!frameData.feature) {
            errors.push('Missing feature');
        }
        
        if (typeof frameData.rotation !== 'number') {
            errors.push('Invalid rotation value');
        }
        
        if (typeof frameData.scale !== 'number' || frameData.scale <= 0) {
            errors.push('Invalid scale value');
        }
        
        if (!Array.isArray(frameData.center) || frameData.center.length !== 2) {
            errors.push('Invalid center coordinates');
        }
        
        if (!frameData.template) {
            errors.push('Missing template');
        }
        
        if (!frameData.extent || typeof frameData.extent.width !== 'number' || typeof frameData.extent.height !== 'number') {
            errors.push('Invalid extent');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    };

})();
