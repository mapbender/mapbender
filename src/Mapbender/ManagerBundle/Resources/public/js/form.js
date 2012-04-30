/**
 * Mapbender Manager form customization.
 */
(function() {
    var form = $('form');

    //
    // Some forms may need to be tabbed
    //
    form.find('div.tabbed').tab();

    // After tabbing, give focus to first visible :input, but mind the search
    // field
    $('form:not(#manager-search) :input:visible').eq(0).focus();

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

    //
    // Sub-Entity class selection modal
    //
    var trigger = form.find('a[href="#modal-application-elements"]');
    var modal = $('div#modal-application-elements');

    trigger.on('click', function() {
        var href = $(this).attr('data-href');
        modal.data('href', href);
        modal.modal('show');
        return false;
    });

    modal.find('div.element').on('click', function() {
        var element = $(this);
        element.addClass('selected')
            .siblings().removeClass('selected');
        element.closest('.modal').find('div.modal-footer a.btn-primary')
            .removeClass('disabled');

    });

    modal.find('div.modal-footer a.btn-primary').on('click', function() {
        var href = modal.data('href');
        var element_class = modal.find('.selected').attr('data-class');

        var url = href + '&class=' + element_class;
        window.location = url;
    });

    modal.on('show', function() {
        var modal = $(this),
            select = modal.find('div.modal-footer a.btn-primary');

        modal.find('div.element').removeClass('selected');

        select.addClass('disabled');
    });

    //
    // YAML textareas
    //
    var yaml = form.find('textarea.code-yaml');
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

    //
    // Elements sortable
    //
    var sortables = $('table.table-sortable');
    sortables.each(function() {
        var me = $(this);
        me.sortable({
            axis: 'y',
            connectWith: sortables.not(me),
            containment: me.closest('form'),
            handle: 'td.sort-grip',
            items: 'tbody > tr',
            tolerance: 'pointer'
        });
    });

    //
    // Roles checkboxes
    //
    var roles = $('#application_roles');
    roles.find('input').each(function() {
        var checkbox = $(this).hide();
        var label = roles.find('label[for="' + checkbox.attr('id') + '"]');
        label.addClass('btn');
        if(checkbox.prop('checked')) {
            label.addClass('btn-success');
        }
        checkbox.on('change', function() {
            label.toggleClass('btn-success');
        })
    });
})();

