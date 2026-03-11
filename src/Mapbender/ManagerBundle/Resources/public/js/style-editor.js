class StyleEditor {
    constructor() {
        this.visualPane = document.getElementById('pane-visual');
        if (!this.visualPane) return;

        this.styleTextarea = document.getElementById('style');
        this.visualInputs = this.visualPane.querySelectorAll('[data-prop]');
        const collectionIdAttr = this.styleTextarea.getAttribute('data-collection-id');
        this.collectionId = collectionIdAttr || null;

        this.dashMap = {
            solid: [], dot: [1,5], dash: [10,10], longdash: [20,10],
            dashdot: [10,10,1,10], longdashdot: [20,10,1,10]
        };
        this.sourceTypeInput = document.getElementById('sourceType');

        this._bindEvents();
        this.updateRangePreviews();
        this.reinitColorpickers();

        if (this.styleTextarea.value.trim()) {
            this.syncJsonToVisual();
        }
        this.drawPreview();
    }

    _bindEvents() {
        this.visualInputs.forEach(el => {
            el.addEventListener('input', () => {
                this.syncVisualToJson();
                this.updateRangePreviews();
                this.drawPreview();
            });
            el.addEventListener('change', () => {
                this.syncVisualToJson();
                this.updateRangePreviews();
                this.drawPreview();
            });
        });

        document.getElementById('tab-visual').addEventListener('shown.bs.tab', () => {
            this.syncJsonToVisual();
            this.drawPreview();
        });
        document.getElementById('tab-json').addEventListener('shown.bs.tab', () => {
            this.syncVisualToJson();
        });

        const importInput = document.getElementById('import-file');
        if (importInput) {
            importInput.addEventListener('change', (e) => this._handleImportFile(e));
        }

        // Mark as manual when user edits JSON textarea directly
        if (this.styleTextarea) {
            this.styleTextarea.addEventListener('input', () => {
                if (this.sourceTypeInput) this.sourceTypeInput.value = 'manual';
            });
        }
    }

    _handleImportFile(e) {
        const file = e.target.files[0];
        if (!file) return;

        const warningsEl = document.getElementById('import-warnings');
        const successEl = document.getElementById('import-success');
        warningsEl.classList.add('d-none');
        warningsEl.innerHTML = '';
        successEl.classList.add('d-none');

        const reader = new FileReader();
        reader.onload = (evt) => {
            const content = evt.target.result;
            const isJson = file.name.endsWith('.json') || content.trimStart().startsWith('{');

            if (isJson) {
                this._importMapboxJson(content, warningsEl, successEl);
            } else {
                this._importSld(content, warningsEl, successEl);
            }
        };
        reader.readAsText(file);
    }

    _importMapboxJson(content, warningsEl, successEl) {
        let parsed;
        try {
            parsed = JSON.parse(content);
        } catch (err) {
            this._showWarnings(warningsEl, ['Invalid JSON: ' + err.message]);
            return;
        }
        this.setJsonToTextarea(parsed);
        this.syncJsonToVisual();
        this.drawPreview();
        if (this.sourceTypeInput) this.sourceTypeInput.value = 'mapbox-json';
        successEl.classList.remove('d-none');
    }

    _importSld(content, warningsEl, successEl) {
        const result = Mapbender.SldConverter.convert(content);
        if (result.warnings.length) {
            this._showWarnings(warningsEl, result.warnings);
        }
        if (!result.style) return;
        this.setJsonToTextarea(result.style);
        this.syncJsonToVisual();
        this.drawPreview();
        if (this.sourceTypeInput) this.sourceTypeInput.value = 'sld';
        successEl.classList.remove('d-none');
    }

    _showWarnings(container, warnings) {
        container.classList.remove('d-none');
        container.innerHTML = warnings.map(w =>
            `<div class="alert alert-warning mb-1"><i class="fas fa-triangle-exclamation me-1"></i>${w}</div>`
        ).join('');
    }

    getJsonFromTextarea() {
        try { return JSON.parse(this.styleTextarea.value); } catch(e) { return {}; }
    }

    setJsonToTextarea(obj) {
        this.styleTextarea.value = JSON.stringify(obj, null, 2);
    }

    _isMapboxDocument(obj) {
        return !!(obj?.version && Array.isArray(obj?.layers));
    }

    syncVisualToJson() {
        const obj = this.getJsonFromTextarea();
        if (this._isMapboxDocument(obj)) {
            this._syncVisualToMapbox(obj);
            return;
        }
        this.visualInputs.forEach(el => {
            const key = el.getAttribute('data-prop');
            let val = el.value;
            if (el.type === 'number' || el.type === 'range') {
                val = val === '' ? null : parseFloat(val);
            }
            obj[key] = val;
        });
        this.setJsonToTextarea(obj);
    }

    syncJsonToVisual() {
        const obj = this.getJsonFromTextarea();
        if (this._isMapboxDocument(obj)) {
            this._syncMapboxToVisual(obj);
            return;
        }
        this.visualInputs.forEach(el => {
            const key = el.getAttribute('data-prop');
            if (obj.hasOwnProperty(key) && obj[key] !== null && obj[key] !== undefined) {
                el.value = obj[key];
            }
        });
        this.updateRangePreviews();
        this.reinitColorpickers();
    }

    _syncMapboxToVisual(doc) {
        const propMap = {};
        for (const layer of doc.layers) {
            const p = layer.paint || {};
            const lo = layer.layout || {};
            if (layer.type === 'fill') {
                if (p['fill-color'] !== undefined) propMap.fillColor = p['fill-color'];
                if (p['fill-opacity'] !== undefined) propMap.fillOpacity = p['fill-opacity'];
            } else if (layer.type === 'line') {
                if (p['line-color'] !== undefined) propMap.strokeColor = p['line-color'];
                if (p['line-width'] !== undefined) propMap.strokeWidth = typeof p['line-width'] === 'object' ? 2 : p['line-width'];
                if (p['line-opacity'] !== undefined) propMap.strokeOpacity = p['line-opacity'];
            } else if (layer.type === 'circle') {
                if (p['circle-radius'] !== undefined) propMap.pointRadius = p['circle-radius'];
                if (p['circle-color'] !== undefined && !propMap.fillColor) propMap.fillColor = p['circle-color'];
                if (p['circle-opacity'] !== undefined && !propMap.fillOpacity) propMap.fillOpacity = p['circle-opacity'];
            } else if (layer.type === 'symbol') {
                if (lo['text-field']) {
                    const tf = lo['text-field'];
                    propMap.label = Array.isArray(tf) ? tf.slice(1).join(', ') : String(tf);
                }
                if (lo['text-size']) propMap.fontSize = lo['text-size'];
                if (lo['text-font']?.[0]) propMap.fontFamily = lo['text-font'][0];
                if (p['text-color']) propMap.fontColor = p['text-color'];
                if (p['text-opacity'] !== undefined) propMap.fontOpacity = p['text-opacity'];
            }
        }
        this.visualInputs.forEach(el => {
            const key = el.getAttribute('data-prop');
            if (propMap[key] !== undefined) el.value = propMap[key];
        });
        this.updateRangePreviews();
        this.reinitColorpickers();
    }

    _syncVisualToMapbox(doc) {
        for (const layer of doc.layers) {
            const p = layer.paint = layer.paint || {};
            layer.layout = layer.layout || {};
            if (layer.type === 'fill') {
                this._setVisualProp(p, 'fill-color', 'fillColor');
                this._setVisualProp(p, 'fill-opacity', 'fillOpacity', true);
            } else if (layer.type === 'line') {
                this._setVisualProp(p, 'line-color', 'strokeColor');
                this._setVisualProp(p, 'line-width', 'strokeWidth', true);
                this._setVisualProp(p, 'line-opacity', 'strokeOpacity', true);
            } else if (layer.type === 'circle') {
                this._setVisualProp(p, 'circle-radius', 'pointRadius', true);
                this._setVisualProp(p, 'circle-color', 'fillColor');
                this._setVisualProp(p, 'circle-opacity', 'fillOpacity', true);
            }
        }
        this.setJsonToTextarea(doc);
    }

    _setVisualProp(target, mapboxKey, dataProp, numeric) {
        const el = this.visualPane.querySelector(`[data-prop="${dataProp}"]`);
        if (!el) return;
        target[mapboxKey] = numeric ? parseFloat(el.value) : el.value;
    }

    getVisualStyle() {
        const s = {};
        this.visualInputs.forEach(el => {
            s[el.getAttribute('data-prop')] = el.value;
        });
        return s;
    }

    drawLabel(ctx, s, x, y) {
        const text = s.label || '';
        if (!text) return;
        const fw = s.fontWeight || 'regular';
        const prefix = fw === 'bold' ? 'bold ' : (fw === 'italic' ? 'italic ' : '');
        ctx.font = `${prefix}${parseInt(s.fontSize) || 11}px ${s.fontFamily || 'Arial, Helvetica, sans-serif'}`;
        ctx.fillStyle = StyleUtils.hexToRgba(s.fontColor || '#000000', parseFloat(s.fontOpacity) || 1);
        ctx.textAlign = 'center';
        ctx.textBaseline = 'top';
        ctx.fillText(text, x, y);
    }

    drawPreview() {
        const json = this.getJsonFromTextarea();
        const mb = StyleUtils.extractMapboxPaint(json, this.collectionId);
        if (mb) {
            ['preview-point', 'preview-line', 'preview-polygon'].forEach(id => {
                const c = document.getElementById(id);
                if (c) StyleUtils.drawStyleCanvas(c, json, {collectionId: this.collectionId});
            });
            return;
        }
        const s = this.getVisualStyle();
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
        {
            const c = document.getElementById('preview-point');
            if (c) {
                const ctx = c.getContext('2d'), w = c.width, h = c.height;
                ctx.clearRect(0,0,w,h); setupCtx(ctx);
                ctx.fillStyle = fillStyle; ctx.strokeStyle = strokeStyle;
                ctx.beginPath();
                ctx.arc(w/2, h/2, Math.max(pointRadius, 3), 0, 2*Math.PI);
                ctx.fill(); if (strokeWidth > 0) ctx.stroke();
                this.drawLabel(ctx, s, w/2, h/2 + Math.max(pointRadius,3) + 4);
            }
        }
        // Line
        {
            const c = document.getElementById('preview-line');
            if (c) {
                const ctx = c.getContext('2d'), w = c.width, h = c.height;
                ctx.clearRect(0,0,w,h); setupCtx(ctx);
                ctx.strokeStyle = strokeStyle;
                ctx.beginPath();
                ctx.moveTo(20, h-20); ctx.lineTo(w*0.35, 25); ctx.lineTo(w*0.65, h-25); ctx.lineTo(w-20, 20);
                ctx.stroke();
                this.drawLabel(ctx, s, w/2, h/2 + 10);
            }
        }
        // Polygon
        {
            const c = document.getElementById('preview-polygon');
            if (c) {
                const ctx = c.getContext('2d'), w = c.width, h = c.height;
                ctx.clearRect(0,0,w,h); setupCtx(ctx);
                ctx.fillStyle = fillStyle; ctx.strokeStyle = strokeStyle;
                ctx.beginPath();
                ctx.moveTo(w*0.5, 15); ctx.lineTo(w-20, h*0.4); ctx.lineTo(w-30, h-15);
                ctx.lineTo(25, h-15); ctx.lineTo(15, h*0.35);
                ctx.closePath(); ctx.fill(); if (strokeWidth > 0) ctx.stroke();
                this.drawLabel(ctx, s, w/2, h-10);
            }
        }
    }

    updateRangePreviews() {
        this.visualPane.querySelectorAll('input[type="range"]').forEach(input => {
            const vp = input.closest('.mb-3').querySelector('.value-preview');
            if (vp) {
                const v = parseFloat(input.value);
                vp.textContent = isNaN(v) ? '' : v.toFixed(2);
            }
        });
    }

    reinitColorpickers() {
        try { $('.-js-colorpicker', this.visualPane).colorpicker('destroy'); } catch(e) {}
        $('.-js-colorpicker', this.visualPane).colorpicker({format: 'hex'});
        $('.-js-colorpicker', this.visualPane).on('changeColor', () => {
            this.syncVisualToJson();
            this.drawPreview();
        });
    }
}
$(function() { new StyleEditor(); });
