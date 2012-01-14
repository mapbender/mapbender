/**
 * Mapbender Manager form customization.
 */
$(function() {
    var form = $('form');

    // Some forms may need to be tabbed
    form.find('div.tabbed').tabs({
        select: function(event, ui) {
            window.location.hash = ui.tab.hash;
        }
    });

    form.find('div.actions').buttonset();

    // If we have a title field AND a slug field, make them dependent
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

    // Handle 'add' buttons on collection fields
    form.find('button.collection-add').bind('click', function(event) {
        var type = $(this).attr('data-type'),
            collection = form.find('div#' + type);

        var elementCount = parseInt(collection.attr('data-item-count'), 10) + 1;
        collection.attr('data-item-count', elementCount);

        var prototype = $(collection.attr('data-prototype')
                .replace(/\$\$name\$\$/g, 'element-' + elementCount));

        collection.append(prototype);

        var yaml = prototype.find('textarea.code-yaml');
        if(yaml.length) {
            yaml.each(function() {
                var editor = CodeMirror.fromTextArea(this, {
                    mode: 'yaml',
                    linenumbers: true,
                    onCursorActivity: function() {
                        editor.setLineClass(hlLine, null);
                        hlLine = editor.setLineClass(editor.getCursor().line,
                            "activeline");
                    }
                });
                var hlLine = editor.setLineClass(0, "activeline");
            });
        }
    });
});

