/**
 * Mapbox layer accordion editor for the Style Editor.
 * Manages per-layer UI controls, mini/full previews, and JSON sync.
 *
 * Requires: StyleUtils (style-utils.js)
 */
class StyleEditorLayers {
    constructor(editor) {
        this.editor = editor;
        this.layerAccordion = editor.layerAccordion;
        this._layerCounter = 0;
    }

    buildAccordion(doc) {
        this.layerAccordion.innerHTML = '';
        this._layerCounter = 0;
        for (const layer of doc.layers) {
            this.appendLayerItem(layer);
        }
    }

    appendLayerItem(layer) {
        const idx = this._layerCounter++;
        const collapseId = `layer-collapse-${idx}`;
        const canvasId = `layer-preview-${idx}`;
        const fullPreviewId = `layer-full-preview-${idx}`;

        const typeIcons = {
            fill: 'fas fa-fill-drip', line: 'fas fa-pen',
            circle: 'fas fa-circle', symbol: 'fas fa-font',
            raster: 'fas fa-image', background: 'fas fa-square',
            'fill-extrusion': 'fas fa-cube', heatmap: 'fas fa-fire'
        };
        const typeBadgeColors = {
            fill: 'primary', line: 'success', circle: 'info',
            symbol: 'warning', raster: 'secondary', background: 'dark'
        };

        const icon = typeIcons[layer.type] || 'fas fa-layer-group';
        const badgeColor = typeBadgeColors[layer.type] || 'secondary';
        const layerId = layer.id || `layer-${idx}`;
        const ruleInfo = this._parseRuleInfo(layerId);
        const filterDesc = layer.filter ? this._describeFilter(layer.filter) : '';

        // Clone template
        const frag = document.getElementById('tpl-layer-item').content.cloneNode(true);
        const item = frag.querySelector('.accordion-item');
        item.dataset.layerIndex = idx;

        // Header button
        const btn = item.querySelector('.accordion-button');
        btn.setAttribute('data-bs-target', '#' + collapseId);

        // Mini preview canvas
        const miniCanvas = item.querySelector('.layer-mini-preview');
        miniCanvas.id = canvasId;

        // Type icon
        const iconEl = item.querySelector('.accordion-button > i');
        iconEl.className = icon + ' me-2';

        // Type badge
        const badge = item.querySelector('.-js-type-badge');
        badge.className = `badge bg-${badgeColor} me-2`;
        badge.textContent = layer.type;

        // Layer ID label
        item.querySelector('.layer-id-label').textContent = ruleInfo.shortLabel || layerId;

        // Rule badge (conditional)
        if (ruleInfo.ruleTag) {
            const ruleBadge = item.querySelector('.layer-rule-badge');
            ruleBadge.textContent = ruleInfo.ruleTag;
            ruleBadge.classList.remove('d-none');
        }

        // Filter description (conditional)
        if (filterDesc) {
            const filterEl = item.querySelector('.layer-filter-desc');
            filterEl.title = filterDesc;
            filterEl.querySelector('.-js-filter-text').textContent = filterDesc;
            filterEl.classList.remove('d-none');
        }

        // Collapse body
        item.querySelector('.accordion-collapse').id = collapseId;

        // Full preview canvas
        const fullCanvas = item.querySelector('.layer-full-canvas');
        fullCanvas.id = fullPreviewId;

        // Paint controls
        const paintContainer = item.querySelector('.layer-paint-controls');
        this._populatePaintControls(paintContainer, layer.type || 'fill', layer.paint || {}, idx, layer.layout || {});

        // Remove button
        item.querySelector('.-fn-remove-layer').dataset.layerIndex = idx;

        this.layerAccordion.appendChild(frag);

        const onLayerChange = () => {
            this.syncLayersToJson();
            const updatedLayer = this._readLayerFromItem(item, this.editor.getJsonFromTextarea());
            this.drawLayerMiniPreview(canvasId, updatedLayer);
            this.drawLayerFullPreview(fullPreviewId, updatedLayer);
            this.editor.drawPreview();
        };

        this.drawLayerMiniPreview(canvasId, layer);
        this.drawLayerFullPreview(fullPreviewId, layer);

        item.querySelectorAll('[data-layer-prop]').forEach(el => {
            el.addEventListener('input', onLayerChange);
            el.addEventListener('change', onLayerChange);
        });

        try {
            $(item).find('.-js-colorpicker').colorpicker({format: 'hex'});
            $(item).find('.-js-colorpicker').on('changeColor', onLayerChange);
        } catch(e) {}

        item.querySelector('.-fn-remove-layer')?.addEventListener('click', () => {
            item.remove();
            this.syncLayersToJson();
            this.editor.drawPreview();
        });
    }

