class StyleEditor {
    constructor() {
        this.visualPane = document.getElementById('pane-visual');
        if (!this.visualPane) return;

        this.styleTextarea = document.getElementById('style');
        this.flatEditor = document.getElementById('flat-editor');
        this.layersEditor = document.getElementById('layers-editor');
        this.layerAccordion = document.getElementById('layer-accordion');
        this.visualInputs = this.flatEditor.querySelectorAll('[data-prop]');
        const collectionIdAttr = this.styleTextarea.getAttribute('data-collection-id');
        this.collectionId = collectionIdAttr || null;

        this.dashMap = {
            solid: [], dot: [1,5], dash: [10,10], longdash: [20,10],
            dashdot: [10,10,1,10], longdashdot: [20,10,1,10]
        };
        this.sourceTypeInput = document.getElementById('sourceType');
        this._layerCounter = 0;

        // Track virginity: a style is virgin if it's a newly created style (data-is-new="1")
        this._isVirgin = this.styleTextarea.getAttribute('data-is-new') === '1';
        this._updateImportWarning();

        this._bindEvents();
        this.updateRangePreviews();
        this.reinitColorpickers();

        const json = this.getJsonFromTextarea();
        if (this._isMultiLayerStyle(json)) {
            this._showLayersEditor(json);
        } else if (this.styleTextarea.value.trim()) {
            this.syncJsonToVisual();
        }
        this.drawPreview();
    }

    _bindEvents() {
        const onFlatChange = () => {
            this.syncVisualToJson();
            this.updateRangePreviews();
            this.drawPreview();
        };
        this.visualInputs.forEach(el => {
            el.addEventListener('input', onFlatChange);
            el.addEventListener('change', onFlatChange);
        });

        document.getElementById('tab-visual').addEventListener('shown.bs.tab', () => {
            const json = this.getJsonFromTextarea();
            if (this._isMultiLayerStyle(json)) {
                this._showLayersEditor(json);
            } else {
                this._showFlatEditor();
                this.syncJsonToVisual();
            }
            this.drawPreview();
        });
        document.getElementById('tab-json').addEventListener('shown.bs.tab', () => {
            // Sync current visual state to JSON
            const json = this.getJsonFromTextarea();
            if (!this._isMultiLayerStyle(json)) {
                this.syncVisualToJson();
            }
        });

        const importTab = document.getElementById('tab-import');
        if (importTab) {
            importTab.addEventListener('shown.bs.tab', () => {
                this._updateImportWarning();
            });
        }

        const importInput = document.getElementById('import-file');
        if (importInput) {
            importInput.addEventListener('change', (e) => this._handleImportFile(e));
        }

        if (this.styleTextarea) {
            this.styleTextarea.addEventListener('input', () => {
                if (this.sourceTypeInput) this.sourceTypeInput.value = 'manual';
                this._isVirgin = false;
                this._updateImportWarning();
            });
        }

        const addBtn = document.getElementById('btn-add-layer');
        if (addBtn) {
            addBtn.addEventListener('click', () => this._addNewLayer());
        }
    }

    // ── Editor mode switching ──

    _showLayersEditor(doc) {
        this.flatEditor.classList.add('d-none');
        this.layersEditor.classList.remove('d-none');
        this._buildLayerAccordion(doc);
    }

    _showFlatEditor() {
        this.layersEditor.classList.add('d-none');
        this.flatEditor.classList.remove('d-none');
    }

    // ── Per-layer accordion ──

    _buildLayerAccordion(doc) {
        this.layerAccordion.innerHTML = '';
        this._layerCounter = 0;
        for (const layer of doc.layers) {
            this._appendLayerItem(layer);
        }
    }

