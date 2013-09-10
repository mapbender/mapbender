$(function() {
    var popup;

    // Delete element
    $('.iconRemove').bind("click", function(){
        var self    = $(this);
        var content = self.attr('title');

        if(popup){
            popup = popup.destroy();
        }
        popup = new Mapbender.Popup2({
            title:"Confirm delete",
            subTitle: " - service",
            closeOnOutsideClick: true,
            content: [content + "?"],
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