window.Mapbender = Mapbender || {};
window.Mapbender.GeometryUtil = (function() {
    'use strict';

    var methods = {
        /**
         * Get coordinate at a specific distance along a LineString
         * 
         * @param {ol.geom.LineString} lineString - OpenLayers LineString geometry
         * @param {number} distance - Distance along the line in map units
         * @returns {Array<number>|null} Coordinate [x, y] at the specified distance, or last coordinate if distance exceeds line length
         */
        getCoordinateAtDistance: function(lineString, distance) {
            var coordinates = lineString.getCoordinates();
            var currentDistance = 0;
            
            for (var i = 0; i < coordinates.length - 1; i++) {
                var segmentStart = coordinates[i];
                var segmentEnd = coordinates[i + 1];
                var segmentLength = Math.sqrt(
                    Math.pow(segmentEnd[0] - segmentStart[0], 2) +
                    Math.pow(segmentEnd[1] - segmentStart[1], 2)
                );
                
                if (currentDistance + segmentLength >= distance) {
                    // The target distance is within this segment
                    var ratio = (distance - currentDistance) / segmentLength;
                    return [
                        segmentStart[0] + ratio * (segmentEnd[0] - segmentStart[0]),
                        segmentStart[1] + ratio * (segmentEnd[1] - segmentStart[1])
                    ];
                }
                
                currentDistance += segmentLength;
            }
            
            // Return last coordinate if distance exceeds line length
            return coordinates[coordinates.length - 1];
        },

        /**
         * Get bearing (direction in degrees) at a specific distance along a LineString
         * 
         * @param {ol.geom.LineString} lineString - OpenLayers LineString geometry
         * @param {number} distance - Distance along the line in map units
         * @returns {number} Bearing in degrees from East (0° = East, 90° = North, -90° = South)
         */
        getBearingAtDistance: function(lineString, distance) {
            var coordinates = lineString.getCoordinates();
            var currentDistance = 0;
            
            for (var i = 0; i < coordinates.length - 1; i++) {
                var segmentStart = coordinates[i];
                var segmentEnd = coordinates[i + 1];
                var segmentLength = Math.sqrt(
                    Math.pow(segmentEnd[0] - segmentStart[0], 2) +
                    Math.pow(segmentEnd[1] - segmentStart[1], 2)
                );
                
                if (currentDistance + segmentLength >= distance) {
                    // Calculate bearing for this segment
                    // atan2(dy, dx) gives angle from East (positive X-axis) in radians
                    var dx = segmentEnd[0] - segmentStart[0];
                    var dy = segmentEnd[1] - segmentStart[1];
                    var angleRadians = Math.atan2(dy, dx);
                    var angleDegrees = angleRadians * (180 / Math.PI);
                    return angleDegrees;
                }
                
                currentDistance += segmentLength;
            }
            
            // Return bearing of last segment
            var lastIdx = coordinates.length - 1;
            if (lastIdx > 0) {
                var dx = coordinates[lastIdx][0] - coordinates[lastIdx - 1][0];
                var dy = coordinates[lastIdx][1] - coordinates[lastIdx - 1][1];
                var angleRadians = Math.atan2(dy, dx);
                var angleDegrees = angleRadians * (180 / Math.PI);
                return angleDegrees;
            }
            
            return 0;
        }
    };

    return methods;
}());
