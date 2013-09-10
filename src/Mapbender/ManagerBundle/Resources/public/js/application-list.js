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

    var popup;

    // Delete application via Ajax
    $('#listFilterApplications').find(".iconRemove").bind("click", function(){
        var self    = $(this);
        var content = self.parent().siblings(".title").text();

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title:"Confirm delete",
            subTitle: " - application",
            closeOnOutsideClick: true,
            content: ["Delete " + content + "?"],
            buttons: {
                'cancel': {
                    label: 'Cancel',
                    cssClass: 'button buttonCancel critical right',
                    callback: function() {
                        this.close();
                    }
                },
                'ok': {
                    label: 'Delete',
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data : {'id': self.attr('data-id')},
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
        return false;
    });
})