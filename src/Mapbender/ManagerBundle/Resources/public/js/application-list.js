$(function(){
    var $appList = $('#listFilterApplications');
    // Switch application state via Ajax when the current state icon is clicked
    $appList.on('click', '.-fn-toggle-publish[data-url]', function() {
        var $this = $(this);
        var url = $this.attr('data-url');
        var requestedState = $this.toggleClass('-js-on').hasClass('-js-on') && 'enabled' || 'disabled';

        $.ajax({
            url: url,
            type: 'POST',
            data: {state: requestedState}
        }).done(function(response) {
            var enabled = response.newState === 'enabled';
            $('i', $this)
                .toggleClass('fa-eye-slash', !enabled)
                .toggleClass('fa-eye', enabled)
            ;
        }).fail(function(jqXHR, textStatus, errorThrown) {
            Mapbender.error(errorThrown);
        });
        return false;
    });
});
