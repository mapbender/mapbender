$(function() {
    console.log("WMS");

    $('section#wms-layers .layer legend').bind("click", function(e){
        console.log($(this).parent().find('dl:first'));
        if($(this).parent().find('dl:first').hasClass("hidde")){
            $(this).parent().find('dl:first').removeClass("hidde");
        } else {
            $(this).parent().find('dl:first').addClass("hidde");
        }
    });
});