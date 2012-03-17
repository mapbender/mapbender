/**
 * Mapbender Manager form customization.
 */
(function() {
    var form = $('form');

    //
    // Some forms may need to be tabbed
    //
    form.find('div.tabbed').tab();

    //
    // If we have a title field AND a slug field, make them dependent
    //
    var title = form.find('input[name$="[title]"]');
    var slug = form.find('input[name$="[slug]"]');
    if(title.length === 1 && slug.length === 1) {
        title.on('change keyup', function() {
            var slugified = title.val().trim().toLowerCase();
            slugified = slugified.replace(/[^-a-zA-Z0-9,&\s]+/ig, '');
            slugified = slugified.replace(/-/gi, "_");
            slugified = slugified.replace(/\s/gi, "-");
            slug.val(slugified);
        });
    }
})();

