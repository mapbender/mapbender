/**
 * Main Style Editor controller.
 * Coordinates the visual/JSON/import tabs, flat editor sync, and form validation.
 *
 * Requires: StyleUtils (style-utils.js), StyleEditorLayers (style-editor-layers.js),
 *           StyleEditorPreview (style-editor-preview.js), SldConverter (sld-converter.js)
 */
class StyleEditor {
    constructor() {
        this.visualPane = document.getElementById('pane-visual');
        if (!this.visualPane) return;

        this.styleTextarea = document.getElementById('style');
        this.flatEditor = document.getElementById('flat-editor');
        this.layersEditor = document.getElementById('layers-editor');
        this.layerAccordion = document.getElementById('layer-accordion');
        this.visualInputs = this.flatEditor.querySelectorAll('[data-prop]');
        this.collectionId = this.styleTextarea.getAttribute('data-collection-id') || null;

        this.dashMap = {
            solid: [], dot: [1,5], dash: [10,10], longdash: [20,10],
            dashdot: [10,10,1,10], longdashdot: [20,10,1,10]
        };
        this.sourceTypeInput = document.getElementById('sourceType');

        this._isVirgin = this.styleTextarea.getAttribute('data-is-new') === '1';
        this._updateImportWarning();

        // Initialize sub-modules
        this._layers = new StyleEditorLayers(this);
        this._preview = new StyleEditorPreview(this.dashMap);

        this._bindEvents();
        this.updateRangePreviews();
        this.reinitColorpickers();

        // Format JSON on load
        const json = this.getJsonFromTextarea();
        if (this.styleTextarea.value.trim() && Object.keys(json).length) {
            this.setJsonToTextarea(json);
        }
        if (this._isMultiLayerStyle(json)) {
            this._showLayersEditor(json);
        } else if (this.styleTextarea.value.trim()) {
            this.syncJsonToVisual();
        }
        this.drawPreview();
    }

    // ── Event binding ──

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
            const json = this.getJsonFromTextarea();
            if (!this._isMultiLayerStyle(json)) {
                this.syncVisualToJson();
            } else {
                this.setJsonToTextarea(json);
            }
        });

        const importTab = document.getElementById('tab-import');
        if (importTab) {
            importTab.addEventListener('shown.bs.tab', () => this._updateImportWarning());
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
            addBtn.addEventListener('click', () => this._layers.addNewLayer());
        }

        this._bindFormValidation();
    }

    _bindFormValidation() {
        const form = this.styleTextarea.closest('form');
        if (!form) return;

        form.addEventListener('submit', (e) => {
            const raw = this.styleTextarea.value.trim();
            if (!raw) return;
            try {
                const parsed = JSON.parse(raw);
                this.setJsonToTextarea(parsed);
            } catch (err) {
                e.preventDefault();
                this.styleTextarea.classList.add('is-invalid');
                const jsonTab = document.getElementById('tab-json');
                if (jsonTab) new bootstrap.Tab(jsonTab).show();
                let feedback = this.styleTextarea.parentElement.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    this.styleTextarea.parentElement.appendChild(feedback);
                }
                feedback.textContent = Mapbender.trans('mb.ogcapifeatures.admin.style.editor.invalid_json', {error: err.message});
                const posMatch = err.message.match(/position\s+(\d+)/i);
                if (posMatch) {
                    const errorPos = parseInt(posMatch[1], 10);
                    this.styleTextarea.focus();
                    this.styleTextarea.setSelectionRange(errorPos, errorPos + 1);
                    const textBefore = this.styleTextarea.value.substring(0, errorPos);
                    const lineNumber = textBefore.split('\n').length;
                    const lineHeight = parseFloat(getComputedStyle(this.styleTextarea).lineHeight) || 18;
                    this.styleTextarea.scrollTop = Math.max(0, (lineNumber - 3) * lineHeight);
                }
            }
        });

        this.styleTextarea.addEventListener('input', () => {
            this.styleTextarea.classList.remove('is-invalid');
        });
    }

    // ── Editor mode switching ──

    _showLayersEditor(doc) {
        this.flatEditor.classList.add('d-none');
        this.layersEditor.classList.remove('d-none');
        this._layers.buildAccordion(doc);
    }

    _showFlatEditor() {
        this.layersEditor.classList.add('d-none');
        this.flatEditor.classList.remove('d-none');
    }

    // ── JSON ↔ Visual sync ──

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
        if (this._isMultiLayerStyle(obj)) return;
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

    // ── Preview ──

    drawPreview() {
        const json = this.getJsonFromTextarea();
        if (this._isMultiLayerStyle(json)) return;
        this._preview.drawAll(this.getVisualStyle());
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

    // ── Import ──

    _updateImportWarning() {
        const isNonVirgin = !this._isVirgin;
        const tabBadge = document.getElementById('import-tab-warning');
        const paneWarning = document.getElementById('import-overwrite-warning');
        if (tabBadge) tabBadge.classList.toggle('d-none', !isNonVirgin);
        if (paneWarning) paneWarning.classList.toggle('d-none', !isNonVirgin);
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
        const jsonTab = document.getElementById('tab-json');
        if (jsonTab) new bootstrap.Tab(jsonTab).show();
    }

    _showWarnings(container, warnings) {
        container.classList.remove('d-none');
        container.innerHTML = '';
        const tpl = document.getElementById('tpl-warning-alert');
        for (const w of warnings) {
            const frag = tpl.content.cloneNode(true);
            frag.querySelector('.-js-warning-text').textContent = w;
            container.appendChild(frag);
        }
    }
}

$(function() { new StyleEditor(); });
