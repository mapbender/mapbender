$(function(){
    var popup;

    $(".checkbox").on("change", function(e){
      $("#selectedUsersGroups").text(($(".tableUserGroups").find(".iconCheckboxActive").length))
    });

    // Delete group via Ajax
    $('#listFilterGroups').on("click", ".iconRemove", function(){
        var self  = $(this);
        var content = self.attr('title');


        if(popup){
            popup = popup.destroy();
        }

        popup = new Mapbender.Popup2({
            title:"Confirm delete",
            subtitle: " - group",
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
                'delete': {
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

    // Delete user via Ajax
    $('#listFilterUsers').on("click", ".iconRemove", function(){
        var self  = $(this);
        var content = self.attr('title');


        if(popup){
            popup = popup.destroy();
        }

        popup = new Mapbender.Popup2({
            title:"Confirm delete",
            subtitle: " - user",
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
                'delete': {
                    label: 'Delete',
                    cssClass: 'button right',
                    callback: function() {
                        $.ajax({
                            url: self.attr('data-url'),
                            data : {
                                'slug': self.attr('data-slug'),
                                'id': self.attr('data-id')
                            },
                            type: 'POST',
                            success: function(data) {
                                window.location.reload();
                            }
                        });
                    }
                }
            }
        });
    });
});