/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
(function($) {

function s4() {
  return Math.floor((1 + Math.random()) * 0x10000)
             .toString(16)
             .substring(1);
}

function guid() {
  return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
         s4() + '-' + s4() + s4() + s4();
}

$(document).on('click', '.collection > .collectionAdd', function() {
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

$(document).on('click', '.collectionItem .collectionRemove', function() {
    $(this).closest('.collectionItem').remove();
    $(this).closest('.collection').trigger('collectionlengthchange');
});

})(jQuery);
