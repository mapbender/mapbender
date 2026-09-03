window.Mapbender = Mapbender || {};
window.Mapbender.GeometryUtil = class {
    /**
     * Get coordinate at a specific distance along a LineString
     *
     * @param {ol.geom.LineString} lineString - OpenLayers LineString geometry
     * @param {number} distance - Distance along the line in map units
     * @returns {{x: number, y: number, segment: number}} Coordinate at the specified distance, or last coordinate if distance exceeds line length
     */
    static getCoordinateAtDistance(lineString, distance, startSegmentIndex = 0) {
        var coordinates = lineString.getCoordinates();
        var currentDistance = 0;

        for (var i = startSegmentIndex; i < coordinates.length - 1; i++) {
            var segmentStart = coordinates[i];
            var segmentEnd = coordinates[i + 1];
            var segmentLength = Math.sqrt(
                Math.pow(segmentEnd[0] - segmentStart[0], 2) +
                Math.pow(segmentEnd[1] - segmentStart[1], 2)
            );

            if (currentDistance + segmentLength >= distance) {
                // The target distance is within this segment
                var ratio = (distance - currentDistance) / segmentLength;
                return {
                    x: segmentStart[0] + ratio * (segmentEnd[0] - segmentStart[0]),
                    y: segmentStart[1] + ratio * (segmentEnd[1] - segmentStart[1]),
                    segment: i,
                };
            }

            currentDistance += segmentLength;
        }

        // Return last coordinate if distance exceeds line length
        const lastCoord = coordinates[coordinates.length - 1];
        return {
            x: lastCoord[0],
            y: lastCoord[1],
            segment: NaN,
        }
    }

    /**
     * @return {{extent: {minX: number, minY: number, maxX: number, maxY: number}}, remainingLineString: null|ol.geom.LineString}
     **/
    static getMaximumExtent(lineString, width, height) {
        if (width <= 0 || height <= 0) {
            throw new RangeError("Width and height must be positive.");
        }

        const coordinates = lineString.getCoordinates();

        if (coordinates.length < 2) {
            throw new RangeError("The LineString must contain at least two coordinates.");
        }

        const bounds = this.expandBounds(null, coordinates[0]);
        let exitCoordinate;
        let exitSegmentIndex;

        for (let index = 0; index < coordinates.length - 1; index += 1) {
            const segmentStart = coordinates[index];
            const segmentEnd = coordinates[index + 1];
            const includedFraction = this.getIncludedFraction(
                segmentStart,
                segmentEnd,
                bounds,
                width,
                height,
            );

            exitCoordinate = [
                segmentStart[0] + (segmentEnd[0] - segmentStart[0]) * includedFraction,
                segmentStart[1] + (segmentEnd[1] - segmentStart[1]) * includedFraction,
            ];

            this.expandBounds(bounds, exitCoordinate);

            if (includedFraction < 1) {
                exitSegmentIndex = index;
                break;
            }
        }

        let remainingLineString = null;

        if (exitSegmentIndex !== undefined) {
            remainingLineString = lineString.clone();
            remainingLineString.setCoordinates([
                exitCoordinate,
                ...coordinates.slice(exitSegmentIndex + 1),
            ]);
        }

        return {
            extent: this.centerBounds(bounds, width, height),
            remainingLineString,
        };
    }

    static getIncludedFraction(start, end, bounds, width, height) {
        const xFraction = this.getAxisFraction(
            start[0],
            end[0],
            bounds.maxX - width,
            bounds.minX + width,
        );

        const yFraction = this.getAxisFraction(
            start[1],
            end[1],
            bounds.maxY - height,
            bounds.minY + height,
        );

        return Math.min(xFraction, yFraction);
    }

    static getAxisFraction(start, end, minimum, maximum) {
        if (end > maximum) {
            return (maximum - start) / (end - start);
        }

        if (end < minimum) {
            return (minimum - start) / (end - start);
        }

        return 1;
    }

    /**
     * @return {{minX: number, minY: number, maxX: number, maxY: number}}
     */
    static expandBounds(bounds, [x, y]) {
        if (!bounds) {
            return {
                minX: x,
                minY: y,
                maxX: x,
                maxY: y,
            };
        }
        bounds.minX = Math.min(bounds.minX, x);
        bounds.minY = Math.min(bounds.minY, y);
        bounds.maxX = Math.max(bounds.maxX, x);
        bounds.maxY = Math.max(bounds.maxY, y);
    }

    /**
     * Get bearing (direction in degrees) at a specific distance along a LineString
     *
     * @param {ol.geom.LineString} lineString - OpenLayers LineString geometry
     * @param {number} distance - Distance along the line in map units
     * @returns {number} Bearing in degrees from East (0° = East, 90° = North, -90° = South)
     */
    static getBearingAtDistance(lineString, distance) {
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

    static centerBounds(bounds, width, height) {
        bounds.minX = (bounds.minX + bounds.maxX - width) / 2;
        bounds.minY = (bounds.minY + bounds.maxY - height) / 2;
        bounds.maxX = bounds.minX + width;
        bounds.maxY = bounds.minY + height;
        return bounds;
    }
}
;
