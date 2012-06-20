/**
 * Mapbender Manager form customization.
 */
$(function() {
    var form = $('form:not(#manager-search)');

    //
    // Link hash and tabs
    //
    form.find('ul.nav-tabs').on('shown', 'a', function(event) {
        var formName = form.attr('name');
        var aId = $(this).attr('href');
        var newHash = aId.substr(formName.length + 2);
        window.location.hash = newHash;
    });

    if(window.location.hash) {
        var tabId = form.attr('name') + '-' + window.location.hash.substr('1');
        form.find('ul.nav-tabs a[href="#' + tabId + '"]').click();
    }

    //
    // Give focus to first visible :input, but mind the search field
    //
    form.find(':input:visible').eq(0).focus();

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
});

