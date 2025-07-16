/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
(function ($) {

    function s4() {
        return Math.floor((1 + Math.random()) * 0x10000)
            .toString(16)
            .substring(1);
    }

    function guid() {
        return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
            s4() + '-' + s4() + s4() + s4();
    }

    function addItem(trigger) {
        const collection = $(trigger).closest('.collection'),
            count = $('.collectionItem', collection).length,
            // The prototype text for the new item...
            prototype = collection.data('prototype'),
            // And finally parse the prototype into a new clean item for insertion.
            item = $($.parseHTML(prototype
                .trim()
                .replace(/__name__label__/g, '')
                .replace(/__name__/g, count + '-' + guid()))[0])
                .addClass('collectionItem');

        collection.append(item);
        return item;
    }

    function onFirstInputOfItemChanged(input) {
        const $label = $(input).closest('.card').find('.panel-label');
        let value = input.value;
        if (!value) value = $label.attr('data-unnamed');
        $label.text(value);
    }

    $(document).on('click', '.collection > .collectionAdd', function (e) {
        const $item = addItem(e.target);
        const $collection = $(e.target).closest('.collection');
        const defaults = $collection.data('defaults');
        if (defaults) {
            const findLastPartOfName = new RegExp(/\[([^\[]+)\]$/);
            $item.find('input, select').each(function (index, el) {
                const $field = $(el);
                const matches = $field.attr('name').match(findLastPartOfName);
                if (matches.length < 2) return;
                const name = matches[1];
                if (name in defaults) {
                    $field.val(defaults[name]);
                }
            });
        }
        $item.closest('.collection').trigger('collectionlengthchange');
    });

    $(document).on('click', '.collection .collectionDuplicate', function (e) {
        const $item = addItem(e.target);
        const $originalItemInputs = $(e.target).closest('.collectionItem').find('input, select');
        const $newItemInputs = $item.find('input, select');
        $newItemInputs.each(function (index, el) {
            $(el).val($originalItemInputs.eq(index).val());
            if (index === 0) onFirstInputOfItemChanged(el);
        });

        $item.closest('.collection').trigger('collectionlengthchange');
    });

    $(document).on('click', '.collectionItem .collectionRemove', function () {
        $(this).closest('.collectionItem').remove();
        $(this).closest('.collection').trigger('collectionlengthchange');
    });

    const initPopovers = function ($modal) {
        const popoverTriggerList = $modal.find('[data-bs-toggle="popover"]');
        [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl, {
            html: true,
            placement: 'left',
            trigger: 'click focus',
        }));
    }

    $(document).on('shown.bs.modal', function (e) {
        const $modal = $(e.target);
        initPopovers($modal);

        $modal.on('collectionlengthchange', function () {
            initPopovers($modal);
        });

        $modal.on('click', '.collapse-toggle', function (e) {
            $(e.target).closest('.card').find('.collapse').collapse('toggle');
        });
        $modal.find('.panel-group').on('keyup', '.mb-3.row:first-child input', function (e) {
            onFirstInputOfItemChanged(e.target);
        });
        $modal.find('.panel-group .mb-3.row:first-child input').each(function (i, e) {
            onFirstInputOfItemChanged(e);
        });
    })

})(jQuery);
