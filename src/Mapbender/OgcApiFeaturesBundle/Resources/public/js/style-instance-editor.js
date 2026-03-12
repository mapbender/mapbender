class StyleInstanceEditor {

    // ── Initialization ──

    constructor() {
        this.$table = $('.collectionTable');
        if (!this.$table.length) return;

        this.styleUrl = this.$table.attr('data-style-url');
        this.sourceId = this.$table.attr('data-source-id') || '';
        this.styleMap = {};
        this._filterState = { name: '', origin: '', source: this.sourceId };
        this._originalOptions = new Map();
        this._savedValues = new Map();
        this._secOriginalOptions = new Map();
        this._secFilterState = { name: '', origin: '', source: this.sourceId };

        this._bindEvents();
        this._loadStyles();
        this._initSortable();
    }

    _loadStyles() {
        if (!this.styleUrl) return;
        $.getJSON(this.styleUrl, (data) => {
            this.styleMap = data;
            this._snapshotOptions();
            this._markNativeStyles();
            this._initFilters();
            this._applyFilters();
            this._initSecFilters();
            document.querySelectorAll('.popover-body').forEach(body => {
                this.updatePreview(body);
            });
        });
    }

    _snapshotOptions() {
        this._collectSelectOptions('select[id$="_styleId"]', this._originalOptions);
        this._originalOptions.forEach((_, select) => this._savedValues.set(select, select.value));
        this._collectSelectOptions('.secondary-style-select', this._secOriginalOptions);
    }

    _collectSelectOptions(selector, targetMap) {
        document.querySelectorAll(selector).forEach(select => {
            const opts = [];
            for (const opt of select.options) {
                opts.push({ value: opt.value, text: opt.textContent });
            }
            targetMap.set(select, opts);
        });
    }

    // ── Events ──

    _bindEvents() {
        $(document).on('change', 'select[id$="_styleId"]', (e) => {
            const popoverBody = e.target.closest('.popover-body');
            if (popoverBody) this.updatePreview(popoverBody);
            this._updateNativeStyleIndicator(e.target);
            this._updateChangedIndicator(e.target);
        });

        this.$table.on('click', '.-fn-toggle-layer-detail', (e) => {
            const $trigger = $(e.currentTarget);
            const target = $trigger.attr('data-toggle-target');
            const $target = $(target);
            $target.parent().toggleClass('display');
            $target.toggleClass('show');
            $(`.popover:not(${target})`).parent().removeClass('display');
            $(`.popover:not(${target})`).removeClass('show');
            if ($target.hasClass('show')) {
                const body = $target.find('.popover-body')[0];
                if (body) this.updatePreview(body);
                const row = $trigger.closest('tr')[0];
                if (body && row) this._initTooltipCheckboxes(body, row);
            }
        });

        this.$table.on('click', '.-fn-close-popover', (e) => {
            e.preventDefault();
            this._dismissPopover($(e.currentTarget));
        });

        this.$table.on('click', '.-fn-confirm-style', (e) => {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            this._dismissPopover($btn);
            const $row = $btn.closest('tr');
            const select = $row.find('select[id$="_styleId"]')[0];
            const checkbox = $row.find('.style-indicator')[0];
            if (select && checkbox) {
                checkbox.checked = !!select.value;
            }
            const secSelect = $row.find('.secondary-style-select')[0];
            if (secSelect) {
                this._updateSecondaryCount(secSelect);
            }
        });

        this.$table.on('click', '.-fn-apply-style-filter', (e) => {
            e.preventDefault();
            const row = e.currentTarget.closest('.style-filter-row');
            if (!row) return;
            this._filterState.name = row.querySelector('.style-filter-name').value;
            this._filterState.origin = row.querySelector('.style-filter-origin').value;
            this._filterState.source = row.querySelector('.style-filter-source').value;
            this._syncFilters();
            this._applyFilters();
            this._updateFilterButtons();
        });

        this.$table.on('click', '.-fn-reset-style-filter', (e) => {
            e.preventDefault();
            this._filterState = { name: '', origin: '', source: this.sourceId };
            this._syncFilters();
            this._applyFilters();
            this._updateFilterButtons();
        });

        // Secondary styles: toggle panel
        this.$table.on('click', '.-fn-toggle-secondary-styles', (e) => {
            e.preventDefault();
            const section = e.currentTarget.closest('.secondary-styles-section');
            const panel = section.querySelector('.secondary-styles-panel');
            const arrow = e.currentTarget.querySelector('.toggle-arrow');
            const visible = panel.style.display !== 'none';
            panel.style.display = visible ? 'none' : '';
            arrow.classList.toggle('fa-chevron-down', visible);
            arrow.classList.toggle('fa-chevron-up', !visible);
        });

        // Secondary styles: apply filter
        this.$table.on('click', '.-fn-apply-sec-filter', (e) => {
            e.preventDefault();
            const row = e.currentTarget.closest('.secondary-filter-row');
            if (!row) return;
            this._secFilterState.name = row.querySelector('.sec-filter-name').value;
            this._secFilterState.origin = row.querySelector('.sec-filter-origin').value;
            this._secFilterState.source = row.querySelector('.sec-filter-source').value;
            this._syncSecFilters();
            this._applySecFilters();
        });

        // Secondary styles: reset filter
        this.$table.on('click', '.-fn-reset-sec-filter', (e) => {
            e.preventDefault();
            this._secFilterState = { name: '', origin: '', source: this.sourceId };
            this._syncSecFilters();
            this._applySecFilters();
        });

        // Secondary styles: update badge on selection change
        this.$table.on('change', '.secondary-style-select', (e) => {
            this._updateSecondaryCount(e.target);
        });
    }

    _dismissPopover($trigger) {
        const target = $trigger.attr('data-toggle-target');
        const $target = $(target);
        $target.parent().removeClass('display');
        $target.removeClass('show');
    }

    // ── Filtering ──

    get _isFilterActive() {
        return !!(this._filterState.name || this._filterState.origin || this._filterState.source !== this.sourceId);
    }

    _initFilters() {
        document.querySelectorAll('.style-filter-source').forEach(input => {
            input.value = this._filterState.source;
        });
        this._updateFilterButtons();
    }

    _syncFilters() {
        this._syncFilterInputs('style-filter', this._filterState);
    }

    _updateFilterButtons() {
        const active = this._isFilterActive;
        const tooltip = this._buildFilterTooltip(active);
        document.querySelectorAll('.-fn-apply-style-filter').forEach(btn => {
            btn.classList.toggle('btn-outline-secondary', !active);
            btn.classList.toggle('btn-primary', active);
            btn.title = tooltip;
        });
        document.querySelectorAll('select[id$="_styleId"]').forEach(select => {
            const dropdown = select.closest('.dropdown');
            if (!dropdown) return;
            dropdown.classList.toggle('filter-active', active);
            this._updateChangedIndicator(select);
        });
    }

    _buildFilterTooltip(active) {
        if (!active) return Mapbender.trans('mb.ogcapifeatures.admin.filter.apply');
        const parts = [];
        if (this._filterState.name) parts.push(Mapbender.trans('mb.ogcapifeatures.admin.filter.name_label') + ' "' + this._filterState.name + '"');
        if (this._filterState.origin) parts.push(Mapbender.trans('mb.ogcapifeatures.admin.filter.origin_label') + ' ' + this._filterState.origin);
        if (this._filterState.source !== this.sourceId) parts.push(Mapbender.trans('mb.ogcapifeatures.admin.filter.source_label') + ' "' + (this._filterState.source || Mapbender.trans('mb.ogcapifeatures.admin.filter.any')) + '"');
        return Mapbender.trans('mb.ogcapifeatures.admin.filter.active') + ' ' + parts.join(', ');
    }

    _matchesFilter(entry, filterState) {
        if (!entry) return false;
        filterState = filterState || this._filterState;
        const nameFilter = filterState.name.toLowerCase();
        const originFilter = filterState.origin;
        const sourceFilter = filterState.source.toLowerCase();
        const name = (entry.name || '').toLowerCase();
        const origin = entry.sourceType || '';
        const source = String(entry.sourceId || '');
        if (nameFilter && !name.includes(nameFilter)) return false;
        if (originFilter && origin !== originFilter) return false;
        if (sourceFilter && source && !source.toLowerCase().includes(sourceFilter)) return false;
        return true;
    }

    _applyFilters() {
        this._originalOptions.forEach((opts, select) => {
            const currentVal = select.value;
            select.innerHTML = '';
            for (const opt of opts) {
                if (!opt.value || opt.value === currentVal) {
                    select.appendChild(new Option(opt.text, opt.value));
                    continue;
                }
                if (this._matchesFilter(this.styleMap[opt.value])) {
                    select.appendChild(new Option(opt.text, opt.value));
                }
            }
            if (currentVal) select.value = currentVal;
            this._rebuildDropdownList(select);
        });
    }

    // ── Secondary Styles Filtering ──

    _initSecFilters() {
        document.querySelectorAll('.sec-filter-source').forEach(input => {
            input.value = this._secFilterState.source;
        });
        this._applySecFilters();
        this._initSecondaryBadges();
    }

    _syncSecFilters() {
        this._syncFilterInputs('sec-filter', this._secFilterState);
    }

    _syncFilterInputs(prefix, filterState) {
        document.querySelectorAll(`.${prefix}-name`).forEach(el => el.value = filterState.name);
        document.querySelectorAll(`.${prefix}-origin`).forEach(el => el.value = filterState.origin);
        document.querySelectorAll(`.${prefix}-source`).forEach(el => el.value = filterState.source);
    }

    _applySecFilters() {
        this._secOriginalOptions.forEach((opts, select) => {
            const selectedVals = new Set(Array.from(select.selectedOptions).map(o => o.value));
            select.innerHTML = '';
            for (const opt of opts) {
                if (selectedVals.has(opt.value)) {
                    const option = new Option(opt.text, opt.value);
                    option.selected = true;
                    select.appendChild(option);
                    continue;
                }
                const entry = this.styleMap[opt.value];
                if (!entry) continue;
                if (this._matchesFilter(entry, this._secFilterState)) {
                    select.appendChild(new Option(opt.text, opt.value));
                }
            }
        });
    }

    _updateSecondaryCount(select) {
        const count = select.selectedOptions.length;
        // Update badge in the popover toggle button
        const section = select.closest('.secondary-styles-section');
        if (section) {
            const badge = section.querySelector('.secondary-count');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? '' : 'none';
            }
        }
        // Update badge in the table row
        const row = select.closest('tr');
        if (row) {
            const rowBadge = row.querySelector('.sec-style-count');
            if (rowBadge) {
                rowBadge.textContent = count;
                rowBadge.style.display = count > 0 ? '' : 'none';
            }
        }
    }

    _initSecondaryBadges() {
        document.querySelectorAll('.secondary-style-select').forEach(select => {
            this._updateSecondaryCount(select);
        });
    }

    // ── Custom Dropdown Sync ──

    _rebuildDropdownList(select) {
        const dropdown = select.closest('.dropdown');
        if (!dropdown) return;
        const list = dropdown.querySelector('.dropdownList');
        if (!list) return;
        const display = dropdown.querySelector('.dropdownValue');
        const nativeId = select.dataset.nativeStyleId || '';
        list.innerHTML = '';
        for (const opt of select.options) {
            const li = document.createElement('li');
            li.className = 'choice';
            li.dataset.value = opt.value;
            li.textContent = opt.textContent;
            if (opt.value === select.value) {
                li.classList.add('selected');
                if (opt.value && !this._matchesFilter(this.styleMap[opt.value])) {
                    li.classList.add('filter-kept');
                }
            }
            if (nativeId && opt.value === nativeId) {
                if (!li.textContent.includes(Mapbender.trans('mb.ogcapifeatures.admin.style.native_style'))) {
                    li.textContent += ' ' + Mapbender.trans('mb.ogcapifeatures.admin.style.native_style');
                }
                li.classList.add('native-style');
            }
            list.appendChild(li);
        }
        if (display) {
            const selectedOpt = select.options[select.selectedIndex];
            display.textContent = selectedOpt ? selectedOpt.textContent : '';
        }
        this._updateNativeStyleIndicator(select);
        this._updateChangedIndicator(select);
    }

    // ── Style Indicators ──

    _markNativeStyles() {
        document.querySelectorAll('select[id$="_styleId"]').forEach(select => {
            const nativeId = select.dataset.nativeStyleId;
            if (!nativeId) return;
            const dropdown = select.closest('.dropdown');
            if (!dropdown) return;
            dropdown.querySelectorAll('.dropdownList .choice').forEach(li => {
                if (li.dataset.value === nativeId) {
                    if (!li.textContent.includes(Mapbender.trans('mb.ogcapifeatures.admin.style.native_style'))) {
                        li.textContent += ' ' + Mapbender.trans('mb.ogcapifeatures.admin.style.native_style');
                    }
                    li.classList.add('native-style');
                }
            });
            this._updateNativeStyleIndicator(select);
        });
    }

    _updateNativeStyleIndicator(select) {
        const nativeId = select.dataset.nativeStyleId;
        const dropdown = select.closest('.dropdown');
        if (!dropdown) return;
        dropdown.classList.toggle('native-style-active', !!(nativeId && select.value === nativeId));
    }

    _updateChangedIndicator(select) {
        const saved = this._savedValues.get(select);
        const dropdown = select.closest('.dropdown');
        if (!dropdown) return;
        const changed = select.value !== saved;
        dropdown.classList.toggle('value-changed', changed);
        this._updateDropdownTooltip(dropdown, select, changed);
    }

    _updateDropdownTooltip(dropdown, select, changed) {
        const parts = [];
        if (changed) {
            const savedLabel = this._getSavedLabel(select);
            parts.push(savedLabel ? Mapbender.trans('mb.ogcapifeatures.admin.style.changed_was', {label: savedLabel}) : Mapbender.trans('mb.ogcapifeatures.admin.style.changed'));
        }
        if (this._isFilterActive) {
            parts.push(this._buildFilterTooltip(true));
        }
        const tip = parts.join('\n');
        dropdown.title = tip;
        const display = dropdown.querySelector('.dropdownValue');
        if (display) display.title = tip;
    }

    _getSavedLabel(select) {
        const savedVal = this._savedValues.get(select);
        if (!savedVal) return Mapbender.trans('mb.ogcapifeatures.admin.style.none');
        const opts = this._originalOptions.get(select) || [];
        const match = opts.find(o => o.value === savedVal);
        return match ? match.text : savedVal;
    }

    // ── Sortable & Preview ──

    _initSortable() {
        this.$table.each(function() {
            $('tbody', this).sortable({
                cursor: 'move',
                axis: 'y',
                items: 'tr',
                distance: 6,
                containment: 'parent',
                stop: () => {
                    $('input[type="hidden"]', $('.collectionTable tbody tr')).each((idx, item) => {
                        $(item).val(idx);
                    });
                }
            });
        });
    }

    updatePreview(popoverBody) {
        const select = popoverBody.querySelector('select[id$="_styleId"]');
        if (!select) return;
        const id = select.value;
        const entry = id ? this.styleMap[id] : null;
        let s = null;
        let collectionId = null;
        if (entry) {
            const raw = entry.style || entry;
            collectionId = entry.collectionId || null;
            try { s = (typeof raw === 'string') ? JSON.parse(raw) : raw; } catch(e) {}
        }
        const previewWrap = popoverBody.querySelector('.layer-preview');
        if (!previewWrap) return;
        const isMultiLayer = Mapbender.StyleUtils.isMultiLayerStyle(s);
        let msg = previewWrap.querySelector('.preview-message');
        if (isMultiLayer) {
            previewWrap.querySelectorAll('canvas').forEach(c => c.style.display = 'none');
            if (!msg) {
                msg = document.createElement('span');
                msg.className = 'preview-message text-muted small';
                previewWrap.appendChild(msg);
            }
            msg.textContent = Mapbender.trans('mb.ogcapifeatures.admin.style.multi_layer_preview', {count: s.layers.length});
            msg.style.display = '';
        } else {
            previewWrap.querySelectorAll('canvas').forEach(c => c.style.display = '');
            if (msg) msg.style.display = 'none';
            previewWrap.querySelectorAll('canvas').forEach(c => {
                Mapbender.StyleUtils.drawStyleCanvas(c, s, {collectionId});
            });
        }
    }

    // ── Tooltip Property Checkboxes ──

    _initTooltipCheckboxes(popoverBody, row) {
        const container = popoverBody.querySelector('.tooltip-checkbox-list');
        if (!container || container.dataset.loaded === 'true') return;

        // Read stored properties from data attribute
        let propNames = [];
        try {
            propNames = JSON.parse(row.dataset.properties || '[]');
        } catch(e) {}
        if (!Array.isArray(propNames)) propNames = [];

        // Find the hidden input for tooltipPropertyMap
        const hiddenInput = popoverBody.querySelector('input[id$="_tooltipPropertyMap"]');
        let selected = [];
        if (hiddenInput && hiddenInput.value) {
            try { selected = JSON.parse(hiddenInput.value); } catch(e) {}
            if (!Array.isArray(selected)) selected = [];
        }

        this._renderCheckboxes(container, propNames, selected, hiddenInput);
    }

    _renderCheckboxes(container, propNames, selected, hiddenInput) {
        container.dataset.loaded = 'true';
        container.innerHTML = '';

        if (propNames.length === 0) {
            container.innerHTML = '<span class="text-muted small">' + (container.dataset.emptyText || 'No properties') + '</span>';
            return;
        }

        propNames.forEach(prop => {
            const id = 'tooltip_cb_' + Math.random().toString(36).substr(2, 6);
            const wrapper = document.createElement('div');
            wrapper.className = 'form-check form-check-inline tooltip-prop-check';

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input';
            cb.id = id;
            cb.value = prop;
            cb.checked = selected.includes(prop);
            cb.addEventListener('change', () => this._syncTooltipHidden(container, hiddenInput));

            const label = document.createElement('label');
            label.className = 'form-check-label small';
            label.htmlFor = id;
            label.textContent = prop;

            wrapper.appendChild(cb);
            wrapper.appendChild(label);
            container.appendChild(wrapper);
        });
    }

    _syncTooltipHidden(container, hiddenInput) {
        if (!hiddenInput) return;
        const checked = [];
        container.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            checked.push(cb.value);
        });
        hiddenInput.value = checked.length > 0 ? JSON.stringify(checked) : '';
    }
}
$(function() { new StyleInstanceEditor(); });
