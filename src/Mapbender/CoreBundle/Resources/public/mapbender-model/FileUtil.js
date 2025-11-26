window.Mapbender = Mapbender || {};

/**
 * File utilities for geospatial file detection and parsing
 */
window.Mapbender.FileUtil = (function() {
    'use strict';
    
    var methods = {
        /**
         * Get appropriate OpenLayers format parser based on file name or extension
         * @param {string} filename - Name of the file
         * @returns {Object|null} Object with parser (ol.format instance) and readMethod ('text' or 'arraybuffer'), or null if unsupported
         */
        getFormatParserByFilename: function(filename) {
            var extension = filename.split('.').pop().toLowerCase();
            
            switch (extension) {
                case 'kml':
                    return {
                        parser: new ol.format.KML({
                            extractStyles: true,
                            showPointNames: false
                        }),
                        readMethod: 'text'
                    };
                    
                case 'geojson':
                case 'json':
                    return {
                        parser: new ol.format.GeoJSON(),
                        readMethod: 'text'
                    };
                    
                case 'gpx':
                    return {
                        parser: new ol.format.GPX(),
                        readMethod: 'text'
                    };
                    
                case 'gml':
                case 'xml':
                    // Note: GML format detection may require content inspection for optimal results
                    // Default to GML3 as it's most common
                    return {
                        parser: new ol.format.GML3(),
                        readMethod: 'text'
                    };
                    
                default:
                    // Unsupported format
                    return null;
            }
        },
        
        /**
         * Read and parse features from a geospatial file
         * @param {File} file - File object from input
         * @param {Object} options - Configuration options
         * @param {string} options.dataProjection - Source projection (default: 'EPSG:4326')
         * @param {string} options.featureProjection - Target projection for features
         * @param {Function} options.onSuccess - Callback on success: function(features, file)
         * @param {Function} options.onError - Callback on error: function(error, file)
         */
        readGeospatialFile: function(file, options) {
            var formatInfo = this.getFormatParserByFilename(file.name);
            
            if (!formatInfo) {
                if (options.onError) {
                    options.onError(new Error('Unsupported file format'), file);
                }
                return;
            }
            
            var reader = new FileReader();
            
            reader.addEventListener('load', function() {
                try {
                    var features = formatInfo.parser.readFeatures(reader.result, {
                        dataProjection: options.dataProjection || 'EPSG:4326',
                        featureProjection: options.featureProjection
                    });
                    
                    if (options.onSuccess) {
                        options.onSuccess(features, file);
                    }
                } catch (error) {
                    if (options.onError) {
                        options.onError(error, file);
                    }
                }
            });
            
            reader.addEventListener('error', function() {
                if (options.onError) {
                    options.onError(new Error('Failed to read file'), file);
                }
            });
            
            // All supported formats use text
            reader.readAsText(file);
        }
    };
    
    return methods;
}());
