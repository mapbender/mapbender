class StyleInstanceEditor {
    constructor() {
        this.$table = $('.collectionTable');
        if (!this.$table.length) return;

        this.styleUrl = this.$table.attr('data-style-url');
        this.styleMap = {};

        this._bindEvents();
        this._loadStyles();
        this._initSortable();
    }

    _bindEvents() {
        // Instant preview on style dropdown change
        $(document).on('change', 'select[id$="_styleId"]', (e) => {
            const popoverBody = e.target.closest('.popover-body');
            if (popoverBody) this.updatePreview(popoverBody);
        });

        // Popover toggling
        this.$table.on('click', '.-fn-toggle-layer-detail', (e) => {
            const $trigger = $(e.currentTarget);
            const target = $trigger.attr('data-toggle-target');
            const $target = $(target);
            $target.parent().toggleClass('display');
            $target.toggleClass('show');
            const otherPopovers = `.popover:not(${target})`;
            $(otherPopovers).parent().removeClass('display');
            $(otherPopovers).removeClass('show');
            if ($target.hasClass('show')) {
                const body = $target.find('.popover-body')[0];
                if (body) this.updatePreview(body);
            }
        });

        // Confirm button closes popover and updates the styled checkbox
        this.$table.on('click', '.-fn-confirm-style', (e) => {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const target = $btn.attr('data-toggle-target');
            const $target = $(target);
            $target.parent().removeClass('display');
            $target.removeClass('show');
            const $row = $btn.closest('tr');
            const select = $row.find('select[id$="_styleId"]')[0];
            const checkbox = $row.find('.style-indicator')[0];
            if (select && checkbox) {
                checkbox.checked = !!select.value;
            }
        });
    }

    _loadStyles() {
        if (!this.styleUrl) return;
        $.getJSON(this.styleUrl, (data) => {
            this.styleMap = data;
            document.querySelectorAll('.popover-body').forEach(body => {
                this.updatePreview(body);
            });
        });
    }

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
        popoverBody.querySelectorAll('.layer-preview canvas').forEach(c => {
            Mapbender.StyleUtils.drawStyleCanvas(c, s, {collectionId});
        });
    }
}
$(function() { new StyleInstanceEditor(); });
