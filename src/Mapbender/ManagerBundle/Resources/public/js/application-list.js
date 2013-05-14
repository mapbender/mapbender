$(function(){
    // Switch application state via Ajax when the current state icon is clicked
    $('#listFilterApplications').find(".iconPublish").bind('click', function() {
        var me             = $(this);
        var url            = Routing.generate('mapbender_manager_application_togglestate', 
                                              {slug: me.attr('data-application-slug')})
        var requestedState;

        if(me.hasClass("enabled")){
            requestedState = "disabled";
            me.removeClass("enabled").removeClass("iconPublishActive").addClass(requestedState);
        }else{
            requestedState = "enabled";
            me.removeClass("disabled").addClass(requestedState).addClass("iconPublishActive");
        }

        var errorHandler = function() {
            me.removeClass(requestedState);
            me.addClass(me.hasClass("enabled") ? 'disabled' : 'enabled');
            if(!$('body').data('mbPopup')) {
                $("body").mbPopup();
                $("body").mbPopup('showHint', {content:"Unfortunately, there was an error switching states."});
            }
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: {'state': requestedState},
            success: function(data) {
                if(data.newState !== requestedState) {
                    errorHandler();
                }
            },
            error: errorHandler
        });

        return false;
    });

    // Delete application via Ajax
    $('#listFilterApplications').find(".iconRemove").bind("click", function(){
        var me  = $(this);
        var url = $(this).attr('data-url');
        var slug =  $(this).attr('data-slug');
        var title = me.parent().siblings(".title").text();

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - application",
                    content:"Delete " + title + "?"
                },
                function(){
                    $.ajax({
                        url: url,
                        data : {'slug': slug},
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });
})