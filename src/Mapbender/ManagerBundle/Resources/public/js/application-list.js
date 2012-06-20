// Switch application state via Ajax when the current state icon is clicked
$('table#application-list').on('click', 'i.application-state', function() {
    var icon = $(this),
        states = {
            'enabled': 'icon-eye-open',
            'disabled': 'icon-eye-close'
        },
        currentState = icon.hasClass('icon-eye-open') ? 'enabled' : 'disabled',
        requestedState = currentState === 'enabled' ? 'disabled' : 'enabled',
        slug = icon.closest('tr').attr('data-application-slug'),
        id = icon.closest('tr').attr('data-application-id');

    if(id === '') {
        alert('YAML-defined applications can not be edited.');
        return;
    }

    icon.removeClass(states[currentState]);
    icon.addClass(states[requestedState]);

    var errorHandler = function() {
        icon.removeClass(states[requestedState]);
        icon.addClass(states[currentState]);
        alert('Unfortunately, there was an error switching states.');
    }

    $.ajax({
        url: Routing.generate('mapbender_manager_application_togglestate', {
            slug: slug}),
        type: 'POST',
        data: {
            'state': requestedState
        },
        success: function(data) {
            if(data.newState !== requestedState) {
                errorHandler();
            }
        },
        error: errorHandler
    });
});

