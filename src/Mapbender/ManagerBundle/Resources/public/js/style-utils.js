/**
 * Shared style drawing utilities for Mapbender admin style previews.
 * Used by: Style index, Style editor, Layerset instance editor.
 *
 * Exposes window.Mapbender.StyleUtils
 */
class StyleUtils {
    static hexToRgba(hex, opacity) {
        hex = (hex || '#000000').replace('#', '');
        if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
        const r = parseInt(hex.substring(0,2), 16);
        const g = parseInt(hex.substring(2,4), 16);
        const b = parseInt(hex.substring(4,6), 16);
        return `rgba(${r},${g},${b},${parseFloat(opacity) || 1})`;
    }

    static extractMapboxPaint(styleJson, collectionId) {
        if (!styleJson?.version || !styleJson?.layers) return null;
        let fillLayer = null, lineLayer = null;
        for (const l of styleJson.layers) {
            if (collectionId && l['source-layer'] && l['source-layer'] !== collectionId) continue;
            if (!fillLayer && l.type === 'fill' && l.paint) fillLayer = l;
            if (!lineLayer && l.type === 'line' && l.paint) lineLayer = l;
        }
        if (!fillLayer && !lineLayer) return null;
        const p = {};
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
            const lw = lineLayer.paint['line-width'];
            p.strokeWidth = typeof lw === 'object' ? (lw.stops ? lw.stops[0][1] : 2) : (lw || 2);
        } else {
            p.strokeColor = 'transparent';
            p.strokeOpacity = 0;
            p.strokeWidth = 0;
        }
        return p;
    }

    static resolveStylePaint(styleJson, collectionId) {
        const mb = StyleUtils.extractMapboxPaint(styleJson, collectionId);
        if (mb) {
            return {
                fillStyle: StyleUtils.hexToRgba(mb.fillColor, mb.fillOpacity),
                strokeStyle: StyleUtils.hexToRgba(mb.strokeColor, mb.strokeOpacity),
                strokeWidth: mb.strokeWidth,
                pointRadius: 5,
                isMapbox: true
            };
        }
        const s = styleJson || {};
        return {
            fillStyle: StyleUtils.hexToRgba(s.fillColor || '#ff0000', parseFloat(s.fillOpacity) || 1),
            strokeStyle: StyleUtils.hexToRgba(s.strokeColor || '#ffffff', parseFloat(s.strokeOpacity) || 1),
            strokeWidth: parseFloat(s.strokeWidth) || 1,
            pointRadius: parseFloat(s.pointRadius) || 5,
            isMapbox: false
        };
    }

    static drawStyleCanvas(canvas, styleJson, options) {
        const ctx = canvas.getContext('2d');
        const w = canvas.width, h = canvas.height;
        ctx.clearRect(0, 0, w, h);
        if (!styleJson) return;

        const opts = options || {};
        const paint = StyleUtils.resolveStylePaint(styleJson, opts.collectionId || null);
        const sw = paint.strokeWidth;
        const pr = Math.min(paint.pointRadius, w / 2 - 2);

        ctx.fillStyle = paint.fillStyle;
        ctx.strokeStyle = paint.strokeStyle;
        ctx.lineWidth = sw;
        ctx.lineJoin = 'round';
        ctx.lineCap = opts.lineCap || 'round';
        ctx.setLineDash(opts.dashes || []);

        const geom = canvas.getAttribute('data-geom');
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
    /**
     * Auto-initialize all canvas.style-preview elements on the page.
     * Each canvas reads its style JSON from data-style and geometry from data-geom.
     */
    static initPreviews() {
        document.querySelectorAll('canvas.style-preview').forEach(c => {
            let s = null;
            try { s = JSON.parse(c.getAttribute('data-style')) || null; } catch(e) {}
            StyleUtils.drawStyleCanvas(c, s);
        });
    }
}
window.Mapbender = window.Mapbender || {};
window.Mapbender.StyleUtils = StyleUtils;

// Auto-init simple canvas previews when script loads
StyleUtils.initPreviews();
