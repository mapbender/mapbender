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

    $(document).on('click', '.collection > .collectionAdd', function () {
        var collection = $(this).closest('.collection'),
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
        collection.trigger('collectionlengthchange');
    });

    $(document).on('click', '.collectionItem .collectionRemove', function () {
        $(this).closest('.collectionItem').remove();
        $(this).closest('.collection').trigger('collectionlengthchange');
    });

    $(document).on('shown.bs.modal', function (e) {
        const $modal = $(e.target);
        const initPopovers = function () {
            $modal.find('[data-toggle="popover"]').popover({
                html: true,
                placement: 'left',
            });
        }
        initPopovers();
        $modal.on('collectionlengthchange', function() {
            initPopovers();
        });

        $modal.on('click', '.collapse-toggle', function (e) {
            $(e.target).closest('.panel').find('.collapse').collapse('toggle');
        });
        const onNameChanged = function (input) {
            const $label = $(input).closest('.panel').find('.panel-label');
            let value = input.value;
            if (!value) value = $label.attr('data-unnamed');
            $label.text(value);
        }
        $modal.find('.panel-group').on('keyup', '.form-group:first-child input', function (e) {
            onNameChanged(e.target);
        });
        $modal.find('.panel-group .form-group:first-child input').each(function (i, e) {
            onNameChanged(e);
        });
    })

})(jQuery);
