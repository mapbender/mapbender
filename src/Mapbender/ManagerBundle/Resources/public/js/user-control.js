$(function(){
    $(".checkbox").on("change", function(e){
      $("#selectedUsersGroups").text(($(".tableUserGroups").find(".iconCheckboxActive").length))
    });

    // Delete group via Ajax
    $('#listFilterGroups').on("click", ".iconRemove", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - group",
                    content: "Delete " + title + "?"
                },
                function(){
                    $.ajax({
                        url: me.attr('data-url'),
                        data : {'id': me.attr('data-id')},
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });

    // Delete user via Ajax
    $('#listFilterUsers').on("click", ".iconRemove", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: " - user",
                    content: title + "?"
                },
                function(){
                    $.ajax({
                        url: me.attr('data-url'),
                        data : {
                            'slug': me.attr('data-slug'),
                            'id': me.attr('data-id')
                        },
                        type: 'POST',
                        success: function(data) {
                            window.location.reload();
                        }
                    });
                });
        }
        return false;
    });
});