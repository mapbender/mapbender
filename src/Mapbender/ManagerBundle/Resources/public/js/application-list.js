$(function(){
    var $appList = $('#listFilterApplications');
    // Switch application state via Ajax when the current state icon is clicked
    $appList.on('click', '.iconPublish[data-url],.iconPublishActive[data-url]', function() {
        var $this = $(this);
        var url = $this.attr('data-url');
        var requestedState;

        if (!$this.hasClass("disabled")){
            requestedState = "disabled";
        }else{
            requestedState = "enabled";
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: {state: requestedState}
        }).done(function(response) {
            if (response.newState === 'enabled') {
                $this.removeClass("disabled").addClass('enabled iconPublishActive');
            } else {
                $this.removeClass('enabled iconPublishActive').addClass('disabled iconPublish');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            Mapbender.error(errorThrown);
        });
        return false;
    });

    $appList.on('click', '.iconRemove[data-url]', function() {
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
