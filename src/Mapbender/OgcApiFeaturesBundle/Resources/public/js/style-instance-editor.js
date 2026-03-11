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
            document.querySelectorAll('.popover-body').forEach(body => {
                this.updatePreview(body);
            });
        });
    }

    _snapshotOptions() {
        document.querySelectorAll('select[id$="_styleId"]').forEach(select => {
            const opts = [];
            for (const opt of select.options) {
                opts.push({ value: opt.value, text: opt.textContent });
            }
            this._originalOptions.set(select, opts);
            this._savedValues.set(select, select.value);
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
        document.querySelectorAll('.style-filter-name').forEach(el => el.value = this._filterState.name);
        document.querySelectorAll('.style-filter-origin').forEach(el => el.value = this._filterState.origin);
        document.querySelectorAll('.style-filter-source').forEach(el => el.value = this._filterState.source);
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
        if (!active) return 'Apply filter';
        const parts = [];
        if (this._filterState.name) parts.push(`Name: "${this._filterState.name}"`);
        if (this._filterState.origin) parts.push(`Origin: ${this._filterState.origin}`);
        if (this._filterState.source !== this.sourceId) parts.push(`Source: "${this._filterState.source || '(any)'}"`);
        return 'Active filter — ' + parts.join(', ');
    }

    _matchesFilter(entry) {
        if (!entry) return false;
        const nameFilter = this._filterState.name.toLowerCase();
        const originFilter = this._filterState.origin;
        const sourceFilter = this._filterState.source.toLowerCase();
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
                if (!li.textContent.includes('(native style)')) {
                    li.textContent += ' (native style)';
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
                    if (!li.textContent.includes('(native style)')) {
                        li.textContent += ' (native style)';
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
            parts.push(savedLabel ? `Changed (was: ${savedLabel})` : 'Changed from saved value');
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
        if (!savedVal) return '-- none --';
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
        const isMultiLayer = s && s.version && Array.isArray(s.layers);
        let msg = previewWrap.querySelector('.preview-message');
        if (isMultiLayer) {
            previewWrap.querySelectorAll('canvas').forEach(c => c.style.display = 'none');
            if (!msg) {
                msg = document.createElement('span');
                msg.className = 'preview-message text-muted small';
                previewWrap.appendChild(msg);
            }
            msg.textContent = `Multi-layer style (${s.layers.length} layers) — no preview`;
            msg.style.display = '';
        } else {
            previewWrap.querySelectorAll('canvas').forEach(c => c.style.display = '');
            if (msg) msg.style.display = 'none';
            previewWrap.querySelectorAll('canvas').forEach(c => {
                Mapbender.StyleUtils.drawStyleCanvas(c, s, {collectionId});
            });
        }
    }
}
$(function() { new StyleInstanceEditor(); });
