$(function(){
    // Switch application state via Ajax when the current state icon is clicked
    $('#listFilterApplications .iconPublish[data-url]').bind('click', function() {
        var $this = $(this);
        var url = $this.attr('data-url');
        var requestedState;

        if($this.hasClass("enabled")){
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
                $this.removeClass('enabled iconPublishActive').addClass('disabled');
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            Mapbender.error(errorThrown);
        });
        return false;
    });

    var popup;

    // Delete application via Ajax
    $('#listFilterApplications').find(".iconRemove").bind("click", function(){
        var self    = $(this);
        var content = self.parent().siblings(".title").text();
        var content = $('<div/>').text(self.parent().siblings(".title").text()).html();

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
});
