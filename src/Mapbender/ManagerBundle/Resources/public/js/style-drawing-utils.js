/**
 * Shared style drawing utilities for Mapbender admin style previews.
 * Used by: Style index, Style editor, Layerset instance editor.
 *
 * Exposes window.Mapbender.StyleUtils
 */
(function() {
    'use strict';
    if (!window.Mapbender) window.Mapbender = {};

    function hexToRgba(hex, opacity) {
        hex = (hex || '#000000').replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        var r = parseInt(hex.substring(0,2), 16);
        var g = parseInt(hex.substring(2,4), 16);
        var b = parseInt(hex.substring(4,6), 16);
        return 'rgba(' + r + ',' + g + ',' + b + ',' + (parseFloat(opacity) || 1) + ')';
    }

    /**
     * Extract fill/stroke paint properties from a Mapbox style JSON.
     * Optionally filters layers by source-layer matching collectionId.
     *
     * @param {Object} styleJson - Parsed Mapbox style JSON
     * @param {string|null} collectionId - Optional source-layer filter
     * @returns {Object|null} {fillColor, fillOpacity, strokeColor, strokeOpacity, strokeWidth} or null
     */
    function extractMapboxPaint(styleJson, collectionId) {
        if (!styleJson || !styleJson.version || !styleJson.layers) return null;
        var fillLayer = null, lineLayer = null;
        for (var i = 0; i < styleJson.layers.length; i++) {
            var l = styleJson.layers[i];
            if (collectionId && l['source-layer'] && l['source-layer'] !== collectionId) continue;
            if (!fillLayer && l.type === 'fill' && l.paint) fillLayer = l;
            if (!lineLayer && l.type === 'line' && l.paint) lineLayer = l;
        }
        if (!fillLayer && !lineLayer) return null;
        var p = {};
        if (fillLayer) {
            p.fillColor = fillLayer.paint['fill-color'] || '#000000';
            p.fillOpacity = fillLayer.paint['fill-opacity'] !== undefined ? fillLayer.paint['fill-opacity'] : 1;
        } else {
            p.fillColor = 'transparent';
            p.fillOpacity = 0;
        }
        if (lineLayer) {
            p.strokeColor = lineLayer.paint['line-color'] || '#000000';
            p.strokeOpacity = lineLayer.paint['line-opacity'] !== undefined ? lineLayer.paint['line-opacity'] : 1;
            var lw = lineLayer.paint['line-width'];
            p.strokeWidth = typeof lw === 'object' ? (lw.stops ? lw.stops[0][1] : 2) : (lw || 2);
        } else {
            p.strokeColor = 'transparent';
            p.strokeOpacity = 0;
            p.strokeWidth = 0;
        }
        return p;
    }

    /**
     * Resolve fill/stroke/width from either Mapbox JSON or simple style object.
     * Returns ready-to-use canvas values.
     *
     * @param {Object} styleJson - Parsed style JSON (Mapbox or simple)
     * @param {string|null} collectionId - Optional source-layer filter for Mapbox
     * @returns {Object} {fillStyle, strokeStyle, strokeWidth, pointRadius, isMapbox}
     */
    function resolveStylePaint(styleJson, collectionId) {
        var mb = extractMapboxPaint(styleJson, collectionId);
        if (mb) {
            return {
                fillStyle: hexToRgba(mb.fillColor, mb.fillOpacity),
                strokeStyle: hexToRgba(mb.strokeColor, mb.strokeOpacity),
                strokeWidth: mb.strokeWidth,
                pointRadius: 5,
                isMapbox: true
            };
        }
        var s = styleJson || {};
        return {
            fillStyle: hexToRgba(s.fillColor || '#ff0000', parseFloat(s.fillOpacity) || 1),
            strokeStyle: hexToRgba(s.strokeColor || '#ffffff', parseFloat(s.strokeOpacity) || 1),
            strokeWidth: parseFloat(s.strokeWidth) || 1,
            pointRadius: parseFloat(s.pointRadius) || 5,
            isMapbox: false
        };
    }

    /**
     * Draw a point/line/polygon preview on a canvas element.
     * Reads geometry type from data-geom attribute.
     *
     * @param {HTMLCanvasElement} canvas
     * @param {Object|null} styleJson - Parsed style JSON (Mapbox or simple), null clears canvas
     * @param {Object} [options]
     * @param {string|null} [options.collectionId] - Source-layer filter for Mapbox styles
     * @param {number[]} [options.dashes] - Line dash pattern, e.g. [10,10]
     * @param {string} [options.lineCap] - Canvas lineCap value
     */
    function drawStyleCanvas(canvas, styleJson, options) {
        var ctx = canvas.getContext('2d');
        var w = canvas.width, h = canvas.height;
        ctx.clearRect(0, 0, w, h);
        if (!styleJson) return;

        var opts = options || {};
        var paint = resolveStylePaint(styleJson, opts.collectionId || null);
        var dashes = opts.dashes || [];
        var lineCap = opts.lineCap || 'round';
        var sw = paint.strokeWidth;
        var pr = Math.min(paint.pointRadius, w / 2 - 2);

        ctx.fillStyle = paint.fillStyle;
        ctx.strokeStyle = paint.strokeStyle;
        ctx.lineWidth = sw;
        ctx.lineJoin = 'round';
        ctx.lineCap = lineCap;
        ctx.setLineDash(dashes);

        var geom = canvas.getAttribute('data-geom');
        if (geom === 'point') {
            ctx.beginPath();
            ctx.arc(w / 2, h / 2, Math.max(pr, 3), 0, 2 * Math.PI);
            ctx.fill();
            if (sw > 0) ctx.stroke();
        } else if (geom === 'line') {
            ctx.beginPath();
            ctx.moveTo(4, h - 4);
            ctx.lineTo(w * 0.4, 6);
            ctx.lineTo(w * 0.6, h - 6);
            ctx.lineTo(w - 4, 4);
            ctx.stroke();
        } else {
            ctx.beginPath();
            ctx.moveTo(w / 2, 4);
            ctx.lineTo(w - 4, h * 0.4);
            ctx.lineTo(w - 6, h - 4);
            ctx.lineTo(6, h - 4);
            ctx.lineTo(4, h * 0.35);
            ctx.closePath();
            ctx.fill();
            if (sw > 0) ctx.stroke();
        }
    }

    window.Mapbender.StyleUtils = {
        hexToRgba: hexToRgba,
        extractMapboxPaint: extractMapboxPaint,
        resolveStylePaint: resolveStylePaint,
        drawStyleCanvas: drawStyleCanvas
    };
})();