    addNewLayer() {
        const doc = this.editor.getJsonFromTextarea();
        if (!Mapbender.StyleUtils.isMultiLayerStyle(doc)) return;

        const newLayer = {
            type: 'fill',
            id: `new-layer-${this._layerCounter}`,
            paint: { 'fill-color': '#ff0000', 'fill-opacity': 0.8 },
            source: doc.layers[0]?.source || 'vector-source',
            'source-layer': doc.layers[0]?.['source-layer'] || ''
        };
        doc.layers.push(newLayer);
        this.editor.setJsonToTextarea(doc);
        this.appendLayerItem(newLayer);
        this.syncLayersToJson();
        this.editor.drawPreview();
    }

    syncLayersToJson() {
        const doc = this.editor.getJsonFromTextarea();
        if (!Mapbender.StyleUtils.isMultiLayerStyle(doc)) return;

        const items = this.layerAccordion.querySelectorAll('.accordion-item');
        const layers = [];
        items.forEach(item => {
            const layer = this._readLayerFromItem(item, doc);
            layers.push(layer);
        });
        doc.layers = layers;
        this.editor.setJsonToTextarea(doc);

        this.layerAccordion.querySelectorAll('input[type="range"]').forEach(input => {
            const vp = input.closest('.row')?.querySelector('.layer-range-preview');
            if (vp) vp.textContent = parseFloat(input.value).toFixed(2);
        });
    }

    // ── Paint control builders (DOM-based, using <template> elements) ──

