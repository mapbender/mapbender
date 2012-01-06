/**
 * Mapbender Manager form customization.
 */
$(function() {
    // Some forms may need to be tabbed
    $('form div.tabbed').tabs({
        select: function(event, ui) {
            window.location.hash = ui.tab.hash;
        }
    });

    $('form div.actions').buttonset();

    // If we have a title field AND a slug field, make them dependent
    var title = $('form input[name$="[title]"]');
    var slug = $('form input[name$="[slug]"]');
    if(title.length === 1 && slug.length === 1) {
        title.on('change keyup', function() {
            var slugified = title.val().trim().toLowerCase();
            slugified = slugified.replace(/[^-a-zA-Z0-9,&\s]+/ig, '');
            slugified = slugified.replace(/-/gi, "_");
            slugified = slugified.replace(/\s/gi, "-");
            slug.val(slugified);
        });
    }
});

