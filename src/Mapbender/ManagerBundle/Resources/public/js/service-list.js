$(function() {
    $('#listFilterServices').on('click', '.iconRemove[data-url]', function() {
        var $el = $(this);
        Mapbender.Manager.confirmDelete($el, $el.attr('data-url'), {
            // @todo: bring your own translation string
            title: "mb.manager.components.popup.delete_element.title",
            // @todo: bring your own translation string
            cancel: "mb.manager.components.popup.delete_element.btn.cancel",
            // @todo: bring your own translation string
            confirm: "mb.manager.components.popup.delete_element.btn.ok"
        });
        return false;
    });
});
