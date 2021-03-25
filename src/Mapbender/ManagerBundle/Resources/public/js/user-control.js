$(function(){
    $('#selectedUsersGroups').each(function() {
        var $displayEl = $(this);
        var $body = $('>tbody', $displayEl.closest('table'));
        $body.on('change', 'input[type="checkbox"]', function() {
            var countSelected = $('input[type="checkbox"]:checked', $body).length;
            $displayEl.text(countSelected);
        });
    });

    // Delete group via Ajax
    $('#listFilterGroups').on("click", '.iconRemove[data-url]', function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        });
        return false;
    });
    $('#listFilterUsers').on("click", ".iconRemove[data-url]", function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            confirm: "mb.actions.delete",
            cancel: "mb.actions.cancel"
        });
        return false;
    });
});
