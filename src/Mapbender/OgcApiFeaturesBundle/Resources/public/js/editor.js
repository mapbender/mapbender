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
        // TODO: on safe refresh style/sec style checkbox
        $(".editCollectionLayer").on("click", e => {
            e.preventDefault();
            Mapbender.startElementEdit($(e.target).closest('.editCollectionLayer').attr('data-url'), {
                // TODO: translate
                title: "Collection-Layer editieren",
            }, undefined, ($modal, data) => {
                Mapbender.info(Mapbender.trans("mb.application.save.success"));
                $modal.modal('hide');
            }).then(response => {
                const row = $(e.target).closest('tr');
                new OgcApiFeaturesEditInstancePopup(response, this.styleUrl, this.sourceId, row.data('properties'), row.data('property-titles'));
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
                cancel: '.popover, input',
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
