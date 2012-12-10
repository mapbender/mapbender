$('button.edit-instance').bind("click", function(e){ 
    var self = this;
    $.ajax({
        url: $(self).attr("data-href"),
        success: function(data){
            $($(self).attr('data-target') + " div.modal-body").html(
                $(data).find("div.well").html()
            );
        }   
    });
});
//$('a.edit-source-layers').bind("click", function(e){ 
//    e.preventDefault();
//    $.ajax({
//        url: $(this).attr("href"),
//        type: "GET",
//        context: document.body,
//        success: function(data){
//            var a = 0;
////            $("#dialog").html(data);
////            $("#dialog").dialog({
////                bgiframe: true,
////                autoOpen: false,
////                height: 100,
////                modal: true
////            });
//        }   
//    });
//});