/**
 * StyleConfig for BatchPrintClient
 * 
 * Centralizes all style configuration and creates OpenLayers style objects for:
 * - Frame styles (default, highlighted)
 * - Rotation control styles (box, handle)
 * - Track styles (line, points)
 */
(function() {
    'use strict';

    window.Mapbender = window.Mapbender || {};

    /**
     * StyleConfig for BatchPrintClient
     * Centralizes all style configuration and creates OpenLayers style objects
     */
    class BatchPrintStyleConfig {
        /**
         * Creates a new StyleConfig with default values
         * @param {Object} [overrides] - Optional overrides for default values
         */
        constructor(overrides) {
            // Colors
            this.highlightColor = '#0066cc';        // Blue color matching Mapbender's highlight color
            this.frameStrokeColor = '#000000';      // Same as normal print frame
            this.frameFillColor = 'rgba(255, 255, 255, 0.5)';
            this.trackColor = '#FF0000';
            
            // Opacities
            this.rotationControlOpacity = 0.6;
            this.highlightFillOpacity = 0.3;
            this.trackFillOpacity = 0.1;
            
            // Stroke widths
            this.highlightStrokeWidthThin = 0.5;
            this.highlightStrokeWidthNormal = 2;
            this.frameStrokeWidth = 1;
            this.rotationBoxStrokeWidth = 2;
            this.rotationHandleStrokeWidth = 1;
            this.trackStrokeWidth = 3;
            this.trackPointStrokeWidth = 2;
            
            // Radii
            this.rotationHandleRadius = 8;
            this.trackPointRadius = 6;
            
            // Line patterns
            this.rotationBoxLineDash = [5, 5];
            
            // Apply any overrides
            if (overrides) {
                for (const key in overrides) {
                    if (overrides.hasOwnProperty(key) && this.hasOwnProperty(key)) {
                        this[key] = overrides[key];
                    }
                }
            }
        }

        /**
         * Get RGBA color string from CSS color with opacity
         * @private
         */
        _toRgba(cssColor, opacity) {
            const rgb = Mapbender.StyleUtil.parseCssColor(cssColor);
            return 'rgba(' + rgb[0] + ', ' + rgb[1] + ', ' + rgb[2] + ', ' + opacity + ')';
        }

        /**
         * Create default frame style (black outline, white fill)
         * @returns {ol.style.Style}
         */
        createDefaultFrameStyle() {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.frameStrokeColor,
                    width: this.frameStrokeWidth
                }),
                fill: new ol.style.Fill({
                    color: this.frameFillColor
                })
            });
        }

        /**
         * Create highlighted frame style (blue outline with distinct top border)
         * Creates a base style with thin borders and a separate style for the thicker top edge
         * @param {ol.Feature} feature - The feature to style (needed to extract top edge coordinates)
         * @returns {Array<ol.style.Style>}
         */
        createHighlightedFrameStyle(feature) {
            // Get the actual polygon coordinates from the rotated geometry
            const geometry = feature.getGeometry();
            const coordinates = geometry.getCoordinates()[0]; // Get exterior ring coordinates
            
            // The polygon coordinates are ordered: [bottom-left, top-left, top-right, bottom-right, bottom-left]
            // Top edge is from index 1 (top-left) to index 2 (top-right)
            const topLineCoords = [
                coordinates[1],  // top-left
                coordinates[2]   // top-right
            ];
            
            // Base highlight style with very thin border and fill
            const baseStyle = new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.highlightColor,
                    width: this.highlightStrokeWidthThin  // Very thin border for bottom, left, right
                }),
                fill: new ol.style.Fill({
                    color: this._toRgba(this.highlightColor, this.highlightFillOpacity)
                })
            });
            
            // Additional style for normal top border
            const topBorderStyle = new ol.style.Style({
                geometry: new ol.geom.LineString(topLineCoords),
                stroke: new ol.style.Stroke({
                    color: this.highlightColor,
                    width: this.highlightStrokeWidthNormal  // Normal border width for top edge
                })
            });
            
            // Apply both styles - the array creates a multi-style rendering
            return [baseStyle, topBorderStyle];
        }

        /**
         * Create rotation box style (dotted outline)
         * @returns {ol.style.Style}
         */
        createRotationBoxStyle() {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.highlightColor,
                    width: this.rotationBoxStrokeWidth,
                    lineDash: this.rotationBoxLineDash
                })
            });
        }

        /**
         * Create rotation handle style (circle)
         * @returns {ol.style.Style}
         */
        createRotationHandleStyle() {
            return new ol.style.Style({
                image: new ol.style.Circle({
                    radius: this.rotationHandleRadius,
                    fill: new ol.style.Fill({
                        color: this._toRgba(this.highlightColor, this.rotationControlOpacity)
                    }),
                    stroke: new ol.style.Stroke({
                        color: this.frameStrokeColor,
                        width: this.rotationHandleStrokeWidth
                    })
                })
            });
        }

        /**
         * Create track line style
         * @returns {ol.style.Style}
         */
        createTrackLineStyle() {
            return new ol.style.Style({
                stroke: new ol.style.Stroke({
                    color: this.trackColor,
                    width: this.trackStrokeWidth
                }),
                fill: new ol.style.Fill({
                    color: this._toRgba(this.trackColor, this.trackFillOpacity)
                })
            });
        }

        /**
         * Create track point style
         * @returns {ol.style.Style}
         */
        createTrackPointStyle() {
            return new ol.style.Style({
                image: new ol.style.Circle({
                    radius: this.trackPointRadius,
                    fill: new ol.style.Fill({
                        color: this.trackColor
                    }),
                    stroke: new ol.style.Stroke({
                        color: '#FFFFFF',
                        width: this.trackPointStrokeWidth
                    })
                })
            });
        }

        /**
         * Create empty style (for hiding features)
         * @returns {ol.style.Style}
         */
        createEmptyStyle() {
            return new ol.style.Style({});
        }
    }

    // Export to global namespace
    Mapbender.BatchPrintStyleConfig = BatchPrintStyleConfig;

})();