    _appendLayerItem(layer) {
        const idx = this._layerCounter++;
        const itemId = `layer-item-${idx}`;
        const collapseId = `layer-collapse-${idx}`;
        const canvasId = `layer-preview-${idx}`;

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

        const item = document.createElement('div');
        item.className = 'accordion-item';
        item.id = itemId;
        item.dataset.layerIndex = idx;

        item.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#${collapseId}">
                    <canvas id="${canvasId}" class="layer-mini-preview me-2" width="36" height="24"></canvas>
                    <i class="${icon} me-2"></i>
                    <span class="badge bg-${badgeColor} me-2">${layer.type}</span>
                    <span class="layer-id-label">${this._escHtml(ruleInfo.shortLabel || layerId)}</span>
                    ${ruleInfo.ruleTag ? `<span class="badge bg-light text-dark ms-2 layer-rule-badge">${this._escHtml(ruleInfo.ruleTag)}</span>` : ''}
                    ${filterDesc ? `<span class="layer-filter-desc ms-2" title="${this._escAttr(filterDesc)}"><i class="fas fa-filter me-1"></i>${this._escHtml(filterDesc)}</span>` : ''}
                </button>
            </h2>
            <div id="${collapseId}" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <div class="layer-full-preview mb-3">
                        <canvas id="layer-full-preview-${idx}" class="layer-full-canvas" width="200" height="80"></canvas>
                    </div>
                    <div class="layer-paint-controls">
                        ${this._buildPaintControls(layer.type || 'fill', layer.paint || {}, idx, layer.layout || {})}
                    </div>
                    <div class="text-end mt-2">
                        <button type="button" class="btn btn-sm btn-outline-danger -fn-remove-layer" data-layer-index="${idx}">
                            <i class="fas fa-trash-can me-1"></i>Remove
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.layerAccordion.appendChild(item);

        const fullPreviewId = `layer-full-preview-${idx}`;

        const onLayerChange = () => {
            this._syncLayersToJson();
            const updatedLayer = this._readLayerFromItem(item, this.getJsonFromTextarea());
            this._drawLayerMiniPreview(canvasId, updatedLayer);
            this._drawLayerFullPreview(fullPreviewId, updatedLayer);
            this.drawPreview();
        };

        // Draw per-layer previews
        this._drawLayerMiniPreview(canvasId, layer);
        this._drawLayerFullPreview(fullPreviewId, layer);

        // Bind events for this layer's controls
        item.querySelectorAll('[data-layer-prop]').forEach(el => {
            el.addEventListener('input', onLayerChange);
            el.addEventListener('change', onLayerChange);
        });

        // Bind colorpickers
        try {
            $(item).find('.-js-colorpicker').colorpicker({format: 'hex'});
            $(item).find('.-js-colorpicker').on('changeColor', onLayerChange);
        } catch(e) {}

        // Bind remove
        item.querySelector('.-fn-remove-layer')?.addEventListener('click', () => {
            item.remove();
            this._syncLayersToJson();
            this.drawPreview();
        });
    }

