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