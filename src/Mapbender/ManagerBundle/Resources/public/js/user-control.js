$(function(){
    $(".checkbox").on("change", function(e){
      $("#selectedUsersGroups").text(($(".tableUserGroups").find(".iconCheckboxActive").length))
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
