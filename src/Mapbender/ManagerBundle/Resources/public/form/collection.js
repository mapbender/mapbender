/**
 * Migrated to Mapbender from FOM v3.0.6.3
 * See https://github.com/mapbender/fom/tree/v3.0.6.3/src/FOM/CoreBundle/Resources/public/js/widgets
 */
(function($) {

function s4() {
  return Math.floor((1 + Math.random()) * 0x10000)
             .toString(16)
             .substring(1);
};

function guid() {
  return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
         s4() + '-' + s4() + s4() + s4();
}

$(document).on('click', '.collectionAdd', function(event) {
    event.preventDefault();

    // Gather all needed information, like
    //  collection we're handling right now...
    var collection = $(event.target).parent(),
        count = $('.collectionItem', collection).length,
        // The prototype text for the new item...
        prototype = collection.data('prototype'),
        // And finally parse the prototype into a new clean item for insertion.
        item = $($.parseHTML(prototype
            .trim()
            .replace(/__name__label__/g, '')
            .replace(/__name__/g, count + '-' + guid()))[0])
            .addClass('collectionItem');

    // Now let's enter that item...
    collection.append(item);
});

$(document).on('click', '.collectionRemove', function(event) {
    event.preventDefault();

    // Get the item...
    var item = $(event.target).closest('.collectionItem');
    // And remove it.
    item.remove();
});

})(jQuery);
