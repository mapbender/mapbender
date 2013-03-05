$(function() {
    $(".subTitle").bind("click", function(){
        var parent = $(this).parent();

        if(parent.hasClass("closed")){
            parent.removeClass("closed");
        }else{
            parent.addClass("closed");
        }
    });
});