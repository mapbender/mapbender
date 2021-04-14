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

});
