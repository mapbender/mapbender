$(function() {
    $('#listFilterServices, .-fn-instance-list, .dropdown-menu').on('click', '.-fn-delete[data-url]', function(e) {
        e.preventDefault();
        var $el = $(this);
        var url = $el.attr('data-url');
        $.ajax(url, {
            method: 'GET'
        }).then(function(response) {
            var stringMap = {
                // @todo: bring your own translation string
                title: "mb.manager.components.popup.delete_element.title",
                confirm: "mb.actions.delete",
                cancel: "mb.actions.cancel"
            };
            Mapbender.Manager.confirmDelete($el, url, stringMap, response);
        });
        return false;
    });
});
