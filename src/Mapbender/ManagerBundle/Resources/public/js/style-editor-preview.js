/**
 * Flat style preview renderer for the Style Editor.
 * Draws point, line, and polygon previews on canvas elements.
 *
 * Requires: StyleUtils (style-utils.js)
 */
class StyleEditorPreview {
    constructor(dashMap) {
        this.dashMap = dashMap || {
            solid: [], dot: [1,5], dash: [10,10], longdash: [20,10],
            dashdot: [10,10,1,10], longdashdot: [20,10,1,10]
        };
    }

    drawLabel(ctx, s, x, y) {
        const text = s.label || '';
        if (!text) return;
        const fw = s.fontWeight || 'regular';
        const prefix = fw === 'bold' ? 'bold ' : (fw === 'italic' ? 'italic ' : '');
        ctx.font = `${prefix}${parseInt(s.fontSize) || 11}px ${s.fontFamily || 'Arial, Helvetica, sans-serif'}`;
        ctx.fillStyle = StyleUtils.hexToRgba(s.fontColor || '#000000', parseFloat(s.fontOpacity) || 1);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, x, y);
    }

    drawAll(visualStyle) {
        const s = visualStyle;
        const fillStyle   = StyleUtils.hexToRgba(s.fillColor || '#ff0000', parseFloat(s.fillOpacity) || 1);
        const strokeStyle = StyleUtils.hexToRgba(s.strokeColor || '#ffffff', parseFloat(s.strokeOpacity) || 1);
        const strokeWidth = parseFloat(s.strokeWidth) || 1;
        const pointRadius = parseFloat(s.pointRadius) || 6;
        const dashes = this.dashMap[s.strokeDashstyle] || [];
        const linecap = s.strokeLinecap || 'round';

        const setupCtx = (ctx) => {
            ctx.setLineDash(dashes); ctx.lineCap = linecap; ctx.lineJoin = 'round';
            ctx.lineWidth = strokeWidth;
        };

        // Point
        const cp = document.getElementById('preview-point');
        if (cp) {
            const ctx = cp.getContext('2d'), w = cp.width, h = cp.height;
            ctx.clearRect(0,0,w,h); setupCtx(ctx);
            ctx.fillStyle = fillStyle; ctx.strokeStyle = strokeStyle;
            ctx.beginPath();
            ctx.arc(w/2, h/2, Math.max(pointRadius, 3), 0, 2*Math.PI);
            ctx.fill(); if (strokeWidth > 0) ctx.stroke();
            this.drawLabel(ctx, s, w/2, h/2);
        }

        // Line
        const cl = document.getElementById('preview-line');
        if (cl) {
            const ctx = cl.getContext('2d'), w = cl.width, h = cl.height;
            ctx.clearRect(0,0,w,h); setupCtx(ctx);
            ctx.strokeStyle = strokeStyle;
            ctx.beginPath();
            ctx.moveTo(20, h-20); ctx.lineTo(w*0.35, 25); ctx.lineTo(w*0.65, h-25); ctx.lineTo(w-20, 20);
            ctx.stroke();
            this.drawLabel(ctx, s, w/2, h/2);
        }

        // Polygon
        const cpg = document.getElementById('preview-polygon');
        if (cpg) {
            const ctx = cpg.getContext('2d'), w = cpg.width, h = cpg.height;
            ctx.clearRect(0,0,w,h); setupCtx(ctx);
            ctx.fillStyle = fillStyle; ctx.strokeStyle = strokeStyle;
            ctx.beginPath();
            ctx.moveTo(w*0.5, 15); ctx.lineTo(w-20, h*0.4); ctx.lineTo(w-30, h-15);
            ctx.lineTo(25, h-15); ctx.lineTo(15, h*0.35);
            ctx.closePath(); ctx.fill(); if (strokeWidth > 0) ctx.stroke();
            this.drawLabel(ctx, s, w/2, h/2);
        }
    }
}