    _populatePaintControls(container, type, paint, idx, layout) {
        container.innerHTML = '';
        layout = layout || {};
        if (type === 'fill') {
            container.appendChild(this._createColorRow('fill-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.fill_color'), paint['fill-color'] || '#000000'));
            container.appendChild(this._createRangeRow('fill-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.fill_opacity'), paint['fill-opacity'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createColorRow('fill-outline-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.outline_color'), paint['fill-outline-color'] || ''));
        } else if (type === 'line') {
            container.appendChild(this._createColorRow('line-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_color'), paint['line-color'] || '#000000'));
            container.appendChild(this._createNumberRow('line-width', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_width'), this._scalarVal(paint['line-width'], 2), 0, 50, 0.5));
            container.appendChild(this._createRangeRow('line-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_opacity'), paint['line-opacity'] ?? 1, 0, 1, 'any'));
        } else if (type === 'circle') {
            container.appendChild(this._createNumberRow('circle-radius', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_radius'), paint['circle-radius'] || 5, 0, 50, 1));
            container.appendChild(this._createColorRow('circle-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_color'), paint['circle-color'] || '#000000'));
            container.appendChild(this._createRangeRow('circle-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_opacity'), paint['circle-opacity'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createColorRow('circle-stroke-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.stroke_color'), paint['circle-stroke-color'] || ''));
            container.appendChild(this._createNumberRow('circle-stroke-width', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.stroke_width'), paint['circle-stroke-width'] || 0, 0, 10, 0.5));
        } else if (type === 'symbol') {
            container.appendChild(this._createSymbolControls(layout, paint));
            container.appendChild(this._createColorRow('text-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.text_color'), paint['text-color'] || '#000000'));
            container.appendChild(this._createRangeRow('text-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.text_opacity'), paint['text-opacity'] ?? 1, 0, 1, 'any'));
        } else if (type === 'background') {
            container.appendChild(this._createColorRow('background-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.background_color'), paint['background-color'] || '#000000'));
            container.appendChild(this._createRangeRow('background-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.background_opacity'), paint['background-opacity'] ?? 1, 0, 1, 'any'));
        } else if (type === 'raster') {
            container.appendChild(this._createRangeRow('raster-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.raster_opacity'), paint['raster-opacity'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createRangeRow('raster-brightness-min', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.raster_brightness_min'), paint['raster-brightness-min'] ?? 0, 0, 1, 'any'));
            container.appendChild(this._createRangeRow('raster-brightness-max', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.raster_brightness_max'), paint['raster-brightness-max'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createRangeRow('raster-contrast', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.raster_contrast'), paint['raster-contrast'] ?? 0, -1, 1, 'any'));
            container.appendChild(this._createRangeRow('raster-saturation', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.raster_saturation'), paint['raster-saturation'] ?? 0, -1, 1, 'any'));
        } else if (type === 'fill-extrusion') {
            container.appendChild(this._createColorRow('fill-extrusion-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.extrusion_color'), paint['fill-extrusion-color'] || '#000000'));
            container.appendChild(this._createRangeRow('fill-extrusion-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.extrusion_opacity'), paint['fill-extrusion-opacity'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createNumberRow('fill-extrusion-height', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.extrusion_height'), this._scalarVal(paint['fill-extrusion-height'], 0), 0, 10000, 1));
            container.appendChild(this._createNumberRow('fill-extrusion-base', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.extrusion_base'), this._scalarVal(paint['fill-extrusion-base'], 0), 0, 10000, 1));
        } else if (type === 'heatmap') {
            container.appendChild(this._createNumberRow('heatmap-radius', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.heatmap_radius'), this._scalarVal(paint['heatmap-radius'], 30), 1, 200, 1));
            container.appendChild(this._createRangeRow('heatmap-intensity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.heatmap_intensity'), this._scalarVal(paint['heatmap-intensity'], 1), 0, 10, 'any'));
            container.appendChild(this._createRangeRow('heatmap-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.heatmap_opacity'), paint['heatmap-opacity'] ?? 1, 0, 1, 'any'));
            container.appendChild(this._createNumberRow('heatmap-weight', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.heatmap_weight'), this._scalarVal(paint['heatmap-weight'], 1), 0, 100, 0.1));
        } else {
            container.appendChild(document.getElementById('tpl-no-props').content.cloneNode(true));
        }
    }

    _createColorRow(prop, label, value) {
        const frag = document.getElementById('tpl-color-row').content.cloneNode(true);
        frag.querySelector('label').textContent = label;
        const input = frag.querySelector('input');
        input.setAttribute('data-layer-prop', prop);
        input.value = value;
        return frag;
    }

    _createRangeRow(prop, label, value, min, max, step) {
        const frag = document.getElementById('tpl-range-row').content.cloneNode(true);
        frag.querySelector('.-js-label-text').textContent = label;
        frag.querySelector('.layer-range-preview').textContent = parseFloat(value).toFixed(2);
        const input = frag.querySelector('input');
        input.setAttribute('data-layer-prop', prop);
        input.min = min;
        input.max = max;
        input.step = step;
        input.value = value;
        return frag;
    }

    _createNumberRow(prop, label, value, min, max, step) {
        const frag = document.getElementById('tpl-number-row').content.cloneNode(true);
        frag.querySelector('label').textContent = label;
        const input = frag.querySelector('input');
        input.setAttribute('data-layer-prop', prop);
        input.min = min;
        input.max = max;
        input.step = step;
        input.value = value;
        return frag;
    }

    _createSymbolControls(layout, paint) {
        const frag = document.getElementById('tpl-symbol-controls').content.cloneNode(true);
        const tf = layout['text-field'];
        const textVal = tf ? (Array.isArray(tf) ? JSON.stringify(tf) : String(tf)) : '';
        frag.querySelector('[data-layer-prop="text-field"]').value = textVal;
        frag.querySelector('[data-layer-prop="text-size"]').value = layout['text-size'] || 12;
        frag.querySelector('[data-layer-prop="text-font"]').value = (layout['text-font'] || []).join(', ');
        return frag;
    }

    // ── Layer data helpers ──

    _readLayerFromItem(item, doc) {
        const origIdx = parseInt(item.dataset.layerIndex);
        const origLayer = doc.layers?.[origIdx] || {};
        const type = origLayer.type || 'fill';

        const layer = {
            ...origLayer,
            paint: {},
            layout: origLayer.layout || {}
        };

        const paintControls = item.querySelector('.layer-paint-controls');
        if (paintControls) {
            paintControls.querySelectorAll('[data-layer-prop]').forEach(el => {
                const prop = el.dataset.layerProp;
                const val = el.value;
                this._applyLayerPropValue(layer, type, prop, val);
            });
        }
        return layer;
    }

    _applyLayerPropValue(layer, type, prop, val) {
        const numericPaint = new Set([
            'fill-opacity', 'line-width', 'line-opacity',
            'circle-radius', 'circle-opacity', 'circle-stroke-width',
            'text-opacity',
            'background-opacity',
            'raster-opacity', 'raster-brightness-min', 'raster-brightness-max', 'raster-contrast', 'raster-saturation',
            'fill-extrusion-opacity', 'fill-extrusion-height', 'fill-extrusion-base',
            'heatmap-radius', 'heatmap-intensity', 'heatmap-opacity', 'heatmap-weight',
        ]);
        const colorPaint = new Set([
            'fill-color', 'fill-outline-color', 'line-color',
            'circle-color', 'circle-stroke-color', 'text-color',
            'background-color', 'fill-extrusion-color',
        ]);

        if (type === 'symbol') {
            if (prop === 'text-field') {
                try { layer.layout['text-field'] = JSON.parse(val); }
                catch(e) { layer.layout['text-field'] = val; }
                return;
            }
            if (prop === 'text-size') { layer.layout['text-size'] = parseFloat(val); return; }
            if (prop === 'text-font') {
                layer.layout['text-font'] = val ? val.split(',').map(s => s.trim()) : [];
                return;
            }
        }

        if (colorPaint.has(prop)) {
            if (val) layer.paint[prop] = val;
        } else if (numericPaint.has(prop)) {
            layer.paint[prop] = parseFloat(val);
        }
    }

    _scalarVal(val, fallback) {
        if (typeof val === 'number') return val;
        if (typeof val === 'object' && val?.stops) return val.stops[0]?.[1] || fallback;
        return fallback;
    }

    // ── Rule & filter info ──

    _parseRuleInfo(layerId) {
        const result = { shortLabel: layerId, ruleTag: '' };
        const ruleMatch = layerId.match(/\(rule#(\d+)\)(labeling)?/);
        if (ruleMatch) {
            const ruleNum = ruleMatch[1];
            const isLabeling = !!ruleMatch[2];
            result.ruleTag = isLabeling ? `Rule #${ruleNum} (Label)` : `Rule #${ruleNum}`;
            const baseName = layerId.split(':(rule#')[0];
            result.shortLabel = baseName.replace(/ Kopie$/, '');
        }
        return result;
    }

    _describeFilter(filter) {
        if (!Array.isArray(filter) || filter.length === 0) return '';
        const op = filter[0];
        if (['==', '!=', '<', '>', '<=', '>='].includes(op)) {
            const prop = Array.isArray(filter[1]) && filter[1][0] === 'get' ? filter[1][1] : filter[1];
            return `${prop} ${op} ${JSON.stringify(filter[2])}`;
        }
        if (['all', 'any', 'none'].includes(op)) {
            const parts = filter.slice(1).map(f => this._describeFilter(f)).filter(Boolean);
            const joiner = op === 'all' ? ' AND ' : op === 'any' ? ' OR ' : ' NOR ';
            return parts.length ? `(${parts.join(joiner)})` : '';
        }
        if (op === 'has') return `has ${filter[1]}`;
        if (op === '!has') return `!has ${filter[1]}`;
        if (op === 'in') return `${filter[1]} in [${filter.slice(2).join(', ')}]`;
        return JSON.stringify(filter);
    }

    // ── Layer previews ──

    drawLayerMiniPreview(canvasId, layer) {
        const c = document.getElementById(canvasId);
        if (!c) return;
        const ctx = c.getContext('2d');
        const w = c.width, h = c.height;
        ctx.clearRect(0, 0, w, h);

        const p = layer.paint || {};
        const lo = layer.layout || {};
        const type = layer.type;

        if (type === 'fill') {
            const color = p['fill-color'] || '#cccccc';
            const opacity = p['fill-opacity'] ?? 0.8;
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.strokeStyle = p['fill-outline-color'] || '#666';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(4, 4); ctx.lineTo(w - 4, 6); ctx.lineTo(w - 6, h - 4);
            ctx.lineTo(6, h - 4); ctx.closePath();
            ctx.fill(); ctx.stroke();
        } else if (type === 'line') {
            const color = p['line-color'] || '#000000';
            const opacity = p['line-opacity'] ?? 1;
            const lw = this._scalarVal(p['line-width'], 2);
            ctx.strokeStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.lineWidth = Math.min(lw, 4);
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(3, h - 3); ctx.lineTo(w / 2, 5); ctx.lineTo(w - 3, h - 3);
            ctx.stroke();
        } else if (type === 'circle') {
            const color = p['circle-color'] || '#000000';
            const opacity = p['circle-opacity'] ?? 1;
            const r = Math.min(p['circle-radius'] || 5, 8);
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.beginPath();
            ctx.arc(w / 2, h / 2, r, 0, 2 * Math.PI);
            ctx.fill();
            if (p['circle-stroke-color']) {
                ctx.strokeStyle = p['circle-stroke-color'];
                ctx.lineWidth = Math.min(p['circle-stroke-width'] || 1, 2);
                ctx.stroke();
            }
        } else if (type === 'symbol') {
            const color = p['text-color'] || '#000000';
            const size = Math.min(lo['text-size'] || 12, 14);
            ctx.fillStyle = color;
            ctx.font = `bold ${size}px sans-serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('T', w / 2, h / 2);
        } else if (type === 'background') {
            const color = p['background-color'] || '#000000';
            const opacity = p['background-opacity'] ?? 1;
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.fillRect(2, 2, w - 4, h - 4);
        } else if (type === 'raster') {
            const opacity = p['raster-opacity'] ?? 1;
            ctx.fillStyle = `rgba(100,180,100,${opacity})`;
            ctx.fillRect(2, 2, w - 4, h - 4);
            ctx.strokeStyle = '#6c6';
            ctx.lineWidth = 1;
            for (let i = 0; i < 3; i++) { ctx.strokeRect(4+i*9, 4, 8, h-8); }
        } else if (type === 'fill-extrusion') {
            const color = p['fill-extrusion-color'] || '#888888';
            const opacity = p['fill-extrusion-opacity'] ?? 1;
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.fillRect(6, 6, w/2-4, h-12);
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity * 0.7);
            ctx.beginPath(); ctx.moveTo(6+w/2-4, 6); ctx.lineTo(w-4, 2); ctx.lineTo(w-4, h-8); ctx.lineTo(6+w/2-4, h-6); ctx.closePath(); ctx.fill();
        } else if (type === 'heatmap') {
            const gradient = ctx.createRadialGradient(w/2, h/2, 2, w/2, h/2, Math.min(w,h)/2);
            gradient.addColorStop(0, 'rgba(255,0,0,0.8)');
            gradient.addColorStop(0.5, 'rgba(255,255,0,0.4)');
            gradient.addColorStop(1, 'rgba(0,0,255,0)');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, w, h);
        } else {
            ctx.fillStyle = '#e9ecef';
            ctx.fillRect(2, 2, w - 4, h - 4);
            ctx.fillStyle = '#999';
            ctx.font = '9px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('?', w / 2, h / 2);
        }
    }

    drawLayerFullPreview(canvasId, layer) {
        const c = document.getElementById(canvasId);
        if (!c) return;
        const ctx = c.getContext('2d');
        const w = c.width, h = c.height;
        ctx.clearRect(0, 0, w, h);

        ctx.fillStyle = '#f8f9fa';
        ctx.fillRect(0, 0, w, h);
        ctx.strokeStyle = '#dee2e6';
        ctx.lineWidth = 1;
        ctx.strokeRect(0.5, 0.5, w - 1, h - 1);

        const p = layer.paint || {};
        const lo = layer.layout || {};
        const type = layer.type;

        if (type === 'fill') {
            const color = p['fill-color'] || '#cccccc';
            const opacity = p['fill-opacity'] ?? 0.8;
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.strokeStyle = p['fill-outline-color'] || '#888';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            ctx.moveTo(w * 0.15, h * 0.2);
            ctx.lineTo(w * 0.75, h * 0.15);
            ctx.lineTo(w * 0.85, h * 0.55);
            ctx.lineTo(w * 0.65, h * 0.85);
            ctx.lineTo(w * 0.25, h * 0.8);
            ctx.lineTo(w * 0.1, h * 0.5);
            ctx.closePath();
            ctx.fill(); ctx.stroke();
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_polygon'), w - 8, h - 6);
        } else if (type === 'line') {
            const color = p['line-color'] || '#000000';
            const opacity = p['line-opacity'] ?? 1;
            const lw = Math.min(this._scalarVal(p['line-width'], 2), 8);
            ctx.strokeStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.lineWidth = lw;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.beginPath();
            ctx.moveTo(15, h * 0.7);
            ctx.quadraticCurveTo(w * 0.25, h * 0.15, w * 0.45, h * 0.5);
            ctx.quadraticCurveTo(w * 0.65, h * 0.85, w * 0.8, h * 0.3);
            ctx.lineTo(w - 15, h * 0.4);
            ctx.stroke();
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_line'), w - 8, h - 6);
        } else if (type === 'circle') {
            const color = p['circle-color'] || '#000000';
            const opacity = p['circle-opacity'] ?? 1;
            const r = Math.min(p['circle-radius'] || 5, 14);
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            const pts = [[w * 0.25, h * 0.4], [w * 0.5, h * 0.3], [w * 0.7, h * 0.55], [w * 0.4, h * 0.65]];
            for (const [px, py] of pts) {
                ctx.beginPath();
                ctx.arc(px, py, r, 0, 2 * Math.PI);
                ctx.fill();
                if (p['circle-stroke-color']) {
                    ctx.strokeStyle = p['circle-stroke-color'];
                    ctx.lineWidth = Math.min(p['circle-stroke-width'] || 1, 3);
                    ctx.stroke();
                }
            }
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_point'), w - 8, h - 6);
        } else if (type === 'symbol') {
            const color = p['text-color'] || '#000000';
            const opacity = p['text-opacity'] ?? 1;
            const size = Math.min(lo['text-size'] || 12, 20);
            const tf = lo['text-field'];
            const sampleText = tf ? (Array.isArray(tf) ? `{${tf[1] || 'attr'}}` : String(tf)) : 'Label';
            const fontStr = lo['text-font']?.[0] || 'sans-serif';
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.font = `${size}px ${fontStr}`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(sampleText, w * 0.3, h * 0.35);
            ctx.fillText(sampleText, w * 0.65, h * 0.55);
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_symbol'), w - 8, h - 6);
        } else if (type === 'background') {
            const color = p['background-color'] || '#000000';
            const opacity = p['background-opacity'] ?? 1;
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.fillRect(w * 0.1, h * 0.1, w * 0.8, h * 0.8);
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_background'), w - 8, h - 6);
        } else if (type === 'raster') {
            const opacity = p['raster-opacity'] ?? 1;
            // Grid pattern to symbolize raster tiles
            for (let row = 0; row < 3; row++) {
                for (let col = 0; col < 5; col++) {
                    const shade = ((row + col) % 2 === 0) ? 160 : 200;
                    ctx.fillStyle = `rgba(${shade},${shade+20},${shade},${opacity})`;
                    ctx.fillRect(w*0.1 + col*(w*0.16), h*0.1 + row*(h*0.26), w*0.15, h*0.24);
                }
            }
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_raster'), w - 8, h - 6);
        } else if (type === 'fill-extrusion') {
            const color = p['fill-extrusion-color'] || '#888888';
            const opacity = p['fill-extrusion-opacity'] ?? 1;
            // Front face
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity);
            ctx.fillRect(w*0.15, h*0.3, w*0.4, h*0.55);
            // Top face
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity * 0.8);
            ctx.beginPath();
            ctx.moveTo(w*0.15, h*0.3); ctx.lineTo(w*0.35, h*0.1);
            ctx.lineTo(w*0.75, h*0.1); ctx.lineTo(w*0.55, h*0.3);
            ctx.closePath(); ctx.fill();
            // Side face
            ctx.fillStyle = StyleUtils.hexToRgba(color, opacity * 0.6);
            ctx.beginPath();
            ctx.moveTo(w*0.55, h*0.3); ctx.lineTo(w*0.75, h*0.1);
            ctx.lineTo(w*0.75, h*0.65); ctx.lineTo(w*0.55, h*0.85);
            ctx.closePath(); ctx.fill();
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_extrusion'), w - 8, h - 6);
        } else if (type === 'heatmap') {
            const opacity = p['heatmap-opacity'] ?? 1;
            const r = Math.min(this._scalarVal(p['heatmap-radius'], 30), 35);
            // Draw a few overlapping radial gradients
            const pts = [[w*0.35, h*0.4], [w*0.55, h*0.35], [w*0.45, h*0.6]];
            for (const [px, py] of pts) {
                const gradient = ctx.createRadialGradient(px, py, 0, px, py, r);
                gradient.addColorStop(0, `rgba(255,0,0,${0.6*opacity})`);
                gradient.addColorStop(0.4, `rgba(255,200,0,${0.3*opacity})`);
                gradient.addColorStop(1, 'rgba(0,0,255,0)');
                ctx.fillStyle = gradient;
                ctx.fillRect(0, 0, w, h);
            }
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_heatmap'), w - 8, h - 6);
        } else {
            ctx.fillStyle = '#adb5bd';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.unknown_layer', {type: type || 'unknown'}), w / 2, h / 2);
        }
    }

}
