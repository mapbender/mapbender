$(function(){
    // Switch application state via Ajax when the current state icon is clicked
    $('#listFilterApplications').find(".publishIcon").bind('click', function() {
        var me             = $(this);
        var url            = Routing.generate('mapbender_manager_application_togglestate', 
                                              {slug: me.attr('data-application-slug')})
        var requestedState;

        if(me.hasClass("enabled")){
            requestedState = "disabled";
            me.removeClass("enabled").addClass(requestedState);
        }else{
            requestedState = "enabled";
            me.removeClass("disabled").addClass(requestedState);
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
    $('#listFilterApplications').find(".deleteIcon").bind("click", function(){
        var me  = $(this);
        var url = ""; // Need a url > Routing.generate('mapbender_manager_application_delete',
        //                             {slug: me.attr('data-application-slug')})

        var title = me.parent().siblings(".title").text();

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal', 
                              {title:"Confirm delete",
                                      subTitle: title,
                                      content:"Do you really want to delete the application " + title + "?"},
                                      function(){
                                        $.ajax({
                                            url: url,
                                            type: 'POST',
                                            success: function(data) {
                                                console.log(data)
                                            }
                                        });
                                      });
        }
        return false;
    });
})