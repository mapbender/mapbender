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
            content: [content + "?", $.ajax({ url: self.attr('data-url')})],
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
                        $('form', popup.$element).submit();
                    }
                }
            }
        });
        return false;
    });
});