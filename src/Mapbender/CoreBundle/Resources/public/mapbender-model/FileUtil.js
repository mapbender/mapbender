window.Mapbender = Mapbender || {};

/**
 * File utilities for geospatial file detection and parsing
 */
window.Mapbender.FileUtil = class {
    /**
     * Get appropriate OpenLayers format parser based on file name or extension
     * @param {string} filename - Name of the file
     * @returns {ol.format|null} OpenLayers format parser instance, or null if unsupported
     */
    static getFormatParserByFilename(filename) {
        var extension = filename.split('.').pop().toLowerCase();
        
        switch (extension) {
            case 'kml':
                return new ol.format.KML({
                    extractStyles: true,
                    showPointNames: false
                });
                
            case 'geojson':
            case 'json':
                return new ol.format.GeoJSON();
                
            case 'gpx':
                return new ol.format.GPX();
                
            case 'gml':
            case 'xml':
                // GML format requires content inspection - use agnostic parser
                return {
                    readFeatures: function(content, options) {
                        return Mapbender.FileUtil.findGmlFormat(content).readFeatures(content, options);
                    }
                };
                
            default:
                // Unsupported format
                return null;
        }
    }
    
    /**
     * Detect the correct GML format by trying to parse with different versions
     * @param {string} gmlContent - The GML content as text
     * @returns {ol.format.GML|ol.format.GML2|ol.format.GML3|ol.format.GML32} The appropriate GML format
     * @throws {Error} If no GML format can parse the content
     */
    static findGmlFormat(gmlContent) {
        var gmlFormats = [
            new ol.format.GML(),
            new ol.format.GML2(),
            new ol.format.GML3(),
            new ol.format.GML32()
        ];
        
        for (var i = 0; i < gmlFormats.length; i++) {
            var format = gmlFormats[i];
            try {
                var features = format.readFeatures(gmlContent);
                if (features.length > 0) {
                    var geometry = features[0].getGeometry();
                    if (geometry && geometry.getCoordinates && geometry.getCoordinates().length > 0) {
                        return format;
                    }
                }
            } catch (e) {
                // Try next format
                continue;
            }
        }
        
        throw new Error('Could not detect GML format version');
    }
    
    /**
     * Read and parse features from a geospatial file
     * @param {File} file - File object from input
     * @param {Object} options - Configuration options
     * @param {string} options.dataProjection - Source projection (default: 'EPSG:4326')
     * @param {string} options.featureProjection - Target projection for features
     * @param {Function} options.onSuccess - Callback on success: function(features, file)
     * @param {Function} options.onError - Callback on error: function(error, file)
     */
    static readGeospatialFile(file, options) {
        var parser = this.getFormatParserByFilename(file.name);
        
        if (!parser) {
            if (options.onError) {
                options.onError(new Error('Unsupported file format'), file);
            }
            return;
        }
        
        var reader = new FileReader();
        
        reader.addEventListener('load', function() {
            try {
                var features = parser.readFeatures(reader.result, {
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
