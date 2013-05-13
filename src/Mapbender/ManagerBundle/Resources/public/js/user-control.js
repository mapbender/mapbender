$(function(){

    // Delete users/groups via ajax
    $('.listFilterContainer').find(".deleteIcon").bind("click", function(){
        var me    = $(this);
        var url   = me.attr("href");
        var title = me.attr("data-name");

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal', 
                              {title:"Confirm delete",
                                      subTitle: " - " + title,
                                      content:"Do you really want to delete the group/user " + title + "?"},
                                      function(){
                                        $.ajax({
                                            url: url,
                                            type: 'POST',
                                            success: function(data) {
                                              me.parent().parent().remove();
                                            }
                                        });
                                      });
        }
        return false;
    });

    $(".checkbox").bind("click", function(e){
      $("#selectedUsersGroups").text(($(".tableUserGroups").find(".iconCheckboxActive").length))
    });

    // Delete group via Ajax
    $('#listFilterGroups').find(".iconRemove").bind("click", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: title,
                    content:"Do you really want to delete the group " + title + "?"
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
    $('#listFilterUsers').find(".iconRemove").bind("click", function(){
        var me  = $(this);
        var title = me.attr('title');

        if(!$('body').data('mbPopup')) {
            $("body").mbPopup();
            $("body").mbPopup('showModal',
                {
                    title:"Confirm delete",
                    subTitle: title,
                    content:"Do you really want to delete the user " + title + "?"
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