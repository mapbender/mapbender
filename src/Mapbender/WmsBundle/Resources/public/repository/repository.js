$(function() {
//    window.console && console.log("WMS");

    $('section#wms-layers .layer legend').bind("click", function(e){
//        window.console && console.log($(this).parent().find('dl:first'));
        if($(this).parent().find('dl:first').hasClass("hidde")){
            $(this).parent().find('dl:first').removeClass("hidde");
        } else {
            $(this).parent().find('dl:first').addClass("hidde");
        }
    });
});