    _buildPaintControls(type, paint, idx, layout) {
        layout = layout || {};
        if (type === 'fill') {
            return this._colorRow('fill-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.fill_color'), paint['fill-color'] || '#000000', idx)
                 + this._rangeRow('fill-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.fill_opacity'), paint['fill-opacity'] ?? 1, idx, 0, 1, 'any')
                 + this._colorRow('fill-outline-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.outline_color'), paint['fill-outline-color'] || '', idx);
        }
        if (type === 'line') {
            return this._colorRow('line-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_color'), paint['line-color'] || '#000000', idx)
                 + this._numberRow('line-width', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_width'), this._scalarVal(paint['line-width'], 2), idx, 0, 50, 0.5)
                 + this._rangeRow('line-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.line_opacity'), paint['line-opacity'] ?? 1, idx, 0, 1, 'any');
        }
        if (type === 'circle') {
            return this._numberRow('circle-radius', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_radius'), paint['circle-radius'] || 5, idx, 0, 50, 1)
                 + this._colorRow('circle-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_color'), paint['circle-color'] || '#000000', idx)
                 + this._rangeRow('circle-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.circle_opacity'), paint['circle-opacity'] ?? 1, idx, 0, 1, 'any')
                 + this._colorRow('circle-stroke-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.stroke_color'), paint['circle-stroke-color'] || '', idx)
                 + this._numberRow('circle-stroke-width', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.stroke_width'), paint['circle-stroke-width'] || 0, idx, 0, 10, 0.5);
        }
        if (type === 'symbol') {
            const tf = layout['text-field'];
            const textVal = tf ? (Array.isArray(tf) ? JSON.stringify(tf) : String(tf)) : '';
            return `
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <label class="form-label fw-bold">${Mapbender.trans('mb.ogcapifeatures.admin.style.editor.text_field')}</label>
                        <input data-layer-prop="text-field" type="text" class="form-control form-control-sm"
                               value="${this._escAttr(textVal)}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label fw-bold">${Mapbender.trans('mb.ogcapifeatures.admin.style.editor.size')}</label>
                        <input data-layer-prop="text-size" type="number" min="1" max="60" step="1"
                               class="form-control form-control-sm" value="${layout['text-size'] || 12}">
                    </div>
                    <div class="col-sm-3">
                        <label class="form-label fw-bold">${Mapbender.trans('mb.ogcapifeatures.admin.style.editor.font')}</label>
                        <input data-layer-prop="text-font" type="text" class="form-control form-control-sm"
                               value="${this._escAttr((layout['text-font'] || []).join(', '))}">
                    </div>
                </div>
            ` + this._colorRow('text-color', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.text_color'), paint['text-color'] || '#000000', idx)
              + this._rangeRow('text-opacity', Mapbender.trans('mb.ogcapifeatures.admin.style.editor.text_opacity'), paint['text-opacity'] ?? 1, idx, 0, 1, 'any');
        }
        return '<p class="text-muted small">' + Mapbender.trans('mb.ogcapifeatures.admin.style.editor.no_editable_properties') + '</p>';
    }

    _colorRow(prop, label, value) {
        return `
            <div class="row mb-2">
                <div class="col-sm-6">
                    <label class="form-label fw-bold">${label}</label>
                    <div class="input-group input-group-sm -js-colorpicker">
                        <input data-layer-prop="${prop}" class="form-control form-control-sm" type="text"
                               value="${this._escAttr(value)}">
                        <span class="input-group-text input-group-addon"><i></i></span>
                    </div>
                </div>
            </div>
        `;
    }

    _rangeRow(prop, label, value, _idx, min, max, step) {
        return `
            <div class="row mb-2">
                <div class="col-sm-6">
                    <label class="form-label fw-bold w-100">${label} <span class="float-end badge bg-secondary layer-range-preview">${parseFloat(value).toFixed(2)}</span></label>
                    <input data-layer-prop="${prop}" class="form-range" type="range"
                           min="${min}" max="${max}" step="${step}" value="${value}">
                </div>
            </div>
        `;
    }

    _numberRow(prop, label, value, _idx, min, max, step) {
        return `
            <div class="row mb-2">
                <div class="col-sm-4">
                    <label class="form-label fw-bold">${label}</label>
                    <input data-layer-prop="${prop}" type="number" min="${min}" max="${max}" step="${step}"
                           class="form-control form-control-sm" value="${value}">
                </div>
            </div>
        `;
    }

    _scalarVal(val, fallback) {
        if (typeof val === 'number') return val;
        if (typeof val === 'object' && val?.stops) return val.stops[0]?.[1] || fallback;
        return fallback;
    }

    _escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    _escAttr(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

    // ── Rule info parsing ──

    /** Parse a Mapbox layer ID like "ave:Flurstueck:(rule#0):1" into readable parts */
    _parseRuleInfo(layerId) {
        const result = { shortLabel: layerId, ruleTag: '' };
        // Match patterns like "(rule#N)" or "(rule#N)labeling"
        const ruleMatch = layerId.match(/\(rule#(\d+)\)(labeling)?/);
        if (ruleMatch) {
            const ruleNum = ruleMatch[1];
            const isLabeling = !!ruleMatch[2];
            result.ruleTag = isLabeling ? `Rule #${ruleNum} (Label)` : `Rule #${ruleNum}`;
            // Extract the base name (before the rule info)
            const baseName = layerId.split(':(rule#')[0];
            // Remove " Kopie" suffix if present
            result.shortLabel = baseName.replace(/ Kopie$/, '');
        }
        return result;
    }

    /** Convert a Mapbox filter expression to a human-readable string */
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
        // Fallback: compact JSON
        return JSON.stringify(filter);
    }

    // ── Per-layer mini-preview ──

    _drawLayerMiniPreview(canvasId, layer) {
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

    /** Draw a full-size geometry preview inside the accordion body for a single layer */
    _drawLayerFullPreview(canvasId, layer) {
        const c = document.getElementById(canvasId);
        if (!c) return;
        const ctx = c.getContext('2d');
        const w = c.width, h = c.height;
        ctx.clearRect(0, 0, w, h);

        // Light background
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
            // Irregular polygon
            ctx.beginPath();
            ctx.moveTo(w * 0.15, h * 0.2);
            ctx.lineTo(w * 0.75, h * 0.15);
            ctx.lineTo(w * 0.85, h * 0.55);
            ctx.lineTo(w * 0.65, h * 0.85);
            ctx.lineTo(w * 0.25, h * 0.8);
            ctx.lineTo(w * 0.1, h * 0.5);
            ctx.closePath();
            ctx.fill(); ctx.stroke();
            // Type label
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
            // Curvy line path
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
            // Draw multiple points spread around
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
            // Draw sample labels at a few positions
            ctx.fillText(sampleText, w * 0.3, h * 0.35);
            ctx.fillText(sampleText, w * 0.65, h * 0.55);
            ctx.fillStyle = '#6c757d';
            ctx.font = '10px sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.geom_symbol'), w - 8, h - 6);
        } else {
            ctx.fillStyle = '#adb5bd';
            ctx.font = '12px sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(Mapbender.trans('mb.ogcapifeatures.admin.style.editor.unknown_layer', {type: type || 'unknown'}), w / 2, h / 2);
        }
    }

    // ── Sync layer controls → textarea JSON ──

    _syncLayersToJson() {
        const doc = this.getJsonFromTextarea();
        if (!this._isMultiLayerStyle(doc)) return;

        const items = this.layerAccordion.querySelectorAll('.accordion-item');
        const layers = [];
        items.forEach(item => {
            const layer = this._readLayerFromItem(item, doc);
            layers.push(layer);
        });
        doc.layers = layers;
        this.setJsonToTextarea(doc);

        // Update range previews
        this.layerAccordion.querySelectorAll('input[type="range"]').forEach(input => {
            const vp = input.closest('.row')?.querySelector('.layer-range-preview');
            if (vp) vp.textContent = parseFloat(input.value).toFixed(2);
        });
    }

    _readLayerFromItem(item, doc) {
        // Find original layer to preserve structural properties (id, type, source, source-layer, filter)
        const origIdx = parseInt(item.dataset.layerIndex);
        const origLayer = doc.layers?.[origIdx] || {};
        const type = origLayer.type || 'fill';

        const layer = {
            ...origLayer,
            paint: {},
            layout: origLayer.layout || {}
        };

        // Read paint/layout props
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

    /** Map a single form control value to the appropriate paint/layout key */
    _applyLayerPropValue(layer, type, prop, val) {
        const numericPaint = new Set([
            'fill-opacity', 'line-width', 'line-opacity',
            'circle-radius', 'circle-opacity', 'circle-stroke-width',
            'text-opacity',
        ]);
        const colorPaint = new Set([
            'fill-color', 'fill-outline-color', 'line-color',
            'circle-color', 'circle-stroke-color', 'text-color',
        ]);

        if (type === 'symbol') {
            // Symbol uses layout for text-field, text-size, text-font
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

    _addNewLayer() {
        const doc = this.getJsonFromTextarea();
        if (!this._isMultiLayerStyle(doc)) return;

        const newLayer = {
            type: 'fill',
            id: `new-layer-${this._layerCounter}`,
            paint: { 'fill-color': '#ff0000', 'fill-opacity': 0.8 },
            source: doc.layers[0]?.source || 'vector-source',
            'source-layer': doc.layers[0]?.['source-layer'] || ''
        };
        doc.layers.push(newLayer);
        this.setJsonToTextarea(doc);
        this._appendLayerItem(newLayer);
        this._syncLayersToJson();
        this.drawPreview();
    }

    _updateImportWarning() {
        const isNonVirgin = !this._isVirgin;
        const tabBadge = document.getElementById('import-tab-warning');
        const paneWarning = document.getElementById('import-overwrite-warning');
        if (tabBadge) tabBadge.classList.toggle('d-none', !isNonVirgin);
        if (paneWarning) paneWarning.classList.toggle('d-none', !isNonVirgin);
    }

    _switchToJsonTab() {
        const jsonTab = document.getElementById('tab-json');
        if (jsonTab) {
            const tab = new bootstrap.Tab(jsonTab);
            tab.show();
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
            this._showWarnings(warningsEl, [Mapbender.trans('mb.ogcapifeatures.admin.style.editor.invalid_json', {error: err.message})]);
            return;
        }
        this.setJsonToTextarea(parsed);
        this._finalizeImport('mapbox-json', successEl);
    }

    _importSld(content, warningsEl, successEl) {
        const result = Mapbender.SldConverter.convert(content);
        if (result.warnings.length) {
            this._showWarnings(warningsEl, result.warnings);
        }
        if (!result.style) return;
        this.setJsonToTextarea(result.style);
        this._finalizeImport('sld', successEl);
    }

    _finalizeImport(sourceType, successEl) {
        const parsed = this.getJsonFromTextarea();
        if (this._isMultiLayerStyle(parsed)) {
            this._showLayersEditor(parsed);
        } else {
            this._showFlatEditor();
            this.syncJsonToVisual();
        }
        this.drawPreview();
        if (this.sourceTypeInput) this.sourceTypeInput.value = sourceType;
        this._isVirgin = false;
        this._updateImportWarning();
        successEl.classList.remove('d-none');
        this._switchToJsonTab();
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

    _isMultiLayerStyle(obj) {
        return Mapbender.StyleUtils.isMultiLayerStyle(obj);
    }

    syncVisualToJson() {
        const obj = this.getJsonFromTextarea();
        if (this._isMultiLayerStyle(obj)) return; // layers editor handles its own sync
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
        if (this._isMultiLayerStyle(obj)) {
            this._showLayersEditor(obj);
            return;
        }
        this._showFlatEditor();
        this.visualInputs.forEach(el => {
            const key = el.getAttribute('data-prop');
            if (obj.hasOwnProperty(key) && obj[key] !== null && obj[key] !== undefined) {
                el.value = obj[key];
            }
        });
        this.updateRangePreviews();
        this.reinitColorpickers();
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
        ctx.textBaseline = 'middle';
        ctx.fillText(text, x, y);
    }

    drawPreview() {
        const json = this.getJsonFromTextarea();
        // For Mapbox documents, per-layer previews handle rendering
        if (this._isMultiLayerStyle(json)) return;

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
                this.drawLabel(ctx, s, w/2, h/2);
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
                this.drawLabel(ctx, s, w/2, h/2);
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
                this.drawLabel(ctx, s, w/2, h/2);
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
