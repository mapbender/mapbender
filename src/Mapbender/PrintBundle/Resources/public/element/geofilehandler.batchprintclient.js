/**
 * GeofileHandler for BatchPrintClient
 * 
 * Manages geospatial file upload and processing including:
 * - File upload and validation (KML, GeoJSON, GPX, GML)
 * - Track rendering on map
 * - Frame placement along tracks with overlap calculation
 * - Track layer management
 */
(function($) {
    'use strict';

    window.Mapbender = window.Mapbender || {};
    
    // Constants
    var MIN_OVERLAP = 0;
    var MAX_OVERLAP = 100;
    var MIN_FRAMES = 2;  // Minimum frames to cover start and end points
    var COVERAGE_BUFFER = 1;  // Extra frame to ensure no gaps at track end
    
    /**
     * Creates a new GeofileHandler
     * @param {Object} options - Configuration options
     * @param {jQuery} options.$element - Widget root element
     * @param {Object} options.widget - Widget instance
     * @param {Object} options.map - Mapbender map widget
     * @param {Mapbender.BatchPrintStyleConfig} options.styleConfig - Style configuration
     * @param {number} options.trackLayerZIndex - Z-index for track layer
     * @param {Array} options.trackFitPadding - Padding for fit extent [top, right, bottom, left]
     * @param {number} options.trackFitDuration - Animation duration for fit extent
     * @param {number} options.trackFitMaxZoom - Maximum zoom level when fitting to track
     * @param {Function} options.onFramePlaced - Callback when a frame is placed (coord, bearing, previousRotation)
     * @constructor
     */
    Mapbender.BatchPrintGeofileHandler = function(options) {
        this.$element = options.$element;
        this.widget = options.widget;
        this.map = options.map;
        this.styleConfig = options.styleConfig;
        this.trackLayerZIndex = options.trackLayerZIndex;
        this.trackFitPadding = options.trackFitPadding || [100, 100, 100, 100];
        this.trackFitDuration = options.trackFitDuration || 500;
        this.trackFitMaxZoom = options.trackFitMaxZoom || 16;
        this.onFramePlaced = typeof options.onFramePlaced === 'function' ? options.onFramePlaced : function() {};
        
        // Track layer and features
        this.geofileLayer = null;
        this.geofileFeatures = [];
        
        // Setup event handlers
        this._setupEventHandlers();
    };

    /**
     * Setup event handlers for file upload controls
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._setupEventHandlers = function() {
        var self = this;
        
        // Custom button click handler - triggers hidden file input
        $('.-fn-geofile-custom-button', this.$element).on('click', function() {
            $('.-fn-geofile-file-input', self.$element).trigger('click');
        });
        
        // File input change handler
        $('.-fn-geofile-file-input', this.$element).on('change', function() {
            var file = this.files && this.files[0];
            
            if (file) {
                self.loadFile();
            } else {
                self._updateStatus('');
                self._hideButtons();
            }
        });
        
        // Clear file button handler
        $('.-fn-clear-geofile-button', this.$element).on('click', function() {
            self.clear();
        });
        
        // Place frames along track button handler
        $('.-fn-place-frames-button', this.$element).on('click', function() {
            self.placeFramesAlongTrack();
        });
    };

    /**
     * Load and display geospatial file on map
     */
    Mapbender.BatchPrintGeofileHandler.prototype.loadFile = function() {
        var $fileInput = $('.-fn-geofile-file-input', this.$element);
        
        if (!$fileInput.length || !$fileInput[0].files) {
            console.error('File input element not found or not accessible');
            alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.selectfile'));
            return;
        }
        
        var file = $fileInput[0].files[0];
        
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
                    self._updateStatus(
                        Mapbender.trans('mb.print.printclient.batchprint.geofile.loaded') + ': ' + file.name,
                        'success'
                    );
                    self._showButtons();
                } catch (error) {
                    self._handleError(error, file);
                }
            },
            onError: function(error, file) {
                self._handleError(error, file);
            }
        });
    };

    /**
     * Render track features from uploaded file
     * @param {Array<ol.Feature>} features - Parsed features
     * @param {File} file - The uploaded file object
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._renderTrackFeatures = function(features, file) {
        var self = this;
        var map = this.map.getModel().olMap;
        
        if (!features || features.length === 0) {
            throw new Error('No features found in file');
        }
        
        if (features.length !== 1) {
            throw new Error('File must contain exactly one feature (found ' + features.length + ')');
        }
        
        if (!features[0].getGeometry) {
            throw new Error('Invalid feature: missing getGeometry method');
        }
        
        var geometry = features[0].getGeometry();
        if (!geometry || geometry.getType() !== 'LineString') {
            var foundType = geometry ? geometry.getType() : 'no geometry';
            throw new Error('File must contain a LineString (found ' + foundType + ')');
        }
        
        // Clear existing layer
        this.clear();
        
        // Create new vector layer
        var source = new ol.source.Vector({
            features: features
        });
        
        this.geofileLayer = new ol.layer.Vector({
            source: source,
            style: function(feature) {
                var geomType = feature.getGeometry().getType();
                if (geomType === 'Point' || geomType === 'MultiPoint') {
                    return self.styleConfig.createTrackPointStyle();
                } else {
                    return self.styleConfig.createTrackLineStyle();
                }
            },
            zIndex: this.trackLayerZIndex
        });
        
        // Mark as internal layer
        this.geofileLayer.set('batchPrintClientInternal', true);
        
        // Add to map
        map.addLayer(this.geofileLayer);
        this.geofileFeatures = features;
        
        // Zoom to extent
        var extent = geometry.getExtent();
        if (!ol.extent.isEmpty(extent)) {
            map.getView().fit(extent, {
                padding: this.trackFitPadding,
                duration: this.trackFitDuration,
                maxZoom: this.trackFitMaxZoom
            });
        }
    };

    /**
     * Place print frames along the track
     */
    Mapbender.BatchPrintGeofileHandler.prototype.placeFramesAlongTrack = function() {
        if (!this.geofileFeatures || this.geofileFeatures.length === 0) {
            alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.loadfirst'));
            return;
        }
        
        var lineString = this.geofileFeatures[0].getGeometry();
        if (!lineString || lineString.getType() !== 'LineString') {
            alert(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.invalidgeometry'));
            return;
        }
        
        // Get settings
        var adjustFrames = $('.-fn-adjust-frames-checkbox', this.$element).is(':checked');
        var overlapPercent = this._getOverlapPercentage();
        
        if (overlapPercent === null) {
            return; // Validation failed
        }
        
        // Calculate frame placement parameters
        var params = this._calculateFramePlacement(lineString, overlapPercent);
        
        // Place frames
        var previousRotation = null;
        for (var i = 0; i < params.numFrames; i++) {
            var distance = i * params.spacing;
            var coord = Mapbender.GeometryUtil.getCoordinateAtDistance(lineString, distance);
            
            if (!coord) break;
            
            var bearing = adjustFrames ? Mapbender.GeometryUtil.getBearingAtDistance(lineString, distance) : null;
            
            // Callback to widget to place frame
            previousRotation = this.onFramePlaced(coord, bearing, previousRotation);
        }
        
        this._updateStatus(
            Mapbender.trans('mb.print.printclient.batchprint.geofile.placed', {
                count: params.numFrames
            }),
            'success'
        );
    };

    /**
     * Get and validate overlap percentage from input
     * @returns {number|null} Overlap percentage or null if invalid
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._getOverlapPercentage = function() {
        var inputOverlap = parseFloat($('.-fn-frame-overlap-input', this.$element).val());
        
        if (isNaN(inputOverlap)) {
            inputOverlap = 10;
        }
        
        if (inputOverlap < MIN_OVERLAP || inputOverlap > MAX_OVERLAP) {
            var message = Mapbender.trans('mb.print.printclient.batchprint.overlap.outofbounds', {
                value: inputOverlap,
                min: MIN_OVERLAP,
                max: MAX_OVERLAP
            });
            console.error('Invalid overlap value:', inputOverlap);
            Mapbender.error(message);
            return null;
        }
        
        return inputOverlap;
    };

    /**
     * Calculate frame placement parameters
     * @param {ol.geom.LineString} lineString - Track geometry
     * @param {number} overlapPercent - Overlap percentage
     * @returns {Object} Parameters {numFrames, spacing}
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._calculateFramePlacement = function(lineString, overlapPercent) {
        var templateWidth = this.widget.width;
        var templateHeight = this.widget.height;
        var scale = this.widget._getPrintScale();
        var unitsPerMeterAtFirstCoordinate = this.map.getModel().getUnitsPerMeterAt(lineString.getFirstCoordinate());
        
        // Calculate frame size in map units (use smaller dimension)
        var frameSize = Math.min(templateWidth, templateHeight) * scale * unitsPerMeterAtFirstCoordinate.h;
        
        // Calculate spacing based on overlap
        var totalLength = lineString.getLength();
        var spacingFactor = 1 - (overlapPercent / 100);
        var idealSpacing = frameSize * spacingFactor;
        
        // Calculate number of frames needed
        var numFrames = Math.max(MIN_FRAMES, Math.ceil(totalLength / idealSpacing) + COVERAGE_BUFFER);
        
        // Recalculate actual spacing to evenly distribute frames
        var actualSpacing = totalLength / (numFrames - 1);
        
        return {
            numFrames: numFrames,
            spacing: actualSpacing
        };
    };

    /**
     * Clear track layer and reset UI
     */
    Mapbender.BatchPrintGeofileHandler.prototype.clear = function() {
        if (this.geofileLayer) {
            var map = this.map.getModel().olMap;
            map.removeLayer(this.geofileLayer);
            this.geofileLayer = null;
            this.geofileFeatures = [];
        }
        
        // Reset UI
        $('.-fn-geofile-file-input', this.$element).val('');
        this._updateStatus('');
        this._hideButtons();
    };

    /**
     * Check if a track is loaded
     * @returns {boolean} True if track is loaded
     */
    Mapbender.BatchPrintGeofileHandler.prototype.hasTrack = function() {
        return this.geofileFeatures && this.geofileFeatures.length > 0;
    };

    /**
     * Get the track layer
     * @returns {ol.layer.Vector|null}
     */
    Mapbender.BatchPrintGeofileHandler.prototype.getLayer = function() {
        return this.geofileLayer;
    };

    /**
     * Update status message
     * @param {string} message - Status message
     * @param {string} type - Message type ('success', 'error', or empty)
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._updateStatus = function(message, type) {
        var $status = $('.-fn-geofile-status', this.$element);
        $status.text(message);
        
        if (type === 'success') {
            $status.addClass('text-success').removeClass('text-danger');
        } else if (type === 'error') {
            $status.addClass('text-danger').removeClass('text-success');
        } else {
            $status.removeClass('text-success text-danger');
        }
    };

    /**
     * Show geofile control buttons
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._showButtons = function() {
        $('.-fn-geofile-controls', this.$element).addClass('show');
    };

    /**
     * Hide geofile control buttons
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._hideButtons = function() {
        $('.-fn-geofile-controls', this.$element).removeClass('show');
    };

    /**
     * Handle file loading errors
     * @param {Error} error - Error object
     * @param {File} file - File that failed to load
     * @private
     */
    Mapbender.BatchPrintGeofileHandler.prototype._handleError = function(error, file) {
        var fileName = file ? file.name : 'unknown';
        console.error('Failed to load geospatial file:', fileName, error);
        
        var errorMessage = Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.error') + ': ' + error.message;
        alert(errorMessage);
        
        this._updateStatus(
            Mapbender.trans('mb.print.printclient.batchprint.geofile.error') + ': ' + error.message,
            'error'
        );
        this._hideButtons();
    };

    /**
     * Destroy the handler and cleanup
     */
    Mapbender.BatchPrintGeofileHandler.prototype.destroy = function() {
        this.clear();
        
        // Remove event handlers
        $('.-fn-geofile-file-input', this.$element).off('change');
        $('.-fn-clear-geofile-button', this.$element).off('click');
        $('.-fn-place-frames-button', this.$element).off('click');
    };

})(jQuery);
