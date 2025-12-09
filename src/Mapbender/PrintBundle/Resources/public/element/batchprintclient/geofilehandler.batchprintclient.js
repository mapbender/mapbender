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
    const MIN_OVERLAP = 0;
    const MAX_OVERLAP = 50;  // Limited to 50% to prevent exponential performance degradation
    const MIN_FRAMES = 2;  // Minimum frames to cover start and end points
    const COVERAGE_BUFFER = 1;  // Extra frame to ensure no gaps at track end
    
    /**
     * GeofileHandler for BatchPrintClient
     * Manages geospatial file upload and processing
     */
    class BatchPrintGeofileHandler {
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
         */
        constructor(options) {
            this.$element = options.$element;
            this.widget = options.widget;
            this.map = options.map;
            this.styleConfig = options.styleConfig;
            this.trackLayerZIndex = options.trackLayerZIndex;
            this.trackFitPadding = options.trackFitPadding || [100, 100, 100, 100];
            this.trackFitDuration = options.trackFitDuration || 500;
            this.trackFitMaxZoom = options.trackFitMaxZoom || 16;
            this.onFramePlaced = typeof options.onFramePlaced === 'function' ? options.onFramePlaced : () => {};
            
            // Track layer and features
            this.geofileLayer = null;
            this.geofileFeatures = [];
            
            // Setup event handlers
            this._setupEventHandlers();
        }

        /**
         * Setup event handlers for file upload controls
         * @private
         */
        _setupEventHandlers() {
            // Custom button click handler - triggers hidden file input
            $('.-fn-geofile-custom-button', this.$element).on('click', () => {
                $('.-fn-geofile-file-input', this.$element).trigger('click');
            });
            
            // File input change handler
            $('.-fn-geofile-file-input', this.$element).on('change', () => {
                const file = $('.-fn-geofile-file-input', this.$element)[0].files && $('.-fn-geofile-file-input', this.$element)[0].files[0];
                
                if (file) {
                    this.loadFile();
                } else {
                    this._updateStatus('');
                    this._hideButtons();
                }
            });
            
            // Clear file button handler
            $('.-fn-clear-geofile-button', this.$element).on('click', () => {
                this.clear();
            });
            
            // Place frames along track button handler
            $('.-fn-place-frames-button', this.$element).on('click', () => {
                this.placeFramesAlongTrack();
            });
        }

        /**
         * Load and display geospatial file on map
         */
        loadFile() {
        const $fileInput = $('.-fn-geofile-file-input', this.$element);
        
        if (!$fileInput.length || !$fileInput[0].files) {
            console.error('File input element not found or not accessible');
            Mapbender.warning(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.selectfile'));
            return;
        }        const file = $fileInput[0].files[0];
        
        if (!file) {
            Mapbender.info(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.selectfile'));
            return;
        }            // Show spinner during file load
            this._showSpinner();
            
            const map = this.map.getModel().olMap;
            const featureProjection = map.getView().getProjection().getCode();
            
            Mapbender.FileUtil.readGeospatialFile(file, {
                dataProjection: 'EPSG:4326',
                featureProjection: featureProjection,
                onSuccess: (features, file) => {
                    try {
                        this._renderTrackFeatures(features, file);
                        this._updateStatus(
                            Mapbender.trans('mb.print.printclient.batchprint.geofile.loaded') + ': ' + file.name,
                            'success'
                        );
                        this._showButtons();
                    } catch (error) {
                        this._handleError(error, file);
                    } finally {
                        this._hideSpinner();
                    }
                },
                onError: (error, file) => {
                    this._handleError(error, file);
                    this._hideSpinner();
                }
            });
        }

        /**
         * Render track features from uploaded file
         * @param {Array<ol.Feature>} features - Parsed features
         * @param {File} file - The uploaded file object
         * @private
         */
        _renderTrackFeatures(features, file) {
            const map = this.map.getModel().olMap;
            
            if (!features || features.length === 0) {
                throw new Error('No features found in file');
            }
            
            if (features.length !== 1) {
                throw new Error('File must contain exactly one feature (found ' + features.length + ')');
            }
            
            if (!features[0].getGeometry) {
                throw new Error('Invalid feature: missing getGeometry method');
            }
            
            const geometry = features[0].getGeometry();
            if (!geometry || geometry.getType() !== 'LineString') {
                const foundType = geometry ? geometry.getType() : 'no geometry';
                throw new Error('File must contain a LineString (found ' + foundType + ')');
            }
            
            // Clear existing layer
            this.clear();
            
            // Create new vector layer
            const source = new ol.source.Vector({
                features: features
            });
            
            this.geofileLayer = new ol.layer.Vector({
                source: source,
                style: (feature) => {
                    const geomType = feature.getGeometry().getType();
                    if (geomType === 'Point' || geomType === 'MultiPoint') {
                        return this.styleConfig.createTrackPointStyle();
                    } else {
                        return this.styleConfig.createTrackLineStyle();
                    }
                },
                zIndex: this.trackLayerZIndex
            });
            
            // Mark as internal layer
            this.geofileLayer.set('batchPrintClientInternal', true);
            
            // Add to map
            map.addLayer(this.geofileLayer);
            this.geofileFeatures = features;
            
            // Update UI state
            this._hideUploadButton();
            this._updateStatus(file.name, 'success');
            
            // Zoom to extent
            const extent = geometry.getExtent();
            if (!ol.extent.isEmpty(extent)) {
                map.getView().fit(extent, {
                    padding: this.trackFitPadding,
                    duration: this.trackFitDuration,
                    maxZoom: this.trackFitMaxZoom
                });
            }
        }

        /**
         * Place print frames along the track
         */
    placeFramesAlongTrack() {
        if (!this.geofileFeatures || this.geofileFeatures.length === 0) {
            Mapbender.warning(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.loadfirst'));
            return;
        }        const lineString = this.geofileFeatures[0].getGeometry();
        if (!lineString || lineString.getType() !== 'LineString') {
            Mapbender.error(Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.invalidgeometry'));
            return;
        }            // Show spinner during frame placement
            this._showSpinner();
            this._disablePlaceButton();
            
            // Use setTimeout to allow UI to update before processing
            setTimeout(() => {
                try {
                    // Get settings
                    const adjustFrames = $('.-fn-adjust-frames-checkbox', this.$element).is(':checked');
                    const overlapPercent = this._getOverlapPercentage();
                    
                    if (overlapPercent === null) {
                        return; // Validation failed
                    }
                    
                    // Calculate frame placement parameters
                    const params = this._calculateFramePlacement(lineString, overlapPercent);
                    
                    // Place frames
                    let previousRotation = null;
                    for (let i = 0; i < params.numFrames; i++) {
                        const distance = i * params.spacing;
                        const coord = Mapbender.GeometryUtil.getCoordinateAtDistance(lineString, distance);
                        
                        if (!coord) break;
                        
                        const bearing = adjustFrames ? Mapbender.GeometryUtil.getBearingAtDistance(lineString, distance) : null;
                        
                        // Callback to widget to place frame
                        previousRotation = this.onFramePlaced(coord, bearing, previousRotation);
                    }
                    
                    this._updatePlacementStatus(
                        Mapbender.trans('mb.print.printclient.batchprint.geofile.placed', {
                            count: params.numFrames
                        }),
                        'success'
                    );
                } finally {
                    this._hideSpinner();
                    this._enablePlaceButton();
                }
            }, 100);
        }

        /**
         * Get and validate overlap percentage from input
         * @returns {number|null} Overlap percentage or null if invalid
         * @private
         */
        _getOverlapPercentage() {
            let inputOverlap = parseFloat($('.-fn-frame-overlap-input', this.$element).val());
            
            if (isNaN(inputOverlap)) {
                inputOverlap = 10;
            }
            
            if (inputOverlap < MIN_OVERLAP || inputOverlap > MAX_OVERLAP) {
                const message = Mapbender.trans('mb.print.printclient.batchprint.overlap.outofbounds', {
                    value: inputOverlap,
                    min: MIN_OVERLAP,
                    max: MAX_OVERLAP
                });
                console.error('Invalid overlap value:', inputOverlap);
                Mapbender.error(message);
                return null;
            }
            
            return inputOverlap;
        }

        /**
         * Calculate frame placement parameters
         * @param {ol.geom.LineString} lineString - Track geometry
         * @param {number} overlapPercent - Overlap percentage
         * @returns {Object} Parameters {numFrames, spacing}
         * @private
         */
        _calculateFramePlacement(lineString, overlapPercent) {
            const templateWidth = this.widget.width;
            const templateHeight = this.widget.height;
            const scale = this.widget._getPrintScale();
            const unitsPerMeterAtFirstCoordinate = this.map.getModel().getUnitsPerMeterAt(lineString.getFirstCoordinate());
            
            // Calculate frame size in map units (use smaller dimension)
            const frameSize = Math.min(templateWidth, templateHeight) * scale * unitsPerMeterAtFirstCoordinate.h;
            
            // Calculate spacing based on overlap
            const totalLength = lineString.getLength();
            const spacingFactor = 1 - (overlapPercent / 100);
            const idealSpacing = frameSize * spacingFactor;
            
            // Calculate number of frames needed
            const numFrames = Math.max(MIN_FRAMES, Math.ceil(totalLength / idealSpacing) + COVERAGE_BUFFER);
            
            // Recalculate actual spacing to evenly distribute frames
            const actualSpacing = totalLength / (numFrames - 1);
            
            return {
                numFrames: numFrames,
                spacing: actualSpacing
            };
        }

        /**
         * Clear track layer and reset UI
         */
        clear() {
            if (this.geofileLayer) {
                const map = this.map.getModel().olMap;
                map.removeLayer(this.geofileLayer);
                this.geofileLayer = null;
                this.geofileFeatures = [];
            }
            
            // Reset UI
            $('.-fn-geofile-file-input', this.$element).val('');
            this._updateStatus('');
            this._updatePlacementStatus('');
            this._hideButtons();
            this._showUploadButton();
        }

        /**
         * Check if a track is loaded
         * @returns {boolean} True if track is loaded
         */
        hasTrack() {
            return this.geofileFeatures && this.geofileFeatures.length > 0;
        }

        /**
         * Get the track layer
         * @returns {ol.layer.Vector|null}
         */
        getLayer() {
            return this.geofileLayer;
        }

        /**
         * Update status message
         * @param {string} message - Status message
         * @param {string} type - Message type ('success', 'error', or empty)
         * @private
         */
        _updateStatus(message, type) {
            const $status = $('.-fn-geofile-status', this.$element);
            $status.text(message);
            
            if (type === 'success') {
                $status.addClass('text-success').removeClass('text-danger');
            } else if (type === 'error') {
                $status.addClass('text-danger').removeClass('text-success');
            } else {
                $status.removeClass('text-success text-danger');
            }
        }

        /**
         * Update placement status message (for "Placed X frames" message)
         * @param {string} message - Status message
         * @param {string} type - Message type ('success', 'error', or empty)
         * @private
         */
        _updatePlacementStatus(message, type) {
            const $status = $('.-fn-geofile-place-status', this.$element);
            $status.text(message);
            
            if (type === 'success') {
                $status.addClass('text-success').removeClass('text-danger');
            } else if (type === 'error') {
                $status.addClass('text-danger').removeClass('text-success');
            } else {
                $status.removeClass('text-success text-danger');
            }
        }

        /**
         * Show geofile control buttons
         * @private
         */
        _showButtons() {
            $('.-fn-geofile-controls', this.$element).addClass('show');
        }

        /**
         * Hide geofile control buttons
         * @private
         */
        _hideButtons() {
            $('.-fn-geofile-controls', this.$element).removeClass('show');
        }

        /**
         * Show upload button and hide status
         * @private
         */
        _showUploadButton() {
            $('.-fn-geofile-custom-button', this.$element).show();
        }

        /**
         * Hide upload button
         * @private
         */
        _hideUploadButton() {
            $('.-fn-geofile-custom-button', this.$element).hide();
        }

        /**
         * Show spinner
         * @private
         */
        _showSpinner() {
            $('.-fn-geofile-spinner', this.$element).show();
        }

        /**
         * Hide spinner
         * @private
         */
        _hideSpinner() {
            $('.-fn-geofile-spinner', this.$element).hide();
        }

        /**
         * Disable place frames button
         * @private
         */
        _disablePlaceButton() {
            $('.-fn-place-frames-button', this.$element).prop('disabled', true);
        }

        /**
         * Enable place frames button
         * @private
         */
        _enablePlaceButton() {
            $('.-fn-place-frames-button', this.$element).prop('disabled', false);
        }

        /**
         * Handle file loading errors
         * @param {Error} error - Error object
         * @param {File} file - File that failed to load
         * @private
         */
        _handleError(error, file) {
            const fileName = file ? file.name : 'unknown';
            console.error('Failed to load geospatial file:', fileName, error);
            
            const errorMessage = Mapbender.trans('mb.print.printclient.batchprint.geofile.alert.error') + ': ' + error.message;
            Mapbender.error(errorMessage);
            
            this._updateStatus(
                Mapbender.trans('mb.print.printclient.batchprint.geofile.error') + ': ' + error.message,
                'error'
            );
            this._hideButtons();
        }

        /**
         * Destroy the handler and cleanup
         */
        destroy() {
            this.clear();
            
            // Remove event handlers
            $('.-fn-geofile-file-input', this.$element).off('change');
            $('.-fn-clear-geofile-button', this.$element).off('click');
            $('.-fn-place-frames-button', this.$element).off('click');
        }
    }

    // Export to global namespace
    Mapbender.BatchPrintGeofileHandler = BatchPrintGeofileHandler;

})(jQuery);
