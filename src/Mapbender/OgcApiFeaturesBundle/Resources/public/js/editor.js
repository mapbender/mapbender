class OgcApiFeaturesEditor {

    constructor() {
        this.$table = $('.collectionTable');
        if (!this.$table.length) return;

        this.styleUrl = this.$table.attr('data-style-url');
        this.sourceId = this.$table.attr('data-source-id') || '';

        this._bindEvents();
        this._initSortable();
    }

    _bindEvents() {
        $(".editCollectionLayer").on("click", e => {
            e.preventDefault();
            const $row = $(e.target).closest('tr');

            const name = $row.find('.titleColumn > input').val();
            Mapbender.startElementEdit($(e.target).closest('.editCollectionLayer').attr('data-url'), {
                title: Mapbender.trans('mb.ogcapifeatures.admin.layer.popup_title', {'name': name})
            }, undefined, ($modal, data) => {
                Mapbender.info(Mapbender.trans('mb.ogcapifeatures.admin.layer.properties_saved'));
                $modal.modal('hide');

                const $hasStyleCheckbox = $row.find('.style-indicator');
                if (data.hasStyle) {
                    $hasStyleCheckbox.attr('checked', 'checked');
                } else {
                    $hasStyleCheckbox.removeAttr('checked');
                }

                const $secStyleBadge = $row.find('.sec-style-count');
                $secStyleBadge.text(data.secondaryStyleCount);
                $secStyleBadge.css('display', data.secondaryStyleCount > 0 ? 'inline-block' : 'none');
            }).then(response => {
                new OgcApiFeaturesEditInstancePopup(response, this.styleUrl, this.sourceId, $row.data('properties'), $row.data('property-titles'));
            });
            return false;
        });
    }

    _initSortable() {
        this.$table.each(function () {
            $('tbody', this).sortable({
                cursor: 'move',
                axis: 'y',
                items: 'tr',
                cancel: '.popover, .static-popover-wrap, input',
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
}

$(function () {
    new OgcApiFeaturesEditor();
});